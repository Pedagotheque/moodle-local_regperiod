<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Checktask task.
 *
 * @package     local_regperiod
 * @copyright  IMT Lille Douai <https://imt-lille-douai.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Martin CORNU-MANSUY <martin@call-learning>
 */

namespace local_regperiod\task;

use core\task\scheduled_task;
use stdClass;

/**
 * A task to suspend users that are not in their registration date range.
 *
 * @copyright   2022 - CALL Learning - Martin CORNU-MANSUY <martin@call-learning>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Martin CORNU-MANSUY
 */
class checktask extends scheduled_task {

    /**
     * Gets the name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('checktask', 'local_regperiod');
    }

    /**
     * Executes this class's task.
     * In that case it gets all the users wich have specified registration dates, and will suspend users that have passed their
     * end registration dates.
     *
     * @return void
     */
    public function execute() {
        // Get fields id.
        $fieldsid = self::get_reg_fields_ids();
        $startfieldid = $fieldsid["startfieldid"];
        $endfieldid = $fieldsid["endfieldid"];

        if (empty($startfieldid) && empty($endfieldid)) {
            return;
        }

        $users = self::get_all_registration_info($startfieldid, $endfieldid);

        // Verify for the users if they have to be suspended or unsuspended due to the date.
        foreach ($users as $user) {
            if (!$user->deleted) {
                self::update_user_status($user); // Todo careful with this BDR call into a loop.
            }
        }
    }

    /**
     * Updates suspend status of the specified user, from start and en registration dates.
     * A user is suspended if the start registration date isn't passed yet and if the end registration date is passed.
     * Note that if one of the fields (start or end registration field) is missing, we take for granted that the missing field
     * meets the conditions to lay the user unsuspended.
     *
     * @param stdClass $user The user to update suspend status.
     * @return void
     */
    public static function update_user_status($user) {
        $currenttime = time();
        $tobesuspendedstart = false;
        $tobesuspendedend = false;
        if (isset($user->startreg) && $user->startreg) {
            // The start registration time is not passed.
            $tobesuspendedstart = $currenttime < $user->startreg;
        }
        if (isset($user->endreg) && $user->endreg) {
            // The end registration time is passed.
            $tobesuspendedend = $currenttime > $user->endreg;
        }
        $tobesuspended = $tobesuspendedstart || $tobesuspendedend;
        self::suspend_user($user->id, $tobesuspended);
    }

    /**
     * Gets all registration information we need for all users.<br>
     * This method has been implemented to avoid having SQL request inside the loop of the method {@see checktask::execute()}
     *
     * @param int $startfieldid The id of the field used to specify the start registration date.
     * @param int $endfieldid The id of the field used to specify the start registration date.
     * @return array An array of objects representing users
     * with their id, suspended, deleted, start registration date, end registration date.
     */
    public static function get_all_registration_info($startfieldid, $endfieldid): array {
        global $DB;
        $params = ['startid' => $startfieldid, 'endid' => $endfieldid];
        $users = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.suspended, u.deleted, uistart.data AS startreg, uiend.data AS endreg
                FROM {user} u
                LEFT JOIN {user_info_data} uistart ON u.id = uistart.userid AND uistart.fieldid = :startid
                LEFT JOIN {user_info_data} uiend ON u.id = uiend.userid AND uiend.fieldid = :endid
                WHERE uistart.id IS NOT NULL OR uiend.id IS NOT NULL ", $params);
        return $users;
    }

    /**
     * Gets the ids of the two fields where we specify the start registration date and the end registration date.
     * Note that it will be returned as an array like this :
     * <pre>
     *  [
     *      "startfieldid" => "x",
     *      "endfieldid" => "y"
     *  ];
     * </pre>
     *
     * @return array an array containing both of the ids of the fields where
     * start registration date and end registration date are specified.
     */
    public static function get_reg_fields_ids(): array {
        global $DB;
        return [
            "startfieldid" => $DB->get_field('user_info_field', 'id',  array('shortname' => 'startreg')),
            "endfieldid" => $DB->get_field('user_info_field', 'id', array('shortname' => 'endreg'))
        ];
    }

    /**
     * Updates the specified user's suspend status.
     *
     * @param int $userid The id of the user we want to update suspend status.
     * @param bool $newstatus The future suspend status of the specified user.
     * @return void
     */
    public static function suspend_user($userid, $newstatus = true ) {
        global $DB;
        $DB->set_field('user', 'suspended', $newstatus, ['id' => $userid]);
        /*
        TODO check wich way to implement this method is better.
        $update = new stdClass();
        $update->id = $userid;
        $update->suspended = $newstatus;
        $DB->update_record('user', $update);
        */
    }

    /**
     * Gets the start registration time and the end registration of the specified user.
     * note that it will be returned into an array like this :
     * <pre>
     *  [
     *      "id" => $userid,
     *      "startreg" => $startregistrationtime,
     *      "endreg" => $endregistrationtime
     *  ];
     *  </pre>
     *
     * @param int $userid The id of the user we want registration info.
     * @return array The times when registration ends and starts as an array.
     */
    public static function get_reg_info($userid): array {
        global $DB;

        $fieldids = self::get_reg_fields_ids();

        $datastartinfo = $DB->get_record(
            'user_info_data', array('userid' => $userid, 'fieldid' => $fieldids["startfieldid"]), 'id, data'
        );
        $dataendinfo = $DB->get_record(
            'user_info_data', array('userid' => $userid, 'fieldid' => $fieldids["endfieldid"]), 'id, data'
        );
        return [
            "id" => $userid,
            "startreg" => $datastartinfo->data ?? null,
            "endreg" => $dataendinfo->data ?? null
        ];
    }

    /**
     * Gets all the users having startfield and endfield.
     *
     * @param int $startfieldid The id of the field where is specified the time when registration starts.
     * @param int $endfieldid The id of the field where is specified the time when registration ends.
     * @return array containing the ids of all users wich have one of these fields set.
     */
    public static function get_reg_users($startfieldid, $endfieldid): array {
        // Todo maybe the method just returns all of the users.
        global $DB;
        [$where, $params] = $DB->get_in_or_equal([$startfieldid, $endfieldid], SQL_PARAMS_NAMED);
        $users = $DB->get_fieldset_sql(
            'SELECT DISTINCT userid FROM {user_info_data} WHERE fieldid '. $where, $params
        );
        return $users;
    }

    /**
     * Gets whether the specified user is suspended or not and whether he is deleted or not.
     * Note that the result is an stdClass:
     * <pre>
     *  (stdClass) [
     *      "suspended" => $suspended,
     *      "deleted" => $deleted
     *  ]
     * </pre>
     * In order to get a value you'll have to use the returned stdclass as it follows :
     * myobject->suspended.
     *
     * @param int $userid The id of the user we want to know if he is suspended and deleted.
     * @return false|mixed a fieldset containing both values.
     */
    public static function get_suspended_deleted($userid) {
        global $DB;
        return $DB->get_record('user', ['id' => $userid], "suspended, deleted");
    }


}

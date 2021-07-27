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
 * @package    local_regperiod
 * @copyright  IMT Lille Douai <https://imt-lille-douai.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Romain DELEAU
 */

namespace local_regperiod\task;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class checktask extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('checktask', 'local_regperiod');
    }

    public function execute() {
        global $DB;

        // Get fields id
        $startfield = $DB->get_record('user_info_field', array('shortname' => 'startreg'), 'id', IGNORE_MISSING);
        $endfield = $DB->get_record('user_info_field', array('shortname' => 'endreg'), 'id', IGNORE_MISSING);

        $startfieldid = null;
        $endfieldid = null;

        if (!empty($startfield)) {
            $startfieldid = $startfield->id;
        }
        if (!empty($endfield)) {
            $endfieldid = $endfield->id;
        }

        $users = array();

        // Get all users which have at least one of the two fields set
        if (isset($startfieldid) && isset($endfieldid)) {
            $sqlrequest = "SELECT {user}.id, suspended, deleted FROM {user}
                JOIN {user_info_data} ON {user}.id = {user_info_data}.userid
                WHERE fieldid = ? OR fieldid = ?
                GROUP BY {user}.id";
            $sqlparams = array($startfieldid, $endfieldid);
            $users = $DB->get_records_sql($sqlrequest, $sqlparams);
        } else {
            if (isset($startfieldid)) {
                $sqlrequest = "SELECT {user}.id, suspended, deleted FROM {user}
                JOIN {user_info_data} ON {user}.id = {user_info_data}.userid
                WHERE fieldid = ? AND data > 0";
                $sqlparams = array($startfieldid);
                $users = $DB->get_records_sql($sqlrequest, $sqlparams);
            }
            if (isset($endfieldid)) {
                $sqlrequest = "SELECT {user}.id, suspended, deleted FROM {user}
                JOIN {user_info_data} ON {user}.id = {user_info_data}.userid
                WHERE fieldid = ? AND data > 0";
                $sqlparams = array($endfieldid);
                $users = $DB->get_records_sql($sqlrequest, $sqlparams);
            }
        }

        // Verify for the users if they have to be suspended or unsuspended due to the date
        foreach ($users as $user) {
            $newstatus = null;
            $status = $user->suspended; // If status = 1 the account is suspended
            if ($user->deleted == 0) {
                if (isset($startfieldid)) {

                    $datastartinfo = array();
                    $newdatastart = null;

                    $datastartinfo = ($DB->get_record(
                        'user_info_data',
                        array('userid' => $user->id, 'fieldid' => $startfieldid),
                        'id, data',
                        IGNORE_MISSING
                    ));

                    if (!empty($datastartinfo)) {
                        $datastart = $datastartinfo->data;
                        $datastartuserfieldid = $datastartinfo->id;

                        // Treatment for the start field
                        if (isset($datastart) && 0 < $datastart) {
                            // If the user isn't suspended while he must be, then suspend him
                            if ($status == 0 && time() < $datastart) {
                                $newstatus = 1;
                            }

                            // If the user is suspended while he must be actived, then activate him
                            if ($status == 1 && $datastart <= time()) {
                                $newstatus = 0;
                                $newdatastart = 0;
                            }
                        }
                    }
                }

                if (isset($endfieldid)) {

                    $dataendinfo = array();
                    $newdataend = null;

                    $dataendinfo = ($DB->get_record(
                        'user_info_data',
                        array('userid' => $user->id, 'fieldid' => $endfieldid),
                        'id, data',
                        IGNORE_MISSING
                    ));

                    if (!empty($dataendinfo)) {
                        $dataend = $dataendinfo->data;
                        $dataenduserfieldid = $dataendinfo->id;

                        // Treatment for the end field
                        if (isset($dataend) && 0 < $dataend) {
                            // If the user is active while he must be suspended, then suspend him
                            if ($status == 0 && $dataend <= time()) {
                                $newstatus = 1;
                                $newdataend = 0;
                            }
                            // Activate the user if there are only the end field set
                            if ($status == 1 && time() < $dataend) {
                                if (isset($datastart)) {
                                    if ($datastart == 0) {
                                        $newstatus = 0;
                                    }
                                }
                            }
                        }
                    }
                }

                // Update the record in the DB
                if (isset($newstatus)) {
                    $update = new stdClass();
                    $update->id = $user->id;
                    $update->suspended = $newstatus;
                    $DB->update_record('user', $update);
                    // Reset the start field if the date is already passed
                    if (isset($newdatastart)) {
                        $update = new stdClass();
                        $update->id = $datastartuserfieldid;
                        $update->data = $newdatastart;
                        $update->userid = $user->id;
                        $DB->update_record('user_info_data', $update);
                    }

                    // Reset the end field if the date is already passed
                    if (isset($newdataend)) {
                        $update = new stdClass();
                        $update->id = $dataenduserfieldid;
                        $update->data = $newdataend;
                        $update->userid = $user->id;
                        $DB->update_record('user_info_data', $update);
                    }
                }
            }
        }
    }
}

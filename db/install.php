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
 * Local regperiod plugin install script.
 *
 * @package    local_regperiod
 * @copyright  IMT Lille Douai <https://imt-lille-douai.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Martin CORNU-MANSUY <martin@call-learning>
 */

/**
 * A function to install local_regperiod plugin into the current database.
 *
 * @return void
 * @throws dml_exception
 */
function xmldb_local_regperiod_install() {
    global $DB;

    // Create the regperiod category if not exist.
    $categoryexist = $DB->get_record(
        'user_info_category',
        array('name' => 'Registration duration to the site')
    );
    if (empty($categoryexist)) {

        // Create the regperiod category.
        $newcategory = new stdClass();
        $newcategory->name = 'Registration duration to the site';
        $newcategory->sortorder = $DB->count_records('user_info_category') + 1;
        $DB->insert_record(
            'user_info_category',
            $newcategory
        );
    }

    // Create the fields in the regperiod category if not exists.
    $categoryid = ($DB->get_record(
        'user_info_category',
        array('name' => 'Registration duration to the site'),
        'id'
    ))->id;
    $fieldstartexist = $DB->get_record(
        'user_info_field',
        array('categoryid' => $categoryid, 'shortname' => 'startreg')
    );
    $fieldendexist = $DB->get_record(
        'user_info_field',
        array('categoryid' => $categoryid, 'shortname' => 'endreg')
    );

    // If the start field not exist, create it.
    if (empty($fieldstartexist)) {
        create_registration_field('Start', 'startreg', $categoryid, 1);
    }

    // If the end field not exist, create it.
    if (empty($fieldendexist)) {
        create_registration_field('End', 'endreg', $categoryid, 2);
    }
}

/**
 * A function to create a registration field.
 * It has been implemented to avoid having long duplicated code fragments in {@see xmldb_local_regperiod_install()}
 *
 * @param string $name The name of the field.
 * @param string $shortname The shortname of the field.
 * @param string $categoryid The category's id of the field.
 * @param int $sortorder The sort order of the field.
 * @return void
 */
function create_registration_field($name, $shortname, $categoryid, $sortorder) {
    global $DB;

    $field = new stdClass();
    $field->shortname = $shortname;
    $field->name = $name;
    $field->datatype = 'datetime';
    $field->description = '';
    $field->descriptionformat = 1;
    $field->categoryid = $categoryid;
    $field->sortorder = $sortorder;
    $field->required = 0;
    $field->locked = 1;
    $field->visible = 2;
    $field->forceunique = 0;
    $field->signup = 1;
    $field->defaultdata = 0;
    $field->defaultdataformat = 0;
    $field->param1 = 2020;
    $field->param2 = 2050;
    $field->param3 = 1;
    $field->param4 = null;
    $field->param5 = null;

    $DB->insert_record(
        'user_info_field',
        $field
    );
}

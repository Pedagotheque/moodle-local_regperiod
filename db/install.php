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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_regperiod_install() {
    global $CFG, $DB;

    // Create the regperiod category if not exist
    $categoryexist = $DB->get_record(
        'user_info_category',
        array('name' => 'Registration duration to the site'),
        '*',
        IGNORE_MISSING
    );
    if (empty($categoryexist)) {

        // Down all the others categorys to put this one first
        $categorys = $DB->get_records(
            'user_info_category',
            null,
            null,
            '*',
            null,
            null
        );
        foreach ($categorys as $category) {
            $update = new stdClass();
            $update->id = $category->id;
            $update->sortorder = ($category->sortorder) + 1;
            $DB->update_record('user_info_category', $update);
        }

        // Create the regperiod category
        $newcategory = new stdClass();
        $newcategory->name = 'Registration duration to the site';
        $newcategory->sortorder = 1;
        $DB->insert_record(
            'user_info_category',
            $newcategory
        );
    }

    // Create the fields in the regperiod category if not exists
    $categoryid = ($DB->get_record(
        'user_info_category',
        array('name' => 'Registration duration to the site'),
        'id', IGNORE_MISSING
    ))->id;
    $fieldstartexist = $DB->get_record(
        'user_info_field',
        array('categoryid' => $categoryid, 'shortname' => 'startreg'),
        '*',
        IGNORE_MISSING
    );
    $fieldendexist = $DB->get_record(
        'user_info_field',
        array('categoryid' => $categoryid, 'shortname' => 'endreg'),
        '*',
        IGNORE_MISSING
    );

    // If the start field not exist, create it
    if (empty($fieldstartexist)) {
        $startfield = new stdClass();
        $startfield->shortname = 'startreg';
        $startfield->name = 'Start';
        $startfield->datatype = 'datetime';
        $startfield->description = '';
        $startfield->descriptionformat = 1;
        $startfield->categoryid = $categoryid;
        $startfield->sortorder = 1;
        $startfield->required = 0;
        $startfield->locked = 1;
        $startfield->visible = 2;
        $startfield->forceunique = 0;
        $startfield->signup = 1;
        $startfield->defaultdata = 0;
        $startfield->defaultdataformat = 0;
        $startfield->param1 = 2020;
        $startfield->param2 = 2050;
        $startfield->param3 = 1;
        $startfield->param4 = null;
        $startfield->param5 = null;

        $DB->insert_record(
            'user_info_field',
            $startfield
        );
    }

    // If the end field not exist, create it
    if (empty($fieldendexist)) {
        $endfield = new stdClass();
        $endfield->shortname = 'endreg';
        $endfield->name = 'End';
        $endfield->datatype = 'datetime';
        $endfield->description = '';
        $endfield->descriptionformat = 1;
        $endfield->categoryid = $categoryid;
        $endfield->sortorder = 2;
        $endfield->required = 0;
        $endfield->locked = 1;
        $endfield->visible = 2;
        $endfield->forceunique = 0;
        $endfield->signup = 1;
        $endfield->defaultdata = 0;
        $endfield->defaultdataformat = 0;
        $endfield->param1 = 2020;
        $endfield->param2 = 2050;
        $endfield->param3 = 1;
        $endfield->param4 = null;
        $endfield->param5 = null;

        $DB->insert_record(
            'user_info_field',
            $endfield
        );
    }
}
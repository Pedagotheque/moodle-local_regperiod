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
 * Local regperiod plugin uninstall script.
 *
 * @package    local_regperiod
 * @copyright  IMT Lille Douai <https://imt-lille-douai.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Romain DELEAU
 */


/**
 * A function to uninstall the plugin from the current database where it is alreeady installed.
 *
 * @return void
 */
function xmldb_local_regperiod_uninstall() {
    global $DB;

    // Delete all the reference to the start and end fields.
    $startfieldid = ($DB->get_record('user_info_field', array('shortname' => 'startreg'), 'id'))->id;
    $endfieldid = ($DB->get_record('user_info_field', array('shortname' => 'endreg'), 'id'))->id;
    $DB->delete_records('user_info_data', array('fieldid' => $startfieldid));
    $DB->delete_records('user_info_data', array('fieldid' => $endfieldid));

    // Delete the start and end fields.
    $DB->delete_records('user_info_field', array('shortname' => 'startreg'));
    $DB->delete_records('user_info_field', array('shortname' => 'endreg'));

    // Delete the regperiod category.
    $DB->delete_records('user_info_category', array('name' => 'Registration duration to the site'));
}

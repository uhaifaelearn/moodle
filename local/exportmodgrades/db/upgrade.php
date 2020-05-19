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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     local_exportmodgrades
 * @category    upgrade
 * @copyright   2017 nadavkav@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_exportmodgrades_upgrade($oldversion) {

    global $DB;
    $dbman = $DB->get_manager();

        // Add field to grade_items.
        if ($oldversion < 2018110702) {
            // Define field completionpass to be added to quiz.
            $table = new xmldb_table('grade_items');
            $field = new xmldb_field('ifexportsap', XMLDB_TYPE_INTEGER, '3', null, null, null, null, 'weightoverride');

            // Conditionally launch add field completionpass.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

    return true;
}

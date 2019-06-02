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
 * @package     local_exportmodsettings
 * @category    upgrade
 * @copyright   2017 nadavkav@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot .'/local/exportmodsettings/locallib.php');

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_local_exportmodsettings_install() {
    global $DB;

    $array = SETTINGSCRONPERIODS;
    reset($array);
    $firstkey = key($array);

    $obj = new \stdClass();
    $obj->plugin = 'local_exportmodsettings';
    $obj->name = 'crontime';
    $obj->value = $firstkey + 1;
    $DB->insert_record('config_plugins', $obj);

    return true;
}

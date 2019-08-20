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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

function xmldb_plagiarism_originality_uninstall() {
    global $DB;

    $DB->delete_records('config_plugins', array("name" => 'originality_key', 'plugin' => 'plagiarism'));
    $DB->delete_records('config_plugins', array("name" => 'originality_server', 'plugin' => 'plagiarism'));
    $DB->delete_records('config_plugins', array("name" => 'originality_use', 'plugin' => 'plagiarism'));
    $DB->delete_records('config_plugins', array("name" => 'originality_view_report', 'plugin' => 'plagiarism'));

    // ... It goes with file_identifier for next version (probably 3.1.8).
    // ...$DB->delete_records('config_plugins',array("name"=>'originality_allow_mutiple_file_submission', 'plugin'=>'plagiarism'));.
}



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
 * @package local_exportmodsettings
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 POET
 */


/**
 * Hook function to extend the course settings navigation. Call all context functions
 */

define("CRONPERIODSSELECT", array(
    0 => get_string('day'),
    1 => get_string('twodays', 'local_exportmodsettings'),
    2 => get_string('week'),
    3 => get_string('month'),
    4 => get_string('all')
));

define("CRONPERIODS", array(
    0 => 24*60*60, //sec
    1 => 2*24*60*60, //sec
    2 => 7*24*60*60, //sec
    3 => 30*24*60*60, //sec
    4 => 0 //all
));

define("DEFAULTYEAR", "default year");
define("DEFAULTSEMESTER", "default semester");

function local_exportmodsettings_generate_output_csv($output, $postdata = array()){
    global $DB;

    //Example
    $num = 0;
    $data = array();

    $headers = array(
        'YEAR',
        'SEMESTER',
        'SM_OBJID',
        'E_OBJID',
        'MOODLE_ID',
        'ASSIGN_NAME',
        'WEIGHT',
        'OBLIGATORY',
        'PASS_GRADE',
        'ASSIGN_REQ',
        'ASSIGN_FOR_AVG',
        'PARENT_ASSIGN',
        'SUPPORTIVE_GRADE',
        'ASSIGN_TYPE',
        'LAST_UPDATED',
    );

    $users = $DB->get_records('user');

    foreach($users as $user){
        $data[$num]['id'] = $user->id;
        $data[$num]['username'] = $user->username;
        $data[$num]['firstname'] = $user->firstname;
        $data[$num]['lastname'] = $user->lastname;

        $num++;
    }

    //headers
    fputcsv($output, $headers);
    foreach($data as $row) {
        fputcsv($output, $row);
    }

    return $output;
}

function local_exportmodsettings_save_file_to_disk(){
    global $DB, $CFG;

    local_exportmodsettings_log_file_success('Start cron');

    $folderPath = $CFG->dataroot.'/sap';
    $filename = 'export_'.time().'.csv';
    $pathToFile = $folderPath.'/'.$filename;

    //Create folder if not exists
    if(!file_exists($folderPath)){
        if (!mkdir($folderPath, 0755, true)){
            die("No permission for ".$folderPath);
        }
    }

    //If file present
    if(file_exists($pathToFile)){
        local_exportmodsettings_log_file_error("File present ".$pathToFile);
        //die("File present ".$pathToFile);
    }

    $output = fopen($pathToFile,'w') or die("Can't open ".$pathToFile);
    $output = local_exportmodsettings_generate_output_csv($output);
    fclose($output) or die("Can't close ".$pathToFile);

    local_exportmodsettings_log_file_success('End cron');
}

function local_exportmodsettings_download_file($postdata){
    global $DB, $CFG;

    local_exportmodsettings_log_file_success('Start download');

    $filename = 'export_'.time().'.csv';

    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=".$filename);

    $output = fopen('php://output', 'w');
    $output = local_exportmodsettings_generate_output_csv($output, $postdata);
    fclose($output);

    local_exportmodsettings_log_file_success('End download');
    exit;
}

function local_exportmodsettings_log_file($status, $str){
    global $DB, $CFG;

    $folderPath = $CFG->dataroot.'/sap';
    $filename = 'log_process.txt';
    $pathToFile = $folderPath.'/'.$filename;

    //Create folder if not exists
    if(!file_exists($folderPath)){
        if (!mkdir($folderPath, 0755, true)){
            die("No permission for ".$folderPath);
        }
    }

    $output = fopen($pathToFile,'a') or die("Can't open ".$pathToFile);

    $data = $status.' '.date("Y-m-d H:i:s").' '.$str.PHP_EOL;
    fwrite($output, $data);
    fclose($output) or die("Can't close ".$pathToFile);
}

function local_exportmodsettings_log_file_success($str){
    local_exportmodsettings_log_file('SUCCESS', $str);
}

function local_exportmodsettings_log_file_error($str){
    local_exportmodsettings_log_file('ERROR', $str);
}
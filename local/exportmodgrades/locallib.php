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
 * @package local_exportmodgrades
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 POET
 */


/**
 * Hook function to extend the course grades navigation. Call all context functions
 */

define("GRADESCRONPERIODSSELECT", array(
    0 => get_string('day'),
    1 => get_string('twodays', 'local_exportmodgrades'),
    2 => get_string('week'),
    3 => get_string('month'),
    4 => get_string('all')
));

define("GRADESCRONPERIODS", array(
    0 => 24*60*60, //sec
    1 => 2*24*60*60, //sec
    2 => 7*24*60*60, //sec
    3 => 30*24*60*60, //sec
    4 => 0 //all
));

define("GRADESTYPESEMESTER", array(
    'A' => '001',
    'B' => '002',
    'C' => '003',
));

define("GRADESTYPESEMESTERVIEW", array(
    '0' => get_string('all'),
    'A' => get_string('char_a', 'local_exportmodgrades'),
    'B' => get_string('char_b', 'local_exportmodgrades'),
    'C' => get_string('char_c', 'local_exportmodgrades'),
));

define("GRADESTYPEASSIGN", array(
    '1' => get_string('type_assign_1', 'local_exportmodgrades'),
    '11' => get_string('type_assign_2', 'local_exportmodgrades'),
    '12' => get_string('type_assign_3', 'local_exportmodgrades'),
));

define("GRADESCATEGORYOFFSET", 90000);

function local_exportmodgrades_generate_output_csv($output, $postdata = array()){
    global $DB;

    $num = 0;
    $data = array();
    $usedids = array();

    $headers = array(
        'YEAR',
        'SEMESTER',
        'SM_OBJID',
        'E_OBJID',
        'MOODLE_ID',

        'Student12',
        'Grade',
        'Passed',

        'LAST_UPDATED',
    );

    //Start test time execute
    $start = microtime(true);

    $query ="
        SELECT 
            gi.id,
            c.shortname AS course_name,
            c.idnumber AS course_idnumber,
            gi.iteminstance AS moodle_id,
            gg.userid AS student12,
            gg.finalgrade AS grade,
            gg.timemodified AS last_updated                
        
        FROM mdl_grade_grades AS gg
	    LEFT JOIN mdl_grade_items AS gi ON (gg.itemid = gi.id)
	    LEFT JOIN mdl_course AS c ON (c.id = gi.courseid)
    ";

    //If used in cron
    if(empty($postdata)){
        $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'crontime'));
        $periodago = GRADESCRONPERIODS[$row->value];

        if($periodago != 0) {
            $attributes = array(time() - $periodago);
            $select = " WHERE gg.finalgrade IS NOT NULL AND gg.timemodified > ? ";
        }else{
            $attributes = array();
            $select = " WHERE gg.finalgrade IS NOT NULL ";
        }
    }

    //If used in download file
    if(!empty($postdata) and isset($postdata->exportfile)){
        $attributes = array($postdata->startdate, $postdata->enddate);
        $select = "        
            WHERE gg.finalgrade IS NOT NULL AND gg.timemodified BETWEEN ? AND ?
        ";

        if($postdata->year != 0){
            $year = '-' . $postdata->year;
            $select .= " AND c.shortname LIKE('%" . $year . "%') ";
        }

        if($postdata->semester != '0'){
            $semester = '-' . $postdata->semester;
            $select .= " AND c.shortname LIKE('%" . $semester . "%') ";
        }
    }

    $query .= $select;

    $result = $DB->get_records_sql($query, $attributes);

    foreach ($result as $item) {

        //Prepare YEAR and SEMESTER
        $arrname = explode('-', $item->course_name);
        $yearvalue = (isset($arrname[3])) ? $arrname[3] - 1 : '';
        $semestrvalue = (isset($arrname[2])) ? GRADESTYPESEMESTER[preg_replace("/[^a-zA-Z]+/", "", $arrname[2])] : '';

        //Prepare SM_OBJID and E_OBJID
        $arridnumber = explode('-', $item->course_idnumber);
        $smobjid = (isset($arridnumber[1])) ? $arridnumber[1] : '';
        $eobjid = (isset($arridnumber[0])) ? $arridnumber[0] : '';

        //Validation
        if(empty($yearvalue) || strlen($yearvalue) != 4 || !is_numeric($yearvalue)) continue;
        if(empty($semestrvalue)) continue;
        if(empty($smobjid) || !is_numeric($smobjid)) continue;
        if(empty($eobjid) || !is_numeric($eobjid)) continue;

        $data[$num]['YEAR'] = $yearvalue;
        $data[$num]['SEMESTER'] = $semestrvalue;
        $data[$num]['SM_OBJID'] = $smobjid;
        $data[$num]['E_OBJID'] = $eobjid;

        $data[$num]['MOODLE_ID'] = $item->moodle_id;
        $data[$num]['Student12'] = $item->student12;
        $data[$num]['Grade'] = $item->grade;
        $data[$num]['Passed'] = '';

        $data[$num]['LAST_UPDATED'] = date('Ymd', $item->last_updated);

        $num++;
        $usedids[] = $item->id;
    }


    $time_elapsed_secs = microtime(true) - $start;
    local_exportmodgrades_log_file_success('Process took  '.$time_elapsed_secs.' sec');
    //End test time execute

    //headers
    fputcsv($output, $headers);
    foreach($data as $row) {
        fputcsv($output, $row);
    }

    return $output;
}

function local_exportmodgrades_save_file_to_disk($postdata = array()){
    global $DB, $CFG;

    local_exportmodgrades_log_file_success('Start save file to disk');

    $folderPath = $CFG->dataroot.'/sap';
    $filename = 'MoodleGrades-'.date("Ymd").'.csv';
    $pathToFile = $folderPath.'/'.$filename;

    //Create folder if not exists
    if(!file_exists($folderPath)){
        if (!mkdir($folderPath, 0755, true)){
            die("No permission for ".$folderPath);
        }
    }

    //If file present
    if(file_exists($pathToFile)){
        unlink($pathToFile);
    }

    $output = fopen($pathToFile,'w') or die("Can't open ".$pathToFile);
    $output = local_exportmodgrades_generate_output_csv($output, $postdata);
    fclose($output) or die("Can't close ".$pathToFile);

    local_exportmodgrades_log_file_success('End save file to disk. Saved to file '.$filename);
}

function local_exportmodgrades_download_file($postdata){
    global $DB, $CFG;

    local_exportmodgrades_save_file_to_disk($postdata);

    $folderPath = $CFG->dataroot.'/sap';
    $filename = 'MoodleGrades-'.date("Ymd").'.csv';
    $pathToFile = $folderPath.'/'.$filename;

    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=".$filename);

    readfile($pathToFile);
    exit;
}

function local_exportmodgrades_log_file($status, $str){
    global $DB, $CFG;

    $folderPath = $CFG->dataroot.'/sap_log';
    $filename = 'log_process_grades.txt';
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

function local_exportmodgrades_log_file_success($str){
    local_exportmodgrades_log_file('SUCCESS', $str);
}

function local_exportmodgrades_log_file_error($str){
    local_exportmodgrades_log_file('ERROR', $str);
}
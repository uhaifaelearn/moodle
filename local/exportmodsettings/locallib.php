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

define("SETTINGSCRONPERIODSSELECT", array(
    0 => get_string('day'),
    1 => get_string('twodays', 'local_exportmodsettings'),
    2 => get_string('week'),
    3 => get_string('month'),
    4 => get_string('all')
));

define("SETTINGSCRONPERIODS", array(
    0 => 24*60*60, //sec
    1 => 2*24*60*60, //sec
    2 => 7*24*60*60, //sec
    3 => 30*24*60*60, //sec
    4 => 0 //all
));

define("SETTINGSTYPESEMESTER", array(
    'A' => '001',
    'B' => '002',
    'C' => '003',
));

define("SETTINGSTYPESEMESTERVIEW", array(
    '0' => get_string('all'),
    'A' => get_string('char_a', 'local_exportmodsettings'),
    'B' => get_string('char_b', 'local_exportmodsettings'),
    'C' => get_string('char_c', 'local_exportmodsettings'),
));

define("SETTINGSTYPEASSIGN", array(
    '10' => get_string('type_assign_1', 'local_exportmodsettings'),
    '11' => get_string('type_assign_2', 'local_exportmodsettings'),
    '12' => get_string('type_assign_3', 'local_exportmodsettings'),
));

define("SETTINGSCATEGORYOFFSET", 90000);

function local_exportmodsettings_generate_output_csv($output, $postdata = array()){
    global $DB, $CFG;

    //require_once $CFG->dirroot.'/grade/lib.php';
    //$gtree = new grade_tree(5810, false, false);

    $num = 0;
    $data = array();
    $usedids = array();

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

    $listmods = array();

    //Get list mods
    $sql = "
      SELECT DISTINCT itemmodule
      FROM {grade_items}
      WHERE itemmodule IS NOT NULL
    ";

    $result = $DB->get_records_sql($sql);

    foreach($result as $item){
        if($item->itemmodule != 'quiz'){
            $listmods[] = $item->itemmodule;
        }
    }

    //Start test time execute
    $start = microtime(true);

    foreach($listmods as $mod) {
        $query = "
        SELECT
            gi.id,
            c.shortname AS course_name,
            c.idnumber AS course_idnumber,
            
            (CASE 
                WHEN gi.itemtype='category' THEN gi.iteminstance+90000
                WHEN gi.itemtype='manual' THEN gi.id+180000
                ELSE gi.iteminstance
            END) AS moodle_id,                       
            
            IF(gi.itemtype='category', gc.fullname, gi.itemname ) AS assign_name,
            gi.aggregationcoef AS weight,
            IF(gi.hidden = 0, 1, '' ) AS obligatory,
            gi.gradepass AS pass_grade,
            
            gi.itemtype AS itemtype,
            IF(gi.itemtype='category', 
                (
                    SELECT COUNT(*)
                    FROM {grade_items} AS sgi
                    WHERE sgi.categoryid=gc.id AND sgi.hidden=0
                )
            , '' ) AS count_children_in_category,
 
            IF(gi.itemtype!='category' && gi.categoryid IS NOT NULL , 1, 0 ) AS if_child_of_category,           
                        
            IF(gi.itemmodule!='".$mod."' OR gi.itemtype='manual', gi.categoryid, '' ) AS parent_assign,
            
            gi.timecreated AS timecreated,            
            
            IF(a.timemodified IS NOT NULL, GREATEST(a.timemodified, gi.timemodified), gi.timemodified ) AS last_updated
            
        FROM {grade_items} AS gi
        LEFT JOIN {course} AS c ON (c.id = gi.courseid)        
        LEFT JOIN {grade_categories} AS gc ON (gc.id = gi.iteminstance)
        LEFT JOIN {grade_categories} AS gcd ON (gcd.id = gi.categoryid)
        LEFT JOIN {".$mod."} AS a ON (a.id = gi.iteminstance)         
    ";

        //If used in cron
        if (empty($postdata)) {
            $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodsettings', 'name' => 'crontime'));
            $periodago = SETTINGSCRONPERIODS[$row->value];

            if ($periodago != 0) {
                $attributes = array(time() - $periodago);
                $select = " WHERE (gi.itemmodule='".$mod."' OR (gi.itemmodule IS NULL AND gi.itemtype!='course') OR gi.itemtype='category') 
                    AND IF(a.timemodified IS NOT NULL, GREATEST(a.timemodified, gi.timemodified), gi.timemodified ) > ?  ";
            } else {
                $attributes = array();
                $select = " WHERE (gi.itemmodule='".$mod."' OR (gi.itemmodule IS NULL AND gi.itemtype!='course') OR gi.itemtype='category') ";
            }
        }

        //If used in download file
        if (!empty($postdata) and isset($postdata->exportfile)) {
            $attributes = array($postdata->startdate, $postdata->enddate);

            $select = " 
                WHERE (gi.itemmodule='".$mod."' OR (gi.itemmodule IS NULL AND gi.itemtype!='course') OR gi.itemtype='category') 
                AND IF(a.timemodified IS NOT NULL, GREATEST(a.timemodified, gi.timemodified), gi.timemodified ) BETWEEN ? AND ? 
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

            if(in_array($item->id, $usedids)) continue;

            //Prepare YEAR and SEMESTER
            $arrname = explode('-', $item->course_name);
            $yearvalue = (isset($arrname[3])) ? $arrname[3] - 1 : '';

            $semestrvalue = '';
            if(!empty($arrname[2])){
                $val = preg_replace("/[^a-zA-Z]+/", "", $arrname[2]);

                $arraykeys = array_keys(SETTINGSTYPESEMESTER);

                if(in_array($val, $arraykeys)){
                    $semestrvalue = SETTINGSTYPESEMESTER[$val];
                }
            }

            //Prepare SM_OBJID and E_OBJID
            $arridnumber = explode('-', $item->course_idnumber);
            $smobjid = (isset($arridnumber[1])) ? $arridnumber[1] : '';
            $eobjid = (isset($arridnumber[0])) ? $arridnumber[0] : '';

            //Validation
            if(empty($yearvalue) || strlen($yearvalue) != 4 || !is_numeric($yearvalue)) continue;
            if(empty($semestrvalue)) continue;
            if(empty($smobjid) || !is_numeric($smobjid)) continue;
            if(empty($eobjid) || !is_numeric($eobjid)) continue;

            //Recalculate for categoryid (course) : if_child_of_category, parent_assign
            if(!empty($item->parent_assign)) {
                $sql = "
                    SELECT *
                    FROM {grade_items}
                    WHERE iteminstance=? AND itemtype='course'
                ";
                $res = $DB->get_records_sql($sql, array($item->parent_assign));

                if (count($res) > 0) {
                    $item->parent_assign = '';
                    $item->if_child_of_category = 0;
                }
            }

            $data[$num]['YEAR'] = $yearvalue;
            $data[$num]['SEMESTER'] = $semestrvalue;
            $data[$num]['SM_OBJID'] = $smobjid;
            $data[$num]['E_OBJID'] = $eobjid;

            $data[$num]['MOODLE_ID'] = $item->moodle_id;
            $data[$num]['ASSIGN_NAME'] = str_replace(',', ' ', $item->assign_name);
            $data[$num]['WEIGHT'] = round($item->weight, 5);
            $data[$num]['OBLIGATORY'] = $item->obligatory;
            $data[$num]['PASS_GRADE'] = round($item->pass_grade, 5);

            $data[$num]['ASSIGN_REQ'] = $item->count_children_in_category;
            $data[$num]['ASSIGN_FOR_AVG'] = $item->count_children_in_category;
            $data[$num]['PARENT_ASSIGN'] = (!empty($item->parent_assign))?$item->parent_assign + 90000:'';
            $data[$num]['SUPPORTIVE_GRADE'] = '';

            $assign_type = '';
            if($item->itemtype == 'category'){
                if($item->count_children_in_category) $assign_type = 10;
            }else{
                if($item->if_child_of_category) $assign_type = 11;
                if(!$item->if_child_of_category) $assign_type = 12;
            }

            $data[$num]['ASSIGN_TYPE'] = $assign_type;

            if($item->last_updated == null || empty($item->last_updated)){
                $data[$num]['LAST_UPDATED'] = date('Ymd', $item->timecreated);
            }else{
                $data[$num]['LAST_UPDATED'] = date('Ymd', $item->last_updated);
            }

            $num++;
            $usedids[] = $item->id;
        }
    }

    $time_elapsed_secs = microtime(true) - $start;
    local_exportmodsettings_log_file_success('Process took  '.$time_elapsed_secs.' sec');
    //End test time execute

    //headers
    fputcsv($output, $headers);
    foreach($data as $row) {
        fputcsv($output, $row);
    }

    return $output;
}

function local_exportmodsettings_save_file_to_disk($postdata = array()){
    global $DB, $CFG;

    local_exportmodsettings_log_file_success('Start save file to disk');

    $folderPath = $CFG->dataroot.'/sap';
    $filename = 'MoodleAssg-'.date("Ymd").'.csv';
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
    $output = local_exportmodsettings_generate_output_csv($output, $postdata);
    fclose($output) or die("Can't close ".$pathToFile);

    local_exportmodsettings_log_file_success('End save file to disk. Saved to file '.$filename);
}

function local_exportmodsettings_download_file($postdata){
    global $DB, $CFG;

    local_exportmodsettings_save_file_to_disk($postdata);

    $folderPath = $CFG->dataroot.'/sap';
    $filename = 'MoodleAssg-'.date("Ymd").'.csv';
    $pathToFile = $folderPath.'/'.$filename;

    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=".$filename);

    readfile($pathToFile);
    exit;
}

function local_exportmodsettings_log_file($status, $str){
    global $DB, $CFG;

    $folderPath = $CFG->dataroot.'/sap_log';
    $filename = 'log_process_settings.txt';
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
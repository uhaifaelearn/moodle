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
        0 => 24 * 60 * 60, //sec
        1 => 2 * 24 * 60 * 60, //sec
        2 => 7 * 24 * 60 * 60, //sec
        3 => 30 * 24 * 60 * 60, //sec
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

function local_exportmodgrades_query_with_grade($postdata) {
    global $DB;

    $query = "
        SELECT 
            gg.id,
            gi.id AS giid,
            gg.userid AS userid,
            c.id AS course_id,
            c.shortname AS course_name,
            c.idnumber AS course_idnumber,
            gi.iteminstance AS iteminstance,
            gi.itemtype AS itemtype,
            gi.itemmodule AS itemmodule,            
            u.idnumber AS student12,
                        
            CASE WHEN gg.finalgrade IS NULL THEN 0 ELSE gg.finalgrade END AS grade,
                        
            gg.timecreated AS timecreated,                
            gg.timemodified AS last_updated,
            gg.feedback AS feedback,
            gi.gradepass AS gradepass,
            
            gi.gradetype AS gradetype,               
            gi.scaleid AS scaleid,               
            gi.ifexportsap AS ifexportsap               
        
        FROM {grade_grades} AS gg
	    LEFT JOIN {grade_items} AS gi ON (gg.itemid = gi.id)
	    LEFT JOIN {course} AS c ON (c.id = gi.courseid)
	    LEFT JOIN {user} AS u ON (u.id = gg.userid)
	    LEFT JOIN {grade_categories} AS gc ON (gc.id = gi.categoryid)
    ";

    //$select = " WHERE (gg.finalgrade IS NOT NULL OR (gi.itemtype = 'category')) AND gi.itemtype != 'course' AND gi.hidden = 0 ";
    $select = " WHERE (gg.finalgrade IS NOT NULL OR (gi.itemtype = 'category')) AND gi.itemtype != 'course' AND c.visible=1 ";

    //If used in cron
    if (empty($postdata)) {
        $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'crontime'));
        $periodago = GRADESCRONPERIODS[$row->value];

        if ($periodago != 0) {
            $attributes = array(time() - $periodago);
            $select .= " AND gg.timemodified > ? ";
        }
    }

    //If used in download file
    if (!empty($postdata) and isset($postdata->exportfile)) {

        // Change enddate.
        $postdata->enddate = $postdata->enddate + 24 * 60 * 60;

        $attributes = array($postdata->startdate, $postdata->enddate);
        $select .= " AND gg.timemodified BETWEEN ? AND ? ";

        if ($postdata->year != 0) {
            $year = '-' . $postdata->year;
            $select .= " AND c.shortname LIKE('%" . $year . "%') ";
        }

        if ($postdata->semester != '0') {
            $semester = '-' . $postdata->semester;
            $select .= " AND c.shortname LIKE('%" . $semester . "%') ";
        }

        if (!empty($postdata->courseid)) {
            $select .= " AND c.id IN(" . $postdata->courseid . ") ";
        }

    }

    $query .= $select;

    return $DB->get_records_sql($query, $attributes);
}

function local_exportmodgrades_query_with_grade_empty($postdata) {
    global $DB;

    $query = "
        SELECT 
            gg.id,
            gi.id AS giid,
            gg.userid AS userid,
            c.id AS course_id,
            c.shortname AS course_name,
            c.idnumber AS course_idnumber,
            gi.iteminstance AS iteminstance,
            gi.itemtype AS itemtype,
            gi.itemmodule AS itemmodule,            
            u.idnumber AS student12,
                        
            CASE WHEN gg.finalgrade IS NULL THEN 0 ELSE gg.finalgrade END AS grade,
                        
            '' AS timecreated,                
            UNIX_TIMESTAMP() AS last_updated,
            gg.feedback AS feedback,
            gi.gradepass AS gradepass,
            
            gi.gradetype AS gradetype,               
            gi.scaleid AS scaleid,               
            gi.ifexportsap AS ifexportsap               
        
        FROM {grade_grades} AS gg
	    LEFT JOIN {grade_items} AS gi ON (gg.itemid = gi.id)
	    LEFT JOIN {course} AS c ON (c.id = gi.courseid)
	    LEFT JOIN {user} AS u ON (u.id = gg.userid)
	    LEFT JOIN {grade_categories} AS gc ON (gc.id = gi.categoryid)
    ";

    //$select = " WHERE (gg.finalgrade IS NULL AND gc.aggregateonlygraded = 0) AND gi.itemtype != 'course' AND gi.hidden = 0 ";
    $select = " WHERE (gg.finalgrade IS NULL AND gc.aggregateonlygraded = 0) AND gi.itemtype != 'course' AND c.visible=1";

    //If used in download file
    if (!empty($postdata) and isset($postdata->exportfile)) {

        $attributes = array();

        if ($postdata->year != 0) {
            $year = '-' . $postdata->year;
            $select .= " AND c.shortname LIKE('%" . $year . "%') ";
        }

        if ($postdata->semester != '0') {
            $semester = '-' . $postdata->semester;
            $select .= " AND c.shortname LIKE('%" . $semester . "%') ";
        }

        if (!empty($postdata->courseid)) {
            $select .= " AND c.id IN(" . $postdata->courseid . ") ";
        }

    }

    $query .= $select;

    return $DB->get_records_sql($query, $attributes);
}

function local_exportmodgrades_query_without_grade($courseid, $postdata) {
    global $DB;

    $context = context_course::instance($courseid);
    $students = get_role_users(5, $context);
    $arr = array();

    foreach ($students as $item) {
        $arr[] = $item->id;
    }

    if (empty($arr)) {
        return array();
    }

    $listusers = implode(',', $arr);

    $query = "
        SELECT  
        CONCAT(gi.giid,gi.userid) AS id,         
        gi.giid AS giid,
        gg.userid AS userid,
        c.id AS course_id,
        c.shortname AS course_name,
        c.idnumber AS course_idnumber,
        gi.iteminstance AS iteminstance,
        gi.itemmodule AS itemmodule,
        gi.itemtype AS itemtype,
        gi.student12 AS student12,
        gi.grade AS grade,
        '' AS timecreated,
        UNIX_TIMESTAMP() AS last_updated,
        '' AS feedback,
        gi.gradepass AS gradepass,
        gi.gradetype AS gradetype,
        gi.scaleid AS scaleid,
        gi.ifexportsap AS ifexportsap
        
        FROM (
            SELECT
                gi.id AS giid,
                u.id AS userid,	
                gi.courseid AS courseid,
                gi.categoryid AS categoryid,
                gi.iteminstance AS iteminstance,
                gi.itemtype AS itemtype,
                gi.itemmodule AS itemmodule,
                gi.gradepass AS gradepass,
                gi.gradetype AS gradetype,
                gi.scaleid AS scaleid,
                gi.ifexportsap AS ifexportsap,
                u.idnumber AS student12,
                0 AS finalgrade,
                0 AS grade
                
            FROM {grade_items} AS gi, {user} AS u
        
            WHERE gi.itemtype != 'course' AND gi.hidden = 0 AND u.id IN(" . $listusers . ")
        ) AS gi
        
        LEFT JOIN {course} AS c ON (c.id = gi.courseid)
        LEFT JOIN {grade_grades} AS gg ON (gg.itemid = gi.giid AND gg.userid = gi.userid)
        LEFT JOIN {grade_categories} AS gc ON (gc.id = gi.categoryid)
        
        WHERE gg.id IS NULL AND gc.aggregateonlygraded = 0 AND c.visible=1
       
    ";

    //If used in download file
    if (!empty($postdata) and isset($postdata->exportfile)) {

        $attributes = array();
        $select = "";

        if ($postdata->year != 0) {
            $year = '-' . $postdata->year;
            $select .= " AND c.shortname LIKE('%" . $year . "%') ";
        }

        if ($postdata->semester != '0') {
            $semester = '-' . $postdata->semester;
            $select .= " AND c.shortname LIKE('%" . $semester . "%') ";
        }

        if (!empty($postdata->courseid)) {
            $select .= " AND c.id IN(" . $postdata->courseid . ") ";
        }

    }

    $query .= $select;

    return $DB->get_records_sql($query, $attributes);
}

function local_exportmodgrades_prepare_csv_content($result, $postdata = array()) {
    global $DB;

    $num = 0;
    $data = array();
    $courses = array();

    foreach ($result as $item) {

        $quizenable = false;

        //If used in cron
        if (empty($postdata)) {
            $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'ifquizcron'));
            if (isset($row->value) && $row->value == 1) {
                $quizenable = true;
            }
        }

        //If used in download file
        if (!empty($postdata) and isset($postdata->exportfile)) {
            if (isset($postdata->ifquiz) && $postdata->ifquiz == 1) {
                $quizenable = true;
            }
        }

        // Check if quiz.
        if ($item->itemmodule == 'quiz' && !$quizenable) {
            continue;
        }

        if ($item->itemmodule == 'quiz') {
            $plugs = \core_component::get_plugin_list('local');
            if (isset($plugs['extendedfields'])) {
                $row = $DB->get_record('local_extendedfields', array('instanceid' => $item->iteminstance));
                if (!empty($row) && $row->status == 1) {
                    continue;
                }
            }
        }

        // Export to SAP items manual
        //If used in cron
        if (empty($postdata)) {
            $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'ifexportsapcron'));
            if (isset($row->value) && $row->value == 1) {
                if ($item->itemtype == 'manual' && $item->ifexportsap != 1) {
                    continue;
                }
            } else {
                if ($item->itemtype == 'manual') {
                    continue;
                }
            }
        }

        //If used in download file
        if (!empty($postdata) and isset($postdata->ifexportsap)) {
            if ($postdata->ifexportsap) {
                if ($item->itemtype == 'manual' && $item->ifexportsap != 1) {
                    continue;
                }
            } else {
                if ($item->itemtype == 'manual') {
                    continue;
                }
            }
        }

        //Prepare YEAR and SEMESTER
        $arrname = explode('-', $item->course_name);
        $yearvalue = (isset($arrname[3])) ? $arrname[3] - 1 : '';

        $semestrvalue = '';
        if (!empty($arrname[2])) {
            $val = preg_replace("/[^a-zA-Z]+/", "", $arrname[2]);

            $arraykeys = array_keys(GRADESTYPESEMESTER);

            if (in_array($val, $arraykeys)) {
                $semestrvalue = GRADESTYPESEMESTER[$val];
            }
        }

        // Prepare SM_OBJID and E_OBJID
        $tatname = local_exportmodgrades_return_tat_course_name($item->course_id, $item->userid);
        if (!empty($tatname)) {
            $arridnumber = explode('-', $tatname);
        } else {
            $arridnumber = explode('-', $item->course_idnumber);
        }

        $smobjid = (isset($arridnumber[1])) ? $arridnumber[1] : '';
        $eobjid = (isset($arridnumber[0])) ? $arridnumber[0] : '';

        // Validation
        if (empty($yearvalue) || strlen($yearvalue) != 4 || !is_numeric($yearvalue)) {
            continue;
        }
        if (empty($semestrvalue)) {
            continue;
        }
        if (empty($smobjid) || !is_numeric($smobjid)) {
            continue;
        }
        if (empty($eobjid) || !is_numeric($eobjid)) {
            continue;
        }

        $data[$num]['YEAR'] = $yearvalue;
        $data[$num]['SEMESTER'] = $semestrvalue;
        $data[$num]['SM_OBJID'] = $smobjid;
        $data[$num]['E_OBJID'] = $eobjid;

        switch ($item->itemtype) {
            case 'manual':
                $moodleid = $item->giid + 180000;
                break;

            case 'category':
                $moodleid = $item->iteminstance + 90000;
                break;

            default:

                // TODO QUIZ numeration
                if ($item->itemmodule == 'quiz') {
                    $moodleid = $item->iteminstance + 200000;
                } else {
                    $moodleid = $item->iteminstance;
                }
        }

        $data[$num]['MOODLE_ID'] = $moodleid;
        $data[$num]['Student12'] = str_pad($item->student12, 12, '0', STR_PAD_LEFT);

        $passed = '';

        // Calculate PASSED T/F  For special settings.
        if ($item->gradetype == 2 && $item->scaleid == 3) {
            if (!empty($item->grade) && round($item->grade) == 1) {
                $passed = 'F';
                $item->grade = '';
            }

            if (!empty($item->grade) && round($item->grade) == 2) {
                $passed = 'P';
                $item->grade = '';
            }

            // For assign.
            if ($item->itemmodule == 'assign' && empty($passed)) {
                if (is_numeric($item->feedback)) {
                    $item->grade = $item->feedback;
                }
            }
        }

        // Calculate PASSED T/F for quiz.
        //if($item->itemmodule == 'quiz'){
        //    if(!empty($item->gradepass)){
        //        if($item->grade >= $item->gradepass) $passed = 'P';
        //        if($item->grade < $item->gradepass) $passed = 'F';
        //    }
        //}

        $data[$num]['Grade'] = (empty($item->grade)) ? $item->grade : round($item->grade);

        $data[$num]['Passed'] = $passed;

        //Lecturer_ID
        $context = context_course::instance($item->course_id);

        //Editing teacher
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $context);

        //Teacher
        $role = $DB->get_record('role', array('shortname' => 'teacher'));
        $teachers = array_merge($teachers, get_role_users($role->id, $context));

        //Teacher Assistant
        $role = $DB->get_record('role', array('shortname' => 'teachingassistant'));
        $teachers = array_merge($teachers, get_role_users($role->id, $context));

        //Coursecreator
        //        $role = $DB->get_record('role', array('shortname' => 'coursecreator'));
        //        $teachers = array_merge($teachers, get_role_users($role->id, $context));

        //$arridnumbers = array_column($teachers, 'idnumber');
        //$arridnumbers = array_filter($arridnumbers);

        $firstteacher = reset($teachers);
        if (!empty($firstteacher)) {
            $data[$num]['Lecturer_ID'] = str_pad($firstteacher->idnumber, 12, '0', STR_PAD_LEFT);
        } else {
            $data[$num]['Lecturer_ID'] = '';
        }

        if ($item->last_updated == null || empty($item->last_updated)) {
            $data[$num]['LAST_UPDATED'] = date('Ymd', $item->timecreated);
        } else {
            $data[$num]['LAST_UPDATED'] = date('Ymd', $item->last_updated);
        }

        $num++;
        $courses[] = $item->course_id;
    }

    return array('data' => $data, 'courses' => $courses);
}

function local_exportmodgrades_generate_output_csv($output, $postdata = array()) {
    global $DB;

    $courses = array();
    $headers = array(
            'YEAR',
            'SEMESTER',
            'SM_OBJID',
            'E_OBJID',
            'MOODLE_ID',

            'Student12',
            'Grade',
            'Passed',
            'Lecturer_ID',

            'LAST_UPDATED',
    );

    //headers
    fputcsv($output, $headers);

    //Start test time execute
    $start = microtime(true);

    // Step 1.
    $result = local_exportmodgrades_query_with_grade($postdata);
    $data = local_exportmodgrades_prepare_csv_content($result, $postdata);
    $courses = array_merge($courses, $data['courses']);
    foreach ($data['data'] as $row) {
        fputs($output, implode(",", array_map("local_exportmodgrades_encodeFunc", $row)) . "\r\n");
    }

    // Step 2.
    $result = local_exportmodgrades_query_with_grade_empty($postdata);
    $data = local_exportmodgrades_prepare_csv_content($result, $postdata);
    $courses = array_merge($courses, $data['courses']);
    foreach ($data['data'] as $row) {
        fputs($output, implode(",", array_map("local_exportmodgrades_encodeFunc", $row)) . "\r\n");
    }

    // Step 3.
    $courses = array_unique($courses);
    foreach ($courses as $courseid) {
        foreach (local_exportmodgrades_query_without_grade($courseid, $postdata) as $row) {
            fputs($output, implode(",", array_map("local_exportmodgrades_encodeFunc", $row)) . "\r\n");
        }
    }

    $time_elapsed_secs = microtime(true) - $start;
    local_exportmodgrades_log_file_success('Process took  ' . $time_elapsed_secs . ' sec');
    //End test time execute

    return $output;
}

function local_exportmodgrades_encodeFunc($value) {
    // remove any ESCAPED double quotes within string.
    $value = str_replace('\\"', '"', $value);
    // then force escape these same double quotes And Any UNESCAPED Ones.
    $value = str_replace('"', '\"', $value);
    // force wrap value in quotes and return
    return $value;
}

function local_exportmodgrades_return_tat_course_name($courseid, $userid) {
    global $DB;

    // Get tat courses.
    $sql = "
        SELECT 
            e.id AS enrolid, 
            e.customint1 AS courseid,
            c.idnumber AS idnumber
        FROM {enrol} AS e
        LEFT JOIN {course} AS c ON (c.id=e.customint1)
        WHERE enrol='meta' AND `courseid` = ?    
    ";
    $tatcourses = $DB->get_records_sql($sql, array($courseid));

    foreach ($tatcourses as $tatcourse) {
        $sql = "
            SELECT *
            FROM {enrol}
            WHERE enrol='manual' AND `courseid` = ?
        ";
        $enrol = $DB->get_record_sql($sql, array($tatcourse->courseid));

        $sql = "
            SELECT *
            FROM {user_enrolments}
            WHERE enrolid = ? AND userid = ?
        ";
        $obj = $DB->get_records_sql($sql, array($enrol->id, $userid));

        if (!empty($obj)) {
            return $tatcourse->idnumber;
        }
    }

    return '';
}

function local_exportmodgrades_save_file_to_disk($postdata = array()) {
    global $DB, $CFG;

    local_exportmodgrades_log_file_success('Start save file to disk');

    $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'filename'));
    $addname = !empty($row) ? $row->value : date("Y");

    $folderPath = $CFG->dataroot . '/sap_grades';
    $filename = 'MoodleGrades-' . date("Ymd") . $addname . '.csv';
    $pathToFile = $folderPath . '/' . $filename;

    //Create folder if not exists
    if (!file_exists($folderPath)) {
        if (!mkdir($folderPath, 0755, true)) {
            die("No permission for " . $folderPath);
        }
    }

    //If file present
    if (file_exists($pathToFile)) {
        unlink($pathToFile);
    }

    $output = fopen($pathToFile, 'w') or die("Can't open " . $pathToFile);

    $courses = local_exportmodgrades_get_courses($postdata);

    if (!empty($courses)) {
        $postdata->courseid = implode(',', $courses);
    }
    $output = local_exportmodgrades_generate_output_csv($output, $postdata);
    fclose($output) or die("Can't close " . $pathToFile);

    local_exportmodgrades_log_file_success('End save file to disk. Saved to file ' . $filename);
}

function local_exportmodgrades_download_file($postdata, $ifcreatefile) {
    global $DB, $CFG;

    local_exportmodgrades_save_file_to_disk($postdata);

    $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'filename'));
    $addname = !empty($row) ? $row->value : date("Y");

    $folderPath = $CFG->dataroot . '/sap_grades';
    $filename = 'MoodleGrades-' . date("Ymd") . $addname . '.csv';
    $pathToFile = $folderPath . '/' . $filename;

    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=" . $filename);

    readfile($pathToFile);

    if (!$ifcreatefile) {
        unlink($pathToFile);
    }

    exit;
}

function local_exportmodgrades_log_file($status, $str) {
    global $DB, $CFG;

    $folderPath = $CFG->dataroot . '/sap_log';
    $filename = 'log_process_grades.txt';
    $pathToFile = $folderPath . '/' . $filename;

    //Create folder if not exists
    if (!file_exists($folderPath)) {
        if (!mkdir($folderPath, 0755, true)) {
            die("No permission for " . $folderPath);
        }
    }

    $output = fopen($pathToFile, 'a') or die("Can't open " . $pathToFile);

    $data = $status . ' ' . date("Y-m-d H:i:s") . ' ' . $str . PHP_EOL;
    fwrite($output, $data);
    fclose($output) or die("Can't close " . $pathToFile);
}

function local_exportmodgrades_log_file_success($str) {
    local_exportmodgrades_log_file('SUCCESS', $str);
}

function local_exportmodgrades_log_file_error($str) {
    local_exportmodgrades_log_file('ERROR', $str);
}

function local_exportmodgrades_get_courses($postdata) {
    global $DB;

    $query = "
        SELECT 
            DISTINCT (c.id),
            c.shortname,
            c.idnumber           
        FROM {grade_grades} AS gg
	    LEFT JOIN {grade_items} AS gi ON (gg.itemid = gi.id)
	    LEFT JOIN {course} AS c ON (c.id = gi.courseid)
	    LEFT JOIN {user} AS u ON (u.id = gg.userid)
	    LEFT JOIN {grade_categories} AS gc ON (gc.id = gi.categoryid)
    ";

    //$select = " WHERE (gg.finalgrade IS NOT NULL OR (gi.itemtype = 'category')) AND gi.itemtype != 'course' AND gi.hidden = 0 ";
    $select = " WHERE (gg.finalgrade IS NOT NULL OR (gi.itemtype = 'category')) AND gi.itemtype != 'course' AND c.visible=1";

    //If used in cron
    if (empty($postdata)) {
        $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'crontime'));
        $periodago = GRADESCRONPERIODS[$row->value];

        if ($periodago != 0) {
            $attributes = array(time() - $periodago);
            $select .= " AND gg.timemodified > ? ";
        }
    }

    //If used in download file
    if (!empty($postdata) and isset($postdata->exportfile)) {

        // Change enddate.
        $postdata->enddate = $postdata->enddate + 24 * 60 * 60;

        $attributes = array($postdata->startdate, $postdata->enddate);
        $select .= " AND gg.timemodified BETWEEN ? AND ? ";

        if ($postdata->year != 0) {
            $year = '-' . $postdata->year;
            $select .= " AND c.shortname LIKE('%" . $year . "%') ";
        }

        if ($postdata->semester != '0') {
            $semester = '-' . $postdata->semester;
            $select .= " AND c.shortname LIKE('%" . $semester . "%') ";
        }

        if (!empty($postdata->courseid)) {
            $select .= " AND c.id IN(" . $postdata->courseid . ") ";
        }

    }
    $query .= $select;
    $courses = $DB->get_records_sql($query, $attributes);
    $result = [];
    foreach ($courses as $course) {

        $arrname = explode('-', $course->shortname);
        $yearvalue = (isset($arrname[3])) ? $arrname[3] - 1 : '';
        $arridnumber = explode('-', $course->idnumber);
        $smobjid = (isset($arridnumber[1])) ? $arridnumber[1] : '';
        $eobjid = (isset($arridnumber[0])) ? $arridnumber[0] : '';
        if (empty($yearvalue) || strlen($yearvalue) != 4 || !is_numeric($yearvalue)) {
            continue;
        }
        if (empty($smobjid) || !is_numeric($smobjid)) {
            continue;
        }
        if (empty($eobjid) || !is_numeric($eobjid)) {
            continue;
        }

        // Validation
        if (empty($yearvalue) || strlen($yearvalue) != 4 || !is_numeric($yearvalue)) {
            continue;
        }
        $result[] = $course->id;
    }

    return $result;

}
<?php

namespace gradeexport_haifa_administration_sap;

use stdClass;
use context_course;
use grade_helper;
use grade_export;
use grade_export_update_buffer;
use graded_users_iterator;
use gradeexport_haifa_administration_sap\Excel\HaifaExcelWorkbook;
use gradeexport_haifa_administration_sap\Excel\HaifaExcelWorksheet;

require_once ($CFG->libdir . '/csvlib.class.php');

//require_once $CFG->dirroot.'/grade/export/lib.php';
//require_once 'grade_export_haifa_administration_form.php';

class GradeExport extends grade_export
{
    public $plugin = 'haifa_administration_sap';
    private $worksheetTitles;
    private $courseModule;

    /**
     * Constructor should set up all the private variables ready to be pulled
     *
     * @param object    $course
     * @param int       $groupId    id of selected group, 0 means all
     * @param stdClass  $formData   The validated data from the grade export form.
     */
    public function __construct($course, $groupId, $formData)
    {
        parent::__construct($course, $groupId, $formData);

        // Overrides.
        $this->usercustomfields = true;
        $this->worksheetTitles  = $formData->worksheetTitles;

        $this->columns = array();
        if (!empty($formData->itemids)) {
            if ($formData->itemids=='-1') {
                //user deselected all items
            } else {
                $flipitems = array_flip($formData->itemids);
                foreach ($flipitems as $itemid=>$selected) {
                    $itemid = clean_param($itemid, PARAM_INT);
                    $flipitems[$itemid] = 1;
                    //if ($selected and array_key_exists($itemid, $this->grade_items)) {
                    if (array_key_exists($itemid, $this->grade_items)) {
                        $this->columns[$itemid] =& $this->grade_items[$itemid];
                    }
                }
            }
        } else {
            foreach ($this->grade_items as $itemid=>$unused) {
                $this->columns[$itemid] =& $this->grade_items[$itemid];
            }
        }

    }

    /**
     * To be implemented by child classes
     */
    public function print_grades()
    {
        global $CFG, $DB;
        
        $result = array();
                
        //$grade_teacher = required_param('grade_teacher', PARAM_INT);
        $grade_teacher = '';
        $grade_option = required_param('grade_option', PARAM_INT);

        $exportTracking     = $this->track_exports();
        
        //Get course context
        //$context = get_context_instance(CONTEXT_COURSE, $this->course->id); 
        $context = context_course::instance($this->course->id);
        
        //Get course tag old version
//        $sql = "
//            SELECT t.*
//            FROM {tag_instance} AS ti
//            LEFT JOIN {tag} AS t ON(ti.tagid=t.id)
//            WHERE ti.contextid=".$context->id."
//            LIMIT 1
//        ";
//
//        $obj_tag = $DB->get_record_sql($sql);
//
//        $tag_course = '';
//        if(!empty($obj_tag)){
//            $tag_course = $obj_tag->name;
//        }

        $arrSapidAndEventID = explode("-", $this->course->idnumber);
        $sapid=isset($arrSapidAndEventID[1])?$arrSapidAndEventID[1]:"";
        $eventid=isset($arrSapidAndEventID[0])?$arrSapidAndEventID[0]:"";


        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $context);
        $firstteacher=reset($teachers);
        if(!empty($firstteacher)){
            $teacher_id = $firstteacher->idnumber;
        }else{

            $role = $DB->get_record('role', array('shortname' => 'teacher'));
            $teachers = get_role_users($role->id, $context);
            $firstteacher=reset($teachers);
            if(!empty($firstteacher)){
                $teacher_id = $firstteacher->idnumber;
            }

        }

        // Print all the lines of data.
        $i                          = 0;
        $gradeExportUpdateBuffer    = new grade_export_update_buffer();
        $gradedUsersIterator        = new graded_users_iterator($this->course, $this->columns, $this->groupid, 'idnumber', 'ASC', null, null);

        $gradedUsersIterator->require_active_enrolment($this->onlyactive);
        $gradedUsersIterator->allow_user_custom_fields($this->usercustomfields);
        $gradedUsersIterator->init();

        while ($userData = $gradedUsersIterator->next_user()) {
            $i++;
            $user   = $userData->user;
            $obj_tmp = new stdClass();

            $obj_tmp->SM_Objid = $sapid;
          //  $obj_tmp->ST_Objid = str_pad($user->idnumber, 8, '0', STR_PAD_LEFT);
            $obj_tmp->ST_Objid = $eventid;
            $obj_tmp->Student12 = str_pad($user->idnumber, 12, '0', STR_PAD_LEFT);
            $obj_tmp->Lecturer_ID = $teacher_id;

            $obj_tmp_grade = array();
            foreach ($userData->grades as $itemId => $grade) {
                $obj_grade = new stdClass();
                foreach ($this->displaytype as $gradedIsPlayConst) {
                    $gradeStr   = $this->format_grade($grade, $gradedIsPlayConst);

                    $obj_grade->grade_id = $itemId;
                    $obj_grade->grade_value = $gradeStr;
                }

                $obj_tmp_grade[] = $obj_grade;
            }

            $obj_tmp->grades = $obj_tmp_grade;

            $result[] = $obj_tmp;
        }

        $gradedUsersIterator->close();
        $gradeExportUpdateBuffer->close();

        $result = $this->prepareDataForCsv($result,$grade_teacher);

        //Export CSV
        $shortname = format_string($this->course->shortname, true, ['context' => context_course::instance($this->course->id)]);
        $arr_shortname = explode('-', $shortname);
        $year = date("Y");
        $moad = $grade_option==0?1:$grade_option;
        $event = $eventid;
        $code = '0002';
        $date = date("Ymd");   ;

        $year = isset($arr_shortname[3])?$arr_shortname[3]:"";
        $year=$year-1;
        $semestr = isset($arr_shortname[2])?substr($arr_shortname[2], 0, 1):"";

        if ($semestr=="A"){
            $semestr=1;
        }

        if ($semestr=="B"){
            $semestr=2;
        }

        if ($semestr=="S"){
            $semestr=3;
        }

        //Name of filename
        $filename = $sapid.$year.$semestr.$moad.$eventid.'-'.$code.'-'.$date;

        $downloadfilename = clean_filename ( $filename );


        // Print names of all the fields
        $fieldnames = array (
                'SM_Objid',
                'ST_Objid',
                'Student12', 
                'Grade', 
                'Passed', 
                'Lecturer_ID' 
        );
            
        $csvexport = new \csv_export_writer ( '' );
      //  $csvexport->set_filename ( $downloadfilename );
        $csvexport->filename = $downloadfilename.'.csv';

        $exporttitle = array ();
        foreach ( $fieldnames as $field ) {
            $exporttitle [] = $field;
        }

        // add the header line to the data
        $csvexport->add_data ( $exporttitle );

        // Print all the lines of data.
        foreach ( $result as $userline ) {
            $csvexport->add_data ( $userline );
        }
        
        
        $path_to_directory = $CFG->dataroot.'/sap';
        if (!file_exists($path_to_directory)) {
            mkdir($path_to_directory, 0777, true);
        }        
                
        $path_to_save = $path_to_directory.'/'.$downloadfilename.'.csv';
        $data = $csvexport->print_csv_data(true);
        
        //Save file
        file_put_contents($path_to_save, $data);
        
        // let him serve the csv-file
        $csvexport->download_file ();        
        
        exit;    
    }

    //prepare and customize Grades
    private function prepareDataForCsv($data, $pass_value=0){
        $result = array();
        $tmpresult= array();
        foreach($data as $item_user){
            
            if(!empty($item_user->grades)){
                //TO DO empty Passed column
                $item_user->Passed='';

                foreach ($item_user->grades as $gradeinstance => $gradevalues) {
                    $item_user->Grade = $gradevalues->grade_value;
                    if ($item_user->Grade=='-'){$item_user->Grade='';}
                    $tmpresult[] = clone $item_user;
                }
            
                //as it was before
                // $item_user->Grade = $item_user->grades[0]->grade_value;
                // if ($item_user->Grade=='-'){
                //     $item_user->Grade='';
                // }
//                if(is_numeric($item_user->grades[0]->grade_value)){
//                    $item_user->Grade = $item_user->grades[0]->grade_value;
//                }else{
//                    $item_user->Grade = '';
//                }

//                if($pass_value != 0 && $item_user->Grade < $pass_value){
//                    $item_user->Passed = 'F';
//                }else{
//                    $item_user->Passed = 'P';
//                }

//                if ($pass_value==0){
//                    $item_user->Passed = '';
//                }else{
//
//                    if ($item_user->Grade==''){
//                        $item_user->Passed='';
//                    }else if($item_user->Grade < $pass_value){
//                        $item_user->Passed = 'F';
//                        $item_user->Grade='';
//                    }else{
//                        $item_user->Passed = '';
//                    }
//                }

            }

            foreach ($tmpresult as $res) {
                unset($res->grades);
            }
            
            //$result[] = $item_user;
            $result = $tmpresult;
        }
        
        //Sort columns

       $studentgrades=array();
       foreach($result as $item_user){
           $result_sorted_tmp = array();
           $result_sorted_tmp['SM_Objid'] = $item_user->SM_Objid;
           $result_sorted_tmp['ST_Objid'] = $item_user->ST_Objid;
           $result_sorted_tmp['Student12'] = $item_user->Student12;
           $result_sorted_tmp['Passed'] = $item_user->Passed;
           $result_sorted_tmp['Lecturer_ID'] = $item_user->Lecturer_ID;
           if (!isset($studentgrades[$item_user->Student12])){
               $studentgrades[$item_user->Student12]['grades']=array();
               $studentgrades[$item_user->Student12]['data']=array();
           }
           $studentgrades[$item_user->Student12]['grades'][]=$item_user->Grade;
           $studentgrades[$item_user->Student12]['data']=$result_sorted_tmp;
        }


        $result_sorted = array();
        foreach($studentgrades as $item_user){
            $result_sorted_tmp = array();
            $result_sorted_tmp = $item_user['data'];


            $result_sorted_tmp = array();
            $result_sorted_tmp['SM_Objid'] = $item_user['data']['SM_Objid'];
            $result_sorted_tmp['ST_Objid'] = $item_user['data']['ST_Objid'];
            $result_sorted_tmp['Student12'] = $item_user['data']['Student12'];
            $result_sorted_tmp['Grade'] = max($item_user['grades']);
            $result_sorted_tmp['Passed'] = $item_user['data']['Passed'];
            $result_sorted_tmp['Lecturer_ID'] = $item_user['data']['Lecturer_ID'];
            $result_sorted[] = $result_sorted_tmp;
        }

        return $result_sorted;
    }


}

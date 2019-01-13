<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('locallib.php');

class general_form extends moodleform {

    public $ifdownload = false;
    public $postdata;

	public function definition() {

		$mform = $this->_form; // Don't forget the underscore!
		//$mform->addElement('hidden', 'id', $this->_customdata['id']);
		//$mform->setType('id', PARAM_RAW);

        //Cron time
        $mform->addElement('header', 'general', get_string('cron_grades', 'local_exportmodgrades'));
        $mform->setExpanded('general', true);

        $attributes = array();
        $select = $mform->addElement('select', 'crontime', get_string('crontime', 'local_exportmodgrades'), GRADESCRONPERIODSSELECT, $attributes);
        $select->setMultiple(false);

        $mform->addElement('submit', 'submitcrontime', get_string('savechanges'));

        //export mod grades
        $mform->addElement('header', 'export', get_string('export_mod', 'local_exportmodgrades'));
        $mform->setExpanded('export', true);

		//Year
        $attributes = array();
        $selectyears = array();
        $currentyear = date("Y");

        $selectyears[0] =  get_string('all');
        for($i = $currentyear-9; $i < $currentyear+1; $i++){
            $selectyears[$i] = $i;
        }

        $select = $mform->addElement('select', 'year', get_string('year', 'local_exportmodgrades'), $selectyears, $attributes);
        $select->setMultiple(false);

        //Semester
        $attributes = array();
        $select = $mform->addElement('select', 'semester', get_string('semester', 'local_exportmodgrades'), GRADESTYPESEMESTERVIEW, $attributes);
        $select->setMultiple(false);

        //Date
        $mform->addElement('date_selector', 'startdate', get_string('start_date', 'local_exportmodgrades'));
        $mform->addElement('date_selector', 'enddate', get_string('end_date', 'local_exportmodgrades'));

        //Set default date
        $defaulttime = time() - 30*24*60*60;
        $mform->setDefault('startdate',  $defaulttime);

        $mform->addElement('submit', 'exportfile', get_string('export_file', 'local_exportmodgrades'));

	}

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        //Export excel
        if(isset($data['exportfile'])){
            if($data['startdate'] > $data['enddate']){
                $errors['enddate'] = get_string('wrong_dates', 'local_exportmodgrades');;
            }
        }

        return $errors;
    }

    function set_default_data() {
        global $USER, $CFG, $DB;

        $defaultdata = array();

        //crontime
        $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'crontime'));
        if(!empty($row)){
            $defaultdata['crontime'] = $row->value;
        }

        //$defaultdata['year'] = date("Y");
        $defaultdata['year'] = 0;

        parent::set_data($defaultdata);
    }

    function get_data() {
        global $DB, $USER, $CFG;

        $data = parent::get_data();
        $this->postdata = $data;

        //Save crontime
        if(isset($data->submitcrontime)){
            $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodgrades', 'name' => 'crontime'));
            if(!empty($row)){
                $row->value = $data->crontime;
                $DB->update_record('config_plugins', $row);
            }else{
                $obj = new \stdClass();
                $obj->plugin = 'local_exportmodgrades';
                $obj->name = 'crontime';
                $obj->value = $data->crontime;
                $DB->insert_record('config_plugins', $obj);
            }
        }

        //Export excel
        if(isset($data->exportfile)){
            $this->ifdownload = true;
        }

        //echo '<pre>';print_r($data);exit;
    }

    public function is_download() {
	    return $this->ifdownload;
    }

    public function download() {
        if($this->ifdownload){
            local_exportmodgrades_download_file($this->postdata);
        }
    }
}

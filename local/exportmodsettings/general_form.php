<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('locallib.php');

class general_form extends moodleform {

    public $ifdownload = false;

	public function definition() {

		$mform = $this->_form; // Don't forget the underscore!
		//$mform->addElement('hidden', 'id', $this->_customdata['id']);
		//$mform->setType('id', PARAM_RAW);

        //Cron time
        $mform->addElement('header', 'general', get_string('cron_settings', 'local_exportmodsettings'));
        $mform->setExpanded('general', true);

        $attributes = array();
        $select = $mform->addElement('select', 'crontime', get_string('crontime', 'local_exportmodsettings'), CRONPERIODS, $attributes);
        $select->setMultiple(false);

        $mform->addElement('submit', 'submitcrontime', get_string('savechanges'));

        //export mod settings
        $mform->addElement('header', 'export', get_string('export_mod_settings', 'local_exportmodsettings'));
        $mform->setExpanded('export', true);

		//Year
        $attributes = array();
        $mform->addElement('text', 'year', get_string('year', 'local_exportmodsettings'), $attributes);
        //$mform->addRule('year', null, 'required', null, 'client');
        $mform->setType('year', PARAM_RAW);

        //Semester
        $attributes = array();
        $mform->addElement('text', 'semester', get_string('semester', 'local_exportmodsettings'), $attributes);
        $mform->setType('semester', PARAM_RAW);

        //Date
        $mform->addElement('date_selector', 'startdate', get_string('from'));
        $mform->addElement('date_selector', 'enddate', get_string('to'));

        $mform->addElement('submit', 'exportfile', get_string('export_file', 'local_exportmodsettings'));

	}

	//TODO
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        //Export excel
        if(isset($data['exportfile'])){
            if(empty($data['year'])){
                $errors['year'] = 'error';
            }

            if(empty($data['semester'])){
                $errors['semester'] = 'error';
            }
        }

        return $errors;
    }

    function set_default_data() {
        global $USER, $CFG, $DB;

        $defaultdata = array();

        //crontime
        $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodsettings', 'name' => 'crontime'));
        if(!empty($row)){
            $defaultdata['crontime'] = $row->value;
        }

        $defaultdata['year'] = DEFAULTYEAR;
        $defaultdata['semester'] = DEFAULTSEMESTER;

        parent::set_data($defaultdata);
    }

    function get_data() {
        global $DB, $USER, $CFG;

        $data = parent::get_data();

        //Save crontime
        if(isset($data->submitcrontime)){
            $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodsettings', 'name' => 'crontime'));
            if(!empty($row)){
                $row->value = $data->crontime;
                $DB->update_record('config_plugins', $row);
            }else{
                $obj = new \stdClass();
                $obj->plugin = 'local_exportmodsettings';
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
            local_exportmodsettings_create_file();
        }
    }
}

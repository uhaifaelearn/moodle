<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once('locallib.php');

class general_form extends moodleform {

    public $ifdownload = false;
    public $ifcreatefile = false;
    public $postdata;

	public function definition() {

		$mform = $this->_form; // Don't forget the underscore!
		//$mform->addElement('hidden', 'id', $this->_customdata['id']);
		//$mform->setType('id', PARAM_RAW);

        //Cron time
        $mform->addElement('header', 'general', get_string('cron_settings', 'local_exportmodsettings'));
        $mform->setExpanded('general', true);

        $attributes = array();
        $select = $mform->addElement('select', 'crontime', get_string('crontime', 'local_exportmodsettings'), SETTINGSCRONPERIODSSELECT, $attributes);
        $select->setMultiple(false);

        //Checkbox ifquiz
        $mform->addElement('advcheckbox', 'ifquizcron', get_string('quiz', 'local_exportmodsettings'), 'Enable/Disable', array('group' => 1), array(0, 1));

        $mform->addElement('submit', 'submitcrontime', get_string('savechanges'));

        //export mod settings
        $mform->addElement('header', 'export', get_string('export_mod', 'local_exportmodsettings'));
        $mform->setExpanded('export', true);

		//Year
        $attributes = array();
        $selectyears = array();
        $currentyear = date("Y");

        $selectyears[0] =  get_string('all');
        for($i = $currentyear-9; $i < $currentyear+1; $i++){
            $selectyears[$i] = $i;
        }

        $select = $mform->addElement('select', 'year', get_string('year', 'local_exportmodsettings'), $selectyears, $attributes);
        $select->setMultiple(false);

        //Semester
        $attributes = array();
        $select = $mform->addElement('select', 'semester', get_string('semester', 'local_exportmodsettings'), SETTINGSTYPESEMESTERVIEW, $attributes);
        $select->setMultiple(false);

        //Date
        $mform->addElement('date_selector', 'startdate', get_string('start_date', 'local_exportmodsettings'));
        $mform->addElement('date_selector', 'enddate', get_string('end_date', 'local_exportmodsettings'));

        //Set default date
        $defaulttime = time() - 30*24*60*60;
        $mform->setDefault('startdate',  $defaulttime);

        // Courseid.
        $attributes = array();
        $mform->addElement('text', 'courseid', get_string('courseid', 'local_exportmodsettings'), $attributes);

        //Checkbox ifcreatefile
        $mform->addElement('advcheckbox', 'ifcreatefile', get_string('createfile', 'local_exportmodsettings'), 'Enable/Disable', array('group' => 1), array(0, 1));

        //Checkbox ifquiz
        $mform->addElement('advcheckbox', 'ifquiz', get_string('quiz', 'local_exportmodsettings'), 'Enable/Disable', array('group' => 1), array(0, 1));

        $mform->addElement('submit', 'exportfile', get_string('export_file', 'local_exportmodsettings'));
	}

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        //Export excel
        if(isset($data['exportfile'])){
            if($data['startdate'] > $data['enddate']){
                $errors['enddate'] = get_string('wrong_dates', 'local_exportmodsettings');;
            }
        }

        //Check input courseid.
        if(!empty($data['courseid'])){
            $arr = explode(',', $data['courseid']);
            foreach($arr as $item){
                $courseid = trim($item);
                if((!is_numeric($courseid) || $courseid < 0)){
                    $errors['courseid'] = get_string('wrong_courseid', 'local_exportmodsettings');
                }
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

        //ifquizcron
        $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodsettings', 'name' => 'ifquizcron'));
        if(!empty($row)){
            $defaultdata['ifquizcron'] = $row->value;
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

        //Save ifquizcron
        if(isset($data->submitcrontime)){
            $row = $DB->get_record('config_plugins', array('plugin' => 'local_exportmodsettings', 'name' => 'ifquizcron'));
            if(!empty($row)){
                $row->value = $data->ifquizcron;
                $DB->update_record('config_plugins', $row);
            }else{
                $obj = new \stdClass();
                $obj->plugin = 'local_exportmodsettings';
                $obj->name = 'ifquizcron';
                $obj->value = $data->ifquizcron;
                $DB->insert_record('config_plugins', $obj);
            }
        }

        //Export excel
        if(isset($data->exportfile)){
            $this->ifdownload = true;
        }

        //If create file.
        if(isset($data->ifcreatefile) && $data->ifcreatefile == 1){
            $this->ifcreatefile = true;
        }
    }

    public function is_download() {
	    return $this->ifdownload;
    }

    public function download() {
        if($this->ifdownload){
            local_exportmodsettings_download_file($this->postdata, $this->ifcreatefile);
        }
    }
}

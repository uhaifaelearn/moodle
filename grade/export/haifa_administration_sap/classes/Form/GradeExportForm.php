<?php

namespace gradeexport_haifa_administration_sap\Form;

use stdClass;
use context_course;
use moodleform;
use grade_seq;
use moodle_url;
use gradeexport_haifa_administration_sap\Globals;

/**
 * Class GradeExportForm
 *
 * @package gradeexport_haifa_administration_sap\Form
 */
class GradeExportForm extends moodleform
{
    const FINAL_PAPAER  = 0;

    const MOED_A        = 1;

    const MOED_B        = 2;

    const MOED_SPECIAL  = 3;

    /**
     * GradeExportForm constructor.
     *
     * @param moodle_url|null $action the action attribute for the form.
     *                                If empty defaults to auto detect the current url.
     *                                If a moodle_url object then outputs params as hidden variables.
     */
    public function __construct($action = null)
    {
        $formOptions    = [
            'publishing'    => true,
            'simpleui'      => true,
        ];

        parent::__construct($action, $formOptions);
    }

    /**
     * Form definition.
     */
    protected function definition()
    {
        global $PAGE;
        
        // is not required now. 
        //$PAGE->requires->js('/grade/export/haifa_administration_sap/javascript/module.js');
                
        $courseId = Globals::getCourseId();
                
        $activity_default = optional_param('default_activity', 0, PARAM_INT);

        $mForm =& $this->_form;

        // hardcoding plugin names here is hacky
        if (isset($this->_customdata)) {
            $features = $this->_customdata;
        } else {
            $features = [];
        }

        if (empty($features['simpleui'])) {
            debugging('Grade export plugin needs updating to support one step exports.', DEBUG_DEVELOPER);
        }


        $mForm->addElement('header', 'gradeitems', get_string('gradeitemsinc', 'grades'));
        $mForm->setExpanded('gradeitems', true);

        $switch = grade_get_setting($courseId, 'aggregationposition', Globals::getCfg()->grade_aggregationposition);

        // Grab the grade_seq for this course
        $gSeq = new grade_seq($courseId, $switch);
        
        if ($gradeItems = $gSeq->items) {
            $needs_multiselect  = false;
            $canViewHidden      = has_capability('moodle/grade:viewhidden', context_course::instance($courseId));
            
            //Prepare data for select activities
            $options_mod = array();
            $options_mod_settings = array();
            $options_mod[""]=get_string('grade_required', 'gradeexport_haifa_administration_sap');
            foreach ($gradeItems as $gradeItem) {
                // Is the grade_item hidden? If so, can the user see hidden grade_items?
                if ($gradeItem->is_hidden() && !$canViewHidden) {
                    continue;
                }

                $options_mod_settings[$gradeItem->id] = $gradeItem;                               
                $options_mod[$gradeItem->id] = $gradeItem->get_name();                
            }            
            
            array_pop($options_mod);
            array_pop($options_mod_settings);
            
            $select1 = $mForm->addElement('select', 'itemids', get_string('grade_option2', 'gradeexport_haifa_administration_sap'), $options_mod);
            $select1->setMultiple(true);
            $mForm->addRule('itemids', null, 'required');
            //$select1->setSelected($activity_default);
                        
            $options_grade = array(0 => get_string('No_pass_grade', 'gradeexport_haifa_administration_sap'));
            for($i=1;$i<=100;$i++){$options_grade[$i] = $i;}
            
//            $grade_default = 0;
//            $grade_default = (int)$options_mod_settings[$activity_default]->gradepass;
                        
//            $select2 = $mForm->addElement('select', 'grade_teacher', get_string('grade_option3', 'gradeexport_haifa_administration_sap'), $options_grade);
//            $select2->setSelected($grade_default);
        }

        $mForm->addElement('header', 'options', get_string('report_final_grades_after', 'gradeexport_haifa_administration_sap'));

        $mForm->setExpanded('options', true);

        $options = $this->getOptions();

        $mForm->addElement('select', 'grade_option', get_string('grade_option', 'gradeexport_haifa_administration_sap'), $options);

        $mForm->addElement('hidden', 'export_onlyactive', 1);
        $mForm->setType('export_onlyactive', PARAM_BOOL);
        $mForm->setConstant('export_onlyactive', 1);

        $mForm->addElement('hidden', 'display', ['real' => GRADE_DISPLAY_TYPE_REAL]);
        $mForm->setType('display', PARAM_INT);
        $mForm->setConstant('display', ['real' => GRADE_DISPLAY_TYPE_REAL]);

        $mForm->addElement('hidden', 'decimals', 0);
        $mForm->setType('decimals', PARAM_INT);
        $mForm->setConstant('decimals', 0);

        $mForm->addElement('hidden', 'id', $courseId);
        $mForm->setType('id', PARAM_INT);
        $submitString = get_string('syncronization', 'gradeexport_haifa_administration_sap');
        $this->add_action_buttons(false, $submitString);
    }

    /**
     * Overrides the mform get_data method.
     *
     * Created to force a value since the validation method does not work with multiple checkbox.
     *
     * @return stdClass form data object.
     */
    public function get_data()
    {
        //global $CFG;

        $data = parent::get_data();

        $data->worksheetTitles = $this->setWorksheetTitles($data->grade_option);

        return $data;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    private function getOptions($type = 'option')
    {
        $type = ($type ? '_' : $type) . $type;

        return [
            self::FINAL_PAPAER  => get_string('final_paper'.$type, 'gradeexport_haifa_administration_sap'),
            self::MOED_A        => get_string('moed_a'.$type, 'gradeexport_haifa_administration_sap'),
            self::MOED_B        => get_string('moed_b'.$type, 'gradeexport_haifa_administration_sap'),
            self::MOED_SPECIAL  => get_string('moed_special'.$type, 'gradeexport_haifa_administration_sap'),
        ];
    }

    /**
     * @param int       $optionSelected
     * @param string    $type
     *
     * @return string
     */
    private function getOptionSelected($optionSelected, $type)
    {
        return $this->getOptions($type)[$optionSelected];
    }

    /**
     * @param int $optionSelected
     *
     * @return array
     */
    private function setWorksheetTitles($optionSelected)
    {
        $grade_title = $this->getOptionSelected($optionSelected, '');

        return [
           'col_1_title'        => get_string('col_1_title', 'gradeexport_haifa_administration_sap'),
           'col_2_title'        => get_string('col_2_title', 'gradeexport_haifa_administration_sap'),
           'col_3_title'        => get_string('col_3_title', 'gradeexport_haifa_administration_sap'),
           'col_4_title'        => get_string('col_4_title', 'gradeexport_haifa_administration_sap'),
           'col_5_title'        => $this->getOptionSelected($optionSelected, 'grade_type'),
           'col_6_title'        => get_string('col_6_title', 'gradeexport_haifa_administration_sap'),
           'col_7_title'        => get_string('col_7_title', 'gradeexport_haifa_administration_sap'),
           'col_8_title'        => get_string('col_8_title', 'gradeexport_haifa_administration_sap', $this->getOptionSelected($optionSelected, 'grade_title')),
           'worksheet_name'     => get_string('worksheet_name', 'gradeexport_haifa_administration_sap', $grade_title),
           'properties_title'   => $grade_title,
       ];
    }
}

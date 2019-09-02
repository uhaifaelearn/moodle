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
 * This page contains navigation hooks for learning plans.
 *
 * @package     local_extendedfields
 * @copyright   2019 Devlion <info@devlion.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Inject the competencies elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function local_extendedfields_coursemodule_standard_elements($formwrapper, $mform) {
    global $CFG, $COURSE, $DB;

    if($formwrapper->get_current()->modulename == 'quiz'){

        $options = array(
                '' => get_string('select'),
                1 => get_string('yes'),
                0 => get_string('no')
        );

        // Default value.
        $default = '';
        $obj = $DB->get_record('local_extendedfields', array('instanceid' => $formwrapper->get_current()->id));
        if(!empty($obj)){
            $default = $obj->status;
        }

        $mform->insertElementBefore(
                $mform->createElement('select', 'quiz_last_semester', get_string('end_of_semester_test', 'local_extendedfields'), $options),
                'timing');

        $mform->addRule('quiz_last_semester', get_string('wrong_quiz_select', 'local_extendedfields'), 'required', null, 'client');
        $mform->setDefault('quiz_last_semester', $default);

        return;
    }

}

/**
 * Hook the add/edit of the course module.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 */
function local_extendedfields_coursemodule_edit_post_actions($data, $course) {
    global $DB,$PAGE;

    if (!empty($PAGE->pagetype)||$PAGE->pagetype!='mod-quiz-mod'){
        return $data;
    }
    $obj = $DB->get_record('local_extendedfields', array('instanceid' => $data->id));
    if(!empty($obj)){
        $obj->status = $data->quiz_last_semester;
        $obj->timemodified = time();
        $DB->update_record('local_extendedfields', $obj);
    }else{
        $obj = new \StdClass();
        $obj->instanceid = $data->id;
        $obj->status = $data->quiz_last_semester;
        $obj->timemodified = time();
        $DB->insert_record('local_extendedfields', $obj);
    }

    return $data;
}

/**
 * Deletes an quiz instance
 *
 * @param $id
 */
function local_extendedfields_delete_instance($id){
    global $CFG, $DB;

    $DB->delete_records('local_extendedfields', array('instanceid' => $id));

    return true;
}
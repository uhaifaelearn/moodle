<?php

/**
 * Plugin version info
 *
 * @package     tool
 * @subpackage  updatewizard
 */

use gradeexport_haifa_administration\Globals;
use gradeexport_haifa_administration\GradeExport;
use gradeexport_haifa_administration\Form\GradeExportForm;

defined('MOODLE_INTERNAL') || die();

//@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
//@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
//$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
//$CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!

defined('HAIFA_ADMINISTRATION') || require_once __DIR__.'/classes/Autoloader/autoload.php';

$cfg = Globals::getCfg();

//require_once $CFG->dirroot.'/grade/export/lib.php';
require_once Globals::getFile('grade/export/lib.php');


function setupHaifaAdministrationPage()
{
    //global $CFG, $DB, $COURSE, $PAGE, $OUTPUT;
    $cfg = Globals::getCfg();
    $output = Globals::getOutput();

//    // course id
    $id             = required_param('id', PARAM_INT);
//
//    //$PAGE->set_url('/grade/export/haifa_administration/index.php', ['id' => $id]);
//    Globals::getPage()->set_url('/grade/export/haifa_administration/index.php', ['id' => $id]);
//
//    //if (!$course = $DB->get_record('course', ['id' => $id])) {
//    if (!$course = Globals::getRecord('course', ['id' => $id])) {
//        print_error('nocourseid');
//    }
//
//    require_login($course);

    $course = setupHaifaAdministrationExportPage('index');

    $courseId       = Globals::getCourseId();

//    require_capability('moodle/grade:export', $context);
//    require_capability('gradeexport/haifa_administration:view', $context);

    print_grade_page_head($courseId, 'export', 'haifa_administration', get_string('export', 'gradeexport_haifa_administration'));
    export_verify_grades($courseId);

    if (!empty($cfg->gradepublishing)) {
        $cfg->gradepublishing   = has_capability('gradeexport/haifa_administration:publish', $context);
    }

    $actionUrl      = new moodle_url('/grade/export/haifa_administration/export.php');

    $mForm          = new GradeExportForm($actionUrl);

    // Groups are being used
    $groupMode      = groups_get_course_groupmode($course);
    $currentGroup   = groups_get_course_group($course, true);

    if (SEPARATEGROUPS == $groupMode && !$currentGroup && !has_capability('moodle/site:accessallgroups', $context)) {
        echo $output->heading(get_string("notingroup"));
        echo $output->footer();

        die;
    }

    groups_print_course_menu($course, 'index.php?id='.$id);

    echo '<div class="clearer"></div>';

    $mForm->display();

    echo $output->footer();
}

function setupExportPage()
{
    //global $CFG, $DB, $COURSE, $USER, $PAGE, $OUTPUT;
    $cfg = Globals::getCfg();

//    // course id
//    $id         = required_param('id', PARAM_INT);
//
//    //$PAGE->set_url('/grade/export/haifa_administration/export.php', ['id' => $id]);
//    Globals::getPage()->set_url('/grade/export/haifa_administration/export.php', ['id' => $id]);
//
//    //if (!$course = $DB->get_record('course', ['id' => $id])) {
//    if (!$course = Globals::getRecord('course', ['id' => $id])) {
//        print_error('nocourseid');
//    }
//
//    require_login($course);
//
//    $context    = context_course::instance($id);

    $course = setupHaifaAdministrationExportPage('export');

    $groupId    = groups_get_course_group($course, true);

//    require_capability('moodle/grade:export', $context);
//    require_capability('gradeexport/haifa_administration:view', $context);

    // We need to call this method here before any print otherwise the menu won't display.
    // If you use this method without this check, will break the direct grade exporting (without publishing).
    $key        = optional_param('key', '', PARAM_RAW);

    //if (!empty($CFG->gradepublishing) && !empty($key)) {
    if (!empty($cfg->gradepublishing) && !empty($key)) {
        ////print_grade_page_head($COURSE->id, 'export', 'haifa_administration', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_haifa_administration'));
        //print_grade_page_head($COURSE->id, 'export', 'haifa_administration', get_string('export', 'gradeexport_haifa_administration'));
        print_grade_page_head(Globals::getCourseId(), 'export', 'haifa_administration', get_string('export', 'gradeexport_haifa_administration'));
    }

    //if (SEPARATEGROUPS == groups_get_course_groupmode($COURSE) && !has_capability('moodle/site:accessallgroups', $context)) {
    if (SEPARATEGROUPS == groups_get_course_groupmode(Globals::getCourse()) && !has_capability('moodle/site:accessallgroups', $context)) {
        //if (!groups_is_member($groupid, $USER->id)) {
        if (!groups_is_member($groupId, Globals::getUserId())) {
            print_error('cannotaccessgroup', 'grades');
        }
    }

    $mForm      = new GradeExportForm();

    $formData   = $mForm->get_data();

    $export     = new GradeExport($course, $groupId, $formData);

    // If the gradepublishing is enabled and user key is selected print the grade publishing link.
    //if (!empty($CFG->gradepublishing) && !empty($key)) {
    if (!empty($cfg->gradepublishing) && !empty($key)) {
        groups_print_course_menu($course, 'index.php?id='.$id);

        echo $export->get_grade_publishing_url();
        //echo $OUTPUT->footer();
        echo Globals::getOutput()->footer();
    } else {
        $export->print_grades();
    }
}

function setupHaifaAdministrationExportPage($pageUrl)
{
    // course id
    $id         = required_param('id', PARAM_INT);

    Globals::getPage()->set_url('/grade/export/haifa_administration/'.$pageUrl.'.php', ['id' => $id]);

    if (!$course = Globals::getRecord('course', ['id' => $id])) {
        print_error('nocourseid');
    }

    require_login($course);

    $context        = context_course::instance($id);

    require_capability('moodle/grade:export', $context);
    require_capability('gradeexport/haifa_administration:view', $context);

    return $course;
}

function setupDumpPage()
{
    //global $CFG, $DB, $COURSE;
    $cfg = Globals::getCfg();

    $id             = required_param('id', PARAM_INT);
    $groupId        = optional_param('groupid', 0, PARAM_INT);
    $itemIds        = required_param('itemids', PARAM_RAW);
    $exportFeedback = optional_param('export_feedback', 0, PARAM_BOOL);
    $displayType    = optional_param('displaytype', $cfg->grade_export_displaytype, PARAM_RAW);
    $decimalPoints  = optional_param('decimalpoints', $cfg->grade_export_decimalpoints, PARAM_INT);
    $onlyActive     = optional_param('export_onlyactive', 0, PARAM_BOOL);

    //if (!$course = $DB->get_record('course', ['id' => $id])) {
    if (!$course = Globals::getRecord('course', ['id' => $id])) {
        print_error('nocourseid');
    }

    // we want different keys for each course
    require_user_key_login('grade/export', $id);

    if (empty($cfd->gradepublishing)) {
        print_error('gradepubdisable');
    }

    $context        = context_course::instance($id);

    require_capability('moodle/grade:export', $context);
    require_capability('gradeexport/haifa_administration:view', $context);
    require_capability('gradeexport/haifa_administration:publish', $context);

    //if (!groups_group_visible($groupId, $COURSE)) {
    if (!groups_group_visible($groupId, Globals::getCourse())) {
        print_error('cannotaccessgroup', 'grades');
    }

    // Get all url parameters and create an object to simulate a form submission.
    $formData       = grade_export::export_bulk_export_data($id, $itemIds, $exportFeedback, $onlyActive, $displayType, $decimalPoints);

    $export         = new GradeExport($course, $groupId, $formData);
    $export->print_grades();
}

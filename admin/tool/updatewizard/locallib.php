<?php

/**
 * Plugin version info
 *
 * @package     tool
 * @subpackage  updatewizard
 */

use tool_updatewizard\Globals;
use tool_updatewizard\Manager\TaskManager;

defined('MOODLE_INTERNAL') || die();

//@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
//@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
//$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
//$CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!

defined('UPDATE_WIZARD') || require_once __DIR__.'/classes/Autoloader/autoload.php';

$cfg = Globals::getCfg();

require_once Globals::getLibFile('adminlib.php');

/**
 *
 */
function setupUpdateWizardPage()
{
    $page = Globals::getPage();

    $returnUrl = '/admin/tool/updatewizard';
    $strHeading = get_string('daily_update', 'tool_updatewizard');

    $page->set_url($returnUrl);
    $page->set_context(context_system::instance());
    $page->set_pagelayout('admin');

    $page->set_title($strHeading);
    $page->set_heading($strHeading);

    admin_externalpage_setup('toolupdatewizard');

    // Some previous checks
    $site = get_site();

    require_login();
    require_capability('moodle/site:config', context_system::instance());

    $action = optional_param('action', '', PARAM_ALPHAEXT);
    $doAction = getPageAction($action);

    // Body of the script, based on action, we delegate the work
    renderWizardTaskPage($doAction);
}

/**
 * @param $action
 *
 * @return string
 */
function getPageAction($action)
{
    $doAction   = '';

    switch ($action) {
        case 'run':
            $doAction = $action;

            return runTask();
            break;

        case '':
            return '';
            break;

//        case 'edit':
//            $doAction = $action;
//            break;
//
//        case 'save':
//            $doAction = $action;
//            break;

        default:
            //$action = optional_param('action', '', PARAM_ALPHAEXT) ?: 'display';
            //$doAction = 'do: '.$action.'. now: '.time();
    }

    return $doAction;
}

/**
 * @return string
 */
function runTask()
{
    $res = TaskManager::runTaskAsAdHoc();

    mtrace($res);

    return $res;
}

/**
 * @param string $extraData
 */
function renderWizardTaskPage($extraData = '')
{
    $page = Globals::getPage();
    $output = Globals::getOutput();

    $taskName = 'tool_updatewizard\task\daily_update_wizard';

    $renderer = $page->get_renderer('tool_task');

    echo $output->header();
    echo $output->heading(get_string('updatewizard', 'tool_updatewizard'));

    echo $extraData;

    echo $output->footer();
}


//function renderWizardTaskPage($extraData = '')
//{
//$renderer = $PAGE->get_renderer('tool_updatewizard');
//$renderer = $PAGE->get_renderer('tool_task');
//
////    $action = optional_param('action', '', PARAM_ALPHAEXT) ?: 'edit';
////    $taskName = optional_param('task', '', PARAM_RAW) ?: 'tool_updatewizard\task\daily_update_wizard';
////    $task = null;
////    $mForm = null;
////
////    if ($taskName) {
//    //$task = \core\task\Manager::get_scheduled_task($taskName);
//
//    //if (!$task) {
//    //    print_error('invaliddata');
//    //}
////    }
////
////    if ($action == 'edit') {
////        $PAGE->navbar->add(get_string('edittaskschedule', 'tool_task', $task->get_name()));
////    }
////
////    if ($task) {
////        $mForm = new tool_task_edit_scheduled_task_form(null, $task);
////    }
////
////    if ($mForm && ($mForm->is_cancelled() || !empty($CFG->preventscheduledtaskchanges))) {
////        redirect(new moodle_url($returnUrl));
////    } elseif ($action == 'edit' && empty($CFG->preventscheduledtaskchanges)) {
////        if ($data = $mForm->get_data()) {
////            if ($data->resettodefaults) {
////                $defaultTask = \core\task\Manager::get_default_scheduled_task($taskName);
////                $task->set_minute($defaultTask->get_minute());
////                $task->set_hour($defaultTask->get_hour());
////                $task->set_month($defaultTask->get_month());
////                $task->set_day_of_week($defaultTask->get_day_of_week());
////                $task->set_day($defaultTask->get_day());
////                $task->set_disabled($defaultTask->get_disabled());
////                $task->set_customised(false);
////            } else {
////                $task->set_minute($data->minute);
////                $task->set_hour($data->hour);
////                $task->set_month($data->month);
////                $task->set_day_of_week($data->dayofweek);
////                $task->set_day($data->day);
////                $task->set_disabled($data->disabled);
////                $task->set_customised(true);
////            }
////
////            try {
////                \core\task\Manager::configure_scheduled_task($task);
////
////                $url = $PAGE->url;
////                $url->params(array('success'=>get_string('changessaved')));
////
////                redirect($url);
////            } catch (Exception $e) {
////                $url = $PAGE->url;
////                $url->params(array('error'=>$e->getMessage()));
////
////                redirect($url);
////            }
////        } else {
////            echo $OUTPUT->header();
////            echo $OUTPUT->heading(get_string('edittaskschedule', 'tool_task', $task->get_name()));
////
////            $error = optional_param('error', '', PARAM_NOTAGS);
////
////            if ($error) {
////                echo $OUTPUT->notification($error, 'notifyerror');
////            }
////
////            $success = optional_param('success', '', PARAM_NOTAGS);
////
////            if ($success) {
////                echo $OUTPUT->notification($success, 'notifysuccess');
////            }
////
////            $mForm->display();
////
////            echo $OUTPUT->footer();
////        }
////    } else {
////        echo $OUTPUT->header();
////
////        $error = optional_param('error', '', PARAM_NOTAGS);
////
////        if ($error) {
////            echo $OUTPUT->notification($error, 'notifyerror');
////        }
////
////        $success = optional_param('success', '', PARAM_NOTAGS);
////
////        if ($success) {
////            echo $OUTPUT->notification($success, 'notifysuccess');
////        }
////
////        $tasks = core\task\Manager::get_all_scheduled_tasks();
////
////        echo $renderer->scheduled_tasks_table($tasks);
////        echo $OUTPUT->footer();
////    }
//}

//function runTask()
//{
//    // create the instance of Scheduled Task
//    //$updateWizardScheduledTask = new \tool_updatewizard\task\daily_update_wizard();
//    //scheduled_task_from_record
//    //load_scheduled_tasks_for_component
////    $updateWizardScheduledTask = getUpdateWizardScheduledTask();
////
////    if (false === $updateWizardScheduledTask) {
////        return '';
////    }
////
////    /** @var $updateWizardScheduledTask \tool_updatewizard\task\daily_update_wizard */
////    $updateWizardScheduledTask->setWebExecEnv();
////    // queue it
////    //\core\task\Manager::queue_adhoc_task($updateWizardAdHocTask);
////    //\core\task\Manager::clear_static_caches();
////    //$updateWizardScheduledTask->set
////    //return $updateWizardScheduledTask;
////    //cron_run();
////    //return $updateWizardScheduledTask->execute();
////    $res = $updateWizardScheduledTask->execute();
////    $updateWizardScheduledTask->get_lock()->release();
//
//    //$res = TaskManager::addTaskRunToNextCron();
//}

//function runWizardTaskNow()
//{
//    global $CFG;
//
//    echo 'Start';
//    echo '<br>';
//
//    //$test = \tool_updatewizard\update_wizard::getInstance($CFG);
//
//    echo $test->getPid();
//    echo '<br>';
//    echo $test->getStartTime();
//    echo '<br>';
//    echo $test->run() ? 'OK' : 'Failed';
//
//    echo '<br>';
//    echo 'Done';
//}
//
//function displayWizardPage($runTaskNow = false)
//{
//
//}



///**
// * @return false|\tool_updatewizard\task\daily_update_wizard
// */
//function getUpdateWizardScheduledTask()
//{
//    $cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
//
//    //if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
//    //    throw new \moodle_exception('locktimeout');
//    //}
//    // create the instance of Scheduled Task
//    //$updateWizardScheduledTask = \core\task\Manager::load_scheduled_tasks_for_component('tool_updatewizard');
//    //$updateWizardScheduledTask = \core\task\Manager::get_scheduled_task('\tool_updatewizard\task\daily_update_wizard');
//    $updateWizardScheduledTask = \core\task\Manager::get_scheduled_task('tool_updatewizard\task\daily_update_wizard');
//
//    if ($lock = $cronlockfactory->get_lock('\tool_updatewizard\task\daily_update_wizard', 10)) {
//        $updateWizardScheduledTask->set_lock($lock);
//        //$updateWizardScheduledTask->get_cron_lock()->release();
//        //$cronlockfactory->get_lock()->release();
//    }
//    //if (1 !== count($updateWizardScheduledTask)) {
//    //    return null;
//    //}
//
//    //return $updateWizardScheduledTask[0];
//    return $updateWizardScheduledTask;
//}

//echo $OUTPUT->header();
//echo $OUTPUT->heading(get_string('updatewizard', 'tool_updatewizard'));
//
//echo $doAction;
//
//echo $OUTPUT->footer();

//// Print the header
//echo $OUTPUT->header();
//echo $OUTPUT->heading(get_string('updatewizard', 'tool_updatewizard'));
//
//echo 'Start';
//echo '<br>';
//
//$test = \tool_updatewizard\update_wizard::getInstance($CFG);
//
//echo $test->getPid();
//echo '<br>';
//echo $test->getStartTime();
//echo '<br>';
//echo $test->run() ? 'OK' : 'Failed';
//
////$test = new \tool_updatewizard\task\daily_update_wizard();
////$test->execute();
//
////http://m.elearn.dev/admin/tool/task/scheduledtasks.php?action=edit&task=tool_updatewizard%5Ctask%5Cdaily_update_wizard
//echo '<br>';
//echo 'Done';
//
//echo $OUTPUT->footer();
//die;
//
//
////$renderer = $PAGE->get_renderer('tool_updatewizard');
//$renderer = $PAGE->get_renderer('tool_task');
//
//$action = optional_param('action', '', PARAM_ALPHAEXT) ?: 'edit';
//$taskName = optional_param('task', '', PARAM_RAW) ?: 'tool_updatewizard\task\daily_update_wizard';
//$task = null;
//$mForm = null;
//
//if ($taskName) {
//    $task = \core\task\Manager::get_scheduled_task($taskName);
//
//    if (!$task) {
//        print_error('invaliddata');
//    }
//}
//
//if ($action == 'edit') {
//    $PAGE->navbar->add(get_string('edittaskschedule', 'tool_task', $task->get_name()));
//}
//
//if ($task) {
//    $mForm = new tool_task_edit_scheduled_task_form(null, $task);
//}
//
//if ($mForm && ($mForm->is_cancelled() || !empty($CFG->preventscheduledtaskchanges))) {
//    redirect(new moodle_url($returnUrl));
//} elseif ($action == 'edit' && empty($CFG->preventscheduledtaskchanges)) {
//    if ($data = $mForm->get_data()) {
//        if ($data->resettodefaults) {
//            $defaultTask = \core\task\Manager::get_default_scheduled_task($taskName);
//            $task->set_minute($defaultTask->get_minute());
//            $task->set_hour($defaultTask->get_hour());
//            $task->set_month($defaultTask->get_month());
//            $task->set_day_of_week($defaultTask->get_day_of_week());
//            $task->set_day($defaultTask->get_day());
//            $task->set_disabled($defaultTask->get_disabled());
//            $task->set_customised(false);
//        } else {
//            $task->set_minute($data->minute);
//            $task->set_hour($data->hour);
//            $task->set_month($data->month);
//            $task->set_day_of_week($data->dayofweek);
//            $task->set_day($data->day);
//            $task->set_disabled($data->disabled);
//            $task->set_customised(true);
//        }
//
//        try {
//            \core\task\Manager::configure_scheduled_task($task);
//
//            $url = $PAGE->url;
//            $url->params(array('success'=>get_string('changessaved')));
//
//            redirect($url);
//        } catch (Exception $e) {
//            $url = $PAGE->url;
//            $url->params(array('error'=>$e->getMessage()));
//
//            redirect($url);
//        }
//    } else {
//        echo $OUTPUT->header();
//        echo $OUTPUT->heading(get_string('edittaskschedule', 'tool_task', $task->get_name()));
//
//        $error = optional_param('error', '', PARAM_NOTAGS);
//
//        if ($error) {
//            echo $OUTPUT->notification($error, 'notifyerror');
//        }
//
//        $success = optional_param('success', '', PARAM_NOTAGS);
//
//        if ($success) {
//            echo $OUTPUT->notification($success, 'notifysuccess');
//        }
//
//        $mForm->display();
//
//        echo $OUTPUT->footer();
//    }
//} else {
//    echo $OUTPUT->header();
//
//    $error = optional_param('error', '', PARAM_NOTAGS);
//
//    if ($error) {
//        echo $OUTPUT->notification($error, 'notifyerror');
//    }
//
//    $success = optional_param('success', '', PARAM_NOTAGS);
//
//    if ($success) {
//        echo $OUTPUT->notification($success, 'notifysuccess');
//    }
//
//    $tasks = core\task\Manager::get_all_scheduled_tasks();
//
//    echo $renderer->scheduled_tasks_table($tasks);
//    echo $OUTPUT->footer();
//}

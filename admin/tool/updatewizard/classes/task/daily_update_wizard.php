<?php

/**
 * A scheduled task for updating courses, users and enrolments.
 *
 * @package    tool_updatewizard
 */
namespace tool_updatewizard\task;

use core\task\scheduled_task;
use tool_updatewizard\Manager\TaskManager;
use tool_updatewizard\Manager\TraceManager;
use tool_updatewizard\Manager\ProcessManager;
use tool_updatewizard\Globals;

defined('UPDATE_WIZARD') || require_once __DIR__.'/../Autoloader/autoload.php';

/**
 * A scheduled task for updating courses, users and enrolments.
 *
 * @package    tool_updatewizard
 */
class daily_update_wizard extends scheduled_task
{
    /**
     * @var bool
     */
    protected $isAdHocRun       = false;

    /**
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name()
    {
        // Shown in admin screens
        return get_string('daily_update', 'tool_updatewizard');
    }

    /**
     * Run daily update wizard
     *
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute()
    {
		//mail('eugen.post@gmail.com', 'UniHaifa', 'Start');
        mtrace('-------------------------');

        $res = TaskManager::execute($this);
        $cfg            = Globals::getCfg();
        $baseDir        = $cfg->dataroot;
        $inputDir       = $baseDir.'/update_wizard/logs/';
        if(!file_exists($inputDir))
        {
            if (!mkdir($inputDir, 0755, true))
            {
                mtrace('Log dir error.');
            }
        }
        $strFileName = date('Ymd_Hi').'.log';
        file_put_contents($inputDir.$strFileName, $res);


        mtrace($res);
        mtrace('-------------------------');
		//mail('eugen.post@gmail.com', 'UniHaifa', 'End');
        return $res;
    }

    /**
     * @param bool $isAdHocRun
     */
    public function setIsAdHocRun($isAdHocRun)
    {
        $this->isAdHocRun = $isAdHocRun;
    }

    /**
     * @return bool
     */
    public function getIsAdHocRun()
    {
        return $this->isAdHocRun;
    }

    /**
     * @param ProcessManager $processManager
     */
    public function setProcessManager(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
    }

    /**
     * @return ProcessManager
     */
    public function getProcessManager()
    {
        return $this->processManager;
    }

    /**
     * @param TraceManager $traceManager
     */
    public function setTraceManager(TraceManager $traceManager)
    {
        $this->processManager->setTraceManager($traceManager);
    }

    /**
     * @return TraceManager
     */
    public function getTraceManager()
    {
        return $this->processManager->getTraceManager();
    }

//    protected function runUpdateWizardTask()
//    {
////        //self::$isRunning = true;
////        self::setIsRunning(true);
////        $trace = debug_backtrace(null, 1);
////        //$trace = debug_backtrace();
////        //$pattern = '/(\w*\/)*(\w+).php/';
////        $pattern = '/(\w*\/)*(\w+\/\w+)\.php/';
////        $replacement = '$2';
////
////        $fileName = $trace[0]['file'];
////        $pathFileName = explode('/', preg_replace($pattern, $replacement, $fileName));
////        return $this->runUpdateWizardTask();
////        //sleep(60);
////        //self::$isRunning = false;
////        self::setIsRunning(false);
//
//        return 'executing';
////        global $CFG;
////
////        try {
////            $updateWizard = update_wizard::getInstance($CFG);
////
////            return $updateWizard->run();
////        } catch (\moodle_exception $e) {
////            mtrace('Daily Update Wizard Skipped.');
////
////            return;
////        }
//    }
}

<?php

namespace tool_updatewizard\Manager;

use Exception;
use moodle_exception;
use core\task\manager;
use core\lock\lock_config;
use core_php_time_limit;
use html_list_progress_trace;
use text_progress_trace;
use tool_updatewizard\Globals;
use tool_updatewizard\Manager\FileHandler\FileHandlerHelper;
use tool_updatewizard\task\daily_update_wizard;
use tool_updatewizard\Cache\Cache;

/**
 * Class TaskManager
 *
 * @package tool_updatewizard\Manager
 */
class TaskManager
{
    /**
     * @param daily_update_wizard $task
     *
     * @return string
     */
    public static function execute(daily_update_wizard $task)
    {
        $traceManager = new TraceManager();

        $traceName = $traceManager->addTrace(new text_progress_trace());

        $isAdHocRun = $task->getIsAdHocRun();

        if ($isAdHocRun) {
            $traceName = $traceManager->addTrace(new html_list_progress_trace());
        }

        $fileManager = new FileManager($traceManager, FileHandlerHelper::getOrderedFilesHandlers());

        $processManager = new ProcessManager($traceManager, $fileManager);

        $task->setProcessManager($processManager);

        Cache::initCache();

        $execMessage = $processManager->execute($traceName, $isAdHocRun);

        Cache::releaseCache();

        manager::clear_static_caches();

        return $execMessage;
    }

    /**
     * @return string
     *
     * @throws moodle_exception
     */
    public static function runTaskAsAdHoc()
    {
        core_php_time_limit::raise();

        $startTime = microtime();

        // Increase memory limit
        raise_memory_limit(MEMORY_EXTRA);

        $taskClassName = 'tool_updatewizard\task\daily_update_wizard';

        $execRes = static::doAdHocRun($taskClassName);

        mtrace('Cron script completed correctly');

        gc_collect_cycles();

        mtrace(sprintf('Cron completed at %s. Memory used %s.', date('H:i:s'), display_size(memory_get_usage())));

        $diffTime = microtime_diff($startTime, microtime());

        mtrace(sprintf('Execution took %s seconds', $diffTime));
        
        return $execRes;
    }

    /**
     * @param $taskClassName
     *
     * @return string
     * 
     * @throws moodle_exception
     */
    protected static function doAdHocRun($taskClassName)
    {
        $cfg    = Globals::getCfg();
        $db     = Globals::getDb();

        $execRes = 'Failed';

        // Start output log
        $timeNow  = time();

        mtrace(sprintf('Server Time: %s%s', date('r', $timeNow), "\n\n"));

        $taskClassName = 0 !== strpos($taskClassName, '\\') ? '\\'.$taskClassName : $taskClassName;

        if ($task = static::getScheduledTask($taskClassName)) {
            $task->setIsAdHocRun(true);

            $fullName = $task->get_name().' ('.get_class($task).')';

            mtrace(sprintf('Execute scheduled task: %s', $fullName));

            gc_collect_cycles();
            mtrace(sprintf('... started %s. Current memory use %s.', date('H:i:s'), display_size(memory_get_usage())));

            $preDbQueries = null;
            $preDbQueries = $db->perf_get_queries();
            $preTime      = microtime(1);

            get_mailer('buffer');

            try {
                $execRes = $task->execute();

                if ($db->is_transaction_started()) {
                    throw new moodle_exception('Task left transaction open');
                }

                if (null !== $preDbQueries) {
                    mtrace(sprintf('... used %s DbQueries', $db->perf_get_queries() - $preDbQueries));
                    mtrace(sprintf('... used %s Seconds', microtime(1) - $preTime));
                }

                mtrace(sprintf('Scheduled task complete:  %s', $fullName));

                manager::scheduled_task_complete($task);
            } catch (Exception $e) {
                if ($db && $db->is_transaction_started()) {
                    error_log(sprintf('Database transaction aborted automatically in %s', get_class($task)));

                    $db->force_transaction_rollback();
                }

                if (null !== $preDbQueries) {
                    mtrace(sprintf('... used %s DbQueries', $db->perf_get_queries() - $preDbQueries));
                    mtrace(sprintf('... used %s Seconds', microtime(1) - $preTime));
                }

                mtrace(sprintf('Scheduled task failed:  %s, %s', $fullName, $e->getMessage()));

                if ($cfg->debugdeveloper) {
                    if (!empty($e->debuginfo)) {
                        mtrace('Debug info: ');
                        mtrace($e->debuginfo);
                    }

                    mtrace('Backtrace: ');
                    mtrace(format_backtrace($e->getTrace(), true));
                }

                manager::scheduled_task_failed($task);
            }

            get_mailer('close');
            unset($task);
        }

        return $execRes;
    }

    /**
     * @param $taskClassName
     *
     * @return daily_update_wizard
     *
     * @throws moodle_exception
     */
    protected static function getScheduledTask($taskClassName)
    {
        $cronLockFactory = lock_config::get_lock_factory('cron');

        if (!$cronLock = $cronLockFactory->get_lock('core_cron', 10)) {
            throw new moodle_exception('locktimeout');
        }

        if ($lock = $cronLockFactory->get_lock($taskClassName, 10)) {
            $task = manager::get_scheduled_task($taskClassName);

            if (!$task || !$task instanceof daily_update_wizard) {
                $lock->release();
                $cronLock->release();

                throw new moodle_exception('wrong_task_info', 'tool_updatewizard');
            }

            $task->set_lock($lock);

            if (!$task->is_blocking()) {
                $cronLock->release();
            } else {
                $task->set_cron_lock($cronLock);
            }

            return $task;
        }

        $cronLock->release();

        throw new moodle_exception('locktimeout');
    }
}

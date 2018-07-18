<?php

/**
 * CLI update wizard task execution.
 *
 * @package tool_updatewizard
 */

use core\task\manager;
use core\lock\lock_config;

define('CLI_SCRIPT', true);

require __DIR__.'/../../../../config.php';
require_once __DIR__.'/locallib.php';
//require_once "$CFG->libdir/clilib.php";
//require_once "$CFG->libdir/cronlib.php";
require_once Globals::getLibFile('clilib.php');
require_once Globals::getLibFile('cronlib.php');

list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'execute' => false],
    ['h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || (!$options['list'] && !$options['execute'])) {
    $help =
"Daily Update Wizard cron task.

Options:
--execute             Execute scheduled task manually
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/tool/updatewizard/cli/update_wizard.php --execute

";

    echo $help;

    die;
}

if ($execute = $options['execute']) {
    if (!$task = manager::get_scheduled_task($execute)) {
        mtrace("Task '$execute' not found");

        exit(1);
    }

    if (moodle_needs_upgrading()) {
        mtrace("Moodle upgrade pending, cannot execute tasks.");

        exit(1);
    }

    // Increase memory limit.
    raise_memory_limit(MEMORY_HUGE);

    // Emulate normal session - we use admin account by default.
    cron_setup_user();

    $predbqueries = $DB->perf_get_queries();
    $pretime = microtime(true);

    mtrace('Scheduled task: '.$task->get_name());
    // NOTE: it would be tricky to move this code to \core\task\Manager class,
    //       because we want to do detailed error reporting.
    $cronlockfactory = lock_config::get_lock_factory('cron');

    if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
        mtrace('Cannot obtain cron lock');

        exit(129);
    }

    if (!$lock = $cronlockfactory->get_lock('\\'.get_class($task), 10)) {
        $cronlock->release();
        mtrace('Cannot obtain task lock');

        exit(130);
    }

    $task->set_lock($lock);

    if (!$task->is_blocking()) {
        $cronlock->release();
    } else {
        $task->set_cron_lock($cronlock);
    }

    try {
        get_mailer('buffer');
        $task->execute();

        if (isset($predbqueries)) {
            mtrace('... used '.($DB->perf_get_queries() - $predbqueries).' dbqueries');
            mtrace('... used '.(microtime(1) - $pretime).' seconds');
        }

        mtrace('Task completed.');
        manager::scheduled_task_complete($task);
        get_mailer('close');

        exit(0);
    } catch (Exception $e) {
        if ($DB->is_transaction_started()) {
            $DB->force_transaction_rollback();
        }

        mtrace('... used '.($DB->perf_get_queries() - $predbqueries).' dbqueries');
        mtrace('... used '.(microtime(true) - $pretime).' seconds');
        mtrace('Task failed: '.$e->getMessage());

        if ($CFG->debugdeveloper) {
            if (!empty($e->debuginfo)) {
                mtrace('Debug info:');
                mtrace($e->debuginfo);
            }

            mtrace('Backtrace:');
            mtrace(format_backtrace($e->getTrace(), true));
        }

        manager::scheduled_task_failed($task);
        get_mailer('close');

        exit(1);
    }
}

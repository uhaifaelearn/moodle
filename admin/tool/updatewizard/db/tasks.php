<?php

/**
 * Definition of updatewizard tasks
 *
 * @package     tool_updatewizard
 * @category    task
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'tool_updatewizard\task\daily_update_wizard',
        'blocking'  => 0,
        'minute'    => '45',
        'hour'      => '0',
        'day'       => '*',
        'dayofweek' => '*',
        'month'     => '*',
    ]
];

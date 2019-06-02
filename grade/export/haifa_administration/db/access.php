<?php

/**
 * Capabilities gradeexport plugin.
 *
 * @package gradeexport_haifa_administration
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'gradeexport/haifa_administration:view'    => [
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => [
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        ]
    ],
    'gradeexport/haifa_administration:publish' => [
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => [
            'manager'           => CAP_ALLOW
        ]
    ]
];

<?php

/**
 * Capabilities gradeexport plugin.
 *
 * @package gradeexport_haifa_administration_sap
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'gradeexport/haifa_administration_sap:view'    => [
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => [
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        ]
    ],
    'gradeexport/haifa_administration_sap:publish' => [
        'riskbitmask'   => RISK_PERSONAL,
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => [
            'manager'           => CAP_ALLOW
        ]
    ]
];

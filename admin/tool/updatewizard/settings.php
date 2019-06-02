<?php

/**
 * Link to update wizard
 *
 * @package tool_updatewizard
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add(
        'root',
        new admin_externalpage(
            'toolupdatewizard',
            get_string('pluginname', 'tool_updatewizard'),
            "$CFG->wwwroot/$CFG->admin/tool/updatewizard"
        )
    );
}

<?php

namespace tool_updatewizard\Autoloader;

defined('MOODLE_INTERNAL') || die();

//if (!array_key_exists('XDEBUG_SESSION', $_COOKIE)) {
//    define('STDOUT', null);
//}
//defined('STDOUT') || define('STDOUT', null);

define('UPDATE_WIZARD', 'tool_updatewizard');

global $CFG;

require_once __DIR__.'/Psr4Autoloader.php';

/**
 * @var string $srcBaseDirectory
 *          Full path to "updatewizard/classes" which is what we want "tool_updatewizard" to map to.
 */
$srcBaseDirectory = dirname(__DIR__);

$spoutSrcBaseDirectory = $CFG->libdir.'/spout/src/Spout';

$loader = new Psr4Autoloader();

$loader->register();

$loader->addNamespace('tool_updatewizard', $srcBaseDirectory);
$loader->addNamespace('Box\Spout', $spoutSrcBaseDirectory);

$loader->addClassMap([
    'coursecat' => $CFG->libdir.'/coursecatlib.php',
]);

return null !== $loader ?: die();

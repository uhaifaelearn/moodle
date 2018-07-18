<?php

namespace gradeexport_haifa_administration\Autoloader;

defined('MOODLE_INTERNAL') || die();

//if (!array_key_exists('XDEBUG_SESSION', $_COOKIE)) {
//    define('STDOUT', null);
//}
defined('STDOUT') || define('STDOUT', null);

define('HAIFA_ADMINISTRATION', 'grade_export_haifa_administration');

global $CFG;

require_once __DIR__.'/Psr4Autoloader.php';

/**
 * @var string $srcBaseDirectory
 *          Full path to "haifa_administration/classes" which is what we want "grade_export_haifa_administration" to map to.
 */
$srcBaseDirectory = dirname(__DIR__);

$loader = new Psr4Autoloader();

$loader->register();

$loader->addNamespace('gradeexport_haifa_administration', $srcBaseDirectory);

$loader->addClassMap([
    'grade_export'          => $CFG->dirroot.'/grade/export/lib.php',
    'moodleform'            => $CFG->libdir.'/formslib.php',
    'MoodleExcelWorkbook'   => $CFG->libdir.'/excellib.class.php',
    'MoodleExcelWorksheet'  => $CFG->libdir.'/excellib.class.php',
]);

return null !== $loader ?: die();

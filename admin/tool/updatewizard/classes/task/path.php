<?php
session_start();
define('CLI_SCRIPT', '1');
include "../../../../../config.php";
echo "START";

echo "<pre>";
print_r($CFG->dataroot);

$baseDir        = $CFG->dataroot;
$inputDir       = $baseDir.'/update_wizard/logs/';
$res = "Text";
if(!file_exists($inputDir))
{
    if (!mkdir($inputDir, 0755, true))
    {
        mtrace('Log dir error.');
    }
    else
    {
        echo "error mkdir";
    }
}
$strFileName = 'test'.date('Ymd_Hi').'.log';
$a = file_put_contents($inputDir.$strFileName, $res);
echo "\n -{$a}-";
echo "\n FINISH";
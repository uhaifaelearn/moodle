<?php

namespace gradeexport_haifa_administration_sap;

use stdClass;
use moodle_exception;

/**
 * Class Globals
 *
 * @package tool_updatewizard
 */
class Globals
{
    /**
     * @var bool
     */
    protected static $initDirs  = false;

    /**
     * @var string
     */
    protected static $rootDir   = '';

    /**
     * @var string
     */
    protected static $libDir    = '';

//    /**
//     * @var stdClass
//     */
//    protected static $user    = null;

    public static function getCfg()
    {
        global $CFG;

        static::initDirs($CFG);

        return $CFG;
    }

    public static function getDb()
    {
        global $DB;

        return $DB;
    }

    public static function getOutput()
    {
        global $OUTPUT;

        return $OUTPUT;
    }

    public static function getPage()
    {
        global $PAGE;

        return $PAGE;
    }

    public static function getCourse()
    {
        global $COURSE;

        return $COURSE;
    }

    public static function getCourseId()
    {
        return static::getCourse()->id;
    }

    public static function getUser()
    {
        global $USER;

        return $USER;
    }

    public static function getUserId()
    {
        return static::getUser()->id;
    }

//    public static function setFakeUser()
//    {
//        global $USER;
//
//        static::$user = $USER;
//
//        $USER = new stdClass();
//
//        $USER->id = 0;
//        $USER->mnethostid = '1';
//    }
//
//    public static function restoreUser()
//    {
//        global $USER;
//
//        $USER = static::$user;
//
//        static::$user = null;
//    }

    protected static function initDirs(stdClass $cfg = null)
    {
        if (static::$initDirs) {
            return;
        }

        if (null === $cfg) {
            global $CFG;

            $cfg = $CFG;
        }

        static::$rootDir    = $cfg->dirroot.'/';
        static::$libDir     = $cfg->libdir.'/';
        static::$initDirs   = true;
    }

    /**
     * @return string
     */
    protected static function getRootDir()
    {
        if (!static::$initDirs) {
            static::initDirs();
        }

        return static::$rootDir;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function getFile($fileName)
    {
        return static::getRootDir().$fileName;
    }

    /**
     * @return string
     */
    protected static function getLibDir()
    {
        if (!static::$initDirs) {
            static::initDirs();
        }

        return static::$libDir;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function getLibFile($fileName)
    {
        return static::getLibDir().$fileName;
    }

    /**
     * @return string
     */
    protected static function getCourseLibDir()
    {
        return static::getRootDir().'course/';
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function getCourseLibFile($fileName)
    {
        return static::getCourseLibDir().$fileName;
    }

    /**
     * @return string
     */
    protected static function getUserLibDir()
    {
        return static::getRootDir().'user/';
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function getUserLibFile($fileName)
    {
        return static::getUserLibDir().$fileName;
    }

    /**
     * @return string
     */
    protected static function getUserProfileLibDir()
    {
        return static::getUserLibDir().'profile/';
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function getUserProfileLibFile($fileName)
    {
        return static::getUserProfileLibDir().$fileName;
    }

    /**
     * @return string
     */
    protected static function getEnrollLibDir()
    {
        return static::getRootDir().'enrol/manual/';
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function getEnrollLibFile($fileName)
    {
        return static::getEnrollLibDir().$fileName;
    }

    /**
     * @param string    $table
     * @param array     $conditions
     * @param int       $strictness IGNORE_MISSING means compatible mode, false returned if record not found, debug message if more found;
     *                              IGNORE_MULTIPLE means return first, ignore multiple records found(not recommended);
     *                              MUST_EXIST means we will throw an exception if no record or multiple records found.
     *
     * @return stdClass
     *
     * @throws moodle_exception
     */
    public static function getRecord($table, array $conditions, $strictness = IGNORE_MISSING)
    {
        //try {
            $db = static::getDb();

            return $db->get_record($table, $conditions, '*', $strictness);
        //} catch (moodle_exception $ex) {
        //
        //}
    }

    /**
     * @param string $table
     * @param array  $conditions
     * @param string $sort
     *
     * @return array
     *
     * @throws moodle_exception
     */
    public static function getRecords($table, array $conditions, $sort = '')
    {
        //try {
            $db = static::getDb();

            return $db->get_records($table, $conditions, $sort, '*');
        //} catch (moodle_exception $ex) {
        //
        //}
    }
}

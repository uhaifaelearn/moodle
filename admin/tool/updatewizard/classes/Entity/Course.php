<?php

namespace tool_updatewizard\Entity;

use stdClass;
use moodle_exception;
//use lang_string;
use context;
use context_coursecat;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Globals;
use tool_updatewizard\Cache\Cache;

require_once Globals::getCourseLibFile('lib.php');

/**
 * Class Course
 *
 * @package tool_updatewizard\Entity
 */
class Course
{
    /**
     * @var stdClass
     */
    protected static $courseConfig;

    /** @var array */
    protected static $validFields;

    /** @var bool */
    protected static $initPrepared = false;

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function create(array $data)
    {
        try {
            $categoryContext = context_coursecat::instance($data['category']);

            // Prepare course and the editor.
            $editorOptions = static::getEditorOptions($categoryContext, 0);

            $courseData = static::prepareNewData($data);
            $course = create_course($courseData, $editorOptions);
			course_create_sections_if_missing($course->id, [0,1,2,3,4,5,6,7,8,9,10,11,12]);
			rebuild_course_cache($course->id, true);
            static::setCacheData($course);

            return true;
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param stdClass  $course
     * @param array     $data
     * @param array     $oldData
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function update(stdClass $course, array $data, array $oldData)
    {
        try {
            $id = $course->id;

            $courseData = static::prepareUpdateData($data, $course);

            update_course($courseData);

            $course = get_course($id);

            static::deleteCacheData($oldData);
            static::setCacheData($course);

            return true;
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return bool
     */
    public static function isExists($dataField, $data)
    {
        try {
            static::resolveCourse($dataField, $data);

            return true;
        } catch (UpdateWizardException $ex) {
            return false;
        }
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return stdClass
     *
     * @throws UpdateWizardException
     */
    public static function resolveCourse($dataField, $data)
    {
        try {
            return static::getCourse($dataField, $data);
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return stdClass
     *
     * @throws moodle_exception
     * @throws UpdateWizardException
     */
    protected static function getCourse($dataField, $data)
    {
        // if not cached by $dataField
        if (false === $course = static::getCacheData($dataField, $data)) {
            $course = Globals::getRecord('course', [$dataField => $data]);

            static::setCacheData($course);
        }

        return $course;
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return string
     */
    protected static function setCacheKey($dataField, $data)
    {
        return 'course_'.$dataField.'_'.$data;
    }

    /**
     * @param stdClass  $course
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function setCacheData(stdClass $course)
    {
        // Store in cache. (by idnumber)
        $cacheByIdnumber    = static::setCacheKey('idnumber', $course->idnumber);
        // Store in cache. (by shortname)
        $cacheByShortname   = static::setCacheKey('shortname', $course->shortname);

        return Cache::setManyCacheData([$cacheByIdnumber => $course, $cacheByShortname => $course]);
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return false|stdClass
     *
     * @throws UpdateWizardException
     */
    protected static function getCacheData($dataField, $data)
    {
        $cacheKey = static::setCacheKey($dataField, $data);

        return Cache::getCacheData($cacheKey);
    }

    /**
     * @param array $oldCacheKeys
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function deleteCacheData(array $oldCacheKeys)
    {
        $deleteCacheKeys = [];

        foreach ($oldCacheKeys as $cacheKeyName => $cacheKey) {
            $deleteCacheKeys[] = static::setCacheKey($cacheKeyName, $cacheKey);
        }

        return Cache::deleteManyCacheData($deleteCacheKeys);
    }

    /**
     *
     */
    protected static function prepareInit()
    {
        if (static::$initPrepared) {
            return;
        }

        static::$courseConfig   = get_config('moodlecourse');

        static::$validFields    = [
            'category',
            'fullname',
            'shortname',
            'idnumber',
            'summary',
            'summary_format',
            'format',
            'showgrades',
            'newsitems',
            'startdate',
            'maxbytes',
            'legacyfiles',
            'showreports',
            'visible',
            'groupmode',
            'groupmodeforce',
            'lang',
            'calendartype',
            'theme',
            'enablecompletion',
        ];

        static::$initPrepared = true;
    }

    /**
     * @param array $data
     *
     * @return stdClass
     */
    protected static function prepareNewData(array $data)
    {
        static::prepareInit();

        $defaultData = static::getDefaultData();
        $validFields = static::$validFields;

        foreach ($validFields as $field) {
            if (!array_key_exists($field, $data) && array_key_exists($field, $defaultData)) {
                $data[$field] = $defaultData[$field];
            }
        }

        $dataObject = (object) $data;
        $courseData = new stdClass();

        foreach ($dataObject as $key => $value) {
            $courseData->$key = $value;
        }

        return $courseData;
    }

    /**
     * @param array    $data
     * @param stdClass $currentData
     *
     * @return stdClass
     */
    protected static function prepareUpdateData(array $data, stdClass $currentData)
    {
        static::prepareInit();

        $validFields    = static::$validFields;
        $courseData     = new stdClass();

        foreach ($validFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== $currentData->$field) {
                $courseData->$field = $data[$field];
            }
        }

        $courseData->id = $currentData->id;

        return $courseData;
    }

    /**
     * @return array
     */
    protected static function getDefaultData()
    {
        $courseConfig = static::$courseConfig;

        return [
//            'summary'           => '',
            'summary_format'    => FORMAT_HTML,
            'format'            => $courseConfig->format,
            'showgrades'        => $courseConfig->showgrades,
            'newsitems'         => $courseConfig->newsitems,
//            'startdate'         => time() + 86400,  // 60 * 60 * 24
            'startdate'         => time(),
            'maxbytes'          => $courseConfig->maxbytes,
            'showreports'       => $courseConfig->showreports,
            'visible'           => $courseConfig->visible,
            'groupmode'         => $courseConfig->groupmode,
            'groupmodeforce'    => $courseConfig->groupmodeforce,
            'lang'              => $courseConfig->lang,
        ];
    }

    /**
     * @param context $context
     * @param         $subDirs
     *
     * @return array
     */
    protected static function getEditorOptions(context $context, $subDirs)
    {
        $cfg = Globals::getCfg();

        return [
            'maxfiles'  => EDITOR_UNLIMITED_FILES,
            'maxbytes'  => $cfg->maxbytes,
            'trusttext' => false,
            'noclean'   => true,
            'context'   => $context,
            'subdirs'   => $subDirs,
        ];
    }
}

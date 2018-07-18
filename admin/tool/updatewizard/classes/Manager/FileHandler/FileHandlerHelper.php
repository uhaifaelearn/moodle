<?php

namespace tool_updatewizard\Manager\FileHandler;

use tool_updatewizard\Exception\UpdateWizardException;

/**
 * Class FileHandlerHelper
 *
 * @package tool_updatewizard\Manager\FileHandler
 */
abstract class FileHandlerHelper
{
    const UPDATE_CATEGORIES                     = 'update_categories';

    const NEW_CATEGORIES                        = 'new_categories';

    const UPDATE_COURSES                        = 'update_courses';

    const NEW_COURSES                           = 'new_courses';

    const UPDATE_USERS                          = 'update_users';

    const NEW_USERS                             = 'new_users';

    const ENROLL_USERS                          = 'enroll_users';

    /**
     * Create course-categories/courses/users that do not exist yet.
     */
    const MODE_CREATE_NEW                       = 'create_new';

    /**
     * Only update existing course-category/courses/users.
     */
    const MODE_UPDATE_ONLY                      = 'update_only';

    const MODE_ENROLL_USERS                     = 'enroll';

    /**
     * During update, only use data passed from the CSV.
     */
    const UPDATE_ALL_WITH_DATA_ONLY             = 1;

    /**
     * @return string
     */
    public static function getFilesDate()
    {
        return date('Ymd');
    }

    /**
     * @return string
     */
    public static function getFilesExt()
    {
        return '.csv';
    }

    /**
     * @return string
     */
    public static function getFilesDateExt()
    {
        return '_'.static::getFilesDate().static::getFilesExt();
    }

    /**
     * @param string $inputDir
     *
     * @return array
     * 
     * @throws UpdateWizardException
     */
    public static function getInputFiles($inputDir)
    {
        $filesDateExt = static::getFilesDateExt();

        if (false === $inputFiles = glob($inputDir.'*'.$filesDateExt)) {
            throw new UpdateWizardException(null, sprintf('Cannot Fetch Files from %s', $inputDir));
        }
        
        return $inputFiles;
    }

    /**
     * @return array
     */
    public static function getOrderedFilesHandlers()
    {
        $filesDate      = static::getFilesDate();
        $filesDateExt   = static::getFilesDateExt();

        return [
            'updateCategories'  => [
                'handler'                   => self::UPDATE_CATEGORIES,
                'name'                      => self::UPDATE_CATEGORIES.'_'.$filesDate,
                'file'                      => self::UPDATE_CATEGORIES.$filesDateExt,
                'class'                     => 'Category',
                'action'                    => [
                    'mode'          => self::MODE_UPDATE_ONLY,
                    'update_mode'   => self::UPDATE_ALL_WITH_DATA_ONLY,
                ],
                'mandatoryColumns'          => [
                    'idnumber',
                ],
                'optionalColumns'           => [
                    'name',
                    'parent_idnumber',
                    'description',
                    'visible',
                    'new_idnumber',
                ],
            ],
            'newCategories'     => [
                'handler'                   => self::NEW_CATEGORIES,
                'name'                      => self::NEW_CATEGORIES.'_'.$filesDate,
                'file'                      => self::NEW_CATEGORIES.$filesDateExt,
                'class'                     => 'Category',
                'action'                    => [
                    'mode'          => self::MODE_CREATE_NEW,
                ],
                'mandatoryColumns'          => [
                    'idnumber',
                    'name',
                    'parent_idnumber',
                ],
                'optionalColumns'           => [
                    'description',
                    'visible',
                ],
            ],
            'updateCourses'     => [
                'handler'                   => self::UPDATE_COURSES,
                'name'                      => self::UPDATE_COURSES.'_'.$filesDate,
                'file'                      => self::UPDATE_COURSES.$filesDateExt,
                'class'                     => 'Course',
                'action'                    => [
                    'mode'          => self::MODE_UPDATE_ONLY,
                    'update_mode'   => self::UPDATE_ALL_WITH_DATA_ONLY,
                ],
                'mandatoryOptionalColumns'  => [
                    'idnumber',
                    'shortname',
                ],
                'optionalColumns'           => [
                    'fullname',
                    'category_idnumber',
                    'summary',
                    'visible',
                    'new_idnumber',
                    'new_shortname',
                ],
                'fieldEnclosure'            => '|',
            ],
            'newCourses'        => [
                'handler'                   => self::NEW_COURSES,
                'name'                      => self::NEW_COURSES.'_'.$filesDate,
                'file'                      => self::NEW_COURSES.$filesDateExt,
                'class'                     => 'Course',
                'action'                    => [
                    'mode'          => self::MODE_CREATE_NEW,
                ],
                'mandatoryColumns'          => [
                    'idnumber',
                    'shortname',
                    'fullname',
                    'category_idnumber',
                ],
                'optionalColumns'           => [
                    'summary',
                    'visible',
                ],
                'fieldEnclosure'            => '|',
            ],
            'updateUsers'       => [
                'handler'                   => self::UPDATE_USERS,
                'name'                      => self::UPDATE_USERS.'_'.$filesDate,
                'file'                      => self::UPDATE_USERS.$filesDateExt,
                'class'                     => 'User',
                'action'                    => [
                    'mode'          => self::MODE_UPDATE_ONLY,
                    'update_mode'   => self::UPDATE_ALL_WITH_DATA_ONLY,
                ],
                'mandatoryOptionalColumns'  => [
                    'username',
                    'idnumber',
                ],
                'optionalColumns'           => [
                    'firstname',
                    'lastname',
                    'email',
                    'lastnamephonetic',
                    'firstnamephonetic',
                    'profile_field_title',
                    'profile_field_titlephonetic',
                    'new_idnumber',
                    'new_username',
                ],
            ],
            'newUsers'          => [
                'handler'                   => self::NEW_USERS,
                'name'                      => self::NEW_USERS.'_'.$filesDate,
                'file'                      => self::NEW_USERS.$filesDateExt,
                'class'                     => 'User',
                'action'                    => [
                    'mode'          => self::MODE_CREATE_NEW,
                ],
                'mandatoryColumns'          => [
                    'username',
                    'idnumber',
                    'firstname',
                    'lastname',
                    'email',
                ],
                'optionalColumns'           => [
                    'lastnamephonetic',
                    'firstnamephonetic',
                    'profile_field_title',
                    'profile_field_titlephonetic',
                ],
            ],
            'enrollUsers'        => [
                'handler'                   => self::ENROLL_USERS,
                'name'                      => self::ENROLL_USERS.'_'.$filesDate,
                'file'                      => self::ENROLL_USERS.$filesDateExt,
                'class'                     => 'Enrollment',
                'action'                    => [
                    'mode'          => self::MODE_ENROLL_USERS,
                ],
                'mandatoryColumns'          => [
                    'action',
                    'role',
                    'user',
                    'course',
                ],
            ],
        ];
    }
}

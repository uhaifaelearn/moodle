<?php

namespace tool_updatewizard\Entity;

use stdClass;
use moodle_exception;
//use lang_string;
use context_course;
use enrol_manual_plugin;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Globals;
use tool_updatewizard\Cache\Cache;

require_once Globals::getEnrollLibFile('lib.php');

/**
 * Class Enrollment
 *
 * @package tool_updatewizard\Entity
 */
class Enrollment extends enrol_manual_plugin
{
    const ENROL_USER_INACTIVE = -1;

    /** @var array */
    protected static $roles;

    /** @var array */
    protected static $rolesId = [];

    /** @var array */
    protected static $coursesStartTime = [];

    /** @var array */
    protected static $unKnownUsers = [];

    /** @var array */
    protected static $unKnownCourses = [];

    /** @var bool */
    protected static $initPrepared = false;

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public function add(array $data)
    {
        $roleId     = $data['role'];
        $userId     = $data['user'];
        $courseId   = $data['course'];

        $courseStartTime            = static::$coursesStartTime[$courseId];

        $enrolledUserStatus         = static::getEnrollmentStatus($courseId, $userId);
        $courseEnrolledUsersRoles   = static::getCourseUserRoles($courseId, $userId);

        $instance                   = static::getInstance($courseId);
        $contextCourse              = context_course::instance($courseId);

        switch ($enrolledUserStatus) {
            case ENROL_USER_SUSPENDED:
                $this->update_user_enrol($instance, $userId, ENROL_USER_ACTIVE);

                static::getCourseUserManualEnrollment($instance->id, $courseId, $userId);
                // NO Break

            case ENROL_USER_ACTIVE:
                if (array_key_exists($roleId, $courseEnrolledUsersRoles)) {
                    throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $userId));
                }

                role_assign($roleId, $userId, $contextCourse);
                break;

            case self::ENROL_USER_INACTIVE:
                $this->enrol_user($instance, $userId, $roleId, $courseStartTime, 0, ENROL_USER_ACTIVE);

                static::getCourseUserManualEnrollment($instance->id, $courseId, $userId);
                break;

            default:
                throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $userId));
        }

        static::getUserRoleAssignment($contextCourse->id, $courseId, $userId, $roleId);

        return true;
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public function del(array $data)
    {
        $roleId     = $data['role'];
        $userId     = $data['user'];
        $courseId   = $data['course'];

        $enrolledUserStatus         = static::getEnrollmentStatus($courseId, $userId);

        $instance                   = static::getInstance($courseId);
        $contextCourse              = context_course::instance($courseId);

        switch ($enrolledUserStatus) {
            case ENROL_USER_SUSPENDED:
            case ENROL_USER_ACTIVE:
                $courseEnrolledUsersRoles   = static::getCourseUserRoles($courseId, $userId);

                if (!array_key_exists($roleId, $courseEnrolledUsersRoles)) {
                   // throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $userId));
                }

                $modifier = $courseEnrolledUsersRoles[$roleId]->modifierid;

                if (Globals::getUserId() !== $modifier) {
                    //throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $modifier));
                }

                if (1 === count($courseEnrolledUsersRoles)) {
                    $this->unenrol_user($instance, $userId);

                    static::deleteCourseUserEnrollmentCacheData($courseId, $userId);
                    static::deleteCourseUserRolesCacheData($courseId, $userId);
                } else {
                    role_unassign($roleId, $userId, $contextCourse->id);

                    static::removeCourseUserRoleCacheData($courseId, $userId, $roleId);
                }

                break;

            default:
                throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $userId));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_name()
    {
        $words = explode('_', get_parent_class($this));

        return $words[1];
    }

    /**
     * @param string $user
     * @param string $course
     * @param string $roleId
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function isUserEnrolled($user, $course, $roleId)
    {
        if (array_key_exists($user, static::$unKnownUsers)) {
            throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $user));
        }

        if (array_key_exists($course, static::$unKnownCourses)) {
            throw new UpdateWizardException(null, sprintf('Unknown Course with idnumber: %s', $course));
        }

        $userId     = static::getUserId($user);
        $courseId   = static::getCourseId($course);

        try {
            return
                ENROL_USER_ACTIVE === static::getEnrollmentStatus($courseId, $userId)
                && array_key_exists($roleId, static::getCourseUserRoles($courseId, $userId));
        } catch (moodle_exception $ex) {
            return false;
        }
    }

    /**
     * @param string $idnumber
     *
     * @return int
     *
     * @throws UpdateWizardException
     */
    public static function getUserId($idnumber)
    {
        try {
            if (false === $user = User::resolveUser('idnumber', $idnumber)) {
                throw new UpdateWizardException(null, '???');
            }

            return $user->id;
        } catch (UpdateWizardException $ex) {
            static::$unKnownUsers[$idnumber] = true;

            throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $idnumber));
        }
    }

    /**
     * @param string $idnumber
     *
     * @return int
     *
     * @throws UpdateWizardException
     */
    public static function getCourseId($idnumber)
    {
        try {
            if (false === $course = Course::resolveCourse('idnumber', $idnumber)) {
                throw new UpdateWizardException(null, '???');
            }

            $id                             = $course->id;
            static::$coursesStartTime[$id]  = $course->startdate;

            return $id;
        } catch (UpdateWizardException $ex) {
            static::$unKnownCourses[$idnumber] = true;

            throw new UpdateWizardException(null, sprintf('Unknown Course with idnumber: %s', $idnumber));
        }
    }

    /**
     * @param string $role
     *
     * @return stdClass
     *
     * @throws UpdateWizardException
     */
    public static function getRoleData($role)
    {
        if (!static::$initPrepared) {
            static::initRolesList();
        }

        $roles = static::$roles;

        if (!array_key_exists($role, $roles)) {
            throw new UpdateWizardException(
                null,
                sprintf('Role "%s", is unknown or not supported in this enrollment', $role)
            );
        }

        return $roles[$role];
    }

    /**
     *
     */
    protected static function initRolesList()
    {
        $roles          = [];
        $rolesId        = [];
        $supportedRoles = [
            'editingteacher'    => ['add'],
            'student'           => ['add', 'del'],
        ];

        foreach (get_all_roles() as $role) {
            $roleName = $role->shortname;

            if (array_key_exists($roleName, $supportedRoles)) {
                $role->allowedActions = $supportedRoles[$roleName];

                $roles[$roleName]   = $role;
                $rolesId[$role->id] = $roleName;
            }
        }

        static::$roles          = $roles;
        static::$rolesId        = $rolesId;

        static::$initPrepared   = true;
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return int
     *
     * @throws UpdateWizardException
     */
    protected static function getEnrollmentStatus($courseId, $userId)
    {
        try {
            $enroll = static::getCourseUserEnrollment($courseId, $userId);

            return false === $enroll ? self::ENROL_USER_INACTIVE : (int) $enroll->status;
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param int $courseId
     * @param int $userId
     *
     * @return false|stdClass
     *
     * @throws moodle_exception
     * @throws UpdateWizardException
     */
    protected static function getCourseUserEnrollment($courseId, $userId)
    {
        // if not cached
        if (false === $enroll = static::getCourseUserEnrollmentCacheData($courseId, $userId)) {
            $instance = static::getCourseManualEnrollInstance($courseId);

            $enroll = static::getCourseUserManualEnrollment($instance->id, $courseId, $userId);
        }

        return $enroll;
    }

    /**
     * @param int $instanceId
     * @param int $courseId
     * @param int $userId
     *
     * @return false|stdClass
     *
     * @throws UpdateWizardException
     */
    protected static function getCourseUserManualEnrollment($instanceId, $courseId, $userId)
    {
        try {
            $enroll = Globals::getRecord('user_enrolments', ['enrolid' => $instanceId, 'userid' => $userId]);

            // Store in cache.
            static::setCourseUserEnrollmentCacheData($courseId, $userId, $enroll);
        } catch (moodle_exception $ex) {
            $enroll = false;
        }

        return $enroll;
    }

    /**
     * @param string $courseId
     *
     * @return stdClass
     *
     * @throws UpdateWizardException
     */
    protected static function getInstance($courseId)
    {
        try {
            return static::getCourseManualEnrollInstance($courseId);
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param string $courseId
     *
     * @return stdClass
     *
     * @throws moodle_exception
     * @throws UpdateWizardException
     */
    protected static function getCourseManualEnrollInstance($courseId)
    {
        // if not cached by $dataField
        if (false === $instance = static::getCourseManualEnrollInstanceCacheData($courseId)) {
            $instance = Globals::getRecord('enrol', ['courseid' => $courseId, 'enrol' => 'manual']);

            // Store in cache. (by course id)
            static::setCourseManualEnrollInstanceCacheData($courseId, $instance);
        }

        return $instance;
    }

    /**
     * @param int $courseId
     * @param int $userId
     *
     * @return array
     *
     * @throws UpdateWizardException
     */
    protected static function getCourseUserRoles($courseId, $userId)
    {
        if ([] === $roles = static::getUserRoles($courseId, $userId)) {
            $context = context_course::instance($courseId);

            $roles = static::getUserRolesAssignments($context->id, $courseId, $userId);
        }

        return $roles;
    }

    /**
     * @param int $contextId
     * @param int $courseId
     * @param int $userId
     *
     * @return array
     *
     * @throws UpdateWizardException
     */
    protected static function getUserRolesAssignments($contextId, $courseId, $userId)
    {
        $roles = [];

        try {
            $roleAssignments = Globals::getRecords('role_assignments', ['contextid' => $contextId, 'userid' => $userId], 'roleid');

            foreach ($roleAssignments as $roleAssignment) {
                $roleId = $roleAssignment->roleid;

                $roles[$roleId] = $roleAssignment;
            }

            if ([] !== $roles) {
                static::addCourseUserRolesCacheData($courseId, $userId, $roles);
            }
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $userId));
        }

        return $roles;
    }

    /**
     * @param int $contextId
     * @param int $courseId
     * @param int $userId
     * @param int $roleId
     *
     * @return array
     *
     * @throws UpdateWizardException
     */
    protected static function getUserRoleAssignment($contextId, $courseId, $userId, $roleId)
    {
        try {
            $roleAssignment = Globals::getRecord(
                'role_assignments',
                ['contextid' => $contextId, 'userid' => $userId, 'roleid' => $roleId]
            );

            static::addCourseUserRoleCacheData($courseId, $userId, $roleId, $roleAssignment);
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException(null, sprintf('Unknown User with idnumber: %s', $userId));
        }

        return $roleAssignment;
    }

    /**
     * @param int $courseId
     * @param int $userId
     *
     * @return array
     *
     * @throws UpdateWizardException
     */
    protected static function getUserRoles($courseId, $userId)
    {
        if (false === $roles = static::getCourseUserRolesCacheData($courseId, $userId)) {
            $roles = [];
        }

        return $roles;
    }

    /**
     * @param string $courseId
     *
     * @return string
     */
    protected static function setCourseManualEnrollInstanceCacheKey($courseId)
    {
        return 'enroll_'.$courseId;
    }

    /**
     * @param string    $courseId
     * @param stdClass  $instance
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function setCourseManualEnrollInstanceCacheData($courseId, stdClass $instance)
    {
        $cacheKey = static::setCourseManualEnrollInstanceCacheKey($courseId);

        return Cache::setCacheData($cacheKey, $instance);
    }

    /**
     * @param string $courseId
     *
     * @return false|stdClass
     *
     * @throws UpdateWizardException
     */
    protected static function getCourseManualEnrollInstanceCacheData($courseId)
    {
        $cacheKey = static::setCourseManualEnrollInstanceCacheKey($courseId);

        return Cache::getCacheData($cacheKey);
    }

    /**
     * @param string $courseId
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function deleteCourseManualEnrollInstanceCacheData($courseId)
    {
        $cacheKey = static::setCourseManualEnrollInstanceCacheKey($courseId);

        return Cache::deleteCacheData($cacheKey);
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return string
     */
    protected static function setCourseUserEnrollmentCacheKey($courseId, $userId)
    {
        return 'user_enrollment_'.$courseId.'_'.$userId;
    }

    /**
     * @param string    $courseId
     * @param string    $userId
     * @param stdClass  $enrollment
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function setCourseUserEnrollmentCacheData($courseId, $userId, stdClass $enrollment)
    {
        $cacheKey = static::setCourseUserEnrollmentCacheKey($courseId, $userId);

        return Cache::setCacheData($cacheKey, $enrollment);
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return false|stdClass
     *
     * @throws UpdateWizardException
     */
    protected static function getCourseUserEnrollmentCacheData($courseId, $userId)
    {
        $cacheKey = static::setCourseUserEnrollmentCacheKey($courseId, $userId);

        return Cache::getCacheData($cacheKey);
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function deleteCourseUserEnrollmentCacheData($courseId, $userId)
    {
        $cacheKey = static::setCourseUserEnrollmentCacheKey($courseId, $userId);

        return Cache::deleteCacheData($cacheKey);
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return string
     */
    protected static function setCourseUserRolesCacheKey($courseId, $userId)
    {
        return 'role_assignment_'.$courseId.'_'.$userId;
    }

    /**
     * @param string    $courseId
     * @param string    $userId
     * @param array     $roles
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function addCourseUserRolesCacheData($courseId, $userId, array $roles)
    {
        $cacheKey = static::setCourseUserRolesCacheKey($courseId, $userId);

        if (false === $cacheData = Cache::getCacheData($cacheKey)) {
            $cacheData = [];
        }

        $cacheData += $roles;
        //$cacheData = array_merge($cacheData, $roles);

        // Store in cache.
        return Cache::setCacheData($cacheKey, $cacheData);
    }

    /**
     * @param string    $courseId
     * @param string    $userId
     * @param string    $roleId
     * @param stdClass  $roleAssignment
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function addCourseUserRoleCacheData($courseId, $userId, $roleId, stdClass $roleAssignment)
    {
        $cacheKey = static::setCourseUserRolesCacheKey($courseId, $userId);

        if (false === $cacheData = Cache::getCacheData($cacheKey)) {
            $cacheData = [];
        }

        $cacheData[$roleId] = $roleAssignment;

        // Store in cache.
        return Cache::setCacheData($cacheKey, $cacheData);
    }

    /**
     * @param string $courseId
     * @param string $userId
     * @param string $roleId
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function removeCourseUserRoleCacheData($courseId, $userId, $roleId)
    {
        $cacheKey = static::setCourseUserRolesCacheKey($courseId, $userId);

        if (
            /** @var $cacheData */
            false === $cacheData = Cache::getCacheData($cacheKey)
            || !array_key_exists($roleId, $cacheData)
        ) {
            throw new UpdateWizardException(null, 'un_enroll_course_user_role');
        }

        unset($cacheData[$roleId]);

        // Store in cache.
        return Cache::setCacheData($cacheKey, $cacheData);
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return false|array
     *
     * @throws UpdateWizardException
     */
    protected static function getCourseUserRolesCacheData($courseId, $userId)
    {
        $cacheKey = static::setCourseUserRolesCacheKey($courseId, $userId);

        return Cache::getCacheData($cacheKey);
    }

    /**
     * @param string $courseId
     * @param string $userId
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function deleteCourseUserRolesCacheData($courseId, $userId)
    {
        $cacheKey = static::setCourseUserRolesCacheKey($courseId, $userId);

        return Cache::deleteCacheData($cacheKey);
    }
}

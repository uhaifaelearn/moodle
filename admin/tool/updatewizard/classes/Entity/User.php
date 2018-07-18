<?php

namespace tool_updatewizard\Entity;

use stdClass;
use moodle_exception;
//use lang_string;
use context_user;
use core\event\user_created;
use core\event\user_updated;
use core\session\manager;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Globals;
use tool_updatewizard\Cache\Cache;

require_once Globals::getUserLibFile('editlib.php');
require_once Globals::getUserProfileLibFile('lib.php');
require_once Globals::getUserLibFile('lib.php');

/**
 * Class User
 *
 * @package tool_updatewizard\Entity
 */
class User
{
    /** @var string */
    protected static $authPlugin;

    /** @var string */
    protected static $timezone;

    /** @var string */
    protected static $password;

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
            $userData = static::prepareNewData($data);

            $userData->id = $id = user_create_user($userData, false, false);

            $userContext = context_user::instance($id);

            // Save custom profile fields data.
            profile_save_data($userData);

            user_created::create_from_userid($id)->trigger();

            // Mark context as dirty.
            // make sure user context exists
            $userContext->mark_dirty();

            manager::gc();

            static::setCacheData($userData);

            return true;
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param stdClass  $user
     * @param array     $data
     * @param array     $oldData
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function update(stdClass $user, array $data, array $oldData)
    {
        try {
            $id = $user->id;

//            // Load custom profile fields data.
//            profile_load_data($user);

            $userData = static::prepareUpdateData($data, $user);

            user_update_user($userData, false, false);

            $userContext = context_user::instance($id);

            // Save custom profile fields data.
            profile_save_data($userData);

            // Reload from db.
            //$userData = Globals::getRecord('user', ['id' => $userData->id]);
            $userData = static::getUserData('id', $userData->id);

            user_updated::create_from_userid($userData->id)->trigger();

            // Mark context as dirty.
            // make sure user context exists
            $userContext->mark_dirty();

            // Remove stale sessions.
            manager::gc();

            static::deleteCacheData($oldData);
            static::setCacheData($userData);

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
            static::resolveUser($dataField, $data);

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
    public static function resolveUser($dataField, $data)
    {
        try {
            return static::getUser($dataField, $data);
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
    protected static function getUser($dataField, $data)
    {
        // if not cached by $dataField
        if (false === $user = static::getCacheData($dataField, $data)) {
            $user = static::getUserData($dataField, $data);

            static::setCacheData($user);
        }

        return $user;
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return stdClass
     *
     * @throws moodle_exception
     */
    protected static function getUserData($dataField, $data)
    {
        $user = Globals::getRecord('user', [$dataField => $data]);

        // Load custom profile fields data.
        profile_load_data($user);

        return $user;
    }

    /**
     * @param string $dataField
     * @param string $data
     *
     * @return string
     */
    protected static function setCacheKey($dataField, $data)
    {
        return 'user_'.$dataField.'_'.$data;
    }

    /**
     * @param stdClass  $user
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function setCacheData(stdClass $user)
    {
        $userFields     = ['id', 'username', 'idnumber', 'email'];
        $cacheKeysData  = [];

        foreach ($userFields as $userField) {
            if ('' !== $cacheKeyIndex = $user->$userField) {
                $cacheKey = static::setCacheKey($userField, $cacheKeyIndex);

                // Store in cache. (by id / username / idnumber / email)
                $cacheKeysData[$cacheKey] = $user;
            }
        }

        return Cache::setManyCacheData($cacheKeysData);
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

        static::$authPlugin = 'saml2';
        static::$password   = AUTH_PASSWORD_NOT_CACHED;
        static::$timezone   = usertimezone();

        static::$validFields    = [
            'id',
            'auth',
            'confirmed',
            'deleted',
            'mnethostid',
            'username',
            'password',
            'idnumber',
            'firstname',
            'lastname',
            'email',
            'timezone',
            'lastnamephonetic',
            'firstnamephonetic',
            'profile_field_title',
            'profile_field_titlephonetic',
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
        $userData = new stdClass();

        foreach ($dataObject as $key => $value) {
            $userData->$key = $value;
        }

        return $userData;
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
        $userData       = new stdClass();

        foreach ($validFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== $currentData->$field) {
                $userData->$field = $data[$field];
            }
        }

        $userData->id = $currentData->id;

        return $userData;
    }

    /**
     * @return array
     */
    protected static function getDefaultData()
    {
        return [
            'id'            => -1,
            'auth'          => static::$authPlugin,
            'confirmed'     => 1,
            'deleted'       => 0,
            'mnethostid'    => Globals::getCfg()->mnet_localhost_id,
            'password'      => static::$password,
            'timezone'      => static::$timezone,
        ];
    }
}

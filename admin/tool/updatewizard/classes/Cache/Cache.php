<?php

namespace tool_updatewizard\Cache;

use stdClass;
use cache as BaseCache;
use coding_exception;
use tool_updatewizard\Exception\UpdateWizardException;

class Cache
{
    /** @var BaseCache */
    protected static $cache = null;

    /** @var bool */
    protected static $initCache = false;

    /**
     * @param string                    $cacheKey
     * @param stdClass|array|string|int $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function setCacheData($cacheKey, $data)
    {
        $cache = static::getCahce();

        return $cache->set($cacheKey, $data);
    }

    /**
     * @param array $cacheKeysData
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function setManyCacheData(array $cacheKeysData)
    {
        $cache = static::getCahce();

        return count($cacheKeysData) === $cache->set_many($cacheKeysData);
    }

    /**
     * @param $cacheKey
     *
     * @return false|mixed
     *
     * @throws UpdateWizardException
     */
    public static function getCacheData($cacheKey)
    {
        try {
            $cache = static::getCahce();

            return $cache->get($cacheKey);
        } catch (coding_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param $cacheKey
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function deleteCacheData($cacheKey)
    {
        $cache = static::getCahce();

        return $cache->delete($cacheKey);
    }

    /**
     * @param array $cacheKeys
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public static function deleteManyCacheData(array $cacheKeys)
    {
        $cache = static::getCahce();

        return count($cacheKeys) === $cache->delete_many($cacheKeys);
    }

    /**
     *
     */
    public static function initCache()
    {
        if (static::$initCache) {
            return;
        }

        static::$cache      = BaseCache::make('tool_updatewizard', 'update_wizard');
        static::$initCache  = true;

        static::$cache->purge();
    }

    /**
     *
     */
    public static function releaseCache()
    {
        static::$cache->purge();

        static::$cache      = null;
        static::$initCache  = false;
    }

    /**
     * @return BaseCache
     *
     * @throws UpdateWizardException
     */
    protected static function getCahce()
    {
        if (!static::$initCache || null === static::$cache) {
            throw new UpdateWizardException(null, '???');
        }

        return static::$cache;
    }
}

<?php

namespace tool_updatewizard\Entity;

use moodle_exception;
use lang_string;
use coursecat;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Cache\Cache;

/**
 * Class Category
 *
 * @package tool_updatewizard\Entity
 */
class Category extends coursecat
{
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
            $category = parent::create($data);

            static::setCacheData($category->id, $category->idnumber);

            return true;
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public function update(array $data)
    {
        try {
            $oldIdnumber = $this->idnumber;

            parent::update($data);

            $idnumber = $this->idnumber;

            if ($oldIdnumber !== $idnumber) {
                static::deleteCacheData($oldIdnumber);
                static::setCacheData($this->id, $idnumber);
            }

            return true;
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param string $idnumber
     *
     * @return bool
     */
    public static function isExists($idnumber)
    {
        try {
            static::resolveCategoryByIdnumber($idnumber);

            return true;
        } catch (UpdateWizardException $ex) {
            return false;
        }
    }

    /**
     * @param string $idnumber
     *
     * @return Category
     *
     * @throws UpdateWizardException
     */
    public static function resolveCategoryByIdnumber($idnumber)
    {
        $id = static::getCategoryId($idnumber);
        
        try {
            return static::get($id, MUST_EXIST, true);
        } catch (moodle_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param string $idnumber
     *
     * @return int
     *
     * @throws UpdateWizardException
     */
    public static function getCategoryId($idnumber)
    {
        if (!$idnumber) {
            return 0;
        }

        $records = null;

        // if not cached by idnumber
        if (false === $id = static::getCacheData($idnumber)) {
            // if not exists
            if ([] === $records = static::get_records('cc.idnumber = :idnumber', ['idnumber' => $idnumber])) {
                throw new UpdateWizardException(null, 'could_not_resolve_category_by_idnumber');
            }

            $record = reset($records);

            // Store in cache. (by idnumber)
            static::setCacheData($record->id, $idnumber);
        }

        return $id;
    }

    /**
     * @param string $idnumber
     *
     * @return string
     */
    protected static function setCacheKey($idnumber)
    {
        return 'category_idnumber_'.$idnumber;
    }

    /**
     * @param int       $id
     * @param string    $idnumber
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function setCacheData($id, $idnumber)
    {
        $cacheKey = static::setCacheKey($idnumber);

        // Store in cache. (by idnumber)
        return Cache::setCacheData($cacheKey, $id);
    }

    /**
     * @param string $idnumber
     *
     * @return false|int
     *
     * @throws UpdateWizardException
     */
    protected static function getCacheData($idnumber)
    {
        $cacheKey = static::setCacheKey($idnumber);

        return Cache::getCacheData($cacheKey);
    }

    /**
     * @param string $oldIdnumber
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected static function deleteCacheData($oldIdnumber)
    {
        $oldCacheKey = static::setCacheKey($oldIdnumber);

        return Cache::deleteCacheData($oldCacheKey);
    }
}

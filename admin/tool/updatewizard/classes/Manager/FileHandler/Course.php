<?php

namespace tool_updatewizard\Manager\FileHandler;

//use lang_string;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Entity\Category;
use tool_updatewizard\Entity\Course as CourseEntity;

/**
 * Class Course
 *
 * @package tool_updatewizard\Manager\FileHandler
 */
class Course extends FileHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkRowData(array $rowData, array $headerRow)
    {
        $dataField      = '';
        $checkHeader    = ['shortname', 'idnumber'];

        foreach ($checkHeader as $checkField) {
            if (
                array_key_exists($checkField, $headerRow)
                && '' !== $data = $rowData[$headerRow[$checkField]]
            ) {
                $data = $this->cleanInput($data);

                $this->checkByShortnameOrIdnumber($checkField, $data, $this->executeMethod);

                $dataField = $checkField;
            }
        }

        if ('' === $dataField) {
            throw new UpdateWizardException(null, 'Cannot Resolve Course without idnumber or shortname');
        }

        return true;
    }

    /**
     * @param string $dataField
     * @param string $data
     * @param string $mode
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected function checkByShortnameOrIdnumber($dataField, $data, $mode)
    {
        switch ($mode) {
            case 'create':
                if (!CourseEntity::isExists($dataField, $data)) {
                    return true;
                }

                throw new UpdateWizardException(null, sprintf('Course with %s: %s, already exists', $dataField, $data));
                break;

            case 'update':
                if (CourseEntity::isExists($dataField, $data)) {
                    return true;
                }

                throw new UpdateWizardException(null, sprintf('Course with %s: %s, not exists', $dataField, $data));
                break;

            default:
                throw new UpdateWizardException(null, 'Unsupported Mode');
        }
    }

    /**
     * @param $idnumber
     *
     * @return int
     *
     * @throws UpdateWizardException
     */
    protected function getCategoryId($idnumber)
    {
        if (Category::isExists($idnumber)) {
            return Category::getCategoryId($idnumber);
        }

        throw new UpdateWizardException(null, 'could_not_resolve_category_by_idnumber');
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected function create(array $data)
    {
        $data['category'] = $this->getCategoryId($data['category_idnumber']);

        unset($data['category_idnumber']);

        return CourseEntity::create($data);
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected function update(array $data)
    {
        $checkCourseId  = [];
        $course         = null;

        foreach (['idnumber', 'shortname'] as $courseField) {
            if (array_key_exists($courseField, $data) && '' !== $data[$courseField]) {
                $course = CourseEntity::resolveCourse($courseField, $data[$courseField]);

                $checkCourseId[$courseField] = $course->id;
            }
        }

        if (
            array_key_exists('idnumber', $checkCourseId)
            && array_key_exists('shortname', $checkCourseId)
            && $checkCourseId['idnumber'] !== $checkCourseId['shortname']
        ) {
            throw new UpdateWizardException(
                null,
                sprintf(
                    'Course with idnumber: %s is not Identical to the Course with shortname: %s',
                    $data['idnumber'],
                    $data['shortname']
                )
            );
        }

        if (array_key_exists('category_idnumber', $data)) {
            $categoryId = $this->getCategoryId($data['category_idnumber']);

            unset($data['category_idnumber']);

            if ($course->category !== $categoryId && 0 !== $categoryId) {
                $data['category'] = $categoryId;
            }
        }

        $oldData = [];

        foreach (['idnumber', 'shortname'] as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }

            $newField = 'new_'.$field;

            if (array_key_exists($newField, $data)) {
                $newData = $data[$newField];

                unset($data[$newField]);

                if ('' !== $newData) {
                    $this->checkByShortnameOrIdnumber($field, $newData, 'create');

                    $data[$field] = $newData;

                    if ($course->$field !== $newData) {
                        $oldData[$field] = $course->$field;
                    }
                }
            }
        }

        return CourseEntity::update($course, $data, $oldData);
    }
}

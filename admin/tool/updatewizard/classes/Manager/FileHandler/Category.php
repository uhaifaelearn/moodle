<?php

namespace tool_updatewizard\Manager\FileHandler;

//use lang_string;
use tool_updatewizard\Exception\UpdateWizardException;
//use tool_updatewizard\Manager\TraceManager;
use tool_updatewizard\Entity\Category as CategoryEntity;

/**
 * Class Category
 *
 * @package tool_updatewizard\Manager\FileHandler
 */
class Category extends FileHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkRowData(array $rowData, array $headerRow)
    {
        $idnumber = $this->cleanInput($rowData[$headerRow['idnumber']]);

        return $this->checkIdnumber($idnumber, $this->executeMethod);
    }

    /**
     * @param string $idnumber
     * @param string $mode
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected function checkIdnumber($idnumber, $mode)
    {
        switch ($mode) {
            case 'create':
                if (!CategoryEntity::isExists($idnumber)) {
                    return true;
                }

                //throw new UpdateWizardException(sprintf('Category with idnumber: %s, already exists', $idnumber));
                throw new UpdateWizardException(null, sprintf('Category with idnumber: %s, already exists', $idnumber));
                break;

            case 'update':
                if (CategoryEntity::isExists($idnumber)) {
                    return true;
                }

                //throw new UpdateWizardException(sprintf('Category with idnumber: %s, not exists', $idnumber));
                throw new UpdateWizardException(null, sprintf('Category with idnumber: %s, not exists', $idnumber));
                break;

            default:
                //throw new UpdateWizardException('Unsupported Mode', 'error');
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
    protected function getParentId($idnumber)
    {
        if (CategoryEntity::isExists($idnumber)) {
            return CategoryEntity::getCategoryId($idnumber);
        }

        //throw new UpdateWizardException(new lang_string('could_not_resolve_category_by_idnumber', 'tool_updatewizard'));
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
        $data['parent'] = $this->getParentId($data['parent_idnumber']);

        unset($data['parent_idnumber']);

        return CategoryEntity::create($data);
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
        $idnumber = $data['idnumber'];
        $category = CategoryEntity::resolveCategoryByIdnumber($idnumber);

        if (array_key_exists('parent_idnumber', $data)) {
            $parentIdnumber = $data['parent_idnumber'];

            unset($data['parent_idnumber']);

            if ($idnumber === $parentIdnumber) {
                //throw new UpdateWizardException('cannotmovecategory', 'error');
                throw new UpdateWizardException(null, 'cannotmovecategory');
            }

            $data['parent'] = $this->getParentId($parentIdnumber);
        }

        if (array_key_exists('new_idnumber', $data)) {
            $idnumber = $data['new_idnumber'];

            $this->checkIdnumber($idnumber, 'create');

            $data['idnumber'] = $idnumber;

            unset($data['new_idnumber']);
        }

        return $category->update($data);
    }
}

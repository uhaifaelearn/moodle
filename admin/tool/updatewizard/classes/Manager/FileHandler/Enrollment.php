<?php

namespace tool_updatewizard\Manager\FileHandler;

use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Entity\Enrollment as Enroll;

/**
 * Class Enrollment
 *
 * @package tool_updatewizard\Manager\FileHandler
 */
class Enrollment extends FileHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkRowData(array $rowData, array $headerRow)
    {
        list ($action, $role, $user, $course) = $this->getRowData($rowData, $headerRow);

        $this->checkAction($action, $role, $user, $course);

        return true;
    }

    /**
     * @param array $rowData
     * @param array $headerRow
     *
     * @return array
     */
    protected function getRowData(array $rowData, array $headerRow)
    {
        return [
            $this->cleanInput($rowData[$headerRow['action']]),
            $this->cleanInput($rowData[$headerRow['role']]),
            $this->cleanInput($rowData[$headerRow['user']]),
            $this->cleanInput($rowData[$headerRow['course']]),
        ];
    }

    /**
     * @param string $action
     * @param string $role
     * @param string $user
     * @param string $course
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected function checkAction($action, $role, $user, $course)
    {
        $roleData       = Enroll::getRoleData($role);
        $allowedActions = $roleData->allowedActions;

        if (!in_array($action, $allowedActions)) {
            throw new UpdateWizardException(null, 'Unsupported Enroll Action');
        }

        switch ($action) {
            case 'add':
                if (!Enroll::isUserEnrolled($user, $course, $roleData->id)) {
                    return true;
                }

                throw new UpdateWizardException(
                    null,
                    sprintf('User with idnumber: %s, already registered to Course with idnumber: %s', $user, $course)
                );
                break;

            case 'del':
				
                if (Enroll::isUserEnrolled($user, $course, $roleData->id)) {
                    return true;
                }

                throw new UpdateWizardException(
                    null,
                    sprintf('User with idnumber: %s, not actively registered to Course with idnumber: %s', $user, $course)
                );
                break;

            default:
                throw new UpdateWizardException(null, 'Unsupported Enroll Action');
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    protected function enroll(array $data)
    {
        $action = $data['action'];

        unset($data['action']);

        $roleData       = Enroll::getRoleData($data['role']);
        $allowedActions = $roleData->allowedActions;

        $data['role']   = $roleData->id;
        $data['user']   = Enroll::getUserId($data['user']);
        $data['course'] = Enroll::getCourseId($data['course']);

        if (!in_array($action, $allowedActions)) {
            throw new UpdateWizardException(null, 'Unsupported Enrol Action');
        }

        $enroll = new Enroll();

        return $enroll->$action($data);
    }
}

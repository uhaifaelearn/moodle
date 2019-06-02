<?php

namespace tool_updatewizard\Manager\FileHandler;

//use lang_string;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Entity\User as UserEntity;

/**
 * Class User
 *
 * @package tool_updatewizard\Manager\FileHandler
 */
class User extends FileHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkRowData(array $rowData, array $headerRow)
    {
        $mode           = $this->executeMethod;
        $dataField      = '';
        $checkHeader    = [
            'email'     => PARAM_EMAIL,
            'username'  => PARAM_USERNAME,
            'idnumber'  => PARAM_TEXT,
        ];

        foreach ($checkHeader as $checkField => $type) {
            if (
                array_key_exists($checkField, $headerRow)
                && '' !== $data = $rowData[$headerRow[$checkField]]
            ) {
                $data = $this->cleanInput($data, $type);

                $this->checkByUsernameOrIdnumberOrEmail($checkField, $data, $mode);

                $dataField = $checkField;
            }
        }

        if ('' === $dataField || 'email' === $dataField) {
            throw new UpdateWizardException(null, 'Cannot Resolve User without idnumber or shortname');
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
    protected function checkByUsernameOrIdnumberOrEmail($dataField, $data, $mode)
    {
        switch ($mode) {
            case 'create':
                if (!UserEntity::isExists($dataField, $data)) {
                    return true;
                }

                throw new UpdateWizardException(null, sprintf('User with %s: %s, already exists', $dataField, $data));
                break;

            case 'update':
                if ('email' === $dataField || UserEntity::isExists($dataField, $data)) {
                    return true;
                }

                throw new UpdateWizardException(null, sprintf('User with %s: %s, not exists', $dataField, $data));
                break;

            default:
                throw new UpdateWizardException(null, 'Unsupported Mode');
        }
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
        return UserEntity::create($data);
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
        $checkUserId    = [];
        $user           = null;

        foreach (['idnumber', 'username'] as $userField) {
            if (array_key_exists($userField, $data) && '' !== $data[$userField]) {
                $user = UserEntity::resolveUser($userField, $data[$userField]);

                $checkUserId[$userField] = $user->id;
            }
        }

        if (
            array_key_exists('idnumber', $checkUserId)
            && array_key_exists('username', $checkUserId)
            && $checkUserId['idnumber'] !== $checkUserId['username']
        ) {
            throw new UpdateWizardException(
                null,
                sprintf(
                    'User with idnumber: %s is not Identical to the User with username: %s',
                    $data['idnumber'],
                    $data['username']
                )
            );
        }

        $oldData = [];

        if (
            array_key_exists('email', $data)
            && '' !== $data['email']
            && $user->email !== $data['email']
        ) {
            if (UserEntity::isExists('email', $data['email'])) {
                $checkUser = UserEntity::resolveUser('email', $data['email']);

                if ($checkUser->id !== $user->id) {
                    throw new UpdateWizardException(
                        null,
                        sprintf("Cannot update User's email address to: %s. Another User already has it.", $data['email'])
                    );
                }
            } else {
                $oldData['email'] = $user->email;
            }
        }

        foreach (['idnumber', 'username'] as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }

            $newField = 'new_'.$field;

            if (array_key_exists($newField, $data)) {
                $newData = $data[$newField];

                unset($data[$newField]);

                if ('' !== $newData) {
                    try {
                        $this->checkByUsernameOrIdnumberOrEmail($field, $newData, 'create');
                    } catch (UpdateWizardException $ex) {
                        throw new UpdateWizardException(null, 'cannotmovecategory');
                    }

                    $data[$field] = $newData;

                    if ($user->$field !== $newData) {
                        $oldData[$field] = $user->$field;
                    }
                }
            }
        }

        return UserEntity::update($user, $data, $oldData);
    }
}

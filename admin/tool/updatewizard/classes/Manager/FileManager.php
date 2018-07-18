<?php

namespace tool_updatewizard\Manager;

use ArrayObject;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Globals;
use tool_updatewizard\Manager\FileHandler\HandlerFactory;
use tool_updatewizard\Manager\FileHandler\FileHandlerHelper;

/**
 * Class FileManager
 *
 * @package tool_updatewizard\Manager
 */
class FileManager extends ArrayObject
{
    /**
     * FileManager constructor.
     *
     * @param TraceManager  $traceManager
     * @param array         $orderedFilesHandlers
     *
     * @throws UpdateWizardException
     */
    public function __construct(TraceManager $traceManager, array $orderedFilesHandlers)
    {
        $handledFiles   = [];

        $cfg            = Globals::getCfg();

        $baseDir        = $cfg->dataroot;
        $inputDir       = $baseDir.'/update_wizard';

        if ($this->checkInputDirExists(
            $baseDir,
            $inputDir,
            $cfg->umaskpermissions,
            $cfg->directorypermissions
        )) {
            $inputDir .= '/';

            $handledFiles = $this->getHandledFiles(
                $traceManager,
                $orderedFilesHandlers,
                FileHandlerHelper::getInputFiles($inputDir),
                $inputDir
            );
        }

        parent::__construct($handledFiles);
    }

    /**
     * Function to check if a directory exists and by default create it if not exists.
     *
     * Previously this was accepting paths only from dataroot, but we now allow
     * files outside of dataroot if you supply custom paths for some settings in config.php.
     * This function does not verify that the directory is writable.
     *
     * NOTE: this function uses current file stat cache,
     *       please use clearstatcache() before this if you expect that the
     *       directories may have been removed recently from a different request.
     *
     * @param string $baseDir               absolute directory path of dataroot (moodledata)
     * @param string $inputDir              absolute directory path of UpdateWizard input (moodledata/update_wizard)
     * @param string $umaskPermissions
     * @param string $directoryPermissions
     *
     * @return boolean true if directory exists or created, false otherwise
     *
     * @throws UpdateWizardException
     */
    protected function checkInputDirExists($baseDir, $inputDir, $umaskPermissions, $directoryPermissions)
    {
        if (is_dir($inputDir)) {
            if (!is_writable($inputDir)) {
                throw new UpdateWizardException(null, sprintf('%s is not writable.', $inputDir));
            }

            return true;
        }

        if (!is_dir($baseDir) || !is_writable($baseDir)) {
            // The basedir is not writable. We will not be able to create the child directory.
            throw new UpdateWizardException(
                null,
                sprintf('%s is not writable. Unable to create a input directory within it.', $baseDir)
            );
        }

        protect_directory($baseDir);

        if (file_exists($inputDir)) {
            throw new UpdateWizardException(
                null,
                sprintf('%s directory can not be created, file with the same name already exists.', $inputDir)
            );
        }

        return $this->createInputDirExists($inputDir, $umaskPermissions, $directoryPermissions);
    }

    /**
     * Function to create a directory if not exists.
     *
     * Previously this was accepting paths only from dataroot, but we now allow
     * files outside of dataroot if you supply custom paths for some settings in config.php.
     * This function does not verify that the directory is writable.
     *
     * NOTE: this function uses current file stat cache,
     *       please use clearstatcache() before this if you expect that the
     *       directories may have been removed recently from a different request.
     *
     * @param string $inputDir              absolute directory path of moodledata/update_wizard
     * @param string $umaskPermissions
     * @param string $directoryPermissions
     *
     * @return boolean true if directory exists or created, false otherwise
     *
     * @throws UpdateWizardException
     */
    protected function createInputDirExists($inputDir, $umaskPermissions, $directoryPermissions)
    {
        umask($umaskPermissions);

        if (!@mkdir($inputDir, $directoryPermissions)) {
            clearstatcache();

            // There might be a race condition when creating directory.
            if (!is_dir($inputDir)) {
                throw new UpdateWizardException(null, sprintf('%s can not be created, check permissions.', $inputDir));
            }
        }

        if (!is_writable($inputDir)) {
            throw new UpdateWizardException(null, sprintf('%s is not writable, check permissions.', $inputDir));
        }

        protect_directory($inputDir);

        return false;
    }

    /**
     * @param TraceManager  $traceManager
     * @param array         $orderedFilesHandlers
     * @param array         $inputFiles
     * @param string        $inputDir
     *
     * @return array
     */
    protected function getHandledFiles(
        TraceManager $traceManager,
        array $orderedFilesHandlers,
        array $inputFiles,
        $inputDir
    ) {
        if ([] === $inputFiles) {
            return [];
        }

        $handledFiles = [];

        foreach ($orderedFilesHandlers as $handlerKey => $handlerData) {
            $handledFileName = $inputDir.$handlerData['file'];

            if (in_array($handledFileName, $inputFiles)) {
                $traceManager->output($handlerData['name']);

                $handlerData['file']        = $handledFileName;

                $handledFiles[$handlerKey]  = HandlerFactory::createHandlerInstance($handlerData);
            }
        }

        return $handledFiles;
    }
}

<?php

namespace tool_updatewizard\Manager;

use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Manager\FileHandler\FileHandler;

/**
 * Class ProcessManager
 *
 * @package tool_updatewizard\Manager
 */
class ProcessManager
{
    /**
     * @var TraceManager
     */
    protected $traceManager;
    
    protected $fileManager;

    /**
     * ProcessManager constructor.
     *
     * @param TraceManager $traceManager
     * @param FileManager  $fileManager
     */
    public function __construct(TraceManager $traceManager, FileManager $fileManager)
    {
        $this->traceManager = $traceManager;
        $this->fileManager = $fileManager;
    }

    /**
     * @param TraceManager $traceManager
     */
    public function setTraceManager(TraceManager $traceManager)
    {
        $this->traceManager = $traceManager;
    }

    /**
     * @return TraceManager
     */
    public function getTraceManager()
    {
        return $this->traceManager;
    }
    
    /**
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @return FileManager
     */
    public function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @param string    $traceName
     * @param bool      $isAdHocRun
     *
     * @return string
     */
    public function execute($traceName, $isAdHocRun)
    {
        $execMessage    = sprintf('executing %s', $isAdHocRun ? 'Task as AdHoc' : 'Task as Scheduled');

        $this->traceManager->output($execMessage);

        $handleResult   = $this->manageInputFiles();

        $this->traceManager->finished();

        return $this->traceManager->getTraceBuffer($traceName);
    }

    /**
     * @return bool
     */
    protected function manageInputFiles()
    {
        $handleResult = true;

        foreach ($this->fileManager as $fileHandler) {
            $handleResult = $this->handleFile($fileHandler) && $handleResult;
        }

        return $handleResult;
    }

    /**
     * @param FileHandler $fileHandler
     *
     * @return bool
     */
    protected function handleFile(FileHandler $fileHandler)
    {
        try {
            return $fileHandler->handle($this->traceManager);
        } catch (UpdateWizardException $ex) {
            $this->traceManager->output($ex);

            return false;
        }
    }
}

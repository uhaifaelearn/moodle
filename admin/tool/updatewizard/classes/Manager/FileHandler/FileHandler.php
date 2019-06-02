<?php

namespace tool_updatewizard\Manager\FileHandler;

use coding_exception;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Common\Exception\EncodingConversionException;
use tool_updatewizard\Exception\UpdateWizardException;
use tool_updatewizard\Manager\TraceManager;
use tool_updatewizard\Manager\Reader\Reader;
use tool_updatewizard\Manager\Reader\RowIterator;

/**
 * Class FileHandler
 *
 * @package tool_updatewizard\Manager\FileHandler
 */
abstract class FileHandler extends Reader
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var array
     */
    protected $action;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $executeMethod;

    /**
     * @var array
     */
    protected $mandatoryColumns;

    /**
     * @var array
     */
    protected $mandatoryOptionalColumns;

    /**
     * @var array
     */
    protected $optionalColumns;

    /**
     * @var array
     */
    protected $headerRow;

    /**
     * @var int
     */
    protected $headerSize;

    /**
     * FileHandler constructor.
     *
     * @param array $handlerData
     */
    public function __construct(array $handlerData)
    {
        parent::__construct();

        $this->fileName         = $handlerData['file'];
        $this->entity           = $handlerData['class'];
        $this->action           = $handlerData['action'];
        $this->mode             = $handlerData['action']['mode'];

        // get first word (until _)
        $this->executeMethod    = $executeMethod = preg_replace('/_.*$/', '', $this->mode);

        $this->setHeaderColumnsType($handlerData);

        if (array_key_exists('fieldEnclosure', $handlerData)) {
            $this->setFieldEnclosure($handlerData['fieldEnclosure']);
        }
    }

    /**
     * @param TraceManager $traceManager
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    public function handle(TraceManager $traceManager)
    {
        try {
            $this->open($this->fileName);

            $headerRow = $this->getHeaderRow();

            $this->headerRow = $this->checkHeaderRow($headerRow);

            $rowCount = 0;

            /** @var RowIterator $rowIterator */
            foreach ($this->getRowIterator() as $rowData) {
                // do stuff with the row
                $rowCount++;

                try {
                    $traceManager->output('Row: '.$rowCount, 1);

                    $this->processRowData($traceManager, $rowData);
                } catch (UpdateWizardException $ex) {
                    $traceManager->output(sprintf('Line: %d - Error: %s', $rowCount, $ex));
                }
            }

            $this->close();

            $this->deleteFile($this->fileName);
        } catch (IOException $ex) {
            throw new UpdateWizardException($ex);
        } catch (UnsupportedTypeException $ex) {
            throw new UpdateWizardException($ex);
        } catch (ReaderNotOpenedException $ex) {
            throw new UpdateWizardException($ex);
        } catch (EncodingConversionException $ex) {
            throw new UpdateWizardException($ex);
        }

        return true;
    }

    /**
     * @param array $handlerData
     */
    protected function setHeaderColumnsType(array $handlerData)
    {
        $headerColumnsType = ['mandatoryColumns', 'mandatoryOptionalColumns', 'optionalColumns'];
        
        foreach ($headerColumnsType as $columnsType) {
            $this->$columnsType = array_key_exists($columnsType, $handlerData) ? $handlerData[$columnsType] : [];
        }
    }

    /**
     * @param array        $unCheckedHeaderRow
     *
     * @return array
     *
     * @throws UpdateWizardException
     */
    protected function checkHeaderRow(array $unCheckedHeaderRow)
    {
        if (2 > $this->headerSize = count($unCheckedHeaderRow)) {
            throw new UpdateWizardException(null, 'Too Few Information: File Header row must have at least 2 columns');
        }

        $headerRow                  = [];
        $mandatoryOptionalExists    = false;

        $mandatoryColumns           = $this->getMandatoryColumns();
        $mandatoryOptionalColumns   = $this->getMandatoryOptionalColumns();
        $optionalColumns            = $this->getOptionalColumns();

        foreach ($unCheckedHeaderRow as $key => $value) {
            $value = trim($value);

            if (array_key_exists($value, $headerRow)) {
                throw new UpdateWizardException(
                    null,
                    sprintf(
                        'File Header row must have unique columns: "%s" already exists (column #%d)',
                        $value,
                        $key + 1
                    )
                );
            }

            if (array_key_exists($value, $mandatoryColumns)) {
                unset($mandatoryColumns[$value]);
            } elseif (array_key_exists($value, $mandatoryOptionalColumns)) {
                $mandatoryOptionalExists = true;
                unset($mandatoryOptionalColumns[$value]);
            } elseif (array_key_exists($value, $optionalColumns)) {
                unset($optionalColumns[$value]);
            } else {
                throw new UpdateWizardException(
                    null,
                    sprintf(
                        'File Header row must have specific columns: "%s" is unknown (column #%d)',
                        $value,
                        $key + 1
                    )
                );
            }

            $headerRow[$value] = $key;
        }

        if ([] !== $mandatoryColumns || (!$mandatoryOptionalExists && [] !== $mandatoryOptionalColumns)) {
            throw new UpdateWizardException(
                null,
                'Not All Mandatory Columns been Used: File Header row must include all Mandatory Columns names.'
            );
        }

        return $headerRow;
    }

    /**
     * @return array
     */
    protected function getMandatoryColumns()
    {
        return array_flip($this->mandatoryColumns);
    }

    /**
     * @return array
     */
    protected function getMandatoryOptionalColumns()
    {
        return array_flip($this->mandatoryOptionalColumns);
    }

    /**
     * @return array
     */
    protected function getOptionalColumns()
    {
        return array_flip($this->optionalColumns);
    }

    /**
     * @param TraceManager $traceManager
     * @param array        $rowData
     *
     * @return bool
     * 
     * @throws UpdateWizardException
     */
    protected function processRowData(TraceManager $traceManager, array $rowData)
    {
        $headerRow      = $this->headerRow;

        if ($this->headerSize !== $rowSize = count($rowData)) {
            throw new UpdateWizardException(
                null,
                sprintf('Row must have %d Columns, but instead has: %d', $this->headerSize, $rowSize)
            );
        }

        $this->checkRowData($rowData, $headerRow);

        $data = [];

        foreach ($headerRow as $ColumnHeader => $columnIndex) {
            $columnData = $rowData[$columnIndex];
            $traceManager->output($ColumnHeader.': '.$columnData, 2);

            $data[$ColumnHeader] = $this->cleanInput($rowData[$columnIndex]);
        }

        return $this->{$this->executeMethod}($data);
    }

    /**
     * @param array $rowData
     * @param array $headerRow
     *
     * @return bool
     *
     * @throws UpdateWizardException
     */
    abstract protected function checkRowData(array $rowData, array $headerRow);

    /**
     * @param string $param
     * @param string $type
     *
     * @return string
     * 
     * @throws UpdateWizardException
     */
    protected function cleanInput($param, $type = PARAM_TEXT)
    {
        try {
            $param = trim(preg_replace('/\s+/', ' ', $param));

            if (clean_param($param, $type) !== $param) {
                throw new UpdateWizardException(null, '???');
            }

            return $param;
        } catch (coding_exception $ex) {
            throw new UpdateWizardException($ex);
        }
    }

    /**
     * @param string $filePath Path of the file to delete
     */
    protected function deleteFile($filePath)
    {
        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
        }
    }
}

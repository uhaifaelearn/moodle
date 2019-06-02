<?php

namespace tool_updatewizard\Manager\Reader;

use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Reader\CSV\Reader as CsvReader;
use Box\Spout\Reader\CSV\ReaderOptions;

/**
 * Class Reader
 *
 * @package tool_updatewizard\Manager
 */
class Reader extends CsvReader
{
    /**
     * @var RowIterator
     */
    protected $rowIterator;

    public function __construct()
    {
        $this->setGlobalFunctionsHelper(new GlobalFunctionsHelper());
    }

    protected function openReader($filePath)
    {
        parent::openReader($filePath);

        $this->rowIterator = $this->sheetIterator->current()->getRowIterator();
        
        // $this->rowIterator = new RowIterator($this->sheetIterator->current()->getRowIterator());
        // $this->rowIterator = new RowIterator($filePath, $this->getOptions(), $this->globalFunctionsHelper);
    }

    /**
     * @return array|null
     */
    public function getHeaderRow()
    {
        $this->rowIterator->rewind();
        return $this->rowIterator->current();
        // return $this->rowIterator->getHeaderRow();
    }

    /**
     * @return RowIterator
     */
    public function getRowIterator()
    {
        return $this->rowIterator;
    }
}

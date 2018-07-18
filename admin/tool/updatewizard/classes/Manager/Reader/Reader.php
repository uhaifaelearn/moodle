<?php

namespace tool_updatewizard\Manager\Reader;

use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Reader\CSV\Reader as CsvReader;

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

        $this->rowIterator = new RowIterator($this->sheetIterator->current()->getRowIterator());
    }

    /**
     * @return array|null
     */
    public function getHeaderRow()
    {
        return $this->rowIterator->getHeaderRow();
    }

    /**
     * @return RowIterator
     */
    public function getRowIterator()
    {
        return $this->rowIterator;
    }
}

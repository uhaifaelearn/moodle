<?php

namespace tool_updatewizard\Manager\Reader;

use Box\Spout\Reader\CSV\RowIterator as CsvRowIterator;

/**
 * Class RowIterator
 *
 * @package tool_updatewizard\Manager\Reader
 */
class RowIterator extends CsvRowIterator
{
    public function __construct(CsvRowIterator $rowIterator)
    {
        parent::__construct(
            $rowIterator->filePointer,
            $rowIterator->fieldDelimiter,
            $rowIterator->fieldEnclosure,
            $rowIterator->encoding,
            $rowIterator->inputEOLDelimiter,
            $rowIterator->globalFunctionsHelper
        );
    }

    public function getHeaderRow()
    {
        parent::rewind();

        return $this->current();
    }

    public function rewind()
    {
        parent::rewind();

        $this->next();
    }
}

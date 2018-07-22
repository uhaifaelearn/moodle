<?php

namespace tool_updatewizard\Manager\Reader;

use Box\Spout\Reader\CSV\RowIterator as CsvRowIterator;
use Box\Spout\Common\Helper\EncodingHelper;

/**
 * Class RowIterator
 *
 * @package tool_updatewizard\Manager\Reader
 */
class RowIterator extends CsvRowIterator
{
    // public function __construct(CsvRowIterator $rowIterator)
    // {
    //     parent::__construct(
    //         $rowIterator->filePointer,
    //         $rowIterator->fieldDelimiter,
    //         $rowIterator->fieldEnclosure,
    //         $rowIterator->encoding,
    //         $rowIterator->inputEOLDelimiter,
    //         $rowIterator->globalFunctionsHelper
    //     );
    // }
    
    public function __construct($filePointer, $options, $globalFunctionsHelper)
    {
        $this->filePointer = $filePointer;
        $this->fieldDelimiter = $options->getFieldDelimiter();
        $this->fieldEnclosure = $options->getFieldEnclosure();
        $this->encoding = $options->getEncoding();
        $this->inputEOLDelimiter = $options->getEndOfLineCharacter();
        $this->shouldPreserveEmptyRows = $options->shouldPreserveEmptyRows();
        $this->globalFunctionsHelper = $globalFunctionsHelper;

        $this->encodingHelper = new EncodingHelper($globalFunctionsHelper);
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

<?php

namespace gradeexport_haifa_administration_sap\Excel;

use PHPExcel;
use PHPExcel_Worksheet_PageSetup;
use PHPExcel_Style_Border;
use PHPExcel_Cell;
use PHPExcel_Style_Protection;
use MoodleExcelWorksheet;

//require_once $CFG->dirroot.'/lib/excellib.class.php';
//require_once Globals::getLibFile('excellib.class.php');

class HaifaExcelWorksheet extends MoodleExcelWorksheet
{
    /**
     * Constructs one Moodle Worksheet.
     *
     * @param string    $name       The name of the file
     * @param PHPExcel  $workbook   The internal Workbook object we are creating.
     */
    public function __construct($name, PHPExcel $workbook)
    {
        parent::__construct($name, $workbook);

        $this->worksheet->setRightToLeft(true);
        $this->worksheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $this->worksheet->freezePane('E2');

        $this->apply_row_format(0, ['bold' => 1, 'h_align' => 'center']);
        $this->setLeftBorderStyle(1);
    }

    /**
     * @param int $row
     */
    public function setLeftBorderStyle($row)
    {
        $this->worksheet->getStyle("F$row")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_DOUBLE);
    }

    /**
     * @param int $cols
     * @param int $lastRow
     */
    public function setAutoSize($cols, $lastRow)
    {
        $titleRow   = 1;
        $maxRow     = $lastRow + 100;
        $maxCol     = PHPExcel_Cell::stringFromColumnIndex($cols + 99);
        $lastCol    = PHPExcel_Cell::stringFromColumnIndex($cols - 1);
        $i          = 0;

        while ($i < $cols) {
            $col = PHPExcel_Cell::stringFromColumnIndex($i++);

            switch ($col) {
                case 'A':
                case 'B':
                case 'C':
                case 'D':
                case 'F':
                case 'G':
                    $this->worksheet->getColumnDimension($col)->setAutoSize(true);
                    break;

                case 'E':
                    $this->worksheet->getStyle("$col$titleRow")->getAlignment()->setWrapText(true);
                    $this->worksheet->getColumnDimension($col)->setWidth(50);
                    break;

                case 'H':
                    $this->worksheet->getColumnDimension($col)->setWidth(18);
                    break;

                default:
                    $this->worksheet->getStyle("$col$titleRow")->getAlignment()->setWrapText(true);
                    $this->worksheet->getColumnDimension($col)->setWidth(18);
            }
        }

        $this->worksheet->getStyle("A1:$maxCol$maxRow")->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
        $this->worksheet->getStyle("A1:$lastCol$titleRow")->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);
        $this->worksheet->getStyle("A2:C$lastRow")->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);
        $this->worksheet->getStyle("E2:G$lastRow")->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);

        $this->worksheet->getProtection()->setPassword('test');
        $this->worksheet->getProtection()->setSheet(true);
        $this->worksheet->setSelectedCell('D2');
    }
}

<?php

namespace gradeexport_haifa_administration_sap\Excel;

use core_useragent;
use PHPExcel_IOFactory;
use MoodleExcelWorkbook;
use MoodleExcelWorksheet;

//require_once $CFG->dirroot.'/lib/excellib.class.php';
//require_once Globals::getLibFile('excellib.class.php');

class HaifaExcelWorkbook extends MoodleExcelWorkbook
{
    /**
     * Constructs one Moodle Workbook.
     *
     * @param string $propertiesTitle
     * @param string $filename The name of the file
     * @param string $type file format type used to be 'Excel5 or Excel2007' but now only 'Excel2007'
     */
    public function __construct($propertiesTitle, $filename, $type = 'Excel2007')
    {
        parent::__construct($filename, $type);

        $this->setProperties($propertiesTitle);
    }

    /**
     * Create one Moodle Worksheet
     *
     * @param string $name Name of the sheet
     *
     * @return MoodleExcelWorksheet
     */
    public function add_worksheet($name)
    {
        return new HaifaExcelWorksheet($name, $this->objPHPExcel);
    }

    /**
     * Close the Moodle Workbook
     */
    public function close()
    {
        $this->objPHPExcel->setActiveSheetIndex(0);

        $filename = preg_replace('/\.xlsx?$/i', '', $this->filename);

        $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $filename = $filename.'.xlsx';

        if (is_https()) {
            // HTTPS sites - watch out for IE! KB812935 and KB316431.
            header('Cache-Control: max-age=10');
            header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            header('Pragma: ');
        } else {
            //normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            header('Pragma: no-cache');
        }

        if (core_useragent::is_ie()) {
            $filename = rawurlencode($filename);
        } else {
            $filename = s($filename);
        }

        header('Content-Type: '.$mimeType);
        header('Content-Disposition: attachment;filename="'.$filename.'"');

        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, $this->type);
        $objWriter->save('php://output');
    }

    protected function setProperties($propertiesTitle)
    {
        $properties = $this->objPHPExcel->getProperties();

        $properties->setCreator(get_string('properties_creator', 'gradeexport_haifa_administration_sap'));
        $properties->setLastModifiedBy(get_string('properties_last_modified_by', 'gradeexport_haifa_administration_sap'));
        $properties->setTitle($propertiesTitle);
        $properties->setCompany(get_string('properties_company', 'gradeexport_haifa_administration_sap'));

        $this->objPHPExcel->getDefaultStyle()->getFont()->setName('Tahoma');
        $this->objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
    }
}

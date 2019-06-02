<?php

namespace gradeexport_haifa_administration;

use stdClass;
use context_course;
use grade_helper;
use grade_export;
use grade_export_update_buffer;
use graded_users_iterator;
use gradeexport_haifa_administration\Excel\HaifaExcelWorkbook;
use gradeexport_haifa_administration\Excel\HaifaExcelWorksheet;

//require_once $CFG->dirroot.'/grade/export/lib.php';
//require_once 'grade_export_haifa_administration_form.php';

class GradeExport extends grade_export
{
    public $plugin = 'haifa_administration';

    private $worksheetTitles;

    private $courseModule;

    /**
     * Constructor should set up all the private variables ready to be pulled
     *
     * @param object    $course
     * @param int       $groupId    id of selected group, 0 means all
     * @param stdClass  $formData   The validated data from the grade export form.
     */
    public function __construct($course, $groupId, $formData)
    {
        parent::__construct($course, $groupId, $formData);

        // Overrides.
        $this->usercustomfields = true;
        $this->worksheetTitles  = $formData->worksheetTitles;
    }

    /**
     * To be implemented by child classes
     */
    public function print_grades()
    {
        $exportTracking     = $this->track_exports();

        // Calculate file name
        $shortname          = format_string($this->course->shortname, true, ['context' => context_course::instance($this->course->id)]);

        $downloadFilename   = $this->fixWorksheetInfo($shortname, $this->course->fullname, $this->course->idnumber);

        // Creating a workbook
        $workbook           = new HaifaExcelWorkbook($this->worksheetTitles['properties_title'], '-');

        // Sending HTTP headers
        $workbook->send($downloadFilename);

        // Adding the worksheet
        /** @var HaifaExcelWorksheet $myXls */
        $myXls              = $workbook->add_worksheet($this->worksheetTitles['worksheet_name']);

        // Print names of all the fields
        $extraFields        = $this->getWorksheetExtraFields();

        foreach ($extraFields as $id => $field) {
            $myXls->write_string(0, $id, $field->fullname);
        }

        $pos                = count($extraFields);

        foreach ($this->columns as $gradeItem) {
            foreach ($this->displaytype as $gradedIsPlayName => $gradedIsPlayConst) {
                $myXls->write_string(0, $pos++, $this->format_column_name($gradeItem, false, $gradedIsPlayName));
            }
        }

        // Print all the lines of data.
        $i                          = 0;
        $gradeExportUpdateBuffer    = new grade_export_update_buffer();
        $gradedUsersIterator        = new graded_users_iterator($this->course, $this->columns, $this->groupid, 'idnumber', 'ASC', null, null);

        $gradedUsersIterator->require_active_enrolment($this->onlyactive);
        $gradedUsersIterator->allow_user_custom_fields($this->usercustomfields);
        $gradedUsersIterator->init();

        while ($userData = $gradedUsersIterator->next_user()) {
            $i++;
            $user   = $userData->user;

            foreach ($extraFields as $id => $field) {
                $format     = [
                    'h_align'   => 'center',
                ];

                switch ($id) {
                    case 0:
                        $fieldValue         = $this->courseModule;
                        $myXls->write_string($i, $id, $fieldValue, $format);
                        break;

                    case 2:
                        $fieldValue         = grade_helper::get_user_field_value($user, $field);
                        $fieldValue         = substr('000'.$fieldValue, -12);
                        $myXls->write_string($i, $id, $fieldValue, $format);
                        break;

                    case 5:
                        $myXls->setLeftBorderStyle($i + 1);
                        // no break
                    case 6:
                        $fieldValue         = grade_helper::get_user_field_value($user, $field);
                        $shortname          = $field->shortname;
                        $field->shortname   = $field->shortname.'phonetic';
                        $fieldValue         = grade_helper::get_user_field_value($user, $field).' ('.$fieldValue.')';
                        $field->shortname   = $shortname;
                        $myXls->write_string($i, $id, $fieldValue);
                        break;

                    case 3:
                    case 7:
                        $format['bg_color'] = $id === 3 ? 'green' : 'yellow';
                        // no break
                    case 1:
                    case 4:
                    $myXls->write_blank($i, $id, $format);
                }
            }

            $j      = count($extraFields);

            foreach ($userData->grades as $itemId => $grade) {
                if ($exportTracking) {
                    $status     = $gradeExportUpdateBuffer->track($grade);
                }

                $format     = array(
                    'h_align'   => 'center',
                );

                foreach ($this->displaytype as $gradedIsPlayConst) {
                    $gradeStr   = $this->format_grade($grade, $gradedIsPlayConst);

                    if ($j === $pos - 1) {
                        $format['bg_color'] = 'yellow';
                    }

                    if (is_numeric($gradeStr)) {
                        $myXls->write_number($i, $j++, $gradeStr, $format);
                    } else {
                        //$myxls->write_string($i, $j++, $gradestr, $format);
                        $myXls->write_blank($i, $j++, $format);
                    }
                }
            }
        }

        $myXls->setAutoSize($pos, $i + 1);

        $gradedUsersIterator->close();
        $gradeExportUpdateBuffer->close();

        // Close the workbook
        $workbook->close();

        exit;
    }

    /**
     * @param string $shortname
     * @param string $fullname
     * @param string $idnumber
     *
     * @return string
     */
    private function fixWorksheetInfo($shortname, $fullname, $idnumber)
    {
        $shortnamePattern       = '/(\d{3})-(\d{4})-([ABS])(\d{2})-(\d{4})/';
        $modulePattern          = '${1}.${2}';
        $yearPattern            = '${5}';
        $semesterPattern        = '${3}';
        $semestersInput         = ['A', 'B', 'S'];
        $semestersOutput        = ['001', '002', '003'];

        $courseModule           = preg_replace($shortnamePattern, $modulePattern, $shortname);
        $this->courseModule     = $idnumber;
        $courseYear             = preg_replace($shortnamePattern, $yearPattern, $shortname) - 1;
        $courseSemester         = str_replace($semestersInput, $semestersOutput, preg_replace($shortnamePattern, $semesterPattern, $shortname));

        $titleData = [
            'year'          => $courseYear,
            'semester'      => $courseSemester,
            'grade_type'    => $this->worksheetTitles['col_5_title'],
            'course_module' => $courseModule,
            'course_name'   => trim(substr($fullname, 0, -12)),
            'grade_title'   => $this->worksheetTitles['properties_title'],
        ];

        $this->worksheetTitles['col_5_title']       = get_string('col_5_title', 'gradeexport_haifa_administration', $titleData);
        $this->worksheetTitles['properties_title']  = get_string('properties_title', 'gradeexport_haifa_administration', $titleData);

        return clean_filename("$courseModule-$courseYear-$courseSemester.xlsx");
    }

    /**
     * @return array
     */
    private function getWorksheetExtraFields()
    {
        $fields = [];

        $colTitlePattern    = '/col_(\d)_title/';
        $colIdPattern       = '${1}';

        foreach ($this->worksheetTitles as $colName => $colTitle) {
            if (strlen($colId = preg_replace($colTitlePattern, $colIdPattern, $colName)) === 1) {
                $obj = new stdClass();

                $obj->customid  = get_string("col_{$colId}_id", 'gradeexport_haifa_administration');
                $obj->shortname = get_string("col_{$colId}_data", 'gradeexport_haifa_administration');
                $obj->fullname  = $colTitle;

                $fields[$colId - 1] = $obj;
            }
        }

        return $fields;
    }
}

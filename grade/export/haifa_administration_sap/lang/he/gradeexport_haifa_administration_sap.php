<?php

$string['pluginname']                   = 'ייצוא למערכת הציונים – מינה';
$string['syncronization']                   = 'סינכרון';
$string['timeexported']                 = 'האחרון שהורד מקורס זה';
$string['haifa_administration_sap:view']    = 'השתמש ביצוא ציונים עבור מינהל תלמידים';
$string['haifa_administration_sap:publish'] = 'פרסם יצוא ציונים עבור מינהל תלמידים';

$string['export']                       = 'יצוא בפורמט של <strong><u>'.$string['pluginname'].'</u></strong>';
$string['report_final_grades_after']    = 'דיווח ציונים סופיים לאחר:';
$string['grade_option']                 = 'בחרו מועד: א, ב, מיוחד או עבודה';

$string['year']                         = 'שנה';
$string['semester']                     = 'סמסטר';
$string['course']                       = 'קורס';
$string['grade']                        = 'ציון';
$string['final_grade']                  = "$string[grade] סופי";
$string['test']                         = 'מבחן';
$string['moed']                         = 'מועד';

$string['name']                         = 'שם';
$string['last_name']                    = "$string[name] משפחה";
$string['first_name']                   = "$string[name] פרטי";

$string['title_year']                   = "$string[year]: ".'{$a->year}';
$string['title_semester']               = "$string[semester]: ".'{$a->semester}';
$string['title_grade_type']             = '{$a->grade_type}';
$string['title_course_module']          = "$string[course]: ".'{$a->course_module}';
$string['title_course_name']            = '{$a->course_name}';
$string['final_grade_title']            = '{$a->grade_title}';

$string['worksheet_name']               = 'ציונים סופיים לאחר {$a}';

$string['col_1_title']                  = $string['course'];
$string['col_2_title']                  = 'אובייקט';
$string['col_3_title']                  = 'מספר סטודנט';
$string['col_4_title']                  = $string['final_grade'];

$string['col_5_title']                  = "$string[title_year] $string[title_semester] $string[title_grade_type] $string[title_course_module]\n$string[title_course_name]";

$string['col_6_title']                  = $string['last_name'];
$string['col_7_title']                  = $string['first_name'];

$string['col_8_title']                  = '{$a}';

$string['col_1_id']                     = '';
$string['col_2_id']                     = '';
$string['col_3_id']                     = '0';
$string['col_4_id']                     = '';
$string['col_5_id']                     = '';
$string['col_6_id']                     = '0';
$string['col_7_id']                     = '0';
$string['col_8_id']                     = '';

$string['col_1_data']                   = 'course_sap_module';
$string['col_2_data']                   = '';
$string['col_3_data']                   = 'idnumber';
$string['col_4_data']                   = 'final_grade';
$string['col_5_data']                   = '';
$string['col_6_data']                   = 'lastname';
$string['col_7_data']                   = 'firstname';
$string['col_8_data']                   = '';

$string['moed_a_sign']                  = 'א';
$string['moed_b_sign']                  = 'ב';
$string['moed_special_sign']            = 'מיוחד';

$string['final_paper']                  = 'עבודה מסכמת';
$string['moed_a']                       = "$string[moed] $string[moed_a_sign]";
$string['moed_b']                       = "$string[moed] $string[moed_b_sign]";
$string['moed_special']                 = "$string[moed] $string[moed_special_sign]";

$string['moed_a_test']                  = "$string[test] $string[moed_a]";
$string['moed_b_test']                  = "$string[test] $string[moed_b]";
$string['moed_special_test']            = "$string[test] $string[moed_special]";

$string['final_paper_option']           = $string['final_paper'];
$string['moed_a_option']                = "$string[moed_a]'";
$string['moed_b_option']                = "$string[moed_b]'";
$string['moed_special_option']          = $string['moed_special'];

$string['final_paper_grade_type']       = $string['final_grade'];
$string['moed_a_grade_type']            = "$string[moed]: $string[moed_a_sign]";
$string['moed_b_grade_type']            = "$string[moed]: $string[moed_b_sign]";
$string['moed_special_grade_type']      = "$string[moed]: $string[moed_special_sign]";

$string['final_paper_grade_title']      = "$string[grade] $string[final_paper]";
$string['moed_a_grade_title']           = "$string[grade] $string[moed_a_test]";
$string['moed_b_grade_title']           = "$string[grade] $string[moed_b_test]";
$string['moed_special_grade_title']     = "$string[grade] $string[moed_special_test]";

$string['properties_creator']           = 'היחידה להוראה נתמכת מחשב - אשף עדכון וניהול הקורסים';
$string['properties_last_modified_by']  = 'היחידה להוראה נתמכת מחשב - אשף עדכון וניהול הקורסים';
$string['properties_title']             = "ציונים סופיים בקורס $string[title_course_name] לאחר $string[final_grade_title]";
$string['properties_company']           = 'אוניברסיטת חיפה';

$string['No_pass_grade']           = 'No pass grade';
$string['grade_option2']           = 'ציון לדיווח';
$string['grade_option3']           = 'ציון';
$string['grade_required']           = 'נא לבחור מועד';
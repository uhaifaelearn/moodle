<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
  * A Moodle block for creating Configurable Reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

  function cr_print_js_function(){
	?>
		<script type="text/javascript">
			function printDiv(id){
				var cdiv, tmpw;

				cdiv = document.getElementById(id);
				tmpw = window.open(" ","Print");

				tmpw.document.open();
				tmpw.document.write('<html><body>');
				tmpw.document.write(cdiv.innerHTML);
				tmpw.document.write('</body></html>');
				tmpw.document.close();
				tmpw.print();
				tmpw.close();
			}
		</script>
	<?php
  }

    function cr_add_jsdatatables($cssid){

        echo html_writer::script(false, new moodle_url('/blocks/configurable_reports/js/datatables/media/js/jquery.js'));
        echo html_writer::script(false, new moodle_url('/blocks/configurable_reports/js/datatables/media/js/jquery.dataTables.min.js'));
        echo html_writer::script(false, new moodle_url('/blocks/configurable_reports/js/datatables/extras/FixedHeader/js/FixedHeader.js'));

        $script = "$(document).ready(function() {
            var oTable = $('$cssid').dataTable({
                'iDisplayLength': 25,
		        'aLengthMenu': [[25, 50, 100, 250, -1], [25, 50, 100, 250, 'All']],
                'bAutoWidth': false,
                'sPaginationType': 'full_numbers',
//                'sScrollX': '100%',
//                'sScrollXInner': '110%',
//                'bScrollCollapse': true
            });
            new FixedHeader( oTable );
        } );";
        echo html_writer::script($script);
    }

  function cr_add_jsordering($cssid){
	global $DB, $CFG, $OUTPUT;
    echo html_writer::script(false, new moodle_url('/blocks/configurable_reports/js/jquery-latest.js'));
    echo html_writer::script(false, new moodle_url('/blocks/configurable_reports/js/jquery.tablesorter.min.js'));
    $script = '$(document).ready(function() {
        // call the tablesorter plugin
        $("'.$cssid.'").tablesorter();
    });';
    echo html_writer::script($script);
	?>

		<style type="text/css">
		<?php echo $cssid; ?> th.header{
			background-image:url(<?php echo $OUTPUT->pix_url('normal', 'block_configurable_reports'); ?>);
			background-position:right center;
			background-repeat:no-repeat;
			cursor:pointer;
		}

		<?php echo $cssid; ?> th.headerSortUp{
		 background-image:url(<?php echo $OUTPUT->pix_url('asc', 'block_configurable_reports');?>);
		}

		<?php echo $cssid; ?> th.headerSortDown{
		 background-image:url(<?php echo $OUTPUT->pix_url('desc', 'block_configurable_reports');?>);
		}
		</style>
	<?php
  }

  function urlencode_recursive($var) {
    if (is_object($var)) {
        $new_var = new stdClass();
        $properties = get_object_vars($var);
        foreach($properties as $property => $value) {
            $new_var->$property = urlencode_recursive($value);
        }

    } else if (is_array($var)) {
        $new_var = array();
        foreach($var as $property => $value) {
            $new_var[$property] = urlencode_recursive($value);
        }

    } else if (is_string($var)) {
        $new_var = urlencode($var);

    } else { // nulls, integers, etc.
        $new_var = $var;
    }

    return $new_var;
  }

  function urldecode_recursive($var) {
    if (is_object($var)) {
        $new_var = new stdClass();
        $properties = get_object_vars($var);
        foreach($properties as $property => $value) {
            $new_var->$property = urldecode_recursive($value);
        }

    } else if(is_array($var)) {
        $new_var = array();
        foreach($var as $property => $value) {
            $new_var[$property] = urldecode_recursive($value);
        }

    } else if(is_string($var)) {
        $new_var = urldecode($var);

    } else {
        $new_var = $var;
    }

    return $new_var;
}

function cr_get_my_reports($courseid, $userid, $allcourses=true){
	global $DB;

	$reports = array();
    if ($courseid == SITEID){
        $context = context_system::instance();
    } else {
        $context = context_course::instance($courseid);
    }


    if(has_capability('block/configurable_reports:managereports', $context, $userid)){
		if($courseid == SITEID && $allcourses)
			$reports = $DB->get_records('block_configurable_reports',null,'name ASC');
		else
			$reports = $DB->get_records('block_configurable_reports',array('courseid' => $courseid),'name ASC');
	}
	else{
		$reports = $DB->get_records_select('block_configurable_reports','ownerid = ? AND courseid = ? ORDER BY name ASC',array($userid,$courseid));
	}
	return $reports;
}

 function cr_serialize($var){
	return serialize(urlencode_recursive($var));
 }

 function cr_unserialize($var){
	return urldecode_recursive(unserialize($var));
 }

 function cr_check_report_permissions($report, $userid, $context){
	global $DB, $CFG;

	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

	$classn = 'report_'.$report->type;
	$classi = new $classn($report->id);
	return $classi->check_permissions($userid, $context);
 }

 function cr_get_report_plugins($courseid){

	$pluginoptions = array();
	$context = ($courseid == SITEID)? context_system::instance() : context_course::instance($courseid);
	$plugins = get_list_of_plugins('blocks/configurable_reports/reports');

	if($plugins)
		foreach($plugins as $p){
			if($p == 'sql' && !has_capability('block/configurable_reports:managesqlreports',$context))
				continue;
			$pluginoptions[$p] = get_string('report_'.$p,'block_configurable_reports');
		}
	return $pluginoptions;
 }

 function cr_get_export_plugins(){

	$exportoptions = array();
	$plugins = get_list_of_plugins('blocks/configurable_reports/export');

	if($plugins)
		foreach($plugins as $p){
			$pluginoptions[$p] = get_string('export_'.$p,'block_configurable_reports');
		}
	return $pluginoptions;
 }

 function cr_print_table($table, $return=false) {
    global $COURSE;

    $output = '';

    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) {
                $align[$key] = ' text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
            } else {
                $align[$key] = '';
            }
        }
    }
    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width:'. $ss .';';
            } else {
                $size[$key] = '';
            }
        }
    }
    if (isset($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $wrap[$key] = ' white-space:nowrap;';
            } else {
                $wrap[$key] = '';
            }
        }
    }

    if (empty($table->width)) {
        $table->width = '80%';
    }

    if (empty($table->tablealign)) {
        $table->tablealign = 'center';
    }

    if (!isset($table->cellpadding)) {
        $table->cellpadding = '5';
    }

    if (!isset($table->cellspacing)) {
        $table->cellspacing = '1';
    }

    if (empty($table->class)) {
        $table->class = 'generaltable';
    }

    $tableid = empty($table->id) ? '' : 'id="'.$table->id.'"';
    $output .= '<form action="send_emails.php" method="post" id="sendemail">';
    $output .= '<table width="'.$table->width.'" ';
    if (!empty($table->summary)) {
        $output .= " summary=\"$table->summary\"";
    }
    $output .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" class=\"$table->class boxalign$table->tablealign\" $tableid rules=\"all\">\n";

    $countcols = 0;
    $isuserid = -1;

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $output .= '<thead><tr>';
        $keys=array_keys($table->head);
        $lastkey = end($keys);
        foreach ($table->head as $key => $heading) {
            if ($heading == 'sendemail') {
                $isuserid = $key;
            }
            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if ($key == $lastkey) {
                $extraclass = ' lastcol';
            } else {
                $extraclass = '';
            }

            $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:normal;" class="header c'.$key.$extraclass.'" scope="col">'. $heading .'</th>';
        }
        $output .= '</tr></thead>'."\n";
    }

    if (!empty($table->data)) {
        $oddeven = 1;
        $keys=array_keys($table->data);
        $lastrowkey = end($keys);
        foreach ($table->data as $key => $row) {
            $oddeven = $oddeven ? 0 : 1;
            if (!isset($table->rowclass[$key])) {
                $table->rowclass[$key] = '';
            }
            if ($key == $lastrowkey) {
                $table->rowclass[$key] .= ' lastrow';
            }
            $output .= '<tr class="r'.$oddeven.' '.$table->rowclass[$key].'">'."\n";
            if ($row == 'hr' and $countcols) {
                $output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
            } else {  /// it's a normal row of data
                $keys2=array_keys($row);
                $lastkey = end($keys2);
                foreach ($row as $key => $item) {
                    if (!isset($size[$key])) {
                        $size[$key] = '';
                    }
                    if (!isset($align[$key])) {
                        $align[$key] = '';
                    }
                    if (!isset($wrap[$key])) {
                        $wrap[$key] = '';
                    }
                    if ($key == $lastkey) {
                      $extraclass = ' lastcol';
                    } else {
                      $extraclass = '';
                    }
                    if ($key == $isuserid) {
                        $output .= '<td style="'. $align[$key].$size[$key].$wrap[$key] .'" class="cell c'.$key.$extraclass.'"><input name="userids[]" type="checkbox" value="'.$item.'"></td>';
                    } else {
                        $output .= '<td style="'. $align[$key].$size[$key].$wrap[$key] .'" class="cell c'.$key.$extraclass.'">'. $item .'</td>';
                    }

                }
            }
            $output .= '</tr>'."\n";
        }
    }
    $output .= '</table>'."\n";
    $output .= '<input type="hidden" name="courseid" value="'.$COURSE->id.'">';
    if ($isuserid != -1) {
        $output .= '<input type="submit" value="'.get_string('sendemails','block_configurable_reports').'">';
    }
    $output .= '</form>';

    if ($return) {
        return $output;
    }

    echo $output;
    return true;
}

function table_to_excel($filename,$table){
    global $DB, $CFG;

    require_once($CFG->dirroot.'/lib/excellib.class.php');


    if (!empty($table->head)) {
        $countcols = count($table->head);
        $keys=array_keys($table->head);
        $lastkey = end($keys);
        foreach ($table->head as $key => $heading) {
                $matrix[0][$key] = str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($heading))));
        }
    }

    if (!empty($table->data)) {
        foreach ($table->data as $rkey => $row) {
            foreach ($row as $key => $item) {
                $matrix[$rkey + 1][$key] = str_replace("\n",' ',htmlspecialchars_decode(strip_tags(nl2br($item))));
            }
        }
    }

    $downloadfilename = clean_filename($filename);
    /// Creating a workbook
    $workbook = new MoodleExcelWorkbook("-");
    /// Sending HTTP headers
    $workbook->send($downloadfilename);
    /// Adding the worksheet
    $myxls =& $workbook->add_worksheet($filename);

    foreach($matrix as $ri=>$col){
        foreach($col as $ci=>$cv){
            $myxls->write_string($ri,$ci,$cv);
        }
    }

    $workbook->close();
    exit;
}



function is_mysqlserver_loaded(){
    global $CFG, $COURSE, $PAGE;

    // Use a custom $remoteDB (and not current system's $DB)
    // todo: major security issue
    $remoteDBhost = get_config('block_configurable_reports', 'dbhost');
    if (empty($remoteDBhost)) {
        $remoteDBhost = $CFG->dbhost;
    }
    $remoteDBuser = get_config('block_configurable_reports', 'dbuser');
    if (empty($remoteDBuser)) {
        $remoteDBuser = $CFG->dbuser;
    }
    $remoteDBpass = get_config('block_configurable_reports', 'dbpass');
    if (empty($remoteDBpass)) {
        $remoteDBpass = $CFG->dbpass;
    }

    // Test for MYSQL server load before running query.
    // TODO: using direct php mysql calls, should be an issue with other DBs
    $link = mysqli_connect($remoteDBhost, $remoteDBuser, $remoteDBpass);
    //$status = explode('  ', mysql_stat($link));

    // Innodb_row_lock_current_waits
    //$sql_dbload = "SHOW GLOBAL STATUS WHERE Variable_name = 'Connections'";
    //$sql_dbload = "SHOW GLOBAL STATUS WHERE Variable_name = 'Threads_running'";
    $sql_dbload = "SHOW GLOBAL STATUS WHERE Variable_name = 'Threads_connected'";

    $serverload = get_config('block_configurable_reports', 'serverload');

    $result = $link->query($sql_dbload);
    $row = mysqli_fetch_array($result);
    //echo $row['Variable_name'] . ' = ' . $row['Value'] . "\n";
    if ( $row['Value'] > $serverload ) {
        $PAGE->set_pagelayout('incourse');
        notice(get_string('serverloadnotice', 'block_configurable_reports', $row['Value']),
            "$CFG->wwwroot/course/view.php?id={$COURSE->id}");
    }

    return false;
}
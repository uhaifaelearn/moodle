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

    require_once("../../config.php");
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");

	$id = optional_param('id', 0, PARAM_INT);
	$download = optional_param('download',false,PARAM_BOOL);
	$format = optional_param('format','',PARAM_ALPHA);
    $courseid = optional_param('courseid', null, PARAM_INT);
    $alias = optional_param('alias','',PARAM_ALPHA);

    if ($id == 0 AND $alias == '')
        print_error("Please supply report ID or Alias to run the report");

    if (!empty($alias)) {
        if(! $report = $DB->get_record('block_configurable_reports',array('alias' => $alias)))
            print_error('reportdoesnotexists','block_configurable_reports');

    } else {

        if(! $report = $DB->get_record('block_configurable_reports',array('id' => $id)))
            print_error('reportdoesnotexists','block_configurable_reports');
    }

    // Ignore report's courseid, If we are running this report on a specific courseid
    // (For permission checks)
    if (empty($courseid))
	    $courseid = $report->courseid;

	if (! $course = $DB->get_record("course",array( "id" =>  $courseid)) ) {
		print_error("No such course id");
	}

	// Force user login in course (SITE or Course)
    if ($course->id == SITEID) {
        require_login();
        $context = context_system::instance();
    } else {
        require_login($course);
        $context = context_course::instance($course->id);
    }

	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

	$reportclassname = 'report_'.$report->type;
	$reportclass = new $reportclassname($report);

	if (!$reportclass->check_permissions($USER->id, $context)){
		print_error("badpermissions",'block_configurable_reports');
	}

	$PAGE->set_context($context);
	$PAGE->set_pagelayout('report');
	$PAGE->set_url('/blocks/configurable_reports/viewreport.php', array('id'=>$id));

    // If MySQL server loaded? then stop.
    is_mysqlserver_loaded();

    $components = cr_unserialize($reportclass->config->components);
    $filters = (isset($components['filters']['elements']))? $components['filters']['elements']: array();

    // Display full report or Enable the user to use the filters first
    if(!empty($filters) AND empty($_GET['fullreport']) AND !$download) {
        // Do we have any filters in this report?
        // & we are not yet requested to display a full report...
        $filterisactive = false;
        $request = array_merge($_POST, $_GET);
        if($request)
            foreach($request as $key=>$val)
                if(strpos($key,'filter_') !== false) {
                    //echo "filter is active";
                    $filterisactive = true;
                }

        if (!$filterisactive) { // Is this report request was to get full report without filters?
            // Have to initiate some $PAGE settings.
            $reportname = format_string($report->name);
            $PAGE->set_title($reportname);
            $PAGE->set_heading( $reportname);
            $PAGE->set_cacheable( true);
            echo $OUTPUT->header();

            if(has_capability('block/configurable_reports:managereports', $context)
                || (has_capability('block/configurable_reports:manageownreports', $context))
                && $report->ownerid == $USER->id ){
                $currenttab = 'viewreport';
                include('tabs.php');
            }

            if (!$download) { // We are probably not downloading anything, just in case.
                $reportclass->check_filters_request(); // Prepare filter Form.
                $reportclass->print_filters();
            }

            echo $OUTPUT->notification(get_string('addfiltersorfullreport','block_configurable_reports'));
            $paramcourseid = (!empty($_GET['courseid'])) ? '&courseid='.$_GET['courseid'] : '';
            $fullreporturl = new moodle_url('viewreport.php?id='.$_GET['id'].$paramcourseid.'&fullreport=1');
            $button = new single_button($fullreporturl, get_string('displayfullreport','block_configurable_reports'), 'get');
            $button->class = 'fullreportbutton';

            echo $OUTPUT->render($button);
            //echo $OUTPUT->continue_button($fullreporturl);
            echo $OUTPUT->footer();
            die;
        }

    }

	$reportclass->create_report();

	$download = ($download && $format && strpos($report->export,$format.',') !== false)? true : false;

	$action = ($download)? 'download' : 'view';
	//add_to_log($report->courseid, 'configurable_reports', $action, '/block/configurable_reports/viewreport.php?id='.$id, $report->name);
    // TODO: create a special event for download?
    \block_configurable_reports\event\report_viewed::create_from_report($report,
        context_course::instance($course->id))->trigger();

    if (get_config('block_configurable_reports', 'reporttableui') == 'datatables') {
        $PAGE->requires->css(new moodle_url('js/datatables/media/css/jquery.dataTables.css'));
    }

    // Chartlets
    // TODO: Check if we need to load and initiate chartlets lib.
    $PAGE->requires->js('/blocks/configurable_reports/js/chartlets/chartlets.min.js');

// No download, build navigation header etc..
	if (!$download) {
		$reportclass->check_filters_request();
		$reportname = format_string($report->name);
		$navlinks = array();

		if(has_capability('block/configurable_reports:managereports', $context)
            || (has_capability('block/configurable_reports:manageownreports', $context))
            && $report->ownerid == $USER->id ) {

            //$courseurl =  new moodle_url($CFG->wwwroot.'/course/view.php',array('id'=>$report->courseid));
            //$PAGE->navbar->add($COURSE->shortname, $courseurl);

            $managereporturl =  new moodle_url($CFG->wwwroot.'/blocks/configurable_reports/managereport.php',
                array('courseid'=>$report->courseid));
            $PAGE->navbar->add(get_string('managereports','block_configurable_reports'), $managereporturl);
            $PAGE->navbar->add($report->name);
        }

		$PAGE->set_title($reportname);
		$PAGE->set_heading( $reportname);
		$PAGE->set_cacheable( true);
		echo $OUTPUT->header();

        if(has_capability('block/configurable_reports:managereports', $context)
            || (has_capability('block/configurable_reports:manageownreports', $context))
            && $report->ownerid == $USER->id ){
			$currenttab = 'viewreport';
			include('tabs.php');
		}

        if ($reportclass->config->sqldebug) {
            echo $reportclass->sql;
        }

        // Print the report HTML
        $reportclass->print_report_page($context);

	} else {

		$exportplugin = $CFG->dirroot.'/blocks/configurable_reports/export/'.$format.'/export.php';
		if (file_exists($exportplugin)) {
			require_once($exportplugin);
			export_report($reportclass->finalreport);
		}
		die;
	}

    // TODO: Check if we need to load and initiate chartlets lib.
    echo \html_writer::nonempty_tag('script', 'Y.on("domready", function(){ Chartlets.render(); });');
	// Never reached if download = true
    echo $OUTPUT->footer();


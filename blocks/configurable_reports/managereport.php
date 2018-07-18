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
	require_once 'import_form.php';

	$courseid = optional_param('courseid', SITEID, PARAM_INT);
    $showtags = optional_param('showtags', '', PARAM_RAW);
    $showcontexttags = optional_param('showcontexttags', '', PARAM_RAW);

	if (! $course = $DB->get_record("course", array( "id" =>  $courseid)) ) {
		print_error("No such course id");
	}

	// Force user login in course (SITE or Course)
    if ($course->id == SITEID){
		require_login();
        $context = context_system::instance();
	} else {
		require_login($course->id);
        $context = context_course::instance($course->id);
	}

	if(! has_capability('block/configurable_reports:managereports', $context) && ! has_capability('block/configurable_reports:manageownreports', $context))
		print_error('badpermissions');

	$PAGE->set_url('/blocks/configurable_reports/managereport.php', array('courseid'=>$course->id));
	$PAGE->set_context($context);
	$PAGE->set_pagelayout('incourse');

    $mform = new import_form(null, array('courseid'=>$course->id));

	if ($data = $mform->get_data()) {
		if ($xml = $mform->get_file_content('userfile')) {
			require_once($CFG->dirroot.'/lib/xmlize.php');
			$data = xmlize($xml, 1, 'UTF-8');

			if(isset($data['report']['@']['version'])){
				$newreport = new stdclass;
				foreach($data['report']['#'] as $key=>$val){
					if($key == 'components') {
                        $val[0]['#'] = base64_decode(trim($val[0]['#']));
						// fix url_encode " and ' when importing SQL queries
						$temp_components = cr_unserialize($val[0]['#']);
						$temp_components['customsql']['config']->querysql = str_replace("\'","'",$temp_components['customsql']['config']->querysql);
						$temp_components['customsql']['config']->querysql = str_replace('\"','"',$temp_components['customsql']['config']->querysql);
						$val[0]['#'] = cr_serialize($temp_components);
                    }
					$newreport->{$key} = trim($val[0]['#']);
				}
				$newreport->courseid = $course->id;
				$newreport->ownerid = $USER->id;
				if(!$DB->insert_record('block_configurable_reports',$newreport))
					print_error('errorimporting');
				header("Location: $CFG->wwwroot/blocks/configurable_reports/managereport.php?courseid={$course->id}");
				die;
			}
		}
	}

	$reports = cr_get_my_reports($course->id, $USER->id);

	$title = get_string('reports','block_configurable_reports');

    //$courseurl =  new moodle_url($CFG->wwwroot.'/course/view.php',array('id'=>$report->courseid));
    //$PAGE->navbar->add($COURSE->shortname, $courseurl);

    //$managereporturl =  new moodle_url($CFG->wwwroot.'/blocks/configurable_reports/managereport.php',array('courseid'=>$courseid));
    $PAGE->navbar->add(get_string('managereports','block_configurable_reports'));//, $managereporturl);

	$PAGE->set_title($title);
	$PAGE->set_heading( $title);
	$PAGE->set_cacheable( true);

    $PAGE->requires->js('/blocks/configurable_reports/js/configurable_reports.js');
    $PAGE->requires->js_init_call('M.block_configurable_reports.init');

	echo $OUTPUT->header();

	if($reports){
		$table = new stdclass;
		//$table->head = array('ID',get_string('name'),get_string('reportsmanage','admin').' '.get_string('course'),get_string('type','block_configurable_reports'),get_string('username'),get_string('edit'),get_string('download','block_configurable_reports'));
        $table->head = array('ID', get_string('name'), get_string('tagstitle', 'block_configurable_reports'), get_string('contexttagstitle', 'block_configurable_reports'), get_string('reportcontext', 'block_configurable_reports').' '.get_string('course'), get_string('edit'));
		$table->align = array('center','left','left','left','left','center','center');
		$stredit = get_string('edit');
		$strdelete = get_string('delete');
		$strhide = get_string('hide');
		$strshow = get_string('show');
		$strcopy = get_string('duplicate');
		$strexport = get_string('exportreport','block_configurable_reports');
        $strsettings = get_string('settings');

        // Build TAGs navigation.
        $tags = array();
        foreach($reports as $r) {
            if (!empty($r->tags)) {
                $taglistraw = explode(',', $r->tags);
                foreach ($taglistraw as $key => $rawtag) {
                    $taglist[$key] = trim($rawtag);
                }
                $tagsdiff = array_diff($taglist, $tags);
                $tags = array_merge($tags, $tagsdiff);
            }
        }
        $tagsnav = get_string('navbytags','block_configurable_reports');
        foreach ($tags as $tag) {
            $tag = trim($tag);
            $tagsnav .= html_writer::link(new moodle_url('managereport.php', array('showtags'=>$tag, 'courseid'=>$courseid)), $tag).', ';
        }
        if (!empty($showtags))
            $tagsnav .= html_writer::link('managereport.php?courseid='.$courseid, get_string('all'));
        $tagsnav = rtrim(rtrim($tagsnav), ',');
        echo html_writer::tag('div', $tagsnav, array('class'=>'tagsnav'));

        // Build Context TAGs navigation.
        $contexttags = array();
        foreach($reports as $r) {
            if (!empty($r->contexttags)) {
                $contexttaglistraw = explode(',', $r->contexttags);
                foreach ($contexttaglistraw as $key => $rawtag) {
                    $contexttaglist[$key] = trim($rawtag);
                }
                $contexttagsdiff = array_diff($contexttaglist, $contexttags);
                $contexttags = array_merge($contexttags, $contexttagsdiff);
            }
        }
        $contexttagsnav = get_string('contextnavbytags','block_configurable_reports');
        foreach ($contexttags as $tag) {
            $tag = trim($tag);
            $contexttagsnav .= html_writer::link(new moodle_url('managereport.php', array('showcontexttags'=>$tag, 'courseid'=>$courseid)), $tag).', ';
        }
        if (!empty($showcontexttags))
            $tagsnav .= html_writer::link('managereport.php?courseid='.$courseid, get_string('all'));
        $contexttagsnav = rtrim(rtrim($contexttagsnav), ',');
        echo html_writer::tag('div', $contexttagsnav, array('class'=>'contexttagsnav'));

		foreach($reports as $r){

            // If we filter TAGs, show only reports with selected TAG
            if (!empty($showtags)) {
                if (strpos($r->tags, $showtags) === false) continue;
            }

            // If we filter Context_TAGs, show only reports with selected Context_TAG
            if (!empty($showcontexttags)) {
                if (strpos($r->contexttags, $showcontexttags) === false) continue;
            }

			if($r->courseid == 1)
				$coursename = '<a href="'.$CFG->wwwroot.'">'.get_string('site').'</a>';
			else if(! $coursename = $DB->get_field('course','fullname',array('id' => $r->courseid)))
				$coursename = get_string('deleted');
			else
				//$coursename = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$r->courseid.'">'.$coursename.'</a>';
                $coursename = '<a href="'.$CFG->wwwroot.'/blocks/configurable_reports/managereport.php?courseid='.$r->courseid.'">'.$coursename.'</a>';

			if($owneruser = $DB->get_record('user',array('id' => $r->ownerid)))
				$owner = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$r->ownerid.'">'.fullname($owneruser).'</a>';
			else
				$owner = get_string('deleted');

			$editcell = '';
			$editcell .= '<a title="'.$stredit.'"  href="editcomp.php?id='.$r->id.'&comp=customsql&courseid='.$courseid.'"><img src="'.$OUTPUT->pix_url('/t/edit').'" class="iconsmall" alt="'.$stredit.'" /></a>&nbsp;&nbsp;';
			$editcell .= '<a title="'.$strdelete.'"  href="editreport.php?id='.$r->id.'&amp;delete=1&amp;sesskey='.$USER->sesskey.'"><img src="'.$OUTPUT->pix_url('/t/delete').'" class="iconsmall" alt="'.$strdelete.'" /></a>&nbsp;&nbsp;';

			if (!empty($r->visible)) {
				$editcell .= '<a title="'.$strhide.'" href="editreport.php?id='.$r->id.'&amp;hide=1&amp;sesskey='.$USER->sesskey.'">'.'<img src="'.$OUTPUT->pix_url('/t/hide').'" class="iconsmall" alt="'.$strhide.'" /></a> ';}
			else {
				$editcell .= '<a title="'.$strshow.'" href="editreport.php?id='.$r->id.'&amp;show=1&amp;sesskey='.$USER->sesskey.'">'.'<img src="'.$OUTPUT->pix_url('/t/show').'" class="iconsmall" alt="'.$strshow.'" /></a> ';
			}
			$editcell .= '<a title="'.$strcopy.'" href="editreport.php?id='.$r->id.'&amp;duplicate=1&amp;sesskey='.$USER->sesskey.'"><img src="'.$OUTPUT->pix_url('/t/copy').'" class="iconsmall" alt="'.$strcopy.'" /></a>&nbsp;&nbsp;';
			$editcell .= '<a title="'.$strexport.'" href="export.php?id='.$r->id.'&amp;sesskey='.$USER->sesskey.'"><img src="'.$OUTPUT->pix_url('/i/backup').'" class="iconsmall" alt="'.$strexport.'" /></a>&nbsp;&nbsp;';
            $editcell .= '<a title="'.$strsettings.'"  href="editreport.php?id='.$r->id.'"><img src="'.$OUTPUT->pix_url('/i/settings').'" class="iconsmall" alt="'.$stredit.'" /></a>&nbsp;&nbsp;';

			$download = '';
			$export = explode(',',$r->export);
			if(!empty($export)){
				foreach($export as $e)
					if($e){
						$download .= '<a href="viewreport.php?id='.$r->id.'&amp;download=1&amp;format='.$e.'"><img src="'.$CFG->wwwroot.'/blocks/configurable_reports/export/'.$e.'/pix.gif" alt="'.$e.'">&nbsp;'.(strtoupper($e)).'</a>&nbsp;&nbsp;<br>';
					}
			}

            if (!empty($r->summary)) {
                $about = '<span id="aboutreport'.$r->id.'"><img id="aboutimg'.$r->id.'" class="aboutimg" src="pix/help-about.png" onclick="M.block_configurable_reports.onclick_showabout(this);"><span class="reportsummary">'.$r->summary.'</span></span>';
            } else {
                $about = '';
            }

            $tags = '';
            if (!empty($r->tags)) {
                $tags = '';//get_string('tags').': ';
                $taglist = explode(',', $r->tags);
                foreach ($taglist as $tag) {
                    $tag = trim($tag);
                    $tags .= html_writer::link('managereport.php?courseid='.$courseid.'&showtags='.$tag, $tag).', ';
                }
                if (!empty($showtags))
                    $tags .= html_writer::link('managereport.php?courseid='.$courseid, get_string('all'));
            }
            $tags = rtrim(rtrim($tags), ',');

            $contexttags = '';
            if (!empty($r->contexttags)) {
                $contexttags = '';//get_string('tags').': ';
                $contexttaglist = explode(',', $r->contexttags);
                foreach ($contexttaglist as $tag) {
                    $tag = trim($tag);
                    $contexttags .= html_writer::link('managereport.php?courseid='.$courseid.'&showcontexttags='.$tag, $tag).', ';
                }
                if (!empty($contexttags))
                    $contexttags .= html_writer::link('managereport.php?courseid='.$courseid, get_string('all'));
            }
            $contexttags = rtrim(rtrim($contexttags), ',');

            $courseentry = html_writer::start_tag('div', array('class'=>'reportdata'));
            $courseentry .= html_writer::tag('div',html_writer::link(new moodle_url('viewreport.php', array('id'=>$r->id)), $r->name, array('class'=>'reportname')));
                if (!empty($r->summary)) {
                    $courseentry .= html_writer::tag('div', $r->summary, array('class'=>'reportsummary'));
                }
                $owner = get_string('createdby','block_configurable_reports', $owner);
                if (!empty($download))
                    $download = get_string('availabletodownloadas','block_configurable_reports', $download);
                $courseentry .= html_writer::tag('div', $owner, array('class'=>'reportinfo'));
            $courseentry .= html_writer::end_tag('div');

            $reporttags = html_writer::tag('div', $tags, array('class'=>'reportinfo tags'));
            $reportcontexttags = html_writer::tag('div', $contexttags, array('class'=>'reportinfo contexttags'));

			//$table->data[] = array($r->id, '<a href="viewreport.php?id='.$r->id.'">'.$r->name.'</a>'.$about, $coursename, get_string('report_'.$r->type,'block_configurable_reports'), $owner, $editcell, $download);
            $table->data[] = array($r->id, $courseentry, $reporttags, $reportcontexttags, $coursename, $editcell."<br><br>".$download);
		}

		$table->id = 'reportslist';
		cr_add_jsordering("#reportslist");
		cr_print_table($table);
	}
	else{
		echo $OUTPUT->heading(get_string('noreportsavailable','block_configurable_reports'));
	}

	echo $OUTPUT->heading('<div class="addbutton"><a class="linkbutton" href="'.$CFG->wwwroot.'/blocks/configurable_reports/editreport.php?courseid='.$course->id.'">'.(get_string('addreport','block_configurable_reports')).'</a></div>');

	$mform->display();

    echo $OUTPUT->footer();

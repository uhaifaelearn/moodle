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

/*
 * Originality Plagiarism Plugin
 * Reprocess and delete requests that were never completely processed.
 * If there are any records left in plagiarism_originality_req then have a page with the list and buttons to delete and resubmit.
 * Last update date: 2017-09-18
 *
 */

/*
Moodle 3.1.7 new file

Simply moved previous functionality from 3.1.6 to this file so now requests.php includes different files based on request.
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

$inputclientkey = $_GET['clientkey'];

if (isset($_POST['bulk_delete']) || isset($_POST['bulk_resubmit'])) {
    if (isset($_POST['reqids'])) {
        $reqids = $_POST['reqids'];

        if (isset($_POST['bulk_delete'])) {
            if ($reqids) {
                foreach ($reqids as $reqid) {
                    delete_req($reqid);
                }
            }
            header("Location: " . $CFG->wwwroot .
                                 '/plagiarism/originality/requests.php'.
                                 "?clientkey=$inputclientkey&requesttype=1");
        }

        if (isset($_POST['bulk_resubmit'])) {
            if ($reqids) {
                foreach ($reqids as $reqid) {
                    resubmit_req($reqid);
                }
            }
            header("Location: " . $CFG->wwwroot .
                                  '/plagiarism/originality/requests.php'.
                                  "?clientkey=$inputclientkey&requesttype=1");
        }
    }
}


if (isset($_GET['delete'])) {
    delete_req($_GET['delete']);
    header("Location: " . $CFG->wwwroot .
    '/plagiarism/originality/requests.php'.
    "?clientkey=$inputclientkey&requesttype=1");
}

if (isset($_GET['resubmit'])) {
    resubmit_req($_GET['resubmit']);
    header("Location: " . $CFG->wwwroot .
    '/plagiarism/originality/requests.php'.
    "?clientkey=$inputclientkey&requesttype=1");
}


/***********************************************************************
 * PREPARE OUTPUT
 * *********************************************************************
 */


$output = "<div style='text-align: center;'><h1>Originality Requests that were not processed</h1>\n";

$output .= "<a href='requests.php?clientkey=$inputclientkey&requesttype=1'>Refresh list</a>";

$requests = $DB->get_recordset_sql("select distinct userid, assignment from ".$CFG->prefix . "plagiarism_originality_req");

$bulkdeletebutton = "<input style='font-size:12px;' type='submit' name='bulk_delete' value='Delete Selected' />\n";
$bulkresubmitbutton = "<input style='font-size:12px;' type='submit' name='bulk_resubmit' value='Resubmit Selected' />\n";


$headerrow = "
           <form method='post' action='' />
           <table  id='requestsTable' cellpadding='2'>\n
           <thead>\n
           <tr>\n
           <th>Assignment ID (Name)</th>\n
           <th>User ID (Name)</th>\n
           <th>Date Last Submitted</th>\n
           <th>Days Elapsed</th>\n
           <th>Delete</th>\n
           <th>Resubmit</th>\n
           <th>$bulkdeletebutton <br /> $bulkresubmitbutton</th>
           </tr>\n
           </thead>\n
           <tbody>\n
";

$count = 0;
$rows = '';

foreach ($requests as $req) {
    list($datemodified, $dayselapsed) = get_assignment_info($req->assignment, $req->userid);

    $deletebutton = "<a href='requests.php?clientkey=$inputclientkey&requesttype=1&delete=$reqid'>Delete</a>\n";
    $resubmitbutton = "<a href='requests.php?clientkey=$inputclientkey&requesttype=1&resubmit=$reqid'>Resubmit Assignment</a>\n";


    $rows .= "<tr>\n".
    '<td>'.$req->assignment . ' ('.get_assignment_name($req->assignment).")</td>\n".
    '<td>'.$req->userid ."</td>\n".
    '<td>'.$datemodified."</td>\n".
    '<td>'.$dayselapsed."</td>\n".
    '<td>'.$deletebutton."</td>\n".
    '<td>'.$resubmitbutton."</td>\n".
    "<td><input type='checkbox' name='reqids[]' value='$reqid' /></td>\n".
    '</tr>';
    $count++;

}

$rows .= "</tbody>\n";

$output .= "<div>Records found: $count</div>" . $headerrow . $rows;

$output .= "</table>
            </form>\n";

/*
   $cm = get_coursemodule_from_instance('assignment', $assignmentid, $courseid);
   $context = get_context_instance(CONTEXT_MODULE, $cm->id);
   echo "The context is: $context->id";
   $lib = new plagiarism_plugin_originality();
*/



function get_course_id($assignmentid) {
    global $DB, $CFG;
    $assignments = $DB->get_recordset_sql("select * from ".$CFG->prefix . "assign where id=?", array('id' => $assignmentid));

    return $assignments->current()->course ? $assignments->current()->course : 0;
}

function get_assignment_info($assignmentid, $userid) {
    global $DB, $CFG;

    $submissions = $DB->get_recordset_sql("select *,DATE_FORMAT(FROM_UNIXTIME(timemodified), '%e %b %Y') AS date_formatted, ".
                                          "floor(((UNIX_TIMESTAMP()-timemodified) / 86400)) AS time_diff from ".
                                           $CFG->prefix . "assign_submission where assignment=? and userid=?", array('assignment'=>$assignmentid, 'userid'=>$userid));

    return array($submissions->current()->date_formatted, $submissions->current()->time_diff);

}

function get_submission_id($assignmentid, $userid) {
    global $DB, $CFG;
    $submissions = $DB->get_recordset_sql("select id from ".
                                          $CFG->prefix . "assign_submission where assignment=? and userid=?", array('assignment'=>$assignmentid, 'userid'=>$userid));

    return array($submissions->current()->id);
}

function get_assignment_name($id) {
    global $DB;
    $assignment = $DB->get_record('assign', array('id' => $id));
    return $assignment->name;
}

function get_user_name($id) {
    global $DB;
    $user = $DB->get_record('user', array('id' => $id));
    return $user->firstname . ' ' . $user->lastname;
}

function delete_req($reqid) {
    global $DB, $CFG;

    $requests = $DB->get_recordset_sql("select * from ".$CFG->prefix . "plagiarism_originality_req where id=?", array('id' => $reqid));

    if ($requests->current()) {
        $userid = $requests->current()->userid;
        $assignmentid = $requests->current()->assignment;

        log_it("Deleting request record id=" . $reqid . " for assignment=? and user=?", array('assignment' => $assignmentid, 'user' => $userid));
        $DB->delete_records('plagiarism_originality_req', array("id" => $reqid)); // Delete any previous requests.
    }
}

function resubmit_req($reqid) {
    global $DB, $CFG;

    $requests = $DB->get_recordset_sql("select * from ".$CFG->prefix . "plagiarism_originality_req where id=?", array('id' => $reqid));

    if ($requests->current()) {
        $userid = $requests->current()->userid;
        $assignmentid = $requests->current()->assignment;

        log_it("Resubmitting request record id=$reqid for assignment=$assignmentid and user=$userid");

        $courseid = get_course_id($assignmentid);

        $submissionid = get_submission_id($assignmentid, $userid);

        $lib = new plagiarism_plugin_originality();

        list($origserver, $origkey) = get_server_and_key();

        // ...https://docs.moodle.org/dev/Course_module.
        $course = $DB->get_record('course', array('id' => $courseid));
        $info = get_fast_modinfo($course);

        $list = get_array_of_activities($courseid);

        foreach ($list as $k => $v) {
            if ($v->mod == 'assign' && $v->id == $assignmentid) {
                $cm = $v->cm;
            }
        }

        $eventdata = array();
        // The only thing this is used for is assignment number and I am passing that in directly.
        $eventdata['contextinstanceid'] = $cm;
        $eventdata['objectid'] = $submissionid[0];
        $eventdata['courseid'] = $courseid;
        $eventdata['userid'] = $userid;
        $eventdata['assignNum'] = $assignmentid;

        $USER = new stdClass();
        $USER->idnumber = $userid;

        if (strpos($requests->current()->file, 'onlinetext') !== FALSE) {
            $type = 'onlinetext';
        } else {
            $type = 'file';
        }

        if ($type == 'onlinetext'){
            $onlinetextrec = $DB->get_recordset_sql("select onlinetext from ".$CFG->prefix . "assignsubmission_onlinetext where assignment=? and submission=?", array('assignment' => $assignmentid, 'submission' => $submissionid[0]));

            $eventdata['other']['content'] = $onlinetextrec->current()->onlinetext;
            $lib->originality_event_onlinetext_submitted($eventdata);
        } else {
            $lib->originality_event_file_uploaded($eventdata);
        }
    }
}



?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">
    <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.12.4.js">
    </script>
    <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js">
    </script>
    <script type="text/javascript" class="init">


        $(document).ready(function() {
            $('#requestsTable').DataTable();
        } );


    </script>
</head>
<body>
<?php
echo $output;
?>

</body>
</html>
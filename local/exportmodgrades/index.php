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
/**
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local_exportmodgrades
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Replace newmodule with the name of your module and remove this line.
require_once(__DIR__.'/../../config.php');
require_once('locallib.php');

require_once('general_form.php');
global $PAGE, $COURSE;

require_login();

if(!is_siteadmin()){
    print_error('User not admin');
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_heading(get_string('page_index','local_exportmodgrades'));
$PAGE->set_title(get_string('page_index','local_exportmodgrades'));
$PAGE->set_url(new moodle_url('/local/exportmodgrades/index.php'), $_GET);
$PAGE->set_pagelayout('incourse');

$params = array('id' => 1);
$mform = new general_form(null, $params);

$mform->set_default_data();
$mform->get_data();

if(!$mform->is_download()) {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}else{
    $mform->download();
}
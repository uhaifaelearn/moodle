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
 * Local plugin "sandbox" - Task definition
 *
 * @package    local_exportmodgrades
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_exportmodgrades\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../lib.php');

/**
 * The local_sandbox restore courses task class.
 *
 * @package    local_exportmodgrades
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class shedule_export_mod_grades extends \core\task\scheduled_task {

    /**
     * Return localised task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('export_mod', 'local_exportmodgrades');
    }

    /**
     * Execute scheduled task
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/exportmodgrades/locallib.php');

        $task = new \local_exportmodgrades\task\adhoc_shedule_export_mod_grades();
        $task->set_custom_data(
            array(
            )
        );
        \core\task\manager::queue_adhoc_task($task);
    }
}
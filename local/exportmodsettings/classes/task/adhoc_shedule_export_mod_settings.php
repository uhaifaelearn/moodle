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
 * @package    local_exportmodsettings
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_exportmodsettings\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../lib.php');

/**
 * The local_sandbox restore courses task class.
 *
 * @package    local_exportmodsettings
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc_shedule_export_mod_settings extends \core\task\adhoc_task {

    /**
     * Return localised task name.
     *
     * @return string
     */
    public function get_component() {
        return 'local_exportmodsettings';
    }

    /**
     * Execute adhoc task
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/exportmodsettings/locallib.php');

        $lockkey = 'export_mod';
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_exportmodsettings_task');
        $lock = $lockfactory->get_lock($lockkey, 0);

        if ($lock !== false) {
            local_exportmodsettings_save_file_to_disk();
            $lock->release();
        }
    }
}
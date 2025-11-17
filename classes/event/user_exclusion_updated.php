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
 * Event user_exclusion_updated
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_reminder\event;

defined('MOODLE_INTERNAL') || die();

class user_exclusion_updated extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u'; // 'u' = update
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'user'; // we're modifying user exclusions
    }

    public static function get_name() {
        return get_string('event:userexclusionupdated', 'local_course_reminder');
    }

    public function get_description() {
        return "The user with id '{$this->relateduserid}' was " .
               "{$this->other['action']} from course reminder exclusion " .
               "for courseid '{$this->other['courseid']}' by admin id '{$this->userid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/course_reminder/incomplete.php', [
            'courseid' => $this->other['courseid']
        ]);
    }

    public function get_legacy_logdata() {
        return null; // No legacy log
    }

    protected function get_legacy_eventname() {
        return null;
    }
}

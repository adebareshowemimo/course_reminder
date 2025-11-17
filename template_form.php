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
 * Template form for course reminder
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_course_reminder_template_form extends moodleform {
    
    public function definition() {
        $mform = $this->_form;
        $record = $this->_customdata['record'] ?? null;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', 'Template Name', ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Required', 'required', null, 'client');

        $mform->addElement('text', 'subject', 'Email Subject', ['size' => 60]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', 'Required', 'required', null, 'client');

        $mform->addElement('editor', 'body_editor', 'Message Body', ['rows' => 20], ['maxfiles' => 0]);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', 'Required', 'required', null, 'client');

        $this->add_action_buttons(true, 'Save Template');
        
        // Set data if editing
        if ($record) {
            $record->body_editor = [
                'text' => $record->body ?? '',
                'format' => $record->format ?? FORMAT_HTML
            ];
            $this->set_data($record);
        }
    }
}

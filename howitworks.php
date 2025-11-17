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
 * TODO describe file howitworks
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$PAGE->set_url(new moodle_url('/local/course_reminder/howitworks.php', $courseid ? ['courseid' => $courseid] : []));
$PAGE->set_title('How the Reminder System Works');
$PAGE->set_heading('How the Reminder System Works');

echo $OUTPUT->header();

echo html_writer::start_div('row');

// Sidebar left
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

echo html_writer::start_div('col-md-9');
echo $OUTPUT->heading("Reminder System Setup Instructions");

// Add your instructional HTML here
echo html_writer::tag('p', 'To use this plugin, you must enable course completion for this course...');

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
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
 * Course Reminder - Main landing page
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('moodle/course:update', $context);

$PAGE->set_url('/local/course_reminder/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_course_reminder'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// Display page heading
echo $OUTPUT->heading(get_string('pluginname', 'local_course_reminder'));

// Introduction text
echo html_writer::div(
    get_string('indexintro', 'local_course_reminder'),
    'alert alert-info'
);

// Create two option cards
echo html_writer::start_div('row mt-4');

// Regular Reminders Card
echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('regularreminders', 'local_course_reminder'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('regularremindersdesc', 'local_course_reminder'), ['class' => 'card-text']);
echo html_writer::start_div('mt-3');
$manageurl = new moodle_url('/local/course_reminder/manage.php', ['courseid' => $courseid]);
echo html_writer::link(
    $manageurl,
    get_string('manageregularreminders', 'local_course_reminder'),
    ['class' => 'btn btn-primary btn-lg w-100']
);
echo html_writer::end_div();
echo html_writer::end_div(); // card-body

echo html_writer::start_div('card-footer text-muted');
echo html_writer::tag('small', get_string('regularremindersexample', 'local_course_reminder'));
echo html_writer::end_div();
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col

// One-Time Reminders Card
echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('onetimereminders', 'local_course_reminder'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('onetimeremindersdesc', 'local_course_reminder'), ['class' => 'card-text']);
echo html_writer::start_div('mt-3');
$scheduleurl = new moodle_url('/local/course_reminder/schedule.php', ['courseid' => $courseid]);
echo html_writer::link(
    $scheduleurl,
    get_string('scheduleonetimereminder', 'local_course_reminder'),
    ['class' => 'btn btn-success btn-lg w-100']
);
echo html_writer::end_div();
echo html_writer::end_div(); // card-body

echo html_writer::start_div('card-footer text-muted');
echo html_writer::tag('small', get_string('onetimeremindersexample', 'local_course_reminder'));
echo html_writer::end_div();
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col

echo html_writer::end_div(); // row

// Help section
echo html_writer::start_div('card mt-4 bg-light');
echo html_writer::start_div('card-body');
echo html_writer::tag('h4', get_string('needhelp', 'local_course_reminder'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('helpdescription', 'local_course_reminder'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('helpregular', 'local_course_reminder'));
echo html_writer::tag('li', get_string('helponetime', 'local_course_reminder'));
echo html_writer::tag('li', get_string('helptemplates', 'local_course_reminder'));
echo html_writer::end_tag('ul');
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();

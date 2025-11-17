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
 * Edit email template
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/template_form.php');

global $DB, $PAGE, $OUTPUT;

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$templateid = optional_param('id', 0, PARAM_INT);
$template = null;

if ($templateid) {
    $template = $DB->get_record('local_course_reminder_tpls', ['id' => $templateid], '*', MUST_EXIST);
}

// Set up page
$PAGE->set_url(new moodle_url('/local/course_reminder/edit_template.php', ['id' => $templateid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title($templateid ? 'Edit Template' : 'Add New Template');
$PAGE->set_heading($PAGE->title);

// Create Form
$mform = new local_course_reminder_template_form(null, ['record' => $template]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/course_reminder/templates.php'));
} elseif ($data = $mform->get_data()) {
    $entry = new stdClass();
    $entry->name = $data->name;
    $entry->subject = $data->subject;
    $entry->body = $data->body_editor['text'];
    $entry->format = $data->body_editor['format'];
    $entry->timemodified = time();

    if ($templateid) {
        $entry->id = $templateid;
        $DB->update_record('local_course_reminder_tpls', $entry);
    } else {
        $entry->timecreated = time();
        $DB->insert_record('local_course_reminder_tpls', $entry);
    }

    redirect(new moodle_url('/local/course_reminder/templates.php'), 'Template saved', 2);
}

// Render Form
echo $OUTPUT->header();

// Sidebar
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

echo html_writer::start_div('col-md-9');
$mform->display();

// Display available placeholders for email templates
echo html_writer::start_div('alert alert-info mt-3');
echo html_writer::tag('h4', 'Available Placeholders');
echo html_writer::tag('p', 'Use these placeholders in your template. They will be replaced with actual values when emails are sent:');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', html_writer::tag('strong', '{firstname}') . ' - User\'s first name');
echo html_writer::tag('li', html_writer::tag('strong', '{lastname}') . ' - User\'s last name');
echo html_writer::tag('li', html_writer::tag('strong', '{fullname}') . ' - User\'s full name');
echo html_writer::tag('li', html_writer::tag('strong', '{email}') . ' - User\'s email address');
echo html_writer::tag('li', html_writer::tag('strong', '{coursename}') . ' - Course full name');
echo html_writer::tag('li', html_writer::tag('strong', '{courseurl}') . ' - Link to the course');
echo html_writer::tag('li', html_writer::tag('strong', '{sitelogo}') . ' - Site logo URL');
echo html_writer::tag('li', html_writer::tag('strong', '{sitelogocompact}') . ' - Site compact logo URL');
echo html_writer::end_tag('ul');
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();

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
 * @package    local_ccp_coursereminder
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
    $template = $DB->get_record('local_ccp_crsremind_tpls', ['id' => $templateid], '*', MUST_EXIST);
}

// Set up page
$PAGE->set_url(new moodle_url('/local/ccp_coursereminder/edit_template.php', ['id' => $templateid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title($templateid ? 'Edit Template' : 'Add New Template');
$PAGE->set_heading($PAGE->title);

// Create Form
$mform = new local_ccp_coursereminder_template_form(null, ['record' => $template]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/ccp_coursereminder/templates.php'));
} elseif ($data = $mform->get_data()) {
    $entry = new stdClass();
    $entry->name = $data->name;
    $entry->subject = $data->subject;
    $entry->body = $data->body_editor['text'];
    $entry->format = $data->body_editor['format'];
    $entry->timemodified = time();

    if ($templateid) {
        $entry->id = $templateid;
        $DB->update_record('local_ccp_crsremind_tpls', $entry);
    } else {
        $entry->timecreated = time();
        $DB->insert_record('local_ccp_crsremind_tpls', $entry);
    }

    redirect(new moodle_url('/local/ccp_coursereminder/templates.php'), 'Template saved', 2);
}

// Render Form
echo $OUTPUT->header();

// Main container with sidebar
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

// Main content area - two-column layout
echo html_writer::start_div('col-md-9');
echo html_writer::start_div('row g-4');

// Left column - Form
echo html_writer::start_div('col-12 col-lg-8');
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body p-4');
$mform->display();
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col-lg-8

// Right column - Available Placeholders sidebar
echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body p-4');

echo html_writer::tag('h5', html_writer::tag('i', '', ['class' => 'bi bi-braces me-2']) . 'Available Placeholders', ['class' => 'mb-3']);
echo html_writer::tag('p', 'Use these placeholders in your template. They will be replaced with actual values when emails are sent:', ['class' => 'text-muted small mb-3']);

echo html_writer::start_tag('ul', ['class' => 'list-unstyled mb-0 small']);

$placeholders = [
    ['{firstname}', "User's first name"],
    ['{lastname}', "User's last name"],
    ['{fullname}', "User's full name"],
    ['{email}', "User's email address"],
    ['{coursename}', 'Course full name'],
    ['{courseurl}', 'Link to the course'],
    ['{sitelogo}', 'Site logo URL'],
    ['{sitelogocompact}', 'Site compact logo URL'],
];

foreach ($placeholders as $index => $placeholder) {
    $borderclass = ($index < count($placeholders) - 1) ? ' border-bottom' : '';
    echo html_writer::start_tag('li', ['class' => 'd-flex justify-content-between py-2' . $borderclass]);
    echo html_writer::tag('code', $placeholder[0], ['class' => 'text-primary']);
    echo html_writer::tag('span', $placeholder[1], ['class' => 'text-muted']);
    echo html_writer::end_tag('li');
}

echo html_writer::end_tag('ul');
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col-lg-4

echo html_writer::end_div(); // row g-4
echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

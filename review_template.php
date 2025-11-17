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
 * Review/Preview email template
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT;

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$templateid = required_param('id', PARAM_INT);
$template = $DB->get_record('local_course_reminder_tpls', ['id' => $templateid], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/local/course_reminder/review_template.php', ['id' => $templateid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title('Review Template');
$PAGE->set_heading('Email Template Preview');

echo $OUTPUT->header();

// Sidebar
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

echo html_writer::start_div('col-md-9');

// Apply colors to the preview
require_once($CFG->dirroot . '/local/course_reminder/classes/template_helper.php');
$body = \local_course_reminder\template_helper::apply_colors($template->body);

// Create dummy placeholder values for preview
$logourl = $OUTPUT->get_logo_url();
$compactlogourl = $OUTPUT->get_compact_logo_url();

$placeholders = [
    '{firstname}'  => 'John',
    '{lastname}'   => 'Doe',
    '{fullname}'   => 'John Doe',
    '{email}'      => 'john.doe@example.com',
    '{coursename}' => 'Sample Course Name',
    '{courseurl}'  => $CFG->wwwroot . '/course/view.php?id=1',
    '{sitelogo}'   => $logourl ? $logourl->out(false) : $CFG->wwwroot . '/theme/image.php/boost/core/logo',
    '{sitelogocompact}' => $compactlogourl ? $compactlogourl->out(false) : $CFG->wwwroot . '/theme/image.php/boost/core/logo',
];

// Also support legacy double-brace placeholders
$legacyPlaceholders = [
    '{{firstname}}'  => 'John',
    '{{lastname}}'   => 'Doe',
    '{{fullname}}'   => 'John Doe',
    '{{email}}'      => 'john.doe@example.com',
    '{{coursename}}' => 'Sample Course Name',
    '{{completionlink}}' => $CFG->wwwroot . '/course/view.php?id=1',
];

// Replace placeholders with dummy values
$subject = !empty($template->subject) ? $template->subject : '';
$subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
$subject = str_replace(array_keys($legacyPlaceholders), array_values($legacyPlaceholders), $subject);

$body = str_replace(array_keys($placeholders), array_values($placeholders), $body);
$body = str_replace(array_keys($legacyPlaceholders), array_values($legacyPlaceholders), $body);

// Display preview
echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/local/course_reminder/templates.php'),
    '← Back to Templates',
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo html_writer::tag('h3', 'Template: ' . format_string($template->name));

echo html_writer::div(
    html_writer::tag('div', 
        html_writer::tag('strong', 'Subject: ') . s($subject),
        ['class' => 'alert alert-info']
    ),
    'mb-3'
);

echo html_writer::div(
    html_writer::tag('div',
        html_writer::tag('strong', 'Note: ') . 'This preview shows the template with sample data and your current color settings. Actual emails will contain real user and course information.',
        ['class' => 'alert alert-warning']
    ),
    'mb-3'
);

// Render email content as HTML in an iframe
echo html_writer::start_div('email-preview-container border mb-4');
echo html_writer::tag('style', '
    .email-preview-frame {
        width: 100%;
        min-height: 600px;
        border: none;
        background: white;
    }
');

echo '<iframe class="email-preview-frame" srcdoc="' . htmlspecialchars($body, ENT_QUOTES) . '"></iframe>';

echo html_writer::end_div();

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

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
 * TODO describe file templates
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

// Only site admins and managers can manage templates
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

admin_externalpage_setup('local_course_reminder_templates');

$PAGE->set_url(new moodle_url('/local/course_reminder/templates.php'));
$PAGE->set_title('Email Templates');
$PAGE->set_heading('Manage Email Templates');

// Delete
if ($action === 'delete' && $id) {
    require_sesskey();
    $DB->delete_records('local_course_reminder_tpls', ['id' => $id]);
    redirect($PAGE->url, 'Template deleted', 1);
}





echo $OUTPUT->header();


echo html_writer::start_div('row');

// Sidebar left
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

echo html_writer::start_div('col-md-9');
echo $OUTPUT->heading('Manage Email Templates');

// Add New Template Button
echo html_writer::tag('div',
    html_writer::link(
        new moodle_url('/local/course_reminder/edit_template.php'),
        'Add New Template',
        ['class' => 'btn btn-primary']
    ),
    ['style' => 'margin-bottom: 20px;']
);

// List existing templates
$templates = $DB->get_records('local_course_reminder_tpls', null, 'timemodified DESC');

if ($templates) {
    $table = new html_table();
    $table->head = ['#', 'Name', 'Subject', 'Actions'];
    $sn = 1;
    foreach ($templates as $tpl) {
        $editurl = new moodle_url('/local/course_reminder/edit_template.php', ['id' => $tpl->id]);
        $delurl = new moodle_url($PAGE->url, ['id' => $tpl->id, 'action' => 'delete', 'sesskey' => sesskey()]);
        $reviewurl = new moodle_url('/local/course_reminder/review_template.php', ['id' => $tpl->id]);
        
        $editlink = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-secondary']);
        $deletelink = html_writer::link($delurl, 'Delete', ['class' => 'btn btn-sm btn-danger', 'onclick' => 'return confirm("Are you sure you want to delete this template?");']);
        $reviewlink = html_writer::link($reviewurl, 'Preview', ['class' => 'btn btn-sm btn-info']);
        
        $table->data[] = [
            $sn++,
            format_string($tpl->name),
            shorten_text(strip_tags($tpl->subject), 50),
            $editlink . ' ' . $reviewlink . ' ' . $deletelink
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div('No templates found.');
}

echo html_writer::end_div();

echo html_writer::end_div(); // row

echo $OUTPUT->footer();

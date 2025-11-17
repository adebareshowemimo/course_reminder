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
 * TODO describe file retrylog
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

// Only site admins and managers can view retry log
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

admin_externalpage_setup('local_course_reminder_retrylog');

$PAGE->set_url(new moodle_url('/local/course_reminder/retrylog.php'));
$PAGE->set_title('Reminder Retry Log');
$PAGE->set_heading('Reminder Retry Log');

global $DB, $OUTPUT;

// Fetch logs.
$logs = $DB->get_records('local_course_reminder_retry', null, 'lastattempttime DESC');

echo $OUTPUT->header();

echo html_writer::start_div('row');

// Sidebar left
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

echo html_writer::start_div('col-md-9');
echo $OUTPUT->heading('Reminder Retry Log');

if (empty($logs)) {
    echo $OUTPUT->notification("No retry records found.", 'notifymessage');
} else {
    $table = new html_table();
    $table->head = ['User', 'Course', 'Status', 'Retry Count', 'Last Attempt'];

    foreach ($logs as $log) {
        $user = $DB->get_record('user', ['id' => $log->userid]);
        $course = $DB->get_record('course', ['id' => $log->courseid]);

        $table->data[] = [
            fullname($user),
            format_string($course->fullname),
            ucfirst($log->status),
            $log->retrycount,
            userdate($log->lastattempttime),
        ];
    }

    echo html_writer::table($table);
}

echo html_writer::end_div();

echo html_writer::end_div(); // row
echo $OUTPUT->footer();


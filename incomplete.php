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
 * TODO describe file incomplete
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/course_reminder/lib.php');

require_login();

use core_completion\progress;

// Support courseid (legacy), recurringid (from manage.php), and scheduleid (from schedule.php)
$courseid = optional_param('courseid', 0, PARAM_INT);
$scheduleid = optional_param('scheduleid', 0, PARAM_INT);
$recurringid = optional_param('recurringid', 0, PARAM_INT);

if ($scheduleid) {
    // Get course from schedule record
    $schedule = $DB->get_record('local_course_reminder_schedule', ['id' => $scheduleid], '*', MUST_EXIST);
    $courseid = $schedule->courseid;
} elseif ($recurringid) {
    // Get course from recurring reminder config
    $recurring = $DB->get_record('local_course_reminder_courses', ['id' => $recurringid], '*', MUST_EXIST);
    $courseid = $recurring->courseid;
}

if (!$courseid) {
    throw new moodle_exception('missingparam', '', '', 'courseid, recurringid, or scheduleid');
}

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Check if user has permission to manage course reminders
$systemcontext = context_system::instance();
$hassiteaccess = has_capability('local/course_reminder:manage', $systemcontext);
$hascourseaccess = has_capability('local/course_reminder:manage', $context);

if (!$hassiteaccess && !$hascourseaccess) {
    throw new required_capability_exception($context, 'local/course_reminder:manage', 'nopermissions', '');
}

$urlparams = [];
if ($scheduleid) {
    $urlparams['scheduleid'] = $scheduleid;
} elseif ($recurringid) {
    $urlparams['recurringid'] = $recurringid;
} else {
    $urlparams['courseid'] = $courseid;
}

$PAGE->set_url(new moodle_url('/local/course_reminder/incomplete.php', $urlparams));
$PAGE->set_context($context);
$PAGE->set_title("Pending Completions: $course->fullname");
$PAGE->set_heading("Pending Completions: $course->fullname");

// Get reminder settings
$reminder = $DB->get_record('local_course_reminder_courses', ['courseid' => $courseid]);
$nextdue = $reminder && $reminder->nextrun ? userdate($reminder->nextrun) : 'Not scheduled';

// Completion info
$completion = new completion_info($course);

// Load students
$studentroleid = 5;
$users = get_role_users($studentroleid, $context, false, 'u.id, u.firstname, u.lastname, u.email');

// Handle form submission
if (optional_param('save', false, PARAM_BOOL) && confirm_sesskey()) {
    $excluded = optional_param_array('exclude', [], PARAM_INT);

    // Get all users currently excluded from DB for this specific schedule or course
    $excludeparams = ['courseid' => $courseid];
    if ($scheduleid) {
        $excludeparams['scheduleid'] = $scheduleid;
    } else {
        // For recurring reminders, get those without scheduleid
        $sql = "SELECT userid, id FROM {local_course_reminder_excl} 
                WHERE courseid = ? AND scheduleid IS NULL";
        $existingexclusions = $DB->get_records_sql_menu($sql, [$courseid]);
    }
    
    if ($scheduleid) {
        $existingexclusions = $DB->get_records_menu(
            'local_course_reminder_excl',
            $excludeparams,
            '',
            'userid, id'
        );
    }

    foreach ($users as $user) {
        $userid = $user->id;
        $wasexcluded = isset($existingexclusions[$userid]);
        $nowexcluded = isset($excluded[$userid]);

        if ($nowexcluded && !$wasexcluded) {
            $record = (object)[
                'courseid' => $courseid,
                'userid' => $userid,
                'timecreated' => time()
            ];
            if ($scheduleid) {
                $record->scheduleid = $scheduleid;
            }
            $DB->insert_record('local_course_reminder_excl', $record);

            \local_course_reminder\event\user_exclusion_updated::create([
                'objectid' => $USER->id,
                'context' => $context,
                'relateduserid' => $userid,
                'other' => [
                    'courseid' => $courseid,
                    'action' => 'excluded'
                ]
            ])->trigger();

        } elseif (!$nowexcluded && $wasexcluded) {
            $deleteparams = [
                'courseid' => $courseid,
                'userid' => $userid
            ];
            if ($scheduleid) {
                $deleteparams['scheduleid'] = $scheduleid;
            } else {
                // For recurring, only delete where scheduleid IS NULL
                $sql = "DELETE FROM {local_course_reminder_excl} 
                        WHERE courseid = ? AND userid = ? AND scheduleid IS NULL";
                $DB->execute($sql, [$courseid, $userid]);
                continue; // Skip the regular delete
            }
            $DB->delete_records('local_course_reminder_excl', $deleteparams);

            \local_course_reminder\event\user_exclusion_updated::create([
                'objectid' => $USER->id,
                'context' => $context,
                'relateduserid' => $userid,
                'other' => [
                    'courseid' => $courseid,
                    'action' => 'restored'
                ]
            ])->trigger();
        }
    }

    redirect($PAGE->url, 'Exclusions updated successfully.', 2);
}

// ==== Page Output ====
echo $OUTPUT->header();

echo html_writer::start_div('row');

// === Sidebar ===
echo html_writer::start_div('col-md-3');

require_once(__DIR__ . '/sidebar.php');

echo html_writer::end_div();

// === Main content ===
echo html_writer::start_div('col-md-9');
echo $OUTPUT->heading("Users who have NOT completed this course");

if (!$completion->is_enabled()) {
    echo $OUTPUT->notification('Completion tracking is not enabled for this course.', 'notifyproblem');
    echo html_writer::end_div(); // col-md-9
    echo html_writer::end_div(); // row
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = ['Firstname', 'Lastname', 'Email', 'Next Reminder Due', 'Exclude?'];
$table->data = [];

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false)
]);
echo html_writer::input_hidden_params($PAGE->url);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

foreach ($users as $user) {
    $percent = progress::get_course_progress_percentage($course, $user->id);
    if ($percent < 100) {
        // Check exclusion for this specific schedule or recurring reminder
        if ($scheduleid) {
            $isexcluded = $DB->record_exists('local_course_reminder_excl', [
                'courseid' => $courseid,
                'userid' => $user->id,
                'scheduleid' => $scheduleid
            ]);
        } else {
            // For recurring reminders, check where scheduleid IS NULL
            $sql = "SELECT COUNT(*) FROM {local_course_reminder_excl} 
                    WHERE courseid = ? AND userid = ? AND scheduleid IS NULL";
            $isexcluded = $DB->count_records_sql($sql, [$courseid, $user->id]) > 0;
        }
        $checkbox = html_writer::checkbox("exclude[{$user->id}]", 1, $isexcluded);
        $table->data[] = [
            s($user->firstname),
            s($user->lastname),
            s($user->email),
            $nextdue,
            $checkbox
        ];
    }
}

echo html_writer::table($table);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'save',
    'value' => 'Save Exclusions',
    'class' => 'btn btn-primary mt-3'
]);

echo html_writer::end_tag('form');

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row

echo $OUTPUT->footer();

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
 * Class send_scheduled_reminders
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_reminder\task;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/course_reminder/lib.php');

/**
 * Scheduled task to send course completion reminders.
 */
class send_reminders extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('sendreminders', 'local_course_reminder');
    }

    public function execute() {
        global $DB;

        mtrace("Running Course Completion Reminder task...");
        $now = time();

        // === 1. Send recurring reminders ===
        $courses = $DB->get_records_select('local_course_reminder_courses',
            'enabled = 1 AND (nextrun IS NULL OR nextrun <= ?)', [$now]);

        foreach ($courses as $courseconfig) {
            $course = $DB->get_record('course', ['id' => $courseconfig->courseid], '*', MUST_EXIST);
            if ($course->id == SITEID) {
                mtrace("Skipping site course ID {$course->id}");
                continue;
            }

            $completion = new \completion_info($course);
            if (!$completion->is_enabled()) {
                mtrace("Completion not enabled for course {$course->id}");
                continue;
            }

            $context = \context_course::instance($course->id);
            $users = get_enrolled_users($context, '', 0,
                'u.id, u.firstname, u.lastname, u.email, u.username');

            $template = \local_course_reminder_get_course_template($courseconfig);
            $remindersent = 0;

            foreach ($users as $user) {
                try {
                    if ($completion->is_course_complete($user->id)) {
                        continue;
                    }
                } catch (\Exception $e) {
                    mtrace("Error checking completion for user {$user->id} in course {$course->id}: {$e->getMessage()}");
                    continue;
                }

                // Skip if excluded
                $excluded = $DB->record_exists('local_course_reminder_excl', [
                    'courseid' => $course->id,
                    'userid' => $user->id
                ]);

                if ($excluded) {
                    continue;
                }

                $status = \local_course_reminder_send_reminder($user, $course, $template);
                if ($status) {
                    mtrace("Reminder SENT to {$user->id} for course {$course->id}");
                    $remindersent++;
                } else {
                    mtrace("Reminder FAILED to {$user->id} for course {$course->id}");
                    $DB->insert_record('local_course_reminder_retry', [
                        'userid' => $user->id,
                        'courseid' => $course->id,
                        'templateid' => $template->id ?? null,
                        'customsubject' => $template->subject ?? '',
                        'custommessage' => $template->body ?? '',
                        'messageformat' => $template->format ?? FORMAT_HTML,
                        'retrycount' => 1,
                        'lastattempttime' => time(),
                        'status' => 'pending'
                    ]);
                }
            }

            if ($remindersent === 0) {
                mtrace("No reminders sent for course {$course->id} (all users completed or excluded)");
            }

            // ✅ Always schedule next run regardless of send success
            $days = max(1, (int)($courseconfig->frequencydays ?? 1));
            $courseconfig->nextrun = $now + ($days * DAYSECS);
            $courseconfig->timemodified = $now;
            $DB->update_record('local_course_reminder_courses', $courseconfig);
            mtrace("Scheduled next run for course {$course->id} at " . userdate($courseconfig->nextrun));
        }

        // === 2. Retry failed sends ===
        mtrace("Checking failed sends for retry...");
        $retryrecords = $DB->get_records_select('local_course_reminder_retry',
            'status = ? AND retrycount < 3', ['pending']);

        foreach ($retryrecords as $retry) {
            $user = $DB->get_record('user', ['id' => $retry->userid], '*', IGNORE_MISSING);
            $course = $DB->get_record('course', ['id' => $retry->courseid], '*', IGNORE_MISSING);

            if (!$user || !$course) {
                $retry->status = 'failed';
                $DB->update_record('local_course_reminder_retry', $retry);
                continue;
            }

            $completion = new \completion_info($course);
            try {
                $coursecomplete = $completion->is_course_complete($user->id);
            } catch (\Exception $e) {
                mtrace("Error during retry check for user {$user->id} in course {$course->id}: {$e->getMessage()}");
                continue;
            }

            if ($coursecomplete) {
                $retry->status = 'completed';
                $DB->update_record('local_course_reminder_retry', $retry);
                continue;
            }

            $template = (object)[
                'subject' => $retry->customsubject,
                'body' => $retry->custommessage,
                'format' => $retry->messageformat,
            ];

            $status = \local_course_reminder_send_reminder($user, $course, $template);
            if ($status) {
                mtrace("Retry SUCCESS for user {$user->id} course {$course->id}");
                $DB->delete_records('local_course_reminder_retry', ['id' => $retry->id]);
            } else {
                $retry->retrycount += 1;
                $retry->lastattempttime = time();
                if ($retry->retrycount >= 3) {
                    $retry->status = 'failed';
                }
                $DB->update_record('local_course_reminder_retry', $retry);
                mtrace("Retry FAILED for user {$user->id} course {$course->id} [attempt {$retry->retrycount}]");
            }
        }

        mtrace("Finished Course Completion Reminder task.");
    }
}


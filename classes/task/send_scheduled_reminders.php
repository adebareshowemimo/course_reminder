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

require_once($GLOBALS['CFG']->dirroot . '/local/course_reminder/lib.php');
require_once($GLOBALS['CFG']->libdir . '/completionlib.php');

class send_scheduled_reminders extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('send_scheduled_reminders', 'local_course_reminder');
    }

    public function execute() {
        global $DB, $OUTPUT;

        $now = time();

        // Fetch all pending reminders due now or earlier.
        $reminders = $DB->get_records_select('local_course_reminder_schedule',
            'status = ? AND sendtime <= ?', ['pending', $now]);

        foreach ($reminders as $reminder) {
            try {
                // Get course and enrolled users.
                $course = $DB->get_record('course', ['id' => $reminder->courseid], '*', MUST_EXIST);
                $context = \context_course::instance($course->id);
                $users = get_enrolled_users($context, '', 0, 'u.*');

                if (!$users) {
                    throw new \moodle_exception('No users found');
                }

                // Prepare course completion object.
                $completion = new \completion_info($course);

                // Get template based on priority: custom > template > default
                $template = null;
                if (!empty($reminder->use_custom_subject) || !empty($reminder->use_custom_message)) {
                    // Custom subject/message enabled
                    if (!empty($reminder->templateid)) {
                        $template = $DB->get_record('local_course_reminder_tpls', ['id' => $reminder->templateid]);
                    }
                    $subject = !empty($reminder->use_custom_subject) && !empty($reminder->customsubject) 
                        ? $reminder->customsubject 
                        : ($template->subject ?? get_config('local_course_reminder', 'defaultsubject'));
                    $message = !empty($reminder->use_custom_message) && !empty($reminder->custommessage) 
                        ? $reminder->custommessage 
                        : ($template->body ?? get_config('local_course_reminder', 'defaultmessage'));
                } else if (!empty($reminder->templateid)) {
                    // Use selected template
                    $template = $DB->get_record('local_course_reminder_tpls', ['id' => $reminder->templateid]);
                    $subject = $template->subject ?? get_config('local_course_reminder', 'defaultsubject');
                    $message = $template->body ?? get_config('local_course_reminder', 'defaultmessage');
                } else {
                    // Use default
                    $subject = get_config('local_course_reminder', 'defaultsubject');
                    $message = get_config('local_course_reminder', 'defaultmessage');
                }
                $format = $reminder->messageformat ?? FORMAT_HTML;

                // Apply colors to message
                $message = \local_course_reminder\template_helper::apply_colors($message);

                $sent = 0;
                $from = \core_user::get_support_user();
                
                foreach ($users as $user) {
                    // ✅ Skip if user has completed the course.
                    if ($completion->is_course_complete($user->id)) {
                        continue;
                    }

                    // ✅ Skip if user is excluded from this specific schedule
                    $excluded = $DB->record_exists('local_course_reminder_excl', [
                        'courseid' => $course->id,
                        'userid' => $user->id,
                        'scheduleid' => $reminder->id
                    ]);
                    if ($excluded) {
                        continue;
                    }

                    // Build course URL
                    $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
                    
                    // Get logo URLs
                
                      $logourl = $OUTPUT->get_logo_url();
                     $compactlogourl = $OUTPUT->get_compact_logo_url();
                    
                    // Replace placeholders for this user
                    $placeholders = [
                        '{{firstname}}' => $user->firstname,
                        '{{lastname}}' => $user->lastname,
                        '{{fullname}}' => fullname($user),
                        '{{email}}' => $user->email,
                        '{{coursename}}' => $course->fullname,
                        '{{completionlink}}' => $courseurl->out(),
                        '{{courseurl}}' => $courseurl->out(),
                        '{firstname}' => $user->firstname,
                        '{lastname}' => $user->lastname,
                        '{fullname}' => fullname($user),
                        '{email}' => $user->email,
                        '{coursename}' => $course->fullname,
                        '{courseurl}' => $courseurl->out(),
                        '{sitelogo}' => $logourl ? $logourl->out(false) : '',
                        '{sitelogocompact}' => $compactlogourl ? $compactlogourl->out(false) : '',
                    ];
                    
                    $user_subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
                    $user_message = str_replace(array_keys($placeholders), array_values($placeholders), $message);

                    // ✅ Send reminder.
                    $status = email_to_user(
                        $user,
                        $from,
                        $user_subject,
                        html_to_text($user_message),
                        $user_message,
                        '',
                        '',
                        true,
                        $format
                    );

                    if ($status) {
                        $sent++;
                    }
                }

                if ($sent > 0) {
                    // ✅ Mark reminder as sent
                    $reminder->status = 'sent';
                    $reminder->timesent = $now;
                } else {
                    // ❌ No eligible users
                    $reminder->status = 'skipped';
                    $reminder->error = 'All users already completed the course.';
                }

                $DB->update_record('local_course_reminder_schedule', $reminder);
            } catch (\Throwable $e) {
                debugging('Failed to send reminder ID ' . $reminder->id . ': ' . $e->getMessage());

                $reminder->status = 'failed';
                $reminder->error = $e->getMessage();
                $DB->update_record('local_course_reminder_schedule', $reminder);
            }
        }
    }
}

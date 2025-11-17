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
 * Callback implementations for OG Course Completion Reminder
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Send reminder email to a user with dynamic content.
 */
function local_course_reminder_send_reminder($user, $course, $template) {
    global $CFG, $DB;

    $from = \core_user::get_support_user();
    $context = \context_course::instance($course->id);

    // Fallback defaults
    $subject = $template->subject ?? get_config('local_course_reminder', 'defaultsubject');
    $body = $template->body ?? get_config('local_course_reminder', 'defaultmessage');
    $format = $template->format ?? FORMAT_HTML;

    // Apply colors to template body
    $body = \local_course_reminder\template_helper::apply_colors($body);
    
    // Build completion link
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);

    // Replace placeholders (both {{}} and {} formats)
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
        '{sitelogo}' => $CFG->wwwroot . '/theme/image.php/boost/core/1/logo',
        '{sitelogocompact}' => $CFG->wwwroot . '/theme/image.php/boost/core/1/logo_compact',
    ];

    $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
    $body = str_replace(array_keys($placeholders), array_values($placeholders), $body);

    // Send email
    $success = email_to_user($user, $from, $subject, strip_tags($body), $body);

    // Log result
    $log = (object)[
        'userid' => $user->id,
        'courseid' => $course->id,
        'type' => 'auto',
        'subject' => $subject,
        'body' => $body,
        'status' => $success ? 'sent' : 'failed',
        'timecreated' => time(),
    ];
    $DB->insert_record('local_course_reminder_logs', $log);

    return $success;
}

/**
 * Get the effective template for a course.
 */
function local_course_reminder_get_course_template($courseconfig) {
    global $DB;

    // Priority 1: Custom subject/message (when checkboxes enabled)
    if (!empty($courseconfig->use_custom_subject) || !empty($courseconfig->use_custom_message)) {
        $template = $DB->get_record('local_course_reminder_tpls', ['id' => $courseconfig->templateid]);
        
        return (object)[
            'subject' => !empty($courseconfig->use_custom_subject) && !empty($courseconfig->customsubject) 
                ? $courseconfig->customsubject 
                : ($template->subject ?? get_config('local_course_reminder', 'defaultsubject')),
            'body' => !empty($courseconfig->use_custom_message) && !empty($courseconfig->custommessage) 
                ? $courseconfig->custommessage 
                : ($template->body ?? get_config('local_course_reminder', 'defaultmessage')),
            'format' => $courseconfig->messageformat ?? FORMAT_HTML,
        ];
    }

    // Priority 2: Selected template
    if (!empty($courseconfig->templateid)) {
        return $DB->get_record('local_course_reminder_tpls', ['id' => $courseconfig->templateid]);
    }

    // Priority 3: Default template
    return (object)[
        'subject' => get_config('local_course_reminder', 'defaultsubject'),
        'body' => get_config('local_course_reminder', 'defaultmessage'),
        'format' => FORMAT_HTML,
    ];
}

/**
 * Adds "Course Reminder" to the course navigation menu.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_course_reminder_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/local/course_reminder/index.php', ['courseid' => $course->id]);
        $node = navigation_node::create(
            get_string('pluginname', 'local_course_reminder'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'local_course_reminder',
            new pix_icon('i/calendar', '')
        );
        $navigation->add_node($node);
    }
}


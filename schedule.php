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
 * TODO describe file schedule
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();

// If no courseid provided, try to get from current page context
if (!$courseid && $PAGE->course && $PAGE->course->id > 1) {
    $courseid = $PAGE->course->id;
}

// Check if accessing from course context or site context
if ($courseid) {
    // Course-level access
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($course->id);
    require_capability('moodle/course:update', $context);
    
    $PAGE->set_course($course);
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/course_reminder/schedule.php', ['courseid' => $courseid]));
    $PAGE->set_title(get_string('pluginname', 'local_course_reminder'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('incourse');
} else {
    // Site-level access (admin only)
    $systemcontext = context_system::instance();
    
    // Check if user has site-level capability OR any course-level capability
    if (!has_capability('local/course_reminder:manage', $systemcontext)) {
        // Check if they have access to at least one course
        $sql = "SELECT DISTINCT c.id
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                WHERE ra.userid = :userid AND c.id > 1";
        
        $courses = $DB->get_records_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $USER->id
        ]);
        
        if (!empty($courses)) {
            // Get first accessible course with proper capability check
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id);
                if (has_capability('moodle/course:update', $coursecontext)) {
                    // Redirect to first accessible course
                    redirect(new moodle_url('/local/course_reminder/schedule.php', ['courseid' => $course->id]), 
                        'Redirecting to schedule reminder', 2);
                    break;
                }
            }
        }
        
        // No access at all
        require_capability('local/course_reminder:manage', $systemcontext);
    }
    
    admin_externalpage_setup('local_course_reminder_schedule');
    $PAGE->set_url(new moodle_url('/local/course_reminder/schedule.php'));
    $PAGE->set_title('Schedule One-Time Reminder');
    $PAGE->set_heading('Schedule One-Time Reminder');
}

// Sidebar navigation
$navitems = [
    'Manage Reminders' => new moodle_url('/local/course_reminder/manage.php'),
    'Email Templates' => new moodle_url('/local/course_reminder/templates.php'),
    'Global Settings' => new moodle_url('/admin/settings.php', ['section' => 'local_course_reminder']),
    'One-Time Schedule' => new moodle_url('/local/course_reminder/schedule.php'),
    'Retry Log' => new moodle_url('/local/course_reminder/retrylog.php'),
];

$sidebar = html_writer::start_div('list-group');
foreach ($navitems as $label => $url) {
    $active = $PAGE->url->compare($url, URL_MATCH_BASE) ? ' active' : '';
    $sidebar .= html_writer::link($url, $label, ['class' => 'list-group-item list-group-item-action' . $active]);
}
$sidebar .= html_writer::end_div();

// === Handle actions ===
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

if ($action && $id && confirm_sesskey()) {
    // Check permission for the specific course
    $reminder = $DB->get_record('local_course_reminder_schedule', ['id' => $id], '*', MUST_EXIST);
    $coursecontext = context_course::instance($reminder->courseid);
    require_capability('moodle/course:update', $coursecontext);
    
    // Preserve courseid in redirect if we're in course context
    $redirecturl = $courseid ? new moodle_url('/local/course_reminder/schedule.php', ['courseid' => $courseid]) : $PAGE->url;
    
    if ($action === 'delete') {
        $DB->delete_records('local_course_reminder_schedule', ['id' => $id]);
        redirect($redirecturl, "Reminder deleted");
    } elseif ($action === 'disable') {
        $DB->set_field('local_course_reminder_schedule', 'status', 'disabled', ['id' => $id]);
        redirect($redirecturl, "Reminder disabled");
    } elseif ($action === 'enable') {
        $DB->set_field('local_course_reminder_schedule', 'status', 'pending', ['id' => $id]);
        redirect($redirecturl, "Reminder enabled");
    }
}

// === Form class ===
class local_course_reminder_schedule_form extends moodleform {
    public function definition() {
        global $DB, $USER;
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $presetcourseid = isset($customdata['courseid']) ? $customdata['courseid'] : 0;

        // Get courses based on user permissions
        $systemcontext = context_system::instance();
        $hassiteaccess = has_capability('local/course_reminder:manage', $systemcontext);
        
        $courseoptions = [];
        
        if ($presetcourseid) {
            // Course context - only show the current course
            $course = $DB->get_record('course', ['id' => $presetcourseid], 'id, fullname', MUST_EXIST);
            $courseoptions[$course->id] = format_string($course->fullname);
        } elseif ($hassiteaccess) {
            // Site-wide access - show all courses
            $courses = $DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id, fullname');
            foreach ($courses as $course) {
                $courseoptions[$course->id] = format_string($course->fullname);
            }
        } else {
            // Course-level access - only show courses where user has capability
            $allcourses = $DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id, fullname');
            foreach ($allcourses as $course) {
                $coursecontext = context_course::instance($course->id);
                if (has_capability('moodle/course:update', $coursecontext)) {
                    $courseoptions[$course->id] = format_string($course->fullname);
                }
            }
        }

        $mform->addElement('autocomplete', 'courseid', 'Course', $courseoptions);
        $mform->addRule('courseid', null, 'required');
        
        // Freeze course selector if accessed from course context
        if ($presetcourseid) {
            $mform->freeze('courseid');
        }

        $mform->addElement('date_time_selector', 'sendtime', 'Send Date & Time');

        $templates = $DB->get_records_menu('local_course_reminder_tpls', null, 'name ASC', 'id, name');
        $templates = ['' => 'Use default template'] + $templates;
        $mform->addElement('select', 'templateid', 'Email Template', $templates);

        // Custom Subject Section
        $mform->addElement('advcheckbox', 'use_custom_subject', 'Use Custom Subject', 'Override template/default subject');
        
        $mform->addElement('text', 'customsubject', 'Custom Subject', ['size' => 60]);
        $mform->setType('customsubject', PARAM_TEXT);
        $mform->hideIf('customsubject', 'use_custom_subject', 'notchecked');
        
        // Custom Message Section
        $mform->addElement('advcheckbox', 'use_custom_message', 'Use Custom Message', 'Override template/default message with custom HTML');
        
        $mform->addElement('editor', 'custommessage_editor', 'Custom Message', ['rows' => 20], ['maxfiles' => 0]);
        $mform->setType('custommessage_editor', PARAM_RAW);
        $mform->hideIf('custommessage_editor', 'use_custom_message', 'notchecked');
        
        // Placeholder info - matching edit_template.php style
        $placeholderinfo = html_writer::start_div('alert alert-info mt-3');
        $placeholderinfo .= html_writer::tag('h5', 'Available Placeholders');
        $placeholderinfo .= html_writer::tag('p', 'Use these placeholders in your template. They will be replaced with actual values when emails are sent:');
        $placeholderinfo .= html_writer::start_tag('ul');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{firstname}') . ' or ' . html_writer::tag('strong', '{{firstname}}') . ' - User\'s first name');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{lastname}') . ' or ' . html_writer::tag('strong', '{{lastname}}') . ' - User\'s last name');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{fullname}') . ' or ' . html_writer::tag('strong', '{{fullname}}') . ' - User\'s full name');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{email}') . ' or ' . html_writer::tag('strong', '{{email}}') . ' - User\'s email address');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{coursename}') . ' or ' . html_writer::tag('strong', '{{coursename}}') . ' - Course full name');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{courseurl}') . ' or ' . html_writer::tag('strong', '{{completionlink}}') . ' - Link to the course');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{sitelogo}') . ' - Site logo URL');
        $placeholderinfo .= html_writer::tag('li', html_writer::tag('strong', '{sitelogocompact}') . ' - Site compact logo URL');
        $placeholderinfo .= html_writer::end_tag('ul');
        $placeholderinfo .= html_writer::end_div();
        $mform->addElement('static', 'placeholder_info', '', $placeholderinfo);

        $this->add_action_buttons(true, 'Schedule Reminder');
    }

    public function get_data_for_db() {
        $data = $this->get_data();
        
        // Handle custom message
        if (!empty($data->use_custom_message)) {
            $data->custommessage = $data->custommessage_editor['text'];
            $data->messageformat = $data->custommessage_editor['format'];
        } else {
            $data->custommessage = null;
            $data->messageformat = null;
        }
        unset($data->custommessage_editor);
        
        // Handle custom subject
        if (empty($data->use_custom_subject)) {
            $data->customsubject = null;
        }
        
        return $data;
    }
}

// === Form processing ===
$form = new local_course_reminder_schedule_form(null, ['courseid' => $courseid]);
if ($form->is_cancelled()) {
    if ($courseid) {
        redirect(new moodle_url('/local/course_reminder/index.php', ['courseid' => $courseid]));
    } else {
        redirect($PAGE->url);
    }
} elseif ($formdata = $form->get_data()) {
    // Check permission for the selected course
    $coursecontext = context_course::instance($formdata->courseid);
    require_capability('moodle/course:update', $coursecontext);
    
    $data = $form->get_data_for_db();
    $record = (object)[
        'courseid' => $data->courseid,
        'templateid' => $data->templateid ?? null,
        'use_custom_subject' => !empty($data->use_custom_subject) ? 1 : 0,
        'use_custom_message' => !empty($data->use_custom_message) ? 1 : 0,
        'customsubject' => $data->customsubject,
        'custommessage' => $data->custommessage,
        'messageformat' => $data->messageformat ?? FORMAT_HTML,
        'sendtime' => $data->sendtime,
        'status' => 'pending',
        'timecreated' => time()
    ];

    $DB->insert_record('local_course_reminder_schedule', $record);
    redirect($PAGE->url, "One-time reminder scheduled.", 2);
}

// === Toggle view: Show all vs pending only ===
$showall = optional_param('showall', 0, PARAM_BOOL);

// Check user permissions for filtering
$systemcontext = context_system::instance();
$hassiteaccess = has_capability('local/course_reminder:manage', $systemcontext);

if ($courseid) {
    // Course context - filter by courseid
    if ($showall) {
        $schedules = $DB->get_records_sql("
            SELECT s.*, c.fullname AS coursename, t.name AS templatename
            FROM {local_course_reminder_schedule} s
            JOIN {course} c ON c.id = s.courseid
            LEFT JOIN {local_course_reminder_tpls} t ON t.id = s.templateid
            WHERE c.id = :courseid
            ORDER BY s.sendtime ASC
        ", ['courseid' => $courseid]);
    } else {
        $schedules = $DB->get_records_sql("
            SELECT s.*, c.fullname AS coursename, t.name AS templatename
            FROM {local_course_reminder_schedule} s
            JOIN {course} c ON c.id = s.courseid
            LEFT JOIN {local_course_reminder_tpls} t ON t.id = s.templateid
            WHERE s.status = 'pending' AND c.id = :courseid
            ORDER BY s.sendtime ASC
        ", ['courseid' => $courseid]);
    }
} elseif ($showall) {
    $schedules = $DB->get_records_sql("
        SELECT s.*, c.fullname AS coursename, t.name AS templatename
        FROM {local_course_reminder_schedule} s
        JOIN {course} c ON c.id = s.courseid
        LEFT JOIN {local_course_reminder_tpls} t ON t.id = s.templateid
        WHERE c.id > 1
        ORDER BY s.sendtime ASC
    ");
} else {
    $schedules = $DB->get_records_sql("
        SELECT s.*, c.fullname AS coursename, t.name AS templatename
        FROM {local_course_reminder_schedule} s
        JOIN {course} c ON c.id = s.courseid
        LEFT JOIN {local_course_reminder_tpls} t ON t.id = s.templateid
        WHERE c.id > 1 AND s.status = 'pending'
        ORDER BY s.sendtime ASC
    ");
}

// Filter by capability if not site-wide access and not already filtered by courseid
if (!$hassiteaccess && !$courseid) {
    $filteredschedules = [];
    foreach ($schedules as $schedule) {
        $coursecontext = context_course::instance($schedule->courseid);
        if (has_capability('moodle/course:update', $coursecontext)) {
            $filteredschedules[$schedule->id] = $schedule;
        }
    }
    $schedules = $filteredschedules;
}

// === Output starts ===
echo $OUTPUT->header();
echo html_writer::start_div('row');


// Sidebar
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();
// Main
echo html_writer::start_div('col-md-9');
echo $OUTPUT->heading('Schedule a One-Time Reminder');
$form->display();

// Toggle view button
$toggleurl = new moodle_url($PAGE->url, ['showall' => $showall ? 0 : 1]);
$togglelabel = $showall ? 'Show Pending Only' : 'Show All Reminders';
echo html_writer::div($OUTPUT->single_button($toggleurl, $togglelabel, 'get'), 'mb-3');

// Scheduled list
echo $OUTPUT->heading('Scheduled Reminders', 3);

if ($schedules) {
    $table = new html_table();
    $table->head = ['Course', 'Send Time', 'Template', 'Status', 'Actions'];
    $table->data = [];

    foreach ($schedules as $s) {
        // Build URLs - preserve courseid if in course context
        $urlparams = $courseid ? ['courseid' => $courseid] : [];
        
        $editurl = new moodle_url($PAGE->url, array_merge($urlparams, ['id' => $s->id]));
        $deleteurl = new moodle_url($PAGE->url, array_merge($urlparams, ['action' => 'delete', 'id' => $s->id, 'sesskey' => sesskey()]));
        $toggleurl = new moodle_url($PAGE->url, array_merge($urlparams, [
            'action' => ($s->status === 'disabled' ? 'enable' : 'disable'),
            'id' => $s->id,
            'sesskey' => sesskey()
        ]));
        $viewurl = new moodle_url('/local/course_reminder/incomplete.php', ['scheduleid' => $s->id]);

        $table->data[] = [
            format_string($s->coursename),
            userdate($s->sendtime),
            $s->templatename ?? 'Custom',
            ucfirst($s->status),
            html_writer::link($editurl, 'Edit') . ' | ' .
            html_writer::link($toggleurl, $s->status === 'disabled' ? 'Enable' : 'Disable') . ' | ' .
            html_writer::link($deleteurl, 'Delete') . ' | ' .
            html_writer::link($viewurl, 'View Upcoming Reminders')
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div('No scheduled reminders.', 'alert alert-info');
}

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row
echo $OUTPUT->footer();

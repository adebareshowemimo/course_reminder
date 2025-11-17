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
 * TODO describe file manage
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

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
    $PAGE->set_url(new moodle_url('/local/course_reminder/manage.php', ['courseid' => $courseid]));
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
                    redirect(new moodle_url('/local/course_reminder/manage.php', ['courseid' => $course->id]), 
                        'Redirecting to course reminder management', 2);
                    break;
                }
            }
        }
        
        // No access at all
        require_capability('local/course_reminder:manage', $systemcontext);
    }
    
    admin_externalpage_setup('local_course_reminder_manage');
    $PAGE->set_url(new moodle_url('/local/course_reminder/manage.php'));
    $PAGE->set_title('Course Reminder Settings');
    $PAGE->set_heading('Course Reminder Settings');
}

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

// === Handle actions (enable, disable, delete) ===
if ($action && $id && confirm_sesskey()) {
    // Check permission for the specific course
    $reminder = $DB->get_record('local_course_reminder_courses', ['id' => $id], '*', MUST_EXIST);
    $coursecontext = context_course::instance($reminder->courseid);
    require_capability('moodle/course:update', $coursecontext);
    
    // Preserve courseid in redirect if we're in course context
    $redirecturl = $courseid ? new moodle_url('/local/course_reminder/manage.php', ['courseid' => $courseid]) : $PAGE->url;
    
    if ($action === 'delete') {
        $DB->delete_records('local_course_reminder_courses', ['id' => $id]);
        redirect($redirecturl, 'Reminder deleted');
    } elseif ($action === 'disable') {
        $DB->set_field('local_course_reminder_courses', 'enabled', 0, ['id' => $id]);
        redirect($redirecturl, 'Reminder disabled');
    } elseif ($action === 'enable') {
        $DB->set_field('local_course_reminder_courses', 'enabled', 1, ['id' => $id]);
        redirect($redirecturl, 'Reminder enabled');
        redirect($PAGE->url, 'Reminder enabled');
    }
}

// === Define form class ===
class local_course_reminder_form extends moodleform {
    public function definition() {
        global $DB, $USER;

        $mform = $this->_form;
        $customdata = $this->_customdata;
        $presetcourseid = isset($customdata['courseid']) ? $customdata['courseid'] : 0;

        // Get courses based on user permissions
        $systemcontext = context_system::instance();
        $hassiteaccess = has_capability('local/course_reminder:manage', $systemcontext);
        
        $courseoptions = ['' => 'Select a course...'];
        
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

        $mform->addElement('advcheckbox', 'enabled', 'Enable Reminder');

        $frequencyoptions = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'custom' => 'Custom (every X days)',
        ];
        $mform->addElement('select', 'frequencytype', 'Reminder Frequency', $frequencyoptions);
        $mform->setDefault('frequencytype', 'weekly');

        $mform->addElement('text', 'frequencydays', 'Number of Days (for Custom)');
        $mform->setType('frequencydays', PARAM_INT);
        $mform->addRule('frequencydays', 'Please enter number of days', 'numeric', null, 'client');
        $mform->hideIf('frequencydays', 'frequencytype', 'neq', 'custom');

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

        $this->add_action_buttons();
    }

    public function set_data_from_record($record) {
        $record->custommessage_editor = ['text' => $record->custommessage ?? '', 'format' => FORMAT_HTML];
        $record->use_custom_subject = !empty($record->customsubject) ? 1 : 0;
        $record->use_custom_message = !empty($record->custommessage) ? 1 : 0;
        parent::set_data($record);
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
        unset($data->use_custom_subject);
        unset($data->use_custom_message);

        switch ($data->frequencytype) {
            case 'daily':
                $data->frequencydays = 1;
                break;
            case 'weekly':
                $data->frequencydays = 7;
                break;
            case 'custom':
                $data->frequencydays = max(1, (int)$data->frequencydays);
                break;
            default:
                $data->frequencydays = 7;
        }

        return $data;
    }
}

$form = new local_course_reminder_form(null, ['courseid' => $courseid]);

// === Form processing ===
if ($form->is_cancelled()) {
    if ($courseid) {
        redirect(new moodle_url('/local/course_reminder/index.php', ['courseid' => $courseid]));
    } else {
        redirect(new moodle_url('/admin/settings.php', ['section' => 'local_course_reminder']));
    }
} elseif ($formdata = $form->get_data()) {
    // Check permission for the selected course
    $coursecontext = context_course::instance($formdata->courseid);
    require_capability('moodle/course:update', $coursecontext);
    
    $course = $DB->get_record('course', ['id' => $formdata->courseid], '*', MUST_EXIST);
    $completion = new completion_info($course);

    if (!$completion->has_criteria()) {
        redirect(new moodle_url('/local/course_reminder/howitworks.php', ['courseid' => $course->id]),
            'Course completion is not enabled. See how the system works.', 5);
    }

    $data = $form->get_data_for_db();
    $data->timemodified = time();

    if ($existing = $DB->get_record('local_course_reminder_courses', ['courseid' => $data->courseid])) {
        $data->id = $existing->id;
        $DB->update_record('local_course_reminder_courses', $data);
    } else {
        $data->id = $DB->insert_record('local_course_reminder_courses', $data);
    }

    // Update nextrun
    $data->nextrun = time() + ($data->frequencydays * DAYSECS);
    $DB->update_record('local_course_reminder_courses', [
        'id' => $data->id,
        'nextrun' => $data->nextrun,
        'timemodified' => time()
    ]);

    redirect($PAGE->url, "Settings saved", 2);
}

// === Load form data if editing ===
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $completion = new completion_info($course);

    if (!$completion->has_criteria()) {
        $redirecturl = new moodle_url('/local/course_reminder/howitworks.php', ['courseid' => $courseid]);
        redirect($redirecturl, 'Course completion is not enabled. See how the system works.', 5);
    }

    if ($existing = $DB->get_record('local_course_reminder_courses', ['courseid' => $courseid])) {
        $form->set_data_from_record($existing);
    } else {
        $form->set_data(['courseid' => $courseid, 'enabled' => 1, 'frequencydays' => 7]);
    }
}



// === Render Page ===
echo $OUTPUT->header();
echo html_writer::start_div('row');

// Sidebar
echo html_writer::start_div('col-md-3');
require_once(__DIR__ . '/sidebar.php');
echo html_writer::end_div();

// Main content
echo html_writer::start_div('col-md-9');
echo $OUTPUT->heading('Course Reminder Settings');
$form->display();

// Existing reminders
echo $OUTPUT->heading('Configured Course Reminders', 3);

// Build WHERE clause based on context
if ($courseid) {
    // Course context - show only this course's reminders
    $reminders = $DB->get_records_sql("
        SELECT r.*, c.fullname AS coursename, t.name AS templatename
        FROM {local_course_reminder_courses} r
        JOIN {course} c ON r.courseid = c.id
        LEFT JOIN {local_course_reminder_tpls} t ON r.templateid = t.id
        WHERE r.courseid = :courseid
        ORDER BY c.fullname ASC
    ", ['courseid' => $courseid]);
} else {
    // Site context - show all or filtered reminders
    $systemcontext = context_system::instance();
    $hassiteaccess = has_capability('local/course_reminder:manage', $systemcontext);

    if ($hassiteaccess) {
        // Site-wide access - show all reminders
        $reminders = $DB->get_records_sql("
            SELECT r.*, c.fullname AS coursename, t.name AS templatename
            FROM {local_course_reminder_courses} r
            JOIN {course} c ON r.courseid = c.id
            LEFT JOIN {local_course_reminder_tpls} t ON r.templateid = t.id
            WHERE c.id > 1
            ORDER BY c.fullname ASC
        ");
    } else {
        // Course-level access - only show reminders for accessible courses
        $reminders = $DB->get_records_sql("
            SELECT r.*, c.fullname AS coursename, t.name AS templatename
            FROM {local_course_reminder_courses} r
            JOIN {course} c ON r.courseid = c.id
            LEFT JOIN {local_course_reminder_tpls} t ON r.templateid = t.id
            WHERE c.id > 1
            ORDER BY c.fullname ASC
        ");
        
        // Filter by capability
        $filteredreminders = [];
        foreach ($reminders as $reminder) {
            $coursecontext = context_course::instance($reminder->courseid);
            if (has_capability('moodle/course:update', $coursecontext)) {
                $filteredreminders[$reminder->id] = $reminder;
            }
        }
        $reminders = $filteredreminders;
    }
}

if ($reminders) {
    $table = new html_table();
    $table->head = ['Course', 'Enabled', 'Frequency (days)', 'Template', 'Next Run', 'Actions'];
    $table->data = [];

    foreach ($reminders as $r) {
        // Build URLs - preserve courseid if in course context
        $urlparams = $courseid ? ['courseid' => $courseid] : [];
        
        $editurl = new moodle_url($PAGE->url, array_merge($urlparams, ['courseid' => $r->courseid]));
        $deleteurl = new moodle_url($PAGE->url, array_merge($urlparams, ['action' => 'delete', 'id' => $r->id, 'sesskey' => sesskey()]));
        $toggleurl = new moodle_url($PAGE->url, array_merge($urlparams, [
            'action' => ($r->enabled ? 'disable' : 'enable'),
            'id' => $r->id,
            'sesskey' => sesskey()
        ]));
        $incompleteurl = new moodle_url('/local/course_reminder/incomplete.php', ['recurringid' => $r->id]);

        $table->data[] = [
            format_string($r->coursename),
            $r->enabled ? 'Yes' : 'No',
            $r->frequencydays,
            $r->templatename ?? 'Default',
            ($r->nextrun ? userdate($r->nextrun) : 'Not scheduled'),
            html_writer::link($editurl, 'Edit') . ' | ' .
            html_writer::link($toggleurl, $r->enabled ? 'Disable' : 'Enable') . ' | ' .
            html_writer::link($deleteurl, 'Delete') . ' | ' .
            html_writer::link($incompleteurl, 'Pending Completions')
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div('No course reminders configured.', 'alert alert-info');
}

echo html_writer::end_div(); // col-md-9
echo html_writer::end_div(); // row
echo $OUTPUT->footer();

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
 * Upgrade steps for OG Course Completion Reminder
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    local_course_reminder
 * @category   upgrade
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_course_reminder_upgrade($oldversion) {
    global $DB;

    // 2025073001 – Retry Table
    if ($oldversion < 2025073001) {
        $table = new xmldb_table('local_course_reminder_retry');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('failreason', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('retrycount', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('lastattempttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'pending');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025073001, 'local', 'course_reminder');
    }

    // 2025080100 – Exclusion Table
    if ($oldversion < 2025080100) {
        $table = new xmldb_table('local_course_reminder_excl');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course_user_idx', XMLDB_KEY_UNIQUE, ['courseid', 'userid']);

        if (!$DB->get_manager()->table_exists($table)) {
            $DB->get_manager()->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025080100, 'local', 'course_reminder');
    }

    // 2025080200 – Install Email Templates
    if ($oldversion < 2025080200) {
        $time = time();
        $templatedir = __DIR__ . '/../email_templates/';
        
        $templates = [
            ['name' => 'Compliance Training Reminder', 'subject' => 'Action Required: Complete Your Compliance Training', 'file' => 'template1_compliance_reminder.html'],
            ['name' => 'Compliance Deadline Approaching (Final Reminder)', 'subject' => 'URGENT: Final Notice - Compliance Training Deadline', 'file' => 'template2_compliance_final_reminder.html'],
            ['name' => 'Leadership Program Invitation', 'subject' => 'Unlock Your Leadership Potential', 'file' => 'template3_leadership_invite.html'],
            ['name' => 'Marketing Skills Course - Re-engagement', 'subject' => 'Don\'t Miss Out on Marketing Skills That Drive Results!', 'file' => 'template4_marketing_reengagement.html'],
            ['name' => 'New Manager Onboarding Pathway', 'subject' => 'Welcome to Leadership - Your Management Journey Starts Here', 'file' => 'template5_new_manager_onboarding.html'],
            ['name' => 'Soft Skills / Communication Program', 'subject' => 'Master the Art of Communication', 'file' => 'template6_communication_skills.html'],
            ['name' => 'Mandatory Annual Refresher', 'subject' => 'Time for Your Annual Refresher Training', 'file' => 'template7_annual_refresher.html'],
            ['name' => 'Learning Milestone Celebration (Progress Nudge)', 'subject' => 'You\'re Halfway There! 🎉', 'file' => 'template8_milestone_celebration.html'],
            ['name' => 'Personalized Course Recommendation', 'subject' => 'A Course Perfectly Matched to Your Goals', 'file' => 'template9_personalized_recommendation.html'],
            ['name' => 'Re-activation for Inactive Learners', 'subject' => 'We Miss You! Come Back to Learning', 'file' => 'template10_inactive_reactivation.html'],
        ];
        
        foreach ($templates as $tpl) {
            $filepath = $templatedir . $tpl['file'];
            if (file_exists($filepath)) {
                // Check if template already exists by name to avoid duplicates
                $existing = $DB->get_record('local_course_reminder_tpls', ['name' => $tpl['name']]);
                if (!$existing) {
                    $template = new stdClass();
                    $template->name = $tpl['name'];
                    $template->subject = $tpl['subject'];
                    $template->body = file_get_contents($filepath);
                    $template->format = 1; // HTML format
                    $template->timecreated = $time;
                    $template->timemodified = $time;
                    $DB->insert_record('local_course_reminder_tpls', $template);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2025080200, 'local', 'course_reminder');
    }

    // 2025080300 – Add use_custom_subject and use_custom_message columns
    if ($oldversion < 2025080300) {
        $table = new xmldb_table('local_course_reminder_courses');
        
        // Add use_custom_subject field
        $field = new xmldb_field('use_custom_subject', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'templateid');
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
        
        // Add use_custom_message field
        $field = new xmldb_field('use_custom_message', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'use_custom_subject');
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025080300, 'local', 'course_reminder');
    }

    // 2025080400 – Add use_custom_subject and use_custom_message to schedule table
    if ($oldversion < 2025080400) {
        $table = new xmldb_table('local_course_reminder_schedule');
        
        // Add use_custom_subject field
        $field = new xmldb_field('use_custom_subject', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sendtime');
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }
        
        // Add use_custom_message field
        $field = new xmldb_field('use_custom_message', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'use_custom_subject');
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025080400, 'local', 'course_reminder');
    }

    // 2025080500 – Add scheduleid to exclusion table
    if ($oldversion < 2025080500) {
        $table = new xmldb_table('local_course_reminder_excl');
        
        // Drop old unique key
        $key = new xmldb_key('course_user_idx', XMLDB_KEY_UNIQUE, ['courseid', 'userid']);
        $dbman = $DB->get_manager();
        if ($dbman->find_key_name($table, $key)) {
            $dbman->drop_key($table, $key);
        }
        
        // Add scheduleid field
        $field = new xmldb_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add new unique index
        $index = new xmldb_index('course_user_schedule_idx', XMLDB_INDEX_UNIQUE, ['courseid', 'userid', 'scheduleid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025080500, 'local', 'course_reminder');
    }

    return true;
}

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
 * Post installation and migration code.
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 */
function xmldb_local_course_reminder_install() {
    global $DB;
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
    
    return true;
}

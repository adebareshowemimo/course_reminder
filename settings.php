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
 * TODO describe file settings
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if ($hassiteconfig) {
    // Custom admin setting class for default message with placeholder info
    if (!class_exists('admin_setting_course_reminder_defaultmessage')) {
        class admin_setting_course_reminder_defaultmessage extends admin_setting_confightmleditor {
            public function output_html($data, $query='') {
                $html = parent::output_html($data, $query);
            
            // Add placeholder information similar to edit_template.php
            $placeholder_info = '
            <div class="alert alert-info mt-3">
                <h5>Available Placeholders</h5>
                <p>Use these placeholders in your template. They will be replaced with actual values when emails are sent:</p>
                <ul>
                    <li><strong>{firstname}</strong> or <strong>{{firstname}}</strong> - User\'s first name</li>
                    <li><strong>{lastname}</strong> or <strong>{{lastname}}</strong> - User\'s last name</li>
                    <li><strong>{fullname}</strong> or <strong>{{fullname}}</strong> - User\'s full name</li>
                    <li><strong>{email}</strong> or <strong>{{email}}</strong> - User\'s email address</li>
                    <li><strong>{coursename}</strong> or <strong>{{coursename}}</strong> - Course full name</li>
                    <li><strong>{courseurl}</strong> or <strong>{{completionlink}}</strong> - Link to the course</li>
                    <li><strong>{sitelogo}</strong> - Site logo URL</li>
                    <li><strong>{sitelogocompact}</strong> - Site compact logo URL</li>
                </ul>
            </div>';
            
            return $html . $placeholder_info;
        }
    }
    }
    
    // Create a new admin category (only once).
    $ADMIN->add('localplugins', new admin_category('course_reminder_category', 'Course Reminder'));

    // Global settings page.
    $settings = new admin_settingpage('local_course_reminder', 'Global Settings');
    $settings->add(new admin_setting_configtext(
        'local_course_reminder/defaultsubject',
        'Default Subject',
        'Subject used if no custom subject or template is set.',
        'Reminder: Complete Your Course'
    ));
    
    // Default HTML Email Template
    $default_html_template = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Reminder</title>
    <style>
        :root {
            --color-primary: #384e91;
            --color-primary-dark: #2f417a;
            --color-accent: #f16736;
            --color-gray: #636568;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: var(--color-primary);">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="70%" valign="middle">
                                        <img src="{sitelogo}" alt="Logo" style="height: 40px; display: block;" />
                                    </td>
                                    <td width="30%" valign="middle" align="right" style="color: #ffffff; font-size: 12px;">
                                        Course Reminder
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px;">
                            <h1 style="margin: 0 0 15px 0; font-size: 28px; color: var(--color-primary); font-weight: bold;">
                                Don\'t Forget to Complete Your Course!
                            </h1>
                            <p style="margin: 0; font-size: 16px; color: var(--color-gray); line-height: 1.5;">
                                Hello {{firstname}},
                            </p>
                        </td>
                    </tr>

                    <!-- Course Details -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 15px; color: var(--color-gray); line-height: 1.6;">
                                This is a friendly reminder that you have an incomplete course that needs your attention.
                            </p>
                            
                            <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 25px 0; width: 100%; background-color: #f8f9fd; border-left: 4px solid var(--color-accent); border-radius: 4px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <p style="margin: 0 0 5px 0; font-size: 14px; color: var(--color-gray); text-transform: uppercase; letter-spacing: 1px;">
                                            Course Name
                                        </p>
                                        <p style="margin: 0; font-size: 20px; color: var(--color-primary); font-weight: bold; line-height: 1.4;">
                                            {{coursename}}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 25px 0; font-size: 15px; color: var(--color-gray); line-height: 1.6;">
                                Completing this course will help you gain valuable knowledge and skills. Take a few moments to continue your learning journey today!
                            </p>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius: 4px; background-color: var(--color-accent);">
                                        <a href="{{completionlink}}" style="display: inline-block; padding: 15px 35px; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">
                                            Continue Course →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fd; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; font-size: 14px; color: var(--color-gray); line-height: 1.6;">
                                <strong>Need Help?</strong> If you have any questions or need assistance, please contact your instructor or visit our support page.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #999999; line-height: 1.5;">
                                This is an automated reminder. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    $settings->add(new admin_setting_course_reminder_defaultmessage(
        'local_course_reminder/defaultmessage',
        'Default Message',
        'HTML email template for course reminders',
        ''
    ));
    
    // Email Template Color Settings
    $settings->add(new admin_setting_heading(
        'local_course_reminder/colorheading',
        get_string('emailcolorsettings', 'local_course_reminder'),
        get_string('emailcolorsettings_desc', 'local_course_reminder')
    ));
    
    $settings->add(new admin_setting_configcolourpicker(
        'local_course_reminder/color_primary',
        get_string('color_primary', 'local_course_reminder'),
        get_string('color_primary_desc', 'local_course_reminder'),
        '#384e91'
    ));
    
    $settings->add(new admin_setting_configcolourpicker(
        'local_course_reminder/color_primary_dark',
        get_string('color_primary_dark', 'local_course_reminder'),
        get_string('color_primary_dark_desc', 'local_course_reminder'),
        '#2f417a'
    ));
    
    $settings->add(new admin_setting_configcolourpicker(
        'local_course_reminder/color_accent',
        get_string('color_accent', 'local_course_reminder'),
        get_string('color_accent_desc', 'local_course_reminder'),
        '#f16736'
    ));
    
    $settings->add(new admin_setting_configcolourpicker(
        'local_course_reminder/color_gray',
        get_string('color_gray', 'local_course_reminder'),
        get_string('color_gray_desc', 'local_course_reminder'),
        '#636568'
    ));
    
    $ADMIN->add('course_reminder_category', $settings);

    // External pages (Manage & Templates)
    $ADMIN->add('course_reminder_category', new admin_externalpage(
        'local_course_reminder_manage',
        'Manage Course Reminders',
        '/local/course_reminder/manage.php',
        'local/course_reminder:manage'
    ));

    $ADMIN->add('course_reminder_category', new admin_externalpage(
    'local_course_reminder_schedule',
    'One-Time Schedule',
    '/local/course_reminder/schedule.php',
    'local/course_reminder:manage'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
    'local_course_reminder_retrylog',
    'Retry Log',
    '/local/course_reminder/retrylog.php',
    'moodle/site:config'
    ));

    $ADMIN->add('course_reminder_category', new admin_externalpage(
        'local_course_reminder_templates',
        'Email Templates',
        '/local/course_reminder/templates.php',
        'moodle/site:config'
    ));
}

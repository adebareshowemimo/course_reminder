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
 * English language pack for OG Course Completion Reminder
 *
 * @package    local_course_reminder
 * @category   string
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Completion Reminder';
$string['sendreminders'] = 'Send reminders';
$string['send_scheduled_reminders'] = 'Send scheduled reminders';
$string['event:userexclusionupdated'] = 'User exclusion updated';

// Email Template Color Settings
$string['emailcolorsettings'] = 'Email Template Colors';
$string['emailcolorsettings_desc'] = 'Customize the colors used in email templates. These colors will be applied to all email templates.';
$string['color_primary'] = 'Primary Color';
$string['color_primary_desc'] = 'Main brand color used for headers and primary elements (default: #384e91)';
$string['color_primary_dark'] = 'Primary Dark Color';
$string['color_primary_dark_desc'] = 'Darker shade of primary color for hover states and accents (default: #2f417a)';
$string['color_accent'] = 'Accent Color';
$string['color_accent_desc'] = 'Accent color for buttons and highlights (default: #f16736)';
$string['color_gray'] = 'Text Gray Color';
$string['color_gray_desc'] = 'Gray color for body text (default: #636568)';

// Capabilities
$string['course_reminder:manage'] = 'Manage course reminders';

// Index page strings
$string['indexintro'] = 'Choose how you want to send reminders to students in this course. You can set up regular automated reminders or schedule a one-time reminder.';
$string['regularreminders'] = 'Regular Reminders';
$string['regularremindersdesc'] = 'Set up automated reminders that run regularly based on a schedule. Perfect for ongoing course completion follow-ups and periodic student engagement.';
$string['manageregularreminders'] = 'Manage Regular Reminders';
$string['regularremindersexample'] = 'Example: Send weekly reminders to students who haven\'t completed the course';
$string['onetimereminders'] = 'One-Time Reminders';
$string['onetimeremindersdesc'] = 'Schedule a single reminder to be sent at a specific date and time. Ideal for urgent notifications or one-off communications.';
$string['scheduleonetimereminder'] = 'Schedule One-Time Reminder';
$string['onetimeremindersexample'] = 'Example: Send a reminder tomorrow at 9 AM about upcoming deadline';
$string['needhelp'] = 'Need Help?';
$string['helpdescription'] = 'Here\'s how to use each reminder type:';
$string['helpregular'] = '<strong>Regular Reminders:</strong> Use this when you want to automatically send reminders on a recurring schedule. Set up the frequency, target audience, and message template once, and the system will handle the rest.';
$string['helponetime'] = '<strong>One-Time Reminders:</strong> Use this when you need to send a reminder just once at a specific time. Choose the date and time, select your recipients, customize the message, and it will be sent automatically.';
$string['helptemplates'] = '<strong>Templates:</strong> Both types of reminders support customizable templates with placeholders like {{firstname}}, {{coursename}}, and {{completionlink}}.';

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
 * TODO describe file sidebar
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemmo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$currentpath = $PAGE->url->get_path();

// Check if user has site-wide admin access
$systemcontext = context_system::instance();
$hassiteconfig = has_capability('moodle/site:config', $systemcontext);

$navitems = [
    'Manage Reminders' => new moodle_url('/local/course_reminder/manage.php'),
];

// Only show templates and settings to site admins/managers
if ($hassiteconfig) {
    $navitems['Email Templates'] = new moodle_url('/local/course_reminder/templates.php');
    $navitems['Global Settings'] = new moodle_url('/admin/settings.php', ['section' => 'local_course_reminder']);
}

$navitems['One-Time Schedule'] = new moodle_url('/local/course_reminder/schedule.php');

// Only show retry log to site admins/managers
if ($hassiteconfig) {
    $navitems['Retry Log'] = new moodle_url('/local/course_reminder/retrylog.php');
}

$navitems['How It Works'] = new moodle_url('/local/course_reminder/howitworks.php');

if ($courseid) {
    $navitems['Incomplete Users'] = new moodle_url('/local/course_reminder/incomplete.php', ['courseid' => $courseid]);
}

$sidebar = html_writer::start_tag('div', ['class' => 'list-group']);

foreach ($navitems as $label => $url) {
    $targetpath = $url->get_path();
    $isactive = ($targetpath === $currentpath) ? ' active' : '';
    $sidebar .= html_writer::link($url, $label, [
        'class' => 'list-group-item list-group-item-action' . $isactive
    ]);
}

$sidebar .= html_writer::end_tag('div');

echo $sidebar;

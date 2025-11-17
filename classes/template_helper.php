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
 * Template helper class for course reminder
 *
 * @package    local_course_reminder
 * @copyright  2025 Adebare Showemimo <adebareshowemimo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_reminder;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for processing email templates
 */
class template_helper {
    
    /**
     * Apply color settings to email template
     *
     * @param string $body Email template body
     * @return string Template with colors applied
     */
    public static function apply_colors($body) {
        // Get color settings from config
        $color_primary = get_config('local_course_reminder', 'color_primary') ?: '#384e91';
        $color_primary_dark = get_config('local_course_reminder', 'color_primary_dark') ?: '#2f417a';
        $color_accent = get_config('local_course_reminder', 'color_accent') ?: '#f16736';
        $color_gray = get_config('local_course_reminder', 'color_gray') ?: '#636568';
        
        // Replace CSS variable definitions in :root
        $body = str_replace('--color-primary: #384e91;', '--color-primary: ' . $color_primary . ';', $body);
        $body = str_replace('--color-primary-dark: #2f417a;', '--color-primary-dark: ' . $color_primary_dark . ';', $body);
        $body = str_replace('--color-accent: #f16736;', '--color-accent: ' . $color_accent . ';', $body);
        $body = str_replace('--color-gray: #636568;', '--color-gray: ' . $color_gray . ';', $body);
        
        return $body;
    }
    
    /**
     * Replace placeholders in template with actual values
     *
     * @param string $text Text with placeholders
     * @param object $user User object
     * @param object $course Course object
     * @return string Text with placeholders replaced
     */
    public static function replace_placeholders($text, $user, $course) {
        global $CFG;
        
        $placeholders = [
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{fullname}' => fullname($user),
            '{email}' => $user->email,
            '{coursename}' => $course->fullname,
            '{courseurl}' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
            '{sitelogo}' => $CFG->wwwroot . '/theme/image.php/boost/core/1/logo',
            '{sitelogocompact}' => $CFG->wwwroot . '/theme/image.php/boost/core/1/logo_compact',
        ];
        
        // Also support legacy double-brace placeholders
        $legacyPlaceholders = [
            '{{firstname}}' => $user->firstname,
            '{{lastname}}' => $user->lastname,
            '{{fullname}}' => fullname($user),
            '{{email}}' => $user->email,
            '{{coursename}}' => $course->fullname,
            '{{courseurl}}' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
            '{{completionlink}}' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
        ];
        
        $text = str_replace(array_keys($placeholders), array_values($placeholders), $text);
        $text = str_replace(array_keys($legacyPlaceholders), array_values($legacyPlaceholders), $text);
        
        return $text;
    }
    
    /**
     * Process template with colors and placeholders
     *
     * @param string $body Template body
     * @param object $user User object
     * @param object $course Course object
     * @return string Processed template
     */
    public static function process_template($body, $user, $course) {
        $body = self::apply_colors($body);
        $body = self::replace_placeholders($body, $user, $course);
        return $body;
    }
}

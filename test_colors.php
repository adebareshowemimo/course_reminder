<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== Course Reminder Color Settings Test ===\n\n";

// Test 1: Check color settings
echo "1. Checking color settings from config:\n";
$colors = [
    'color_primary' => get_config('local_course_reminder', 'color_primary'),
    'color_primary_dark' => get_config('local_course_reminder', 'color_primary_dark'),
    'color_accent' => get_config('local_course_reminder', 'color_accent'),
    'color_gray' => get_config('local_course_reminder', 'color_gray'),
];

foreach($colors as $name => $value) {
    echo "   $name: " . ($value ?: 'NOT SET') . "\n";
}

// Test 2: Test color replacement
echo "\n2. Testing color replacement:\n";
$sample_html = '<div style="background-color: #384e91; color: #f16736;"><p style="color: #636568;">Test</p></div>';
echo "   Original HTML: $sample_html\n";

$processed = \local_course_reminder\template_helper::apply_colors($sample_html);
echo "   After color replacement: $processed\n";

// Test 3: Check if colors were actually replaced
echo "\n3. Verification:\n";
if ($processed !== $sample_html) {
    echo "   ✓ Colors were successfully replaced!\n";
} else {
    echo "   ✗ Colors were NOT replaced (all using defaults)\n";
}

// Test 4: Load a template and show first 200 chars
echo "\n4. Sample template content:\n";
$templates = $DB->get_records('local_course_reminder_tpls', null, '', '*', 0, 1);
if ($templates) {
    $template = reset($templates);
    echo "   Template: {$template->name}\n";
    echo "   Subject: {$template->subject}\n";
    echo "   Body preview: " . substr(strip_tags($template->body), 0, 100) . "...\n";
    
    // Count color occurrences in template
    $color_counts = [
        '#384e91' => substr_count($template->body, '#384e91'),
        '#2f417a' => substr_count($template->body, '#2f417a'),
        '#f16736' => substr_count($template->body, '#f16736'),
        '#636568' => substr_count($template->body, '#636568'),
    ];
    
    echo "\n   Color usage in template:\n";
    foreach ($color_counts as $color => $count) {
        echo "   - $color: $count occurrences\n";
    }
}

echo "\n=== Test Complete ===\n";

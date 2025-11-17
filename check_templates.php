<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

global $DB;

$templates = $DB->get_records('local_course_reminder_tpls');
echo 'Total templates installed: ' . count($templates) . PHP_EOL;
foreach($templates as $t) {
    echo '- ' . $t->name . PHP_EOL;
}

$colors = [
    'color_primary' => get_config('local_course_reminder', 'color_primary'),
    'color_primary_dark' => get_config('local_course_reminder', 'color_primary_dark'),
    'color_accent' => get_config('local_course_reminder', 'color_accent'),
    'color_gray' => get_config('local_course_reminder', 'color_gray'),
];

echo PHP_EOL . 'Color settings:' . PHP_EOL;
foreach($colors as $name => $value) {
    echo "- $name: $value" . PHP_EOL;
}

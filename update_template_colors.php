<?php
/**
 * Script to update all templates to use CSS variables
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

echo "Updating templates to use CSS variables...\n\n";

$templates = [
    'template1_compliance_reminder.html',
    'template2_compliance_final_reminder.html',
    'template3_leadership_invite.html',
    'template4_marketing_reengagement.html',
    'template5_new_manager_onboarding.html',
    'template6_communication_skills.html',
    'template7_annual_refresher.html',
    'template8_milestone_celebration.html',
    'template9_personalized_recommendation.html',
    'template10_inactive_reactivation.html',
];

$templateDir = __DIR__ . '/email_templates/';

foreach ($templates as $templateFile) {
    $filePath = $templateDir . $templateFile;
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: $templateFile\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Replace hardcoded colors with CSS variables
    $replacements = [
        // Primary color replacements
        'background-color: #384e91' => 'background-color: var(--color-primary)',
        'color: #384e91' => 'color: var(--color-primary)',
        'border: 2px solid #384e91' => 'border: 2px solid var(--color-primary)',
        'border-left: 4px solid #384e91' => 'border-left: 4px solid var(--color-primary)',
        
        // Primary dark color replacements
        'background-color: #2f417a' => 'background-color: var(--color-primary-dark)',
        'color: #2f417a' => 'color: var(--color-primary-dark)',
        
        // Accent color replacements
        'background-color: #f16736' => 'background-color: var(--color-accent)',
        'color: #f16736' => 'color: var(--color-accent)',
        'border: 2px solid #f16736' => 'border: 2px solid var(--color-accent)',
        'border-left: 4px solid #f16736' => 'border-left: 4px solid var(--color-accent)',
        
        // Gray color replacements
        'color: #636568' => 'color: var(--color-gray)',
        
        // Gradients
        'background: linear-gradient(135deg, #384e91 0%, #2f417a 100%)' => 'background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%)',
        'background: linear-gradient(135deg, #f16736 0%, #ff8a5c 100%)' => 'background: linear-gradient(135deg, var(--color-accent) 0%, #ff8a5c 100%)',
        
        // Strong tags
        '<strong style="color: #f16736;">' => '<strong style="color: var(--color-accent);">',
        '<strong style="color: #384e91;">' => '<strong style="color: var(--color-primary);">',
        
        // Gradient backgrounds (complete)
        'background: linear-gradient(to right, #f8f9fd, #ffffff); border: 2px solid #384e91' => 'background: linear-gradient(to right, #f8f9fd, #ffffff); border: 2px solid var(--color-primary)',
        'background: linear-gradient(to right, #f8f9fd, #ffffff); border-left: 4px solid #f16736' => 'background: linear-gradient(to right, #f8f9fd, #ffffff); border-left: 4px solid var(--color-accent)',
        
        // Specific span replacements
        '<span style="color: #f16736;' => '<span style="color: var(--color-accent);',
        
        // Button backgrounds
        'padding: 15px 35px; background-color: #f16736;' => 'padding: 15px 35px; background-color: var(--color-accent);',
        'padding: 14px 32px; background-color: #f16736;' => 'padding: 14px 32px; background-color: var(--color-accent);',
        'padding: 16px 40px; background-color: #f16736;' => 'padding: 16px 40px; background-color: var(--color-accent);',
    ];
    
    $originalContent = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Updated: $templateFile\n";
    } else {
        echo "ℹ️  No changes needed: $templateFile\n";
    }
}

echo "\n✅ All templates processed!\n";
echo "\nUpdating templates in database...\n\n";

// Update templates in database
global $DB;

foreach ($templates as $templateFile) {
    $filePath = $templateDir . $templateFile;
    $content = file_get_contents($filePath);
    
    // Extract template name from filename
    $name = ucwords(str_replace(['.html', '_'], [' ', ' '], substr($templateFile, 9)));
    
    // Find template by name pattern
    $records = $DB->get_records_sql(
        "SELECT * FROM {local_course_reminder_tpls} WHERE name LIKE ?",
        ['%' . $name . '%']
    );
    
    if (!empty($records)) {
        foreach ($records as $record) {
            $record->body = $content;
            $record->timemodified = time();
            $DB->update_record('local_course_reminder_tpls', $record);
            echo "✅ Database updated: {$record->name}\n";
        }
    } else {
        echo "⚠️  No database record found for: $name\n";
    }
}

echo "\n✅ Database update complete!\n";

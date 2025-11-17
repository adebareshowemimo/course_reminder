<?php
/**
 * Update default message to beautiful HTML template
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

echo "Updating default message template...\n\n";

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

// Update the config
set_config('defaultmessage', $default_html_template, 'local_course_reminder');
set_config('defaultsubject', 'Reminder: Complete Your Course', 'local_course_reminder');

echo "✅ Default message updated successfully!\n";
echo "✅ Default subject updated successfully!\n\n";

echo "The default template now includes:\n";
echo "  - Modern responsive HTML design\n";
echo "  - CSS variables for colors (--color-primary, --color-accent, etc.)\n";
echo "  - Professional layout with header, content, and footer\n";
echo "  - Call-to-action button\n";
echo "  - Support for all placeholders: {{firstname}}, {{coursename}}, etc.\n";
echo "  - Site logo integration\n\n";

echo "You can view and customize this template in:\n";
echo "Site Administration → Plugins → Local plugins → Course Reminder → Settings\n";

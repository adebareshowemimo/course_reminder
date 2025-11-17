# Course Reminder Email Templates

## Overview
The Course Reminder plugin includes 10 professionally designed, responsive HTML email templates that can be customized with your brand colors.

## Email Templates Included

1. **Compliance Training Reminder** - Professional compliance training reminder
2. **Compliance Deadline Approaching (Final Reminder)** - Urgent final compliance notice
3. **Leadership Program Invitation** - Inspirational leadership program invitation
4. **Marketing Skills Course - Re-engagement** - Energetic marketing course re-engagement
5. **New Manager Onboarding Pathway** - Structured new manager onboarding
6. **Soft Skills / Communication Program** - People-focused communication training
7. **Mandatory Annual Refresher** - Concise annual refresher requirement
8. **Learning Milestone Celebration** - Celebratory progress milestone
9. **Personalized Course Recommendation** - Consultative personalized course recommendation
10. **Re-activation for Inactive Learners** - Friendly re-activation message

## Customizing Colors

### Admin Settings
Navigate to: **Site Administration → Plugins → Local plugins → Course Reminder → Global Settings**

You can customize 4 brand colors:

1. **Primary Color** (default: #384e91)
   - Used for headers and primary elements
   
2. **Primary Dark Color** (default: #2f417a)
   - Darker shade for hover states and accents
   
3. **Accent Color** (default: #f16736)
   - Used for buttons and highlights
   
4. **Text Gray Color** (default: #636568)
   - Used for body text

### How It Works
When an email is sent, the plugin automatically:
1. Loads the template from the database
2. Replaces the default color codes with your custom colors
3. Replaces placeholders with actual user/course data
4. Sends the personalized email

### Supported Placeholders
All templates support the following placeholders:

- `{firstname}` - User's first name
- `{lastname}` - User's last name
- `{fullname}` - User's full name
- `{email}` - User's email address
- `{coursename}` - Course full name
- `{courseurl}` - Direct link to the course
- `{sitelogo}` - Site main logo URL
- `{sitelogocompact}` - Site compact logo URL

Legacy placeholders (also supported):
- `{{firstname}}`, `{{lastname}}`, `{{fullname}}`, `{{email}}`, `{{coursename}}`, `{{courseurl}}`, `{{completionlink}}`

## Template Installation

### Automatic Installation
Templates are automatically installed when you:
- Install the plugin for the first time
- Upgrade the plugin to version 1.1 or higher

### Manual Template Update
If you need to manually update templates (e.g., after modifying template files):

1. Navigate to the email templates directory: `/local/course_reminder/email_templates/`
2. Edit the HTML files as needed
3. Run the upgrade: `php admin/cli/upgrade.php`

Or delete existing templates from the database and re-run the upgrade.

## Technical Details

### Color Application
Colors are applied dynamically using the `\local_course_reminder\template_helper::apply_colors()` method, which:
1. Retrieves color settings from Moodle config
2. Uses str_replace to swap default colors with custom colors
3. Returns the modified template

### Database Table
Templates are stored in: `local_course_reminder_tpls`

Fields:
- `id` - Primary key
- `name` - Template name
- `subject` - Email subject line
- `body` - HTML email body
- `format` - Text format (1 = HTML)
- `timecreated` - Creation timestamp
- `timemodified` - Last modified timestamp

## Testing Colors

To test your color customization:
1. Update the colors in admin settings
2. Send a test reminder to yourself
3. Check the email to verify colors are applied correctly

## Developer Notes

### Adding New Templates
To add a new template:

1. Create the HTML file in `/local/course_reminder/email_templates/`
2. Add it to the templates array in `/local/course_reminder/db/install.php` and `/local/course_reminder/db/upgrade.php`
3. Increment the version in `/local/course_reminder/version.php`
4. Run the upgrade

### Modifying Template Processing
The main processing happens in:
- `/local/course_reminder/classes/template_helper.php` - Color and placeholder replacement
- `/local/course_reminder/lib.php` - Email sending function

## Support

For questions or issues, contact the plugin maintainer or refer to the plugin documentation.

# WP Notes Manager - User Guide

## Quick Start

### 1. Installation
1. Upload the plugin to `/wp-content/plugins/wp-notes-manager/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create necessary database tables

### 2. First Steps
1. Go to **WP Notes Manager** in your WordPress admin menu
2. You'll see the dashboard with statistics and quick add form
3. Create your first note using the "Quick Add Note" form

## Features Overview

### Dashboard
- **Statistics**: View total notes, active notes, overdue notes, and upcoming deadlines
- **Quick Add**: Create notes directly from the dashboard
- **Recent Activity**: See your latest notes and changes
- **Layout Controls**: Switch between list, 2-column, and 3-column layouts

### Note Management
- **Create Notes**: Add notes with title, content, priority, and deadline
- **Edit Notes**: Update note content, priority, and assignments
- **Archive Notes**: Move completed notes to archive
- **Delete Notes**: Permanently remove notes
- **Assign Notes**: Assign notes to specific users

### Stage Management
- **Default Stages**: Todo, In Progress, Review, Done
- **Custom Stages**: Create your own stages with custom colors
- **Stage Colors**: Visual organization with color-coded stages
- **Stage Ordering**: Drag and drop to reorder stages

### User Assignment
- **Assign to Users**: Assign notes to specific team members
- **Assignment Tracking**: See who's assigned to what
- **User Filtering**: Filter notes by assigned user

### Priority System
- **Low Priority**: Green color, less urgent
- **Medium Priority**: Yellow color, normal priority
- **High Priority**: Orange color, important
- **Urgent Priority**: Red color, immediate attention

### Deadline Management
- **Set Deadlines**: Add due dates to notes
- **Overdue Tracking**: Automatic detection of overdue notes
- **Upcoming Deadlines**: See notes due soon
- **Deadline Notifications**: Email notifications for approaching deadlines

## Settings

### General Settings
- **Enable Notifications**: Turn on/off deadline notifications
- **Notification Frequency**: How often to send notifications
- **Default Priority**: Set default priority for new notes
- **Auto-archive**: Automatically archive completed notes

### Display Settings
- **Default Layout**: Choose default view (list, 2-column, 3-column)
- **Show Statistics**: Display statistics on dashboard
- **Compact Header**: Use compact header design
- **Responsive Design**: Mobile-friendly layouts

### Security Settings
- **User Permissions**: Control who can manage notes
- **Audit Logging**: Track all note changes
- **Rate Limiting**: Prevent spam and abuse
- **IP Blocking**: Block suspicious IP addresses

## Usage Tips

### Organizing Notes
1. **Use Stages**: Move notes through different stages (Todo → In Progress → Review → Done)
2. **Set Priorities**: Use priority levels to focus on important tasks
3. **Assign Users**: Delegate tasks to team members
4. **Set Deadlines**: Add due dates to keep track of deadlines

### Best Practices
1. **Regular Updates**: Update note stages as work progresses
2. **Clear Titles**: Use descriptive titles for easy identification
3. **Detailed Content**: Add comprehensive content to notes
4. **Archive Completed**: Move finished notes to archive
5. **Review Regularly**: Check overdue and upcoming deadlines

### Keyboard Shortcuts
- **Ctrl+N**: Quick add new note (when focused on dashboard)
- **Enter**: Save note when editing
- **Escape**: Cancel editing
- **Tab**: Navigate between form fields

## Troubleshooting

### Common Issues
1. **Notes not saving**: Check user permissions and database connection
2. **Missing stages**: Verify database tables were created properly
3. **Notifications not working**: Check email settings and cron jobs
4. **Layout issues**: Clear browser cache and check CSS conflicts

### Getting Help
- Check the plugin settings for configuration issues
- Review WordPress error logs for technical problems
- Ensure all required WordPress capabilities are available
- Contact support for persistent issues

## Advanced Features

### AJAX Operations
- All note operations use AJAX for smooth user experience
- No page refreshes when creating, updating, or deleting notes
- Real-time updates and notifications

### Database Integration
- Custom database tables for optimal performance
- Proper indexing for fast queries
- Audit logging for compliance

### Security Features
- Nonce verification for all operations
- Input sanitization and validation
- User permission checks
- Rate limiting and IP blocking

### Responsive Design
- Mobile-friendly interface
- Touch-friendly controls
- Adaptive layouts for different screen sizes

## Support

For additional help:
- Check the plugin documentation
- Review WordPress error logs
- Ensure proper user permissions
- Contact the development team

---

**WP Notes Manager** - Efficient note management for WordPress teams.
# WP Notes Manager

A comprehensive note management system for WordPress that helps teams organize, track, and manage notes efficiently.

## Features

- **Note Management**: Create, edit, archive, and delete notes
- **Stage System**: Organize notes with customizable stages (Todo, In Progress, Review, Done)
- **User Assignment**: Assign notes to team members
- **Priority Levels**: Set priorities (Low, Medium, High, Urgent)
- **Deadline Management**: Set due dates and track deadlines
- **Audit Logging**: Track all note changes for compliance
- **Responsive Design**: Mobile-friendly interface
- **AJAX Operations**: Smooth user experience with no page refreshes
- **Security Features**: Nonce verification, input sanitization, rate limiting
- **Notification System**: Email notifications for approaching deadlines

## Installation

1. Upload the plugin to `/wp-content/plugins/wp-notes-manager/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create necessary database tables
4. Start creating and managing notes!

## Quick Start

1. Go to **WP Notes Manager** in your WordPress admin menu
2. Use the "Quick Add Note" form on the dashboard
3. Set priorities, assign users, and add deadlines
4. Move notes through stages as work progresses

## Usage

### Creating Notes
- Use the quick add form on the dashboard
- Set title, content, priority, and deadline
- Assign notes to team members
- Choose appropriate stage

### Managing Stages
- Default stages: Todo, In Progress, Review, Done
- Create custom stages with custom colors
- Drag and drop to reorder stages
- Visual organization with color coding

### User Assignment
- Assign notes to specific users
- Track who's working on what
- Filter notes by assigned user
- See assignment statistics

### Priority System
- **Low**: Green color, less urgent
- **Medium**: Yellow color, normal priority
- **High**: Orange color, important
- **Urgent**: Red color, immediate attention

### Deadline Management
- Set due dates for notes
- Automatic overdue detection
- Email notifications for approaching deadlines
- Dashboard statistics for deadline tracking

## Settings

### General Settings
- Enable/disable notifications
- Set notification frequency
- Choose default priority
- Auto-archive completed notes

### Display Settings
- Default layout (list, 2-column, 3-column)
- Show/hide statistics
- Compact header design
- Responsive layouts

### Security Settings
- User permission controls
- Audit logging
- Rate limiting
- IP blocking

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Security

The plugin implements comprehensive security measures:
- Nonce verification for all operations
- Input sanitization and validation
- User capability checks
- Rate limiting and IP blocking
- Audit logging for compliance
- SQL injection prevention

## Performance

- Optimized database queries
- Proper indexing for fast searches
- Caching with WordPress transients
- Minified CSS and JavaScript
- Conditional asset loading

## Testing

The plugin includes a comprehensive test suite:
- Database schema tests
- CRUD operation tests
- Security feature tests
- AJAX handler tests
- Performance benchmarks

Run tests from the admin menu: **WP Notes Manager > Test Suite**

## Support

For support and documentation:
- Check the User Guide (`USAGE.md`)
- Review the Developer Guide (`DEVELOPER_GUIDE.md`)
- Check WordPress error logs
- Ensure proper user permissions

## Changelog

### Version 1.0.0
- Initial release
- Core note management functionality
- Stage system with custom stages
- User assignment system
- Priority levels and deadline management
- Audit logging and security features
- Responsive design and AJAX operations
- Comprehensive test suite

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Credits

Developed with ❤️ for the WordPress community.

---

**WP Notes Manager** - Efficient note management for WordPress teams.

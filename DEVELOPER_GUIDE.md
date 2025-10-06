# WP Notes Manager - Developer Guide

## Architecture Overview

### Plugin Structure
```
wp-notes-manager/
├── wp-notes-manager.php          # Main plugin file
├── uninstall.php                 # Uninstall script
├── readme.txt                    # Plugin information
├── LICENSE                       # GPL License
├── src/                          # Source code
│   ├── Admin/                    # Admin interface
│   ├── Database/                 # Database operations
│   ├── Security/                 # Security features
│   ├── Notes/                    # Note management
│   ├── Stages/                   # Stage management
│   ├── Audit/                    # Audit logging
│   ├── Ajax/                     # AJAX handlers
│   └── Assets/                   # CSS/JS assets
├── assets/                       # Static assets
│   ├── css/                      # Stylesheets
│   └── js/                       # JavaScript files
└── tests/                        # Test files
```

### Core Components

#### 1. WPNotesManager (Main Class)
- **Singleton Pattern**: Ensures single instance
- **Component Registry**: Manages all plugin components
- **Initialization**: Handles plugin setup and activation

#### 2. AdminManager
- **Menu Management**: Creates admin menu structure
- **Page Rendering**: Handles admin page display
- **Asset Management**: Enqueues CSS/JS files
- **Settings Management**: Handles plugin settings

#### 3. DatabaseManager
- **CRUD Operations**: Create, Read, Update, Delete notes
- **Query Building**: Constructs database queries
- **Data Validation**: Validates input data
- **Performance Optimization**: Optimized queries

#### 4. SecurityManager
- **Nonce Verification**: CSRF protection
- **Input Sanitization**: XSS prevention
- **Permission Checks**: User capability validation
- **Rate Limiting**: Prevents abuse
- **IP Blocking**: Security measures

#### 5. NotesManager
- **Note Display**: Renders notes in admin
- **Meta Boxes**: Adds note functionality to posts
- **Dashboard Widgets**: Shows notes on dashboard
- **Admin Bar Integration**: Quick access to notes

#### 6. StageManager
- **Stage CRUD**: Manages note stages
- **Default Stages**: Handles default stage creation
- **Stage Ordering**: Manages stage sort order
- **Color Management**: Handles stage colors

#### 7. AuditManager
- **Action Logging**: Logs all note actions
- **Log Retrieval**: Gets audit logs
- **Log Cleanup**: Removes old logs
- **Compliance**: Audit trail for compliance

#### 8. AjaxHandler
- **AJAX Endpoints**: Handles AJAX requests
- **Data Processing**: Processes AJAX data
- **Response Formatting**: Formats AJAX responses
- **Error Handling**: Handles AJAX errors

#### 9. AssetManager
- **Asset Enqueuing**: Manages CSS/JS loading
- **Dependency Management**: Handles asset dependencies
- **Version Control**: Manages asset versions
- **Conditional Loading**: Loads assets when needed

#### 10. NotificationManager
- **Email Notifications**: Sends deadline notifications
- **Cron Scheduling**: Schedules notification tasks
- **Template Management**: Manages email templates
- **User Preferences**: Handles notification settings

## Database Schema

### Tables

#### wpnm_notes
```sql
CREATE TABLE wpnm_notes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    content longtext,
    priority enum('low','medium','high','urgent') DEFAULT 'medium',
    note_type varchar(50) DEFAULT 'dashboard',
    author_id bigint(20) NOT NULL,
    assigned_to bigint(20) DEFAULT NULL,
    stage_id bigint(20) DEFAULT NULL,
    deadline datetime DEFAULT NULL,
    is_archived tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY author_id (author_id),
    KEY assigned_to (assigned_to),
    KEY stage_id (stage_id),
    KEY deadline (deadline),
    KEY is_archived (is_archived)
);
```

#### wpnm_stages
```sql
CREATE TABLE wpnm_stages (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    description text,
    color varchar(7) DEFAULT '#0073aa',
    sort_order int(11) DEFAULT 0,
    is_default tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY sort_order (sort_order),
    KEY is_default (is_default)
);
```

#### wpnm_audit_logs
```sql
CREATE TABLE wpnm_audit_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    note_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    action varchar(50) NOT NULL,
    old_value longtext,
    new_value longtext,
    ip_address varchar(45),
    user_agent text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY note_id (note_id),
    KEY user_id (user_id),
    KEY action (action),
    KEY created_at (created_at)
);
```

## API Reference

### DatabaseManager Methods

#### createNote($data)
Creates a new note.
```php
$note_id = $database->createNote([
    'title' => 'Note Title',
    'content' => 'Note content',
    'priority' => 'high',
    'note_type' => 'dashboard',
    'author_id' => get_current_user_id(),
    'assigned_to' => 123,
    'stage_id' => 1,
    'deadline' => '2024-12-31 23:59:59'
]);
```

#### getNote($id)
Retrieves a note by ID.
```php
$note = $database->getNote(123);
```

#### updateNote($id, $data)
Updates an existing note.
```php
$result = $database->updateNote(123, [
    'title' => 'Updated Title',
    'priority' => 'urgent'
]);
```

#### deleteNote($id)
Deletes a note permanently.
```php
$result = $database->deleteNote(123);
```

#### archiveNote($id)
Archives a note.
```php
$result = $database->archiveNote(123);
```

#### restoreNote($id)
Restores an archived note.
```php
$result = $database->restoreNote(123);
```

### StageManager Methods

#### getStages()
Gets all stages.
```php
$stages = $stages_manager->getStages();
```

#### createStage($data)
Creates a new stage.
```php
$stage_id = $stages_manager->createStage([
    'name' => 'New Stage',
    'description' => 'Stage description',
    'color' => '#ff0000',
    'sort_order' => 10
]);
```

#### updateStage($id, $data)
Updates a stage.
```php
$result = $stages_manager->updateStage(1, [
    'name' => 'Updated Stage',
    'color' => '#00ff00'
]);
```

#### deleteStage($id)
Deletes a stage.
```php
$result = $stages_manager->deleteStage(1);
```

### AjaxHandler Methods

#### wpnm_add_note
AJAX endpoint for adding notes.
```javascript
jQuery.post(ajaxurl, {
    action: 'wpnm_add_note',
    nonce: wpnm_admin.nonce,
    title: 'Note Title',
    content: 'Note content',
    priority: 'high'
}, function(response) {
    // Handle response
});
```

#### wpnm_update_note
AJAX endpoint for updating notes.
```javascript
jQuery.post(ajaxurl, {
    action: 'wpnm_update_note',
    nonce: wpnm_admin.nonce,
    note_id: 123,
    title: 'Updated Title'
}, function(response) {
    // Handle response
});
```

#### wpnm_delete_note
AJAX endpoint for deleting notes.
```javascript
jQuery.post(ajaxurl, {
    action: 'wpnm_delete_note',
    nonce: wpnm_admin.nonce,
    note_id: 123
}, function(response) {
    // Handle response
});
```

## Hooks and Filters

### Actions

#### wpnm_after_note_created
Fired after a note is created.
```php
add_action('wpnm_after_note_created', function($note_id, $note_data) {
    // Custom logic after note creation
}, 10, 2);
```

#### wpnm_after_note_updated
Fired after a note is updated.
```php
add_action('wpnm_after_note_updated', function($note_id, $old_data, $new_data) {
    // Custom logic after note update
}, 10, 3);
```

#### wpnm_after_note_deleted
Fired after a note is deleted.
```php
add_action('wpnm_after_note_deleted', function($note_id) {
    // Custom logic after note deletion
}, 10, 1);
```

### Filters

#### wpnm_note_data
Filters note data before saving.
```php
add_filter('wpnm_note_data', function($data) {
    // Modify note data before saving
    return $data;
});
```

#### wpnm_stage_data
Filters stage data before saving.
```php
add_filter('wpnm_stage_data', function($data) {
    // Modify stage data before saving
    return $data;
});
```

#### wpnm_notification_email
Filters notification email content.
```php
add_filter('wpnm_notification_email', function($content, $note) {
    // Modify notification email content
    return $content;
}, 10, 2);
```

## Security Considerations

### Input Validation
- All user input is sanitized using WordPress functions
- Database queries use prepared statements
- Nonce verification for all AJAX requests
- User capability checks for all operations

### Data Sanitization
```php
// Sanitize text input
$title = sanitize_text_field($_POST['title']);

// Sanitize textarea content
$content = wp_kses_post($_POST['content']);

// Sanitize color values
$color = sanitize_hex_color($_POST['color']);

// Sanitize priority
$priority = in_array($_POST['priority'], ['low', 'medium', 'high', 'urgent']) 
    ? $_POST['priority'] 
    : 'medium';
```

### Permission Checks
```php
// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

// Verify nonce
if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
    wp_die('Security check failed');
}
```

### SQL Injection Prevention
```php
// Use prepared statements
$wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}wpnm_notes WHERE author_id = %d",
    $user_id
);

// Escape output
echo esc_html($note->title);
```

## Performance Optimization

### Database Optimization
- Proper indexing on frequently queried columns
- Use of WordPress transients for caching
- Optimized queries with LIMIT clauses
- Regular cleanup of old audit logs

### Caching Strategy
```php
// Cache stages
$stages = get_transient('wpnm_stages');
if (false === $stages) {
    $stages = $this->getStages();
    set_transient('wpnm_stages', $stages, HOUR_IN_SECONDS);
}
```

### Asset Optimization
- Minified CSS and JavaScript
- Conditional loading of assets
- Proper dependency management
- Version control for cache busting

## Testing

### Unit Testing
The plugin includes a comprehensive test suite:
- Database schema tests
- CRUD operation tests
- Security feature tests
- AJAX handler tests
- Performance tests

### Running Tests
```php
// Run test suite
$test_suite = new WPNM_TestSuite();
$test_suite->runAllTests();
```

### Test Coverage
- Database operations
- User interface functionality
- Security measures
- AJAX endpoints
- Performance benchmarks

## Deployment

### Pre-deployment Checklist
1. Run test suite
2. Check for linter errors
3. Verify security measures
4. Test all functionality
5. Update version numbers
6. Update changelog

### Version Management
- Semantic versioning (MAJOR.MINOR.PATCH)
- Proper version bumping
- Changelog updates
- Database version tracking

## Contributing

### Code Standards
- Follow WordPress Coding Standards
- Use proper PHPDoc comments
- Implement proper error handling
- Write comprehensive tests

### Pull Request Process
1. Fork the repository
2. Create feature branch
3. Make changes
4. Add tests
5. Submit pull request
6. Code review
7. Merge

### Development Setup
1. Clone repository
2. Install dependencies
3. Set up local WordPress
4. Activate plugin
5. Run tests
6. Start development

---

**WP Notes Manager** - Developer documentation for contributors and integrators.
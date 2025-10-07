<?php
/**
 * Database Manager
 *
 * Handles all database operations for the notes plugin
 *
 * @package WPNotesManager
 * @subpackage Database
 * @since 1.0.0
 */

namespace WPNotesManager\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Manager Class
 */
class DatabaseManager {
    
    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Notes table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'wpnm_notes';
    }
    
    /**
     * Initialize database operations
     */
    public function init() {
        // Add any initialization code here
    }
    
    /**
     * Create database tables
     */
    public function createTables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Notes table
        $notes_table = $this->wpdb->prefix . 'wpnm_notes';
        $notes_sql = "CREATE TABLE $notes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) DEFAULT NULL,
            title varchar(255) NOT NULL,
            content longtext,
            priority enum('low','medium','high','urgent') DEFAULT 'medium',
            note_type varchar(50) DEFAULT 'dashboard',
            author_id bigint(20) NOT NULL,
            assigned_to bigint(20) DEFAULT NULL,
            stage_id bigint(20) DEFAULT NULL,
            deadline datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            is_archived tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY author_id (author_id),
            KEY assigned_to (assigned_to),
            KEY stage_id (stage_id),
            KEY deadline (deadline),
            KEY status (status),
            KEY is_archived (is_archived)
        ) " . $this->wpdb->get_charset_collate() . ";";
        
        dbDelta($notes_sql);
        
        // Run migrations for existing installations
        $this->runMigrations();
        
        // Stages table
        $stages_table = $this->wpdb->prefix . 'wpnm_stages';
        $stages_sql = "CREATE TABLE $stages_table (
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
        ) " . $this->wpdb->get_charset_collate() . ";";
        
        dbDelta($stages_sql);
        
        // Audit logs table
        $audit_table = $this->wpdb->prefix . 'wpnm_audit_logs';
        $audit_sql = "CREATE TABLE $audit_table (
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
        ) " . $this->wpdb->get_charset_collate() . ";";
        
        dbDelta($audit_sql);
    }
    
    /**
     * Create a new note
     *
     * @param array $data Note data
     * @return int|false Note ID on success, false on failure
     */
    public function createNote($data) {
        try {
            // Sanitize and validate data
            $note_data = $this->sanitizeNoteData($data);
            
            if (!$this->validateNoteData($note_data)) {
                error_log('WP Notes Manager: Note validation failed for data: ' . print_r($data, true));
                return false;
            }
            
            // Prepare data for insert (matching table structure)
            $insert_data = [
                'post_id' => $note_data['post_id'],
                'title' => $note_data['title'],
                'content' => $note_data['content'],
                'priority' => $note_data['priority'],
                'note_type' => $note_data['note_type'],
                'author_id' => $note_data['author_id'],
                'assigned_to' => $note_data['assigned_to'],
                'stage_id' => $note_data['stage_id'],
                'deadline' => $note_data['deadline'],
                'status' => $note_data['status']
            ];
            
            // Insert note
            $result = $this->wpdb->insert(
                $this->table_name,
                $insert_data,
                [
                    '%d', // post_id
                    '%s', // title
                    '%s', // content
                    '%s', // priority
                    '%s', // note_type
                    '%d', // author_id
                    '%d', // assigned_to
                    '%d', // stage_id
                    '%s', // deadline
                    '%s'  // status
                ]
            );
            
            if ($result === false) {
                error_log('WP Notes Manager: Database insert failed: ' . $this->wpdb->last_error);
                return false;
            }
            
            return $this->wpdb->insert_id;
            
        } catch (\Exception $e) {
            error_log('WP Notes Manager: Exception in createNote: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get note by ID
     *
     * @param int $note_id Note ID
     * @return object|null Note object or null if not found
     */
    public function getNote($note_id) {
        $note_id = absint($note_id);
        
        if (!$note_id) {
            return null;
        }
        
        $note = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d AND status != 'deleted'",
                $note_id
            )
        );
        
        return $note;
    }
    
    /**
     * Update note
     *
     * @param int   $note_id Note ID
     * @param array $data    Updated data
     * @return bool True on success, false on failure
     */
    public function updateNote($note_id, $data) {
        $note_id = absint($note_id);
        
        if (!$note_id) {
            return false;
        }
        
        // Sanitize and validate data
        $note_data = $this->sanitizeNoteData($data);
        
        if (!$this->validateNoteData($note_data, false)) {
            return false;
        }
        
        // Update note
        $result = $this->wpdb->update(
            $this->table_name,
            $note_data,
            ['id' => $note_id],
            [
                '%d', // post_id
                '%s', // note_type
                '%s', // title
                '%s', // content
                '%d', // author_id
                '%d', // assigned_to
                '%d', // stage_id
                '%s', // deadline
                '%s', // status
                '%s'  // priority
                // color removed
            ],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Update only specific note fields
     *
     * @param int   $note_id Note ID
     * @param array $data    Data to update
     * @return bool True on success, false on failure
     */
    public function updateNoteFields($note_id, $data) {
        $note_id = absint($note_id);
        
        if (!$note_id) {
            return false;
        }
        
        // Sanitize data
        $note_data = [];
        
        if (isset($data['title'])) {
            $note_data['title'] = sanitize_text_field($data['title']);
        }
        
        if (isset($data['content'])) {
            $note_data['content'] = wp_kses_post($data['content']);
        }
        
        if (isset($data['priority'])) {
            $note_data['priority'] = sanitize_text_field($data['priority']);
        }
        
        // color removed
        
        if (isset($data['assigned_to'])) {
            $note_data['assigned_to'] = absint($data['assigned_to']);
        }
        
        if (isset($data['stage_id'])) {
            $note_data['stage_id'] = absint($data['stage_id']);
        }

        if (isset($data['deadline'])) {
            $note_data['deadline'] = sanitize_text_field($data['deadline']);
        }

        if (isset($data['status'])) {
            $note_data['status'] = sanitize_text_field($data['status']);
        }
        
        if (empty($note_data)) {
            return false;
        }
        
        // Update note
        $result = $this->wpdb->update(
            $this->table_name,
            $note_data,
            ['id' => $note_id],
            array_fill(0, count($note_data), '%s'),
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Delete note (soft delete)
     *
     * @param int $note_id Note ID
     * @return bool True on success, false on failure
     */
    public function deleteNote($note_id) {
        $note_id = absint($note_id);
        
        if (!$note_id) {
            return false;
        }
        
        $result = $this->wpdb->update(
            $this->table_name,
            ['status' => 'deleted'],
            ['id' => $note_id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Get notes by type
     *
     * @param string $note_type Note type (dashboard, post, page)
     * @param int    $post_id   Post ID (optional)
     * @param int    $limit     Number of notes to retrieve
     * @param int    $offset    Offset for pagination
     * @return array Array of note objects
     */
    public function getNotes($note_type = 'dashboard', $post_id = null, $limit = 20, $offset = 0) {
        $note_type = sanitize_text_field($note_type);
        $post_id = $post_id ? absint($post_id) : null;
        $limit = absint($limit);
        $offset = absint($offset);
        
        // Validate note type
        $allowed_types = ['dashboard', 'post', 'page'];
        if (!in_array($note_type, $allowed_types)) {
            $note_type = 'dashboard';
        }
        
        // Build query
        $where_conditions = ["note_type = %s", "status != 'deleted'"];
        $where_values = [$note_type];
        
        if ($post_id) {
            $where_conditions[] = "post_id = %d";
            $where_values[] = $post_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $notes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE {$where_clause} 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                array_merge($where_values, [$limit, $offset])
            )
        );
        
        return $notes;
    }
    
    /**
     * Get dashboard notes
     *
     * @param int $limit  Number of notes to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of note objects
     */
    public function getDashboardNotes($limit = 20, $offset = 0) {
        return $this->getNotes('dashboard', null, $limit, $offset);
    }
    
    /**
     * Get post notes
     *
     * @param int $post_id Post ID
     * @param int $limit   Number of notes to retrieve
     * @param int $offset  Offset for pagination
     * @return array Array of note objects
     */
    public function getPostNotes($post_id, $limit = 20, $offset = 0) {
        return $this->getNotes('post', $post_id, $limit, $offset);
    }
    
    /**
     * Get page notes
     *
     * @param int $page_id Page ID
     * @param int $limit   Number of notes to retrieve
     * @param int $offset  Offset for pagination
     * @return array Array of note objects
     */
    public function getPageNotes($page_id, $limit = 20, $offset = 0) {
        return $this->getNotes('page', $page_id, $limit, $offset);
    }
    
    /**
     * Get notes count by type
     *
     * @param string $note_type Note type
     * @param int    $post_id   Post ID (optional)
     * @return int Number of notes
     */
    public function getNotesCount($note_type = 'dashboard', $post_id = null) {
        $note_type = sanitize_text_field($note_type);
        $post_id = $post_id ? absint($post_id) : null;
        
        // Validate note type
        $allowed_types = ['dashboard', 'post', 'page'];
        if (!in_array($note_type, $allowed_types)) {
            $note_type = 'dashboard';
        }
        
        // Build query
        $where_conditions = ["note_type = %s", "status != 'deleted'"];
        $where_values = [$note_type];
        
        if ($post_id) {
            $where_conditions[] = "post_id = %d";
            $where_values[] = $post_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
                $where_values
            )
        );
        
        return absint($count);
    }
    
    /**
     * Archive note
     *
     * @param int $note_id Note ID
     * @return bool True on success, false on failure
     */
    public function archiveNote($note_id) {
        $note_id = absint($note_id);
        
        if (!$note_id) {
            return false;
        }
        
        $result = $this->wpdb->update(
            $this->table_name,
            ['status' => 'archived'],
            ['id' => $note_id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Restore note from archive
     *
     * @param int $note_id Note ID
     * @return bool True on success, false on failure
     */
    public function restoreNote($note_id) {
        $note_id = absint($note_id);
        
        if (!$note_id) {
            return false;
        }
        
        $result = $this->wpdb->update(
            $this->table_name,
            ['status' => 'active'],
            ['id' => $note_id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Get all notes from all types
     *
     * @param int $limit  Number of notes to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of note objects with post/page info
     */
    public function getAllNotes($limit = 20, $offset = 0) {
        $limit = absint($limit);
        $offset = absint($offset);
        
        // Get all notes
        $notes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE status != 'deleted' 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
        
        // Add post/page information
        foreach ($notes as $note) {
            if ($note->post_id && $note->note_type !== 'dashboard') {
                $post = get_post($note->post_id);
                if ($post) {
                    $note->post_title = $post->post_title;
                    $note->post_type = $post->post_type;
                    $note->post_status = $post->post_status;
                    $note->edit_link = get_edit_post_link($note->post_id);
                }
            } else {
                $note->post_title = esc_html__('Dashboard Note', 'wp-notes-manager');
                $note->post_type = 'dashboard';
                $note->post_status = 'publish';
                $note->edit_link = admin_url('admin.php?page=wpnm-dashboard');
            }
        }
        
        return $notes;
    }
    
    /**
     * Get total count of all notes
     *
     * @return int Total number of notes
     */
    public function getAllNotesCount() {
        $count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status != 'deleted'"
        );
        
        return absint($count);
    }
    
    /**
     * Get notes by stage
     *
     * @param int $stage_id Stage ID
     * @param int $limit    Number of notes to retrieve
     * @param int $offset   Offset for pagination
     * @return array Array of note objects
     */
    public function getNotesByStage($stage_id, $limit = 20, $offset = 0) {
        $limit = absint($limit);
        $offset = absint($offset);
        $stage_id = absint($stage_id);
        
        $notes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT n.*, u.display_name as author_name, u2.display_name as assigned_name, s.name as stage_name, s.color as stage_color
                 FROM {$this->table_name} n
                 LEFT JOIN {$this->wpdb->users} u ON n.author_id = u.ID
                 LEFT JOIN {$this->wpdb->users} u2 ON n.assigned_to = u2.ID
                 LEFT JOIN {$this->wpdb->prefix}wpnm_stages s ON n.stage_id = s.id
                 WHERE n.status = 'active' AND n.stage_id = %d
                 ORDER BY n.created_at DESC
                 LIMIT %d OFFSET %d",
                $stage_id,
                $limit,
                $offset
            )
        );
        
        return $notes;
    }
    
    /**
     * Get notes count by stage
     *
     * @param int $stage_id Stage ID
     * @return int Number of notes
     */
    public function getNotesCountByStage($stage_id) {
        $stage_id = absint($stage_id);
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'active' AND stage_id = %d",
                $stage_id
            )
        );
        
        return absint($count);
    }
    
    /**
     * Get notes by author
     *
     * @param int $author_id Author ID
     * @param int $limit     Number of notes to retrieve
     * @param int $offset    Offset for pagination
     * @return array Array of note objects with post/page info
     */
    public function getNotesByAuthor($author_id, $limit = 20, $offset = 0) {
        $author_id = absint($author_id);
        $limit = absint($limit);
        $offset = absint($offset);
        
        // Get notes by author
        $notes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE author_id = %d AND status != 'deleted' 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $author_id,
                $limit,
                $offset
            )
        );
        
        // Add post/page information
        foreach ($notes as $note) {
            if ($note->post_id && $note->note_type !== 'dashboard') {
                $post = get_post($note->post_id);
                if ($post) {
                    $note->post_title = $post->post_title;
                    $note->post_type = $post->post_type;
                    $note->post_status = $post->post_status;
                    $note->edit_link = get_edit_post_link($note->post_id);
                }
            } else {
                $note->post_title = esc_html__('Dashboard Note', 'wp-notes-manager');
                $note->post_type = 'dashboard';
                $note->post_status = 'publish';
                $note->edit_link = admin_url('admin.php?page=wpnm-dashboard');
            }
        }
        
        return $notes;
    }
    
    /**
     * Get notes by assignment
     *
     * @param int $assigned_to Assigned user ID
     * @param int $limit       Number of notes to retrieve
     * @param int $offset      Offset for pagination
     * @return array Array of note objects with post/page info
     */
    public function getNotesByAssignment($assigned_to, $limit = 20, $offset = 0) {
        $assigned_to = absint($assigned_to);
        $limit = absint($limit);
        $offset = absint($offset);
        
        // Get notes assigned to user
        $notes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE assigned_to = %d AND status != 'deleted' 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $assigned_to,
                $limit,
                $offset
            )
        );
        
        // Add post/page information
        foreach ($notes as $note) {
            if ($note->post_id && $note->note_type !== 'dashboard') {
                $post = get_post($note->post_id);
                if ($post) {
                    $note->post_title = $post->post_title;
                    $note->post_type = $post->post_type;
                    $note->post_status = $post->post_status;
                    $note->edit_link = get_edit_post_link($note->post_id);
                }
            } else {
                $note->post_title = esc_html__('Dashboard Note', 'wp-notes-manager');
                $note->post_type = 'dashboard';
                $note->post_status = 'publish';
                $note->edit_link = admin_url('admin.php?page=wpnm-dashboard');
            }
        }
        
        return $notes;
    }
    
    /**
     * Get notes count by author
     *
     * @param int $author_id Author ID
     * @return int Number of notes
     */
    public function getNotesCountByAuthor($author_id) {
        $author_id = absint($author_id);
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                 WHERE author_id = %d AND status != 'deleted'",
                $author_id
            )
        );
        
        return absint($count);
    }
    
    /**
     * Get notes count by assignment
     *
     * @param int $assigned_to Assigned user ID
     * @return int Number of notes
     */
    public function getNotesCountByAssignment($assigned_to) {
        $assigned_to = absint($assigned_to);
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                 WHERE assigned_to = %d AND status != 'deleted'",
                $assigned_to
            )
        );
        
        return absint($count);
    }
    
    /**
     * Get notes statistics
     *
     * @return array Statistics array
     */
    public function getStats() {
        $stats = [];
        
        // Total notes
        $stats['total'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status != 'deleted'"
        );
        
        // Dashboard notes
        $stats['dashboard'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE note_type = 'dashboard' AND status != 'deleted'"
        );
        
        // Post notes
        $stats['posts'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE note_type = 'post' AND status != 'deleted'"
        );
        
        // Page notes
        $stats['pages'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE note_type = 'page' AND status != 'deleted'"
        );
        
        // Archived notes
        $stats['archived'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'archived'"
        );
        
        // Recent notes (last 7 days)
        $stats['recent'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'deleted'"
        );
        
        return array_map('absint', $stats);
    }
    
    /**
     * Clean up old deleted notes
     *
     * @param int $days Number of days to keep deleted notes
     * @return int Number of notes permanently deleted
     */
    public function cleanupDeletedNotes($days = 30) {
        $days = absint($days);
        
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE status = 'deleted' AND updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        
        return absint($deleted);
    }
    
    /**
     * Sanitize note data
     *
     * @param array $data Raw note data
     * @return array Sanitized note data
     */
    private function sanitizeNoteData($data) {
        $sanitized = [];
        
        // Post ID
        $sanitized['post_id'] = isset($data['post_id']) ? absint($data['post_id']) : null;
        
        // Note type
        $sanitized['note_type'] = isset($data['note_type']) ? sanitize_text_field($data['note_type']) : 'dashboard';
        
        // Title
        $sanitized['title'] = isset($data['title']) ? sanitize_text_field($data['title']) : '';
        
        // Content
        $sanitized['content'] = isset($data['content']) ? wp_kses_post($data['content']) : '';
        
        // Author ID
        $sanitized['author_id'] = isset($data['author_id']) ? absint($data['author_id']) : get_current_user_id();
        
        // Assigned to
        $sanitized['assigned_to'] = isset($data['assigned_to']) ? absint($data['assigned_to']) : null;
        
        // Stage ID - if not provided, use default stage
        if (isset($data['stage_id'])) {
            $sanitized['stage_id'] = absint($data['stage_id']);
        } else {
            // Get default stage
            $stages_manager = wpnm()->getComponent('stages');
            $default_stage = $stages_manager->getDefaultStage();
            $sanitized['stage_id'] = $default_stage ? $default_stage->id : null;
        }
        
        // Deadline
        $sanitized['deadline'] = isset($data['deadline']) && !empty($data['deadline']) ? sanitize_text_field($data['deadline']) : null;
        
        // Status
        $sanitized['status'] = isset($data['status']) ? sanitize_text_field($data['status']) : 'active';
        
        // Priority
        $sanitized['priority'] = isset($data['priority']) ? sanitize_text_field($data['priority']) : 'medium';
        
        // Color removed - no longer needed
        
        return $sanitized;
    }
    
    /**
     * Validate note data
     *
     * @param array $data      Note data
     * @param bool  $is_create Whether this is for creating a new note
     * @return bool True if valid, false otherwise
     */
    private function validateNoteData($data, $is_create = true) {
        // Required fields for creation
        if ($is_create) {
            if (empty($data['title']) || empty($data['content'])) {
                return false;
            }
        }
        
        // Validate note type
        $allowed_types = ['dashboard', 'post', 'page'];
        if (!in_array($data['note_type'], $allowed_types)) {
            return false;
        }
        
        // Validate status
        $allowed_statuses = ['active', 'archived', 'deleted'];
        if (!in_array($data['status'], $allowed_statuses)) {
            return false;
        }
        
        // Validate priority
        $allowed_priorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($data['priority'], $allowed_priorities)) {
            return false;
        }
        
        // Validate author exists
        if (!get_user_by('id', $data['author_id'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Run database migrations for existing installations
     */
    private function runMigrations() {
        $table_name = $this->wpdb->prefix . 'wpnm_notes';
        
        // Check if table exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            return; // Table doesn't exist yet, will be created by dbDelta
        }
        
        // Check if post_id column exists
        $post_id_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_name}` LIKE 'post_id'"
        );
        
        if (empty($post_id_exists)) {
            // Add post_id column after id
            $this->wpdb->query(
                "ALTER TABLE `{$table_name}` ADD COLUMN `post_id` bigint(20) DEFAULT NULL AFTER `id`"
            );
            $this->wpdb->query(
                "ALTER TABLE `{$table_name}` ADD KEY `post_id` (`post_id`)"
            );
            error_log('WP Notes Manager: Added post_id column to notes table');
        }
        
        // Check if status column exists
        $status_exists = $this->wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_name}` LIKE 'status'"
        );
        
        if (empty($status_exists)) {
            // Add status column before is_archived
            $this->wpdb->query(
                "ALTER TABLE `{$table_name}` ADD COLUMN `status` varchar(20) DEFAULT 'active' AFTER `deadline`"
            );
            $this->wpdb->query(
                "ALTER TABLE `{$table_name}` ADD KEY `status` (`status`)"
            );
            error_log('WP Notes Manager: Added status column to notes table');
        }
    }
}


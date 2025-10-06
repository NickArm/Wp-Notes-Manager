<?php
/**
 * Notes Manager
 *
 * Handles note creation, management, and display
 *
 * @package WPNotesManager
 * @subpackage Notes
 * @since 1.0.0
 */

namespace WPNotesManager\Notes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Notes Manager Class
 */
class NotesManager {
    
    /**
     * Database manager instance
     *
     * @var \WPNotesManager\Database\DatabaseManager
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Database will be initialized when needed
    }
    
    /**
     * Get database component
     */
    private function getDatabase() {
        if (!$this->database) {
            $this->database = wpnm()->getComponent('database');
        }
        return $this->database;
    }
    
    /**
     * Initialize notes manager
     */
    public function init() {
        // Add meta boxes to posts and pages
        add_action('add_meta_boxes', [$this, 'addNotesMetaBox']);
        
        // Save note data
        add_action('save_post', [$this, 'savePostNotes']);
        
        // Add notes to admin bar
        add_action('admin_bar_menu', [$this, 'addNotesToAdminBar'], 100);
        
        // Add notes widget to dashboard
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidget']);
        add_action('wp_dashboard_setup', [$this, 'addDeadlineDashboardWidget']);
        
        // Add notes to post list table
        add_filter('manage_posts_columns', [$this, 'addNotesColumn']);
        add_filter('manage_pages_columns', [$this, 'addNotesColumn']);
        add_action('manage_posts_custom_column', [$this, 'showNotesColumn'], 10, 2);
        add_action('manage_pages_custom_column', [$this, 'showNotesColumn'], 10, 2);
    }
    
    /**
     * Add notes meta box to posts and pages
     */
    public function addNotesMetaBox() {
        // Add to posts
        add_meta_box(
            'wpnm_post_notes',
            __('Post Notes', 'wp-notes-manager'),
            [$this, 'renderNotesMetaBox'],
            'post',
            'side',
            'high'
        );
        
        // Add to pages
        add_meta_box(
            'wpnm_page_notes',
            __('Page Notes', 'wp-notes-manager'),
            [$this, 'renderNotesMetaBox'],
            'page',
            'side',
            'high'
        );
    }
    
    /**
     * Render notes meta box
     *
     * @param \WP_Post $post Current post object
     */
    public function renderNotesMetaBox($post) {
        // Add nonce for security
        wp_nonce_field('wpnm_notes_nonce', 'wpnm_notes_nonce');
        
        // Get existing notes
        $notes = $this->getDatabase()->getNotes($post->post_type, $post->ID, 10);
        
        ?>
        <div id="wpnm-notes-container">
            <div class="wpnm-notes-form">
                <h4><?php _e('Add New Note', 'wp-notes-manager'); ?></h4>
                <p>
                    <label for="wpnm-note-title"><?php _e('Title:', 'wp-notes-manager'); ?></label>
                    <input type="text" id="wpnm-note-title" name="wpnm_note_title" class="widefat" />
                </p>
                <p>
                    <label for="wpnm-note-content"><?php _e('Content:', 'wp-notes-manager'); ?></label>
                    <textarea id="wpnm-note-content" name="wpnm_note_content" rows="3" class="widefat"></textarea>
                </p>
                <p>
                    <label for="wpnm-note-priority"><?php _e('Priority:', 'wp-notes-manager'); ?></label>
                    <select id="wpnm-note-priority" name="wpnm_note_priority">
                        <option value="low"><?php _e('Low', 'wp-notes-manager'); ?></option>
                        <option value="medium" selected><?php _e('Medium', 'wp-notes-manager'); ?></option>
                        <option value="high"><?php _e('High', 'wp-notes-manager'); ?></option>
                        <option value="urgent"><?php _e('Urgent', 'wp-notes-manager'); ?></option>
                    </select>
                </p>
                <!-- Color field removed -->
                <p>
                    <button type="button" id="wpnm-add-note" class="button button-primary">
                        <?php _e('Add Note', 'wp-notes-manager'); ?>
                    </button>
                </p>
            </div>
            
            <div class="wpnm-notes-list">
                <h4><?php _e('Existing Notes', 'wp-notes-manager'); ?></h4>
                <?php if (empty($notes)): ?>
                    <p><?php _e('No notes yet.', 'wp-notes-manager'); ?></p>
                <?php else: ?>
                    <div class="wpnm-notes-items">
                        <?php foreach ($notes as $note): ?>
                            <?php $this->renderNoteItem($note); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .wpnm-note-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            background: <?php echo esc_attr($note->color ?? '#f1f1f1'); ?>;
        }
        .wpnm-note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .wpnm-note-title {
            font-weight: bold;
            margin: 0;
        }
        .wpnm-note-priority {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .wpnm-note-priority.low { background: #e7f3ff; color: #0066cc; }
        .wpnm-note-priority.medium { background: #fff3cd; color: #856404; }
        .wpnm-note-priority.high { background: #f8d7da; color: #721c24; }
        .wpnm-note-priority.urgent { background: #f5c6cb; color: #721c24; font-weight: bold; }
        .wpnm-note-content {
            margin: 5px 0;
            font-size: 13px;
        }
        .wpnm-note-meta {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .wpnm-note-actions {
            margin-top: 5px;
        }
        .wpnm-note-actions button {
            margin-right: 5px;
            font-size: 11px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#wpnm-add-note').on('click', function() {
                var title = $('#wpnm-note-title').val();
                var content = $('#wpnm-note-content').val();
                var priority = $('#wpnm-note-priority').val();
                var color = $('#wpnm-note-color').val();
                
                if (!title || !content) {
                    alert('<?php _e('Please fill in both title and content.', 'wp-notes-manager'); ?>');
                    return;
                }
                
                // AJAX call to add note
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpnm_add_note',
                        post_id: <?php echo $post->ID; ?>,
                        note_type: '<?php echo $post->post_type; ?>',
                        title: title,
                        content: content,
                        priority: priority,
                        color: color,
                        nonce: '<?php echo wp_create_nonce('wpnm_add_note'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error adding note. Please try again.', 'wp-notes-manager'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error adding note. Please try again.', 'wp-notes-manager'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render individual note item
     *
     * @param object $note Note object
     */
    private function renderNoteItem($note) {
        $author = get_user_by('id', $note->author_id);
        $author_name = $author ? $author->display_name : __('Unknown', 'wp-notes-manager');
        $created_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($note->created_at));
        
        ?>
        <div class="wpnm-note-item" data-note-id="<?php echo esc_attr($note->id); ?>">
            <div class="wpnm-note-header">
                <h5 class="wpnm-note-title"><?php echo esc_html($note->title); ?></h5>
                <span class="wpnm-note-priority <?php echo esc_attr($note->priority); ?>">
                    <?php echo esc_html(ucfirst($note->priority)); ?>
                </span>
            </div>
            <div class="wpnm-note-content">
                <?php echo wp_kses_post($note->content); ?>
            </div>
            <div class="wpnm-note-meta">
                <?php printf(__('By %s on %s', 'wp-notes-manager'), esc_html($author_name), esc_html($created_date)); ?>
                
                <?php if ($note->deadline): ?>
                    <div class="wpnm-note-deadline">
                        <span class="dashicons dashicons-clock"></span>
                        <?php 
                        $deadline_timestamp = strtotime($note->deadline);
                        $now = time();
                        $is_overdue = $deadline_timestamp < $now;
                        $deadline_class = $is_overdue ? 'overdue' : '';
                        
                        echo sprintf(
                            '<span class="deadline-date %s">%s</span>',
                            $deadline_class,
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $deadline_timestamp)
                        );
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="wpnm-note-actions">
                <button type="button" class="button button-small wpnm-archive-note" data-note-id="<?php echo esc_attr($note->id); ?>">
                    <?php _e('Archive', 'wp-notes-manager'); ?>
                </button>
                <button type="button" class="button button-small wpnm-delete-note" data-note-id="<?php echo esc_attr($note->id); ?>">
                    <?php _e('Delete', 'wp-notes-manager'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save post notes (legacy method for non-AJAX saves)
     *
     * @param int $post_id Post ID
     */
    public function savePostNotes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['wpnm_notes_nonce']) || !wp_verify_nonce($_POST['wpnm_notes_nonce'], 'wpnm_notes_nonce')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if note data is submitted
        if (isset($_POST['wpnm_note_title']) && isset($_POST['wpnm_note_content'])) {
            $note_data = [
                'post_id' => $post_id,
                'note_type' => get_post_type($post_id),
                'title' => sanitize_text_field($_POST['wpnm_note_title']),
                'content' => wp_kses_post($_POST['wpnm_note_content']),
                'priority' => sanitize_text_field($_POST['wpnm_note_priority'] ?? 'medium'),
                'color' => sanitize_hex_color($_POST['wpnm_note_color'] ?? '#f1f1f1')
            ];
            
            $this->getDatabase()->createNote($note_data);
        }
    }
    
    /**
     * Add notes to admin bar
     *
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function addNotesToAdminBar($wp_admin_bar) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $notes_count = $this->getDatabase()->getNotesCount('dashboard');
        
        $wp_admin_bar->add_node([
            'id' => 'wpnm-notes',
            'title' => sprintf(__('Notes (%d)', 'wp-notes-manager'), $notes_count),
            'href' => admin_url('admin.php?page=wpnm-dashboard'),
            'meta' => [
                'title' => __('View Notes Dashboard', 'wp-notes-manager')
            ]
        ]);
    }
    
    /**
     * Add dashboard widget
     */
    public function addDashboardWidget() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'wpnm_dashboard_widget',
            __('Recent Notes', 'wp-notes-manager'),
            [$this, 'renderDashboardWidget']
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function renderDashboardWidget() {
        $notes = $this->getDatabase()->getDashboardNotes(5);
        
        if (empty($notes)) {
            echo '<p>' . __('No notes yet.', 'wp-notes-manager') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=wpnm-dashboard') . '" class="button">' . __('Add Note', 'wp-notes-manager') . '</a></p>';
            return;
        }
        
        echo '<div class="wpnm-dashboard-notes">';
        foreach ($notes as $note) {
            $this->renderDashboardNoteItem($note);
        }
        echo '</div>';
        echo '<p><a href="' . admin_url('admin.php?page=wpnm-dashboard') . '" class="button">' . __('View All Notes', 'wp-notes-manager') . '</a></p>';
    }
    
    /**
     * Render dashboard note item
     *
     * @param object $note Note object
     */
    private function renderDashboardNoteItem($note) {
        $author = get_user_by('id', $note->author_id);
        $author_name = $author ? $author->display_name : __('Unknown', 'wp-notes-manager');
        $created_date = date_i18n(get_option('date_format'), strtotime($note->created_at));
        
        ?>
        <div class="wpnm-dashboard-note" style="border-left: 3px solid <?php echo esc_attr($note->color); ?>; padding: 8px; margin-bottom: 10px; background: #f9f9f9;">
            <strong><?php echo esc_html($note->title); ?></strong>
            <span class="wpnm-note-priority <?php echo esc_attr($note->priority); ?>" style="float: right; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                <?php echo esc_html(ucfirst($note->priority)); ?>
            </span>
            <p style="margin: 5px 0; font-size: 13px;"><?php echo wp_kses_post(wp_trim_words($note->content, 20)); ?></p>
            <small style="color: #666;"><?php printf(__('By %s on %s', 'wp-notes-manager'), esc_html($author_name), esc_html($created_date)); ?></small>
        </div>
        <?php
    }
    
    /**
     * Add deadline dashboard widget
     */
    public function addDeadlineDashboardWidget() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'wpnm-deadline-widget',
            __('Upcoming & Overdue Tasks', 'wp-notes-manager'),
            [$this, 'renderDeadlineDashboardWidget']
        );
    }
    
    /**
     * Render deadline dashboard widget
     */
    public function renderDeadlineDashboardWidget() {
        $notification_manager = wpnm()->getComponent('notifications');
        $current_user_id = get_current_user_id();
        
        $overdue_count = $notification_manager->getOverdueNotesCount($current_user_id);
        $upcoming_count = $notification_manager->getUpcomingNotesCount($current_user_id);
        
        ?>
        <div class="wpnm-deadline-widget">
            <div class="wpnm-stats-grid">
                <div class="wpnm-stat-item overdue">
                    <h4><?php _e('Overdue', 'wp-notes-manager'); ?></h4>
                    <div class="stat-number"><?php echo $overdue_count; ?></div>
                    <p><?php _e('Tasks past deadline', 'wp-notes-manager'); ?></p>
                </div>
                
                <div class="wpnm-stat-item upcoming">
                    <h4><?php _e('Due Soon', 'wp-notes-manager'); ?></h4>
                    <div class="stat-number"><?php echo $upcoming_count; ?></div>
                    <p><?php _e('Tasks due in 7 days', 'wp-notes-manager'); ?></p>
                </div>
            </div>
            
            <?php if ($overdue_count > 0 || $upcoming_count > 0): ?>
                <div class="wpnm-widget-actions">
                    <a href="<?php echo admin_url('admin.php?page=wpnm-all-notes'); ?>" class="button button-primary">
                        <?php _e('View All Tasks', 'wp-notes-manager'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="wpnm-widget-empty">
                    <p><?php _e('🎉 Great job! No overdue or upcoming tasks.', 'wp-notes-manager'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=wpnm-all-notes'); ?>" class="button">
                        <?php _e('Create New Task', 'wp-notes-manager'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="wpnm-widget-footer">
                <small>
                    <?php 
                    $preferences = get_user_meta($current_user_id, 'wpnm_notification_preferences', true);
                    if ($preferences && isset($preferences['deadlines']['enabled'])) {
                        $days_ahead = $preferences['deadlines']['days_ahead'] ?? 3;
                        printf(__('Receiving daily notifications for tasks due within %d days.', 'wp-notes-manager'), $days_ahead);
                    } else {
                        _e('Configure email notifications in your profile settings.', 'wp-notes-manager');
                    }
                    ?>
                </small>
                
                <?php if ($preferences && isset($preferences['deadlines']['enabled'])): ?>
                    <br>
                    <button type="button" id="wpnm-test-notification" class="button button-small" style="margin-top: 5px;">
                        <?php _e('Test Notification', 'wp-notes-manager'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .wpnm-deadline-widget {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .wpnm-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .wpnm-stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid;
        }
        
        .wpnm-stat-item.overdue {
            border-color: #dc2626;
            background: #fef2f2;
        }
        
        .wpnm-stat-item.upcoming {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        .wpnm-stat-item h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .wpnm-stat-item.overdue .stat-number {
            color: #dc2626 !important;
        }
        
        .wpnm-stat-item.upcoming .stat-number {
            color: #f59e0b;
        }
        
        .wpnm-stat-item p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .wpnm-widget-actions {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .wpnm-widget-empty {
            text-align: center;
            padding: 20px;
            color: #059669;
        }
        
        .wpnm-widget-empty p {
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .wpnm-widget-footer {
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .wpnm-widget-footer small {
            color: #6b7280;
            font-size: 11px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#wpnm-test-notification').on('click', function() {
                var button = $(this);
                var originalText = button.text();
                
                button.text('<?php _e('Sending...', 'wp-notes-manager'); ?>').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpnm_test_notification',
                        nonce: wpnm_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Test notification sent successfully! Check your email.', 'wp-notes-manager'); ?>');
                        } else {
                            alert(response.data.message || '<?php _e('Error sending test notification.', 'wp-notes-manager'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error sending test notification.', 'wp-notes-manager'); ?>');
                    },
                    complete: function() {
                        button.text(originalText).prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add notes column to post list table
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function addNotesColumn($columns) {
        $columns['wpnm_notes'] = __('Notes', 'wp-notes-manager');
        return $columns;
    }
    
    /**
     * Show notes column content
     *
     * @param string $column_name Column name
     * @param int    $post_id     Post ID
     */
    public function showNotesColumn($column_name, $post_id) {
        if ($column_name === 'wpnm_notes') {
            $notes_count = $this->getDatabase()->getNotesCount(get_post_type($post_id), $post_id);
            
            if ($notes_count > 0) {
                echo '<span style="color: #0073aa;">' . sprintf(_n('%d note', '%d notes', $notes_count, 'wp-notes-manager'), $notes_count) . '</span>';
            } else {
                echo '<span style="color: #999;">' . __('No notes', 'wp-notes-manager') . '</span>';
            }
        }
    }
}


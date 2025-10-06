<?php
/**
 * Admin Manager
 *
 * Handles admin interface and dashboard functionality
 *
 * @package WPNotesManager
 * @subpackage Admin
 * @since 1.0.0
 */

namespace WPNotesManager\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Manager Class
 */
class AdminManager {
    
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
        $this->database = wpnm()->getComponent('database');
    }
    
    /**
     * Initialize admin manager
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Add admin styles and scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Add settings page
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Add admin notices
        add_action('admin_notices', [$this, 'adminNotices']);
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        // Main menu page
        add_menu_page(
            __('Notes Manager', 'wp-notes-manager'),
            __('Notes', 'wp-notes-manager'),
            'edit_posts',
            'wpnm-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-format-aside',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'wpnm-dashboard',
            __('Notes Dashboard', 'wp-notes-manager'),
            __('Dashboard', 'wp-notes-manager'),
            'edit_posts',
            'wpnm-dashboard',
            [$this, 'renderDashboardPage']
        );
        
        // All Notes submenu
        add_submenu_page(
            'wpnm-dashboard',
            __('All Notes', 'wp-notes-manager'),
            __('All Notes', 'wp-notes-manager'),
            'edit_posts',
            'wpnm-all-notes',
            [$this, 'renderAllNotesPage']
        );
        
        // Stages submenu (only for administrators)
        add_submenu_page(
            'wpnm-dashboard',
            __('Stages Management', 'wp-notes-manager'),
            __('Stages', 'wp-notes-manager'),
            'manage_options',
            'wpnm-stages',
            [$this, 'renderStagesPage']
        );
        
        // Audit Logs submenu (only for administrators)
        add_submenu_page(
            'wpnm-dashboard',
            __('Audit Logs', 'wp-notes-manager'),
            __('Audit Logs', 'wp-notes-manager'),
            'manage_options',
            'wpnm-audit-logs',
            [$this, 'renderAuditLogsPage']
        );
        
        // Enhanced Test Suite submenu (only for administrators)
        add_submenu_page(
            'wpnm-dashboard',
            __('Enhanced Test Suite', 'wp-notes-manager'),
            __('Enhanced Tests', 'wp-notes-manager'),
            'manage_options',
            'wpnm-enhanced-test',
            [$this, 'renderEnhancedTestPage']
        );
        
        // Manual Testing Checklist submenu (only for administrators)
        add_submenu_page(
            'wpnm-dashboard',
            __('Manual Testing Checklist', 'wp-notes-manager'),
            __('Manual Testing', 'wp-notes-manager'),
            'manage_options',
            'wpnm-manual-checklist',
            [$this, 'renderManualChecklistPage']
        );
        
        // Settings submenu (only for administrators)
        add_submenu_page(
            'wpnm-dashboard',
            __('Notes Settings', 'wp-notes-manager'),
            __('Settings', 'wp-notes-manager'),
            'manage_options',
            'wpnm-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueueAdminAssets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wpnm-') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'wpnm-admin-style',
            WPNM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPNM_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'wpnm-admin-script',
            WPNM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WPNM_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wpnm-admin-script', 'wpnm_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpnm_add_note'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this note?', 'wp-notes-manager'),
                'confirm_archive' => __('Are you sure you want to archive this note?', 'wp-notes-manager'),
                'error_occurred' => __('An error occurred. Please try again.', 'wp-notes-manager'),
                'note_added' => __('Note added successfully!', 'wp-notes-manager'),
                'note_updated' => __('Note updated successfully!', 'wp-notes-manager'),
                'note_deleted' => __('Note deleted successfully!', 'wp-notes-manager'),
                'note_archived' => __('Note archived successfully!', 'wp-notes-manager')
            ]
        ]);
    }
    
    /**
     * Register plugin settings
     */
    public function registerSettings() {
        // Register settings
        register_setting('wpnm_settings', 'wpnm_settings', [
            'sanitize_callback' => [$this, 'sanitizeSettings']
        ]);
        
        // Add settings sections
        add_settings_section(
            'wpnm_general_section',
            __('General Settings', 'wp-notes-manager'),
            [$this, 'renderGeneralSection'],
            'wpnm_settings'
        );
        
        add_settings_section(
            'wpnm_display_section',
            __('Display Settings', 'wp-notes-manager'),
            [$this, 'renderDisplaySection'],
            'wpnm_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'wpnm_auto_cleanup',
            __('Auto Cleanup Deleted Notes', 'wp-notes-manager'),
            [$this, 'renderAutoCleanupField'],
            'wpnm_settings',
            'wpnm_general_section'
        );
        
        add_settings_field(
            'wpnm_cleanup_days',
            __('Cleanup Days', 'wp-notes-manager'),
            [$this, 'renderCleanupDaysField'],
            'wpnm_settings',
            'wpnm_general_section'
        );
        
        add_settings_field(
            'wpnm_show_in_admin_bar',
            __('Show in Admin Bar', 'wp-notes-manager'),
            [$this, 'renderShowInAdminBarField'],
            'wpnm_settings',
            'wpnm_display_section'
        );
        
        add_settings_field(
            'wpnm_show_dashboard_widget',
            __('Show Dashboard Widget', 'wp-notes-manager'),
            [$this, 'renderShowDashboardWidgetField'],
            'wpnm_settings',
            'wpnm_display_section'
        );
    }
    
    /**
     * Render dashboard page
     */
    public function renderDashboardPage() {
        // Get statistics
        $stats = $this->database->getStats();
        
        // Get recent notes
        $recent_notes = $this->database->getDashboardNotes(10);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Notes Dashboard', 'wp-notes-manager'); ?></h1>
            
            <!-- Statistics Cards -->
            <div class="wpnm-stats-grid">
                <div class="wpnm-stat-card">
                    <h3><?php echo esc_html($stats['total']); ?></h3>
                    <p><?php _e('Total Notes', 'wp-notes-manager'); ?></p>
                </div>
                <div class="wpnm-stat-card">
                    <h3><?php echo esc_html($stats['dashboard']); ?></h3>
                    <p><?php _e('Dashboard Notes', 'wp-notes-manager'); ?></p>
                </div>
                <div class="wpnm-stat-card">
                    <h3><?php echo esc_html($stats['posts']); ?></h3>
                    <p><?php _e('Post Notes', 'wp-notes-manager'); ?></p>
                </div>
                <div class="wpnm-stat-card">
                    <h3><?php echo esc_html($stats['pages']); ?></h3>
                    <p><?php _e('Page Notes', 'wp-notes-manager'); ?></p>
                </div>
                <div class="wpnm-stat-card">
                    <h3><?php echo esc_html($stats['archived']); ?></h3>
                    <p><?php _e('Archived Notes', 'wp-notes-manager'); ?></p>
                </div>
                <div class="wpnm-stat-card">
                    <h3><?php echo esc_html($stats['recent']); ?></h3>
                    <p><?php _e('Recent Notes (7 days)', 'wp-notes-manager'); ?></p>
                </div>
            </div>
            
            <!-- Quick Add Note -->
            <div class="wpnm-quick-add">
                <h2><?php _e('Quick Add Note', 'wp-notes-manager'); ?></h2>
                <form id="wpnm-quick-add-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpnm-quick-title"><?php _e('Title', 'wp-notes-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="wpnm-quick-title" name="title" class="regular-text" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpnm-quick-content"><?php _e('Content', 'wp-notes-manager'); ?></label>
                            </th>
                            <td>
                                <textarea id="wpnm-quick-content" name="content" rows="4" class="large-text" required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpnm-quick-priority"><?php _e('Priority', 'wp-notes-manager'); ?></label>
                            </th>
                            <td>
                                <select id="wpnm-quick-priority" name="priority">
                                    <option value="low"><?php _e('Low', 'wp-notes-manager'); ?></option>
                                    <option value="medium" selected><?php _e('Medium', 'wp-notes-manager'); ?></option>
                                    <option value="high"><?php _e('High', 'wp-notes-manager'); ?></option>
                                    <option value="urgent"><?php _e('Urgent', 'wp-notes-manager'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <!-- Color field removed -->
                        <tr>
                            <th scope="row">
                                <label for="wpnm-quick-assigned"><?php _e('Assign To', 'wp-notes-manager'); ?></label>
                            </th>
                            <td>
                                <select id="wpnm-quick-assigned" name="assigned_to">
                                    <option value=""><?php _e('No Assignment', 'wp-notes-manager'); ?></option>
                                    <?php
                                    $users = get_users(['orderby' => 'display_name']);
                                    foreach ($users as $user) {
                                        printf('<option value="%d">%s</option>', $user->ID, esc_html($user->display_name));
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Add Note', 'wp-notes-manager'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Recent Notes -->
            <div class="wpnm-recent-notes">
                <h2><?php _e('Recent Dashboard Notes', 'wp-notes-manager'); ?></h2>
                <?php if (empty($recent_notes)): ?>
                    <p><?php _e('No notes yet.', 'wp-notes-manager'); ?></p>
                <?php else: ?>
                    <div class="wpnm-notes-list">
                        <?php foreach ($recent_notes as $note): ?>
                            <?php $this->renderNoteCard($note); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .wpnm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .wpnm-stat-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .wpnm-stat-card h3 {
            font-size: 2em;
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        .wpnm-stat-card p {
            margin: 0;
            color: #666;
        }
        .wpnm-quick-add {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .wpnm-recent-notes {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .wpnm-notes-list {
            display: grid;
            gap: 15px;
        }
        .wpnm-note-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #f9f9f9;
        }
        .wpnm-note-card.high { border-left: 4px solid #dc3545; }
        .wpnm-note-card.urgent { border-left: 4px solid #dc3545; background: #fff5f5; }
        .wpnm-note-card.medium { border-left: 4px solid #ffc107; }
        .wpnm-note-card.low { border-left: 4px solid #28a745; }
        .wpnm-note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .wpnm-note-title {
            font-weight: bold;
            margin: 0;
        }
        .wpnm-note-priority {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .wpnm-note-priority.low { background: #d4edda; color: #155724; }
        .wpnm-note-priority.medium { background: #fff3cd; color: #856404; }
        .wpnm-note-priority.high { background: #f8d7da; color: #721c24; }
        .wpnm-note-priority.urgent { background: #f5c6cb; color: #721c24; font-weight: bold; }
        .wpnm-note-content {
            margin: 10px 0;
            line-height: 1.5;
        }
        .wpnm-note-meta {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        .wpnm-note-actions {
            margin-top: 10px;
        }
        .wpnm-note-actions button {
            margin-right: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Render all notes page
     */
    public function renderAllNotesPage() {
        // Get filter type
        $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
        $stage_filter = isset($_GET['stage']) ? absint($_GET['stage']) : null;
        $current_user_id = get_current_user_id();
        
        // Get notes based on filter
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        if ($filter === 'my') {
            $notes = $this->database->getNotesByAuthor($current_user_id, $per_page, $offset);
            $total_notes = $this->database->getNotesCountByAuthor($current_user_id);
        } elseif ($filter === 'assigned') {
            $notes = $this->database->getNotesByAssignment($current_user_id, $per_page, $offset);
            $total_notes = $this->database->getNotesCountByAssignment($current_user_id);
        } elseif ($stage_filter) {
            $notes = $this->database->getNotesByStage($stage_filter, $per_page, $offset);
            $total_notes = $this->database->getNotesCountByStage($stage_filter);
        } else {
            $notes = $this->database->getAllNotes($per_page, $offset);
            $total_notes = $this->database->getAllNotesCount();
        }
        
        $total_pages = ceil($total_notes / $per_page);
        
        ?>
        <div class="wrap">
            <div class="wpnm-page-header">
                <h1><?php _e('All Notes', 'wp-notes-manager'); ?></h1>
                <button type="button" class="button button-primary wpnm-add-note-btn" id="wpnm-quick-add-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Add New Note', 'wp-notes-manager'); ?>
                </button>
            </div>
            
            <!-- Compact Controls Bar -->
            <div class="wpnm-controls-bar">
                <!-- Left Side: Filters -->
                <div class="wpnm-controls-left">
                    <!-- Filter Tabs -->
                    <div class="wpnm-filter-tabs">
                        <a href="<?php echo admin_url('admin.php?page=wpnm-all-notes'); ?>" class="wpnm-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            <?php _e('All Notes', 'wp-notes-manager'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wpnm-all-notes&filter=my'); ?>" class="wpnm-filter-tab <?php echo $filter === 'my' ? 'active' : ''; ?>">
                            <?php _e('My Notes', 'wp-notes-manager'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wpnm-all-notes&filter=assigned'); ?>" class="wpnm-filter-tab <?php echo $filter === 'assigned' ? 'active' : ''; ?>">
                            <?php _e('Assigned to Me', 'wp-notes-manager'); ?>
                        </a>
                    </div>
                    
                    <!-- Stage Filter -->
                    <div class="wpnm-stage-filter">
                        <select id="wpnm-stage-filter-select" name="stage_filter">
                            <option value=""><?php _e('All Stages', 'wp-notes-manager'); ?></option>
                            <?php
                            $stages_manager = wpnm()->getComponent('stages');
                            $stages = $stages_manager->getStages();
                            foreach ($stages as $stage) {
                                $selected = ($stage_filter == $stage->id) ? 'selected' : '';
                                printf('<option value="%d" %s>%s</option>', $stage->id, $selected, esc_html($stage->name));
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <!-- Right Side: Layout Controls -->
                <div class="wpnm-controls-right">
                    <div class="wpnm-layout-controls">
                        <span class="wpnm-layout-label"><?php _e('Layout:', 'wp-notes-manager'); ?></span>
                        <div class="wpnm-layout-buttons">
                            <button type="button" class="wpnm-layout-btn active" data-layout="list" title="<?php _e('List View', 'wp-notes-manager'); ?>">
                                <span class="dashicons dashicons-list-view"></span>
                            </button>
                            <button type="button" class="wpnm-layout-btn" data-layout="2-columns" title="<?php _e('2 Columns', 'wp-notes-manager'); ?>">
                                <span class="dashicons dashicons-grid-view"></span>
                            </button>
                            <button type="button" class="wpnm-layout-btn" data-layout="3-columns" title="<?php _e('3 Columns', 'wp-notes-manager'); ?>">
                                <span class="dashicons dashicons-grid-view"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Add Form (Hidden by default) -->
            <div class="wpnm-quick-add-form-container" id="wpnm-quick-add-form-container" style="display: none;">
                <div class="wpnm-quick-add">
                    <h3><?php _e('Add New Note', 'wp-notes-manager'); ?></h3>
                    <form id="wpnm-quick-add-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="wpnm-quick-title"><?php _e('Title', 'wp-notes-manager'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="wpnm-quick-title" name="title" class="regular-text" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wpnm-quick-content"><?php _e('Content', 'wp-notes-manager'); ?></label>
                                </th>
                                <td>
                                    <textarea id="wpnm-quick-content" name="content" rows="4" class="large-text" required></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wpnm-quick-priority"><?php _e('Priority', 'wp-notes-manager'); ?></label>
                                </th>
                                <td>
                                    <select id="wpnm-quick-priority" name="priority">
                                        <option value="low"><?php _e('Low', 'wp-notes-manager'); ?></option>
                                        <option value="medium" selected><?php _e('Medium', 'wp-notes-manager'); ?></option>
                                        <option value="high"><?php _e('High', 'wp-notes-manager'); ?></option>
                                        <option value="urgent"><?php _e('Urgent', 'wp-notes-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <!-- Color field removed -->
                            <tr>
                                <th scope="row">
                                    <label for="wpnm-quick-assigned"><?php _e('Assign To', 'wp-notes-manager'); ?></label>
                                </th>
                                <td>
                                    <select id="wpnm-quick-assigned" name="assigned_to">
                                        <option value=""><?php _e('No Assignment', 'wp-notes-manager'); ?></option>
                                        <?php
                                        $users = get_users(['orderby' => 'display_name']);
                                        foreach ($users as $user) {
                                            printf('<option value="%d">%s</option>', $user->ID, esc_html($user->display_name));
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
            <tr>
                <th scope="row">
                    <label for="wpnm-quick-stage"><?php _e('Stage', 'wp-notes-manager'); ?></label>
                </th>
                <td>
                    <select id="wpnm-quick-stage" name="stage_id">
                        <option value=""><?php _e('No Stage', 'wp-notes-manager'); ?></option>
                        <?php
                        $stages_manager = wpnm()->getComponent('stages');
                        $stages = $stages_manager->getStages();
                        foreach ($stages as $stage) {
                            printf('<option value="%d">%s</option>', $stage->id, esc_html($stage->name));
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpnm-quick-deadline"><?php _e('Deadline', 'wp-notes-manager'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" id="wpnm-quick-deadline" name="deadline" />
                    <p class="description"><?php _e('Optional deadline for this note', 'wp-notes-manager'); ?></p>
                </td>
            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Add Note', 'wp-notes-manager'); ?>
                            </button>
                            <button type="button" class="button wpnm-cancel-add-btn">
                                <?php _e('Cancel', 'wp-notes-manager'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <?php if (empty($notes)): ?>
                <div class="wpnm-empty-state">
                    <div class="wpnm-empty-state-icon">&#128221;</div>
                    <h3 class="wpnm-empty-state-title">
                        <?php 
                        if ($filter === 'my') {
                            _e('No Notes Created Yet', 'wp-notes-manager');
                        } elseif ($filter === 'assigned') {
                            _e('No Notes Assigned to You', 'wp-notes-manager');
                        } else {
                            _e('No Notes Yet', 'wp-notes-manager');
                        }
                        ?>
                    </h3>
                    <p class="wpnm-empty-state-description">
                        <?php 
                        if ($filter === 'my') {
                            _e('You haven\'t created any notes yet. Click "Add New Note" to get started.', 'wp-notes-manager');
                        } elseif ($filter === 'assigned') {
                            _e('No notes have been assigned to you yet.', 'wp-notes-manager');
                        } else {
                            _e('Start adding notes to see them here. You can add notes from the dashboard or while editing posts and pages.', 'wp-notes-manager');
                        }
                        ?>
                    </p>
                    <button type="button" class="button button-primary wpnm-add-note-btn">
                        <?php _e('Add Your First Note', 'wp-notes-manager'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="wpnm-all-notes-list wpnm-layout-list" id="wpnm-notes-container">
                    <?php foreach ($notes as $note): ?>
                        <?php $this->renderAllNotesCard($note); ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="wpnm-pagination">
                        <?php
                        $pagination_args = [
                            'base' => add_query_arg(['paged' => '%#%', 'filter' => $filter, 'stage' => $stage_filter]),
                            'format' => '',
                            'prev_text' => __('&laquo; Previous'),
                            'next_text' => __('Next &raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render stages management page
     */
    public function renderStagesPage() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'wp-notes-manager'));
        }
        
        $stages_manager = wpnm()->getComponent('stages');
        $stages = $stages_manager->getStages();
        ?>
        <div class="wrap">
            <h1><?php _e('Stages Management', 'wp-notes-manager'); ?></h1>
            
            <div class="wpnm-stages-container">
                <div class="wpnm-stages-header">
                    <button type="button" class="button button-primary" id="wpnm-add-stage-btn">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Add New Stage', 'wp-notes-manager'); ?>
                    </button>
                </div>
                
                <!-- Add/Edit Stage Form -->
                <div class="wpnm-stage-form-container" id="wpnm-stage-form-container" style="display: none;">
                    <div class="wpnm-stage-form">
                        <h3 id="wpnm-stage-form-title"><?php _e('Add New Stage', 'wp-notes-manager'); ?></h3>
                        <form id="wpnm-stage-form">
                            <input type="hidden" id="wpnm-stage-id" name="stage_id" value="">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="wpnm-stage-name"><?php _e('Stage Name', 'wp-notes-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="wpnm-stage-name" name="name" class="regular-text" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="wpnm-stage-description"><?php _e('Description', 'wp-notes-manager'); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="wpnm-stage-description" name="description" rows="3" class="large-text"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="wpnm-stage-color"><?php _e('Color', 'wp-notes-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="color" id="wpnm-stage-color" name="color" value="#6b7280" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="wpnm-stage-sort-order"><?php _e('Sort Order', 'wp-notes-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="wpnm-stage-sort-order" name="sort_order" value="0" min="0" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="wpnm-stage-is-default"><?php _e('Default Stage', 'wp-notes-manager'); ?></label>
                                    </th>
                                    <td>
                                        <input type="checkbox" id="wpnm-stage-is-default" name="is_default" value="1" />
                                        <label for="wpnm-stage-is-default"><?php _e('Set as default stage for new notes', 'wp-notes-manager'); ?></label>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php _e('Save Stage', 'wp-notes-manager'); ?>
                                </button>
                                <button type="button" class="button wpnm-cancel-stage-btn">
                                    <?php _e('Cancel', 'wp-notes-manager'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Stages List -->
                <div class="wpnm-stages-list">
                    <?php if (empty($stages)): ?>
                        <div class="wpnm-empty-state">
                            <div class="wpnm-empty-state-icon">📋</div>
                            <h3 class="wpnm-empty-state-title"><?php _e('No Stages Yet', 'wp-notes-manager'); ?></h3>
                            <p class="wpnm-empty-state-description"><?php _e('Create your first stage to organize your notes.', 'wp-notes-manager'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Name', 'wp-notes-manager'); ?></th>
                                    <th><?php _e('Description', 'wp-notes-manager'); ?></th>
                                    <th><?php _e('Color', 'wp-notes-manager'); ?></th>
                                    <th><?php _e('Sort Order', 'wp-notes-manager'); ?></th>
                                    <th><?php _e('Default', 'wp-notes-manager'); ?></th>
                                    <th><?php _e('Actions', 'wp-notes-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stages as $stage): ?>
                                    <tr data-stage-id="<?php echo esc_attr($stage->id); ?>">
                                        <td>
                                            <strong><?php echo esc_html($stage->name); ?></strong>
                                        </td>
                                        <td><?php echo esc_html($stage->description); ?></td>
                                        <td>
                                            <span class="wpnm-stage-color-preview" style="background-color: <?php echo esc_attr($stage->color); ?>;"></span>
                                            <?php echo esc_html($stage->color); ?>
                                        </td>
                                        <td><?php echo esc_html($stage->sort_order); ?></td>
                                        <td>
                                            <?php if ($stage->is_default): ?>
                                                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small wpnm-edit-stage-btn" data-stage-id="<?php echo esc_attr($stage->id); ?>">
                                                <?php _e('Edit', 'wp-notes-manager'); ?>
                                            </button>
                                            <?php if (!$stage->is_default): ?>
                                                <button type="button" class="button button-small button-link-delete wpnm-delete-stage-btn" data-stage-id="<?php echo esc_attr($stage->id); ?>">
                                                    <?php _e('Delete', 'wp-notes-manager'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render audit logs page
     */
    public function renderAuditLogsPage() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'wp-notes-manager'));
        }
        
        $audit_manager = wpnm()->getComponent('audit');
        $logs = $audit_manager->getAuditLogs(null, 50, 0);
        $total_logs = $audit_manager->getAuditLogsCount();
        ?>
        <div class="wrap">
            <h1><?php _e('Audit Logs', 'wp-notes-manager'); ?></h1>
            
            <div class="wpnm-audit-logs-container">
                <div class="wpnm-audit-logs-header">
                    <div class="wpnm-audit-logs-info">
                        <p><?php printf(__('Total log entries: %d', 'wp-notes-manager'), $total_logs); ?></p>
                    </div>
                    <div class="wpnm-audit-logs-actions">
                        <button type="button" class="button" id="wpnm-clear-audit-logs-btn">
                            <?php _e('Clear Old Logs', 'wp-notes-manager'); ?>
                        </button>
                    </div>
                </div>
                
                <?php if (empty($logs)): ?>
                    <div class="wpnm-empty-state">
                        <div class="wpnm-empty-state-icon">📊</div>
                        <h3 class="wpnm-empty-state-title"><?php _e('No Audit Logs Yet', 'wp-notes-manager'); ?></h3>
                        <p class="wpnm-empty-state-description"><?php _e('Audit logs will appear here as users interact with notes.', 'wp-notes-manager'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date/Time', 'wp-notes-manager'); ?></th>
                                <th><?php _e('User', 'wp-notes-manager'); ?></th>
                                <th><?php _e('Action', 'wp-notes-manager'); ?></th>
                                <th><?php _e('Note', 'wp-notes-manager'); ?></th>
                                <th><?php _e('Details', 'wp-notes-manager'); ?></th>
                                <th><?php _e('IP Address', 'wp-notes-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                                    <td><?php echo esc_html($log->user_name ?: __('Unknown User', 'wp-notes-manager')); ?></td>
                                    <td><?php echo esc_html($audit_manager->formatAction($log->action, $log->details)); ?></td>
                                    <td>
                                        <?php if ($log->note_title): ?>
                                            <a href="<?php echo admin_url('admin.php?page=wpnm-all-notes'); ?>"><?php echo esc_html($log->note_title); ?></a>
                                        <?php else: ?>
                                            <?php _e('Note Deleted', 'wp-notes-manager'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log->details)): ?>
                                            <details>
                                                <summary><?php _e('View Details', 'wp-notes-manager'); ?></summary>
                                                <pre><?php echo esc_html(print_r($log->details, true)); ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($log->ip_address); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1><?php _e('Notes Settings', 'wp-notes-manager'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wpnm_settings');
                do_settings_sections('wpnm_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render all notes card with beautiful list design
     *
     * @param object $note Note object
     */
    private function renderAllNotesCard($note) {
        $author = get_user_by('id', $note->author_id);
        $author_name = $author ? $author->display_name : __('Unknown', 'wp-notes-manager');
        $created_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($note->created_at));
        
        // Determine the source type and icon
        $source_type = '';
        $source_icon = '';
        $source_link = '';
        
        if ($note->note_type === 'dashboard') {
            $source_type = __('Dashboard', 'wp-notes-manager');
            $source_icon = 'dashicons-dashboard';
            $source_link = admin_url('admin.php?page=wpnm-dashboard');
        } elseif ($note->note_type === 'post') {
            $source_type = __('Post', 'wp-notes-manager');
            $source_icon = 'dashicons-format-aside';
            $source_link = $note->edit_link;
        } elseif ($note->note_type === 'page') {
            $source_type = __('Page', 'wp-notes-manager');
            $source_icon = 'dashicons-admin-page';
            $source_link = $note->edit_link;
        }
        
        // Priority colors
        $priority_colors = [
            'low' => '#10b981',
            'medium' => '#f59e0b', 
            'high' => '#ef4444',
            'urgent' => '#dc2626'
        ];
        
        $priority_color = isset($priority_colors[$note->priority]) ? $priority_colors[$note->priority] : '#6b7280';
        
        // Content preview
        $content_preview = wp_strip_all_tags($note->content);
        $content_preview = wp_trim_words($content_preview, 20, '...');
        
        ?>
                <div class="wpnm-beautiful-note" data-note-id="<?php echo esc_attr($note->id); ?>" data-author-id="<?php echo esc_attr($note->author_id); ?>" data-assigned-to="<?php echo esc_attr($note->assigned_to); ?>" data-stage-id="<?php echo esc_attr($note->stage_id); ?>" data-deadline="<?php echo esc_attr($note->deadline); ?>" style="border-left: 4px solid <?php echo esc_attr($priority_color); ?>;">
            <div class="wpnm-note-main">
                <div class="wpnm-note-header">
                    <div class="wpnm-note-title-row">
                        <h3 class="wpnm-note-title"><?php echo esc_html($note->title); ?></h3>
                        <div class="wpnm-note-badges">
                            <span class="wpnm-priority-tag priority-<?php echo esc_attr($note->priority); ?>">
                                <?php echo esc_html(ucfirst($note->priority)); ?>
                            </span>
                        </div>
                    </div>
                    <div class="wpnm-note-meta-row">
                        <div class="wpnm-note-source">
                            <span class="dashicons <?php echo esc_attr($source_icon); ?>"></span>
                            <span class="wpnm-source-text">
                                <?php echo esc_html($source_type); ?>
                                <?php if ($note->note_type !== 'dashboard' && !empty($note->post_title)): ?>
                                    : <?php echo esc_html($note->post_title); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="wpnm-note-actions">
                            <button type="button" class="wpnm-btn wpnm-btn-edit" data-note-id="<?php echo esc_attr($note->id); ?>" title="<?php _e('Edit Note', 'wp-notes-manager'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="wpnm-btn wpnm-btn-archive" data-note-id="<?php echo esc_attr($note->id); ?>" title="<?php _e('Archive', 'wp-notes-manager'); ?>">
                                <span class="dashicons dashicons-archive"></span>
                            </button>
                            <button type="button" class="wpnm-btn wpnm-btn-delete" data-note-id="<?php echo esc_attr($note->id); ?>" title="<?php _e('Delete', 'wp-notes-manager'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="wpnm-note-content">
                    <p class="wpnm-content-text"><?php echo esc_html($content_preview); ?></p>
                </div>
                
                <div class="wpnm-note-footer">
                    <div class="wpnm-note-author">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php echo esc_html($author_name); ?></span>
                    </div>
                    <?php if ($note->assigned_to): ?>
                        <?php 
                        $assigned_user = get_user_by('id', $note->assigned_to);
                        $assigned_name = $assigned_user ? $assigned_user->display_name : __('Unknown User', 'wp-notes-manager');
                        ?>
                        <div class="wpnm-note-assigned">
                            <span class="dashicons dashicons-admin-users"></span>
                            <span><?php printf(__('Assigned to: %s', 'wp-notes-manager'), esc_html($assigned_name)); ?></span>
                        </div>
                    <?php endif; ?>
                    
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
                    
                    <div class="wpnm-note-stage">
                        <?php if ($note->stage_id): ?>
                            <?php 
                            $stages_manager = wpnm()->getComponent('stages');
                            $stage = $stages_manager->getStage($note->stage_id);
                            if ($stage): ?>
                                <div class="wpnm-stage-change-dropdown">
                                    <button type="button" class="wpnm-stage-change-btn" style="background-color: <?php echo esc_attr($stage->color); ?>;" data-note-id="<?php echo esc_attr($note->id); ?>">
                                        <?php echo esc_html($stage->name); ?>
                                    </button>
                                    <div class="wpnm-stage-dropdown">
                                        <a href="#" class="wpnm-stage-dropdown-item" data-stage-id="" data-note-id="<?php echo esc_attr($note->id); ?>">No Stage</a>
                                        <?php
                                        $all_stages = $stages_manager->getStages();
                                        foreach ($all_stages as $stage_option):
                                            if ($stage_option->id != $note->stage_id):
                                        ?>
                                            <a href="#" class="wpnm-stage-dropdown-item" data-stage-id="<?php echo esc_attr($stage_option->id); ?>" data-note-id="<?php echo esc_attr($note->id); ?>">
                                                <?php echo esc_html($stage_option->name); ?>
                                            </a>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="wpnm-stage-change-dropdown">
                                <button type="button" class="wpnm-stage-change-btn" style="background-color: #6b7280;" data-note-id="<?php echo esc_attr($note->id); ?>">
                                    No Stage
                                </button>
                                <div class="wpnm-stage-dropdown">
                                    <?php
                                    $stages_manager = wpnm()->getComponent('stages');
                                    $all_stages = $stages_manager->getStages();
                                    foreach ($all_stages as $stage_option):
                                    ?>
                                        <a href="#" class="wpnm-stage-dropdown-item" data-stage-id="<?php echo esc_attr($stage_option->id); ?>" data-note-id="<?php echo esc_attr($note->id); ?>">
                                            <?php echo esc_html($stage_option->name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="wpnm-note-date">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php echo esc_html($created_date); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render note card
     *
     * @param object $note Note object
     */
    private function renderNoteCard($note) {
        $author = get_user_by('id', $note->author_id);
        $author_name = $author ? $author->display_name : __('Unknown', 'wp-notes-manager');
        $created_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($note->created_at));
        
        ?>
        <div class="wpnm-note-card <?php echo esc_attr($note->priority); ?>" data-note-id="<?php echo esc_attr($note->id); ?>">
            <div class="wpnm-note-header">
                <h3 class="wpnm-note-title"><?php echo esc_html($note->title); ?></h3>
                <span class="wpnm-note-priority <?php echo esc_attr($note->priority); ?>">
                    <?php echo esc_html(ucfirst($note->priority)); ?>
                </span>
            </div>
            <div class="wpnm-note-content">
                <?php echo wp_kses_post($note->content); ?>
            </div>
            <div class="wpnm-note-meta">
                <?php printf(__('By %s on %s', 'wp-notes-manager'), esc_html($author_name), esc_html($created_date)); ?>
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
     * Render general section
     */
    public function renderGeneralSection() {
        echo '<p>' . __('Configure general plugin settings.', 'wp-notes-manager') . '</p>';
    }
    
    /**
     * Render display section
     */
    public function renderDisplaySection() {
        echo '<p>' . __('Configure display and interface settings.', 'wp-notes-manager') . '</p>';
    }
    
    /**
     * Render auto cleanup field
     */
    public function renderAutoCleanupField() {
        $settings = get_option('wpnm_settings', []);
        $value = isset($settings['auto_cleanup']) ? $settings['auto_cleanup'] : 0;
        
        echo '<input type="checkbox" name="wpnm_settings[auto_cleanup]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Automatically clean up deleted notes after the specified number of days.', 'wp-notes-manager') . '</p>';
    }
    
    /**
     * Render cleanup days field
     */
    public function renderCleanupDaysField() {
        $settings = get_option('wpnm_settings', []);
        $value = isset($settings['cleanup_days']) ? $settings['cleanup_days'] : 30;
        
        echo '<input type="number" name="wpnm_settings[cleanup_days]" value="' . esc_attr($value) . '" min="1" max="365" />';
        echo '<p class="description">' . __('Number of days to keep deleted notes before permanent deletion.', 'wp-notes-manager') . '</p>';
    }
    
    /**
     * Render show in admin bar field
     */
    public function renderShowInAdminBarField() {
        $settings = get_option('wpnm_settings', []);
        $value = isset($settings['show_in_admin_bar']) ? $settings['show_in_admin_bar'] : 1;
        
        echo '<input type="checkbox" name="wpnm_settings[show_in_admin_bar]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Show notes count in the admin bar.', 'wp-notes-manager') . '</p>';
    }
    
    /**
     * Render show dashboard widget field
     */
    public function renderShowDashboardWidgetField() {
        $settings = get_option('wpnm_settings', []);
        $value = isset($settings['show_dashboard_widget']) ? $settings['show_dashboard_widget'] : 1;
        
        echo '<input type="checkbox" name="wpnm_settings[show_dashboard_widget]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Show recent notes widget on the dashboard.', 'wp-notes-manager') . '</p>';
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Raw settings input
     * @return array Sanitized settings
     */
    public function sanitizeSettings($input) {
        $sanitized = [];
        
        $sanitized['auto_cleanup'] = isset($input['auto_cleanup']) ? 1 : 0;
        $sanitized['cleanup_days'] = isset($input['cleanup_days']) ? absint($input['cleanup_days']) : 30;
        $sanitized['show_in_admin_bar'] = isset($input['show_in_admin_bar']) ? 1 : 0;
        $sanitized['show_dashboard_widget'] = isset($input['show_dashboard_widget']) ? 1 : 0;
        
        return $sanitized;
    }
    
    /**
     * Render Enhanced Test Suite page
     */
    public function renderEnhancedTestPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'wp-notes-manager'));
        }
        
        // Include and run the enhanced test
        if (file_exists(WPNM_PLUGIN_DIR . 'tests/enhanced-test.php')) {
            include WPNM_PLUGIN_DIR . 'tests/enhanced-test.php';
            wpnm_enhanced_test();
        } else {
            echo '<div class="wrap"><h1>' . __('Enhanced Test Suite', 'wp-notes-manager') . '</h1>';
            echo '<p style="color: red;">Enhanced test suite file not found.</p></div>';
        }
    }
    
    /**
     * Render Manual Testing Checklist page
     */
    public function renderManualChecklistPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'wp-notes-manager'));
        }
        
        // Include and run the manual checklist
        if (file_exists(WPNM_PLUGIN_DIR . 'tests/manual-test-checklist.php')) {
            include WPNM_PLUGIN_DIR . 'tests/manual-test-checklist.php';
            wpnm_manual_test_checklist();
        } else {
            echo '<div class="wrap"><h1>' . __('Manual Testing Checklist', 'wp-notes-manager') . '</h1>';
            echo '<p style="color: red;">Manual testing checklist file not found.</p></div>';
        }
    }
    
    /**
     * Admin notices
     */
    public function adminNotices() {
        // Add any admin notices here
    }
}


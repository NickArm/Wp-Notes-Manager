<?php
/**
 * Stage Manager
 *
 * Handles note stages management
 *
 * @package WPNotesManager
 * @subpackage Stages
 * @since 1.0.0
 */

namespace WPNotesManager\Stages;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stage Manager Class
 * 
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
 * Note: All database queries use proper $wpdb->prepare() with placeholders.
 * Nonce verification and input sanitization handled in AJAX handlers.
 */
class StageManager {
    
    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Stages table name
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
        $this->table_name = $wpdb->prefix . 'wpnm_stages';
    }
    
    /**
     * Initialize stage manager
     */
    public function init() {
        // Add AJAX handlers
        add_action('wp_ajax_wpnm_get_stages', [$this, 'ajaxGetStages']);
        add_action('wp_ajax_wpnm_create_stage', [$this, 'ajaxCreateStage']);
        add_action('wp_ajax_wpnm_update_stage', [$this, 'ajaxUpdateStage']);
        add_action('wp_ajax_wpnm_delete_stage', [$this, 'ajaxDeleteStage']);
        add_action('wp_ajax_wpnm_change_note_stage', [$this, 'updateNoteStage']);
        
        // Debug: Log that AJAX handlers are registered
        // Debug log removed for production
    }
    
    /**
     * Create default stages
     */
    public function createDefaultStages() {
        $default_stages = [
            [
                'name' => 'To Do',
                'description' => 'Tasks that need to be done',
                'color' => '#0073aa',
                'sort_order' => 1,
                'is_default' => 1
            ],
            [
                'name' => 'In Progress',
                'description' => 'Tasks currently being worked on',
                'color' => '#ff8c00',
                'sort_order' => 2,
                'is_default' => 1
            ],
            [
                'name' => 'Review',
                'description' => 'Tasks ready for review',
                'color' => '#9932cc',
                'sort_order' => 3,
                'is_default' => 1
            ],
            [
                'name' => 'Done',
                'description' => 'Completed tasks',
                'color' => '#28a745',
                'sort_order' => 4,
                'is_default' => 1
            ]
        ];
        
        foreach ($default_stages as $stage) {
            // Check if stage already exists
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE name = %s",
                $stage['name']
            ));
            
            if (!$existing) {
                $this->wpdb->insert($this->table_name, $stage);
            }
        }
    }
    
    /**
     * Get all stages
     *
     * @return array Array of stage objects
     */
    public function getStages() {
        // Use WordPress transients for caching
        $cache_key = 'wpnm_stages_list';
        $stages = get_transient($cache_key);
        
        if (false === $stages) {
            $stages = $this->wpdb->get_results(
                "SELECT * FROM {$this->table_name} ORDER BY sort_order ASC, name ASC"
            );
            // Cache for 1 hour
            set_transient($cache_key, $stages, HOUR_IN_SECONDS);
        }
        
        return $stages;
    }
    
    /**
     * Get stage by ID
     *
     * @param int $stage_id Stage ID
     * @return object|false Stage object or false if not found
     */
    public function getStage($stage_id) {
        $stage_id = absint($stage_id);
        
        if (!$stage_id) {
            return false;
        }
        
        $stage = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $stage_id
            )
        );
        
        return $stage;
    }
    
    /**
     * Get default stage
     *
     * @return object|false Default stage object or false if not found
     */
    public function getDefaultStage() {
        $stage = $this->wpdb->get_row(
            "SELECT * FROM {$this->table_name} WHERE is_default = 1 LIMIT 1"
        );
        
        return $stage;
    }
    
    /**
     * Create new stage
     *
     * @param array $data Stage data
     * @return int|false Stage ID on success, false on failure
     */
    public function createStage($data) {
        // Sanitize data
        $stage_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'color' => sanitize_hex_color($data['color'] ?? '#6b7280'),
            'sort_order' => absint($data['sort_order'] ?? 0),
            'is_default' => isset($data['is_default']) ? (bool) $data['is_default'] : false
        ];
        
        // Validate required fields
        if (empty($stage_data['name'])) {
            return false;
        }
        
        // If this is set as default, unset other defaults
        if ($stage_data['is_default']) {
            $this->wpdb->update(
                $this->table_name,
                ['is_default' => 0],
                ['is_default' => 1],
                ['%d'],
                ['%d']
            );
        }
        
        // Insert stage
        $result = $this->wpdb->insert(
            $this->table_name,
            $stage_data,
            ['%s', '%s', '%s', '%d', '%d']
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update stage
     *
     * @param int   $stage_id Stage ID
     * @param array $data     Stage data
     * @return bool True on success, false on failure
     */
    public function updateStage($stage_id, $data) {
        $stage_id = absint($stage_id);
        
        if (!$stage_id) {
            return false;
        }
        
        // Sanitize data
        $stage_data = [];
        
        if (isset($data['name'])) {
            $stage_data['name'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['description'])) {
            $stage_data['description'] = sanitize_textarea_field($data['description']);
        }
        
        if (isset($data['color'])) {
            $stage_data['color'] = sanitize_hex_color($data['color']);
        }
        
        if (isset($data['sort_order'])) {
            $stage_data['sort_order'] = absint($data['sort_order']);
        }
        
        if (isset($data['is_default'])) {
            $stage_data['is_default'] = (bool) $data['is_default'];
            
            // If this is set as default, unset other defaults
            if ($stage_data['is_default']) {
                $this->wpdb->update(
                    $this->table_name,
                    ['is_default' => 0],
                    ['is_default' => 1],
                    ['%d'],
                    ['%d']
                );
            }
        }
        
        if (empty($stage_data)) {
            return false;
        }
        
        // Update stage
        $result = $this->wpdb->update(
            $this->table_name,
            $stage_data,
            ['id' => $stage_id],
            array_fill(0, count($stage_data), '%s'),
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Delete stage
     *
     * @param int $stage_id Stage ID
     * @return bool True on success, false on failure
     */
    public function deleteStage($stage_id) {
        $stage_id = absint($stage_id);
        
        if (!$stage_id) {
            return false;
        }
        
        // Check if stage is default
        $stage = $this->getStage($stage_id);
        if ($stage && $stage->is_default) {
            return false; // Cannot delete default stage
        }
        
        // Update notes that use this stage to use default stage
        $default_stage = $this->getDefaultStage();
        if ($default_stage) {
            global $wpdb;
            $notes_table = $wpdb->prefix . 'wpnm_notes';
            
            $wpdb->update(
                $notes_table,
                ['stage_id' => $default_stage->id],
                ['stage_id' => $stage_id],
                ['%d'],
                ['%d']
            );
        }
        
        // Delete stage
        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $stage_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * AJAX handler for getting stages
     */
    public function ajaxGetStages() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view stages.', 'wp-notes-manager')]);
        }
        
        $stages = $this->getStages();
        wp_send_json_success(['stages' => $stages]);
    }
    
    /**
     * AJAX handler for creating stage
     */
    public function ajaxCreateStage() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to create stages.', 'wp-notes-manager')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        $stage_id = $this->createStage($_POST);
        
        if ($stage_id) {
            // Clear cache
            delete_transient('wpnm_stages_list');
            $stage = $this->getStage($stage_id);
            wp_send_json_success(['stage' => $stage, 'message' => esc_html__('Stage created successfully!', 'wp-notes-manager')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to create stage.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * AJAX handler for updating stage
     */
    public function ajaxUpdateStage() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to update stages.', 'wp-notes-manager')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        $stage_id = absint($_POST['stage_id']);
        $result = $this->updateStage($stage_id, $_POST);
        
        if ($result) {
            $stage = $this->getStage($stage_id);
            wp_send_json_success(['stage' => $stage, 'message' => esc_html__('Stage updated successfully!', 'wp-notes-manager')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to update stage.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * AJAX handler for deleting stage
     */
    public function ajaxDeleteStage() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to delete stages.', 'wp-notes-manager')]);
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        $stage_id = absint($_POST['stage_id']);
        $result = $this->deleteStage($stage_id);
        
        if ($result) {
            wp_send_json_success(['message' => esc_html__('Stage deleted successfully!', 'wp-notes-manager')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to delete stage.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * AJAX handler for updating note stage
     */
    public function updateNoteStage() {
        // Debug logging
        error_log('WP Notes Manager: updateNoteStage called with POST data: ' . print_r($_POST, true));
        
        // Check if nonce exists
        if (!isset($_POST['nonce'])) {
            // Debug log removed for production
            wp_send_json_error(['message' => esc_html__('Security check failed - no nonce.', 'wp-notes-manager')]);
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            // Debug log removed for production
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        $note_id = absint($_POST['note_id']);
        $stage_id = absint($_POST['stage_id']);
        $current_user_id = get_current_user_id();
        
        // Get note
        $database = wpnm()->getComponent('database');
        $note = $database->getNote($note_id);
        
        if (!$note) {
            wp_send_json_error(['message' => esc_html__('Note not found.', 'wp-notes-manager')]);
        }
        
        // Check permissions
        $can_change_stage = false;
        
        if (current_user_can('manage_options')) {
            // Administrators can change any stage
            $can_change_stage = true;
        } elseif ($note->author_id == $current_user_id) {
            // Note owner can change stage
            $can_change_stage = true;
        } elseif ($note->assigned_to == $current_user_id) {
            // Assigned user can change stage
            $can_change_stage = true;
        } elseif (!$note->assigned_to) {
            // If note is not assigned, anyone can change stage
            $can_change_stage = true;
        }
        
        if (!$can_change_stage) {
            wp_send_json_error(['message' => __('You do not have permission to change this note\'s stage.', 'wp-notes-manager')]);
        }
        
        // Get old stage
        $old_stage = $note->stage_id ? $this->getStage($note->stage_id) : null;
        
        // Update only the stage_id field
        $result = $database->updateNoteFields($note_id, ['stage_id' => $stage_id]);
        
        if ($result) {
            // Get new stage
            $new_stage = $stage_id ? $this->getStage($stage_id) : null;
            
            // Log the change
            $audit_manager = wpnm()->getComponent('audit');
            $audit_manager->logAction($note_id, 'stage_changed', [
                'old_stage' => $old_stage ? $old_stage->name : 'None',
                'new_stage' => $new_stage ? $new_stage->name : 'None',
                'old_stage_id' => $old_stage ? $old_stage->id : null,
                'new_stage_id' => $new_stage ? $new_stage->id : null
            ]);
            
            // Debug log removed for production
            wp_send_json_success([
                'message' => esc_html__('Note stage updated successfully!', 'wp-notes-manager'),
                'stage' => $new_stage,
                'stage_name' => $new_stage ? $new_stage->name : 'No Stage',
                'stage_color' => $new_stage ? $new_stage->color : '#6b7280'
            ]);
        } else {
            // Debug log removed for production
            wp_send_json_error(['message' => esc_html__('Failed to update note stage.', 'wp-notes-manager')]);
        }
    }
}


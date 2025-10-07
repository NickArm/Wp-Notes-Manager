<?php
/**
 * AJAX Handler
 *
 * Handles all AJAX requests for the notes plugin
 *
 * @package WPNotesManager
 * @subpackage Ajax
 * @since 1.0.0
 */

namespace WPNotesManager\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler Class
 */
class AjaxHandler {
    
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
     * Initialize AJAX handlers
     */
    public function init() {
        // Add note
        add_action('wp_ajax_wpnm_add_note', [$this, 'addNote']);
        add_action('wp_ajax_nopriv_wpnm_add_note', [$this, 'addNote']);
        
        // Update note
        add_action('wp_ajax_wpnm_update_note', [$this, 'updateNote']);
        add_action('wp_ajax_nopriv_wpnm_update_note', [$this, 'updateNote']);
        
        // Delete note
        add_action('wp_ajax_wpnm_delete_note', [$this, 'deleteNote']);
        add_action('wp_ajax_nopriv_wpnm_delete_note', [$this, 'deleteNote']);
        
        // Archive note
        add_action('wp_ajax_wpnm_archive_note', [$this, 'archiveNote']);
        add_action('wp_ajax_nopriv_wpnm_archive_note', [$this, 'archiveNote']);
        
        // Restore note
        add_action('wp_ajax_wpnm_restore_note', [$this, 'restoreNote']);
        add_action('wp_ajax_nopriv_wpnm_restore_note', [$this, 'restoreNote']);
        
        // Get notes
        add_action('wp_ajax_wpnm_get_notes', [$this, 'getNotes']);
        add_action('wp_ajax_nopriv_wpnm_get_notes', [$this, 'getNotes']);
        
        // Get note
        add_action('wp_ajax_wpnm_get_note', [$this, 'getNote']);
        add_action('wp_ajax_nopriv_wpnm_get_note', [$this, 'getNote']);
        
        // Get statistics
        add_action('wp_ajax_wpnm_get_stats', [$this, 'getStats']);
        add_action('wp_ajax_nopriv_wpnm_get_stats', [$this, 'getStats']);
        
        // Save layout preference
        add_action('wp_ajax_wpnm_save_layout_preference', [$this, 'saveLayoutPreference']);
        
        // Get current post ID from URL (frontend)
        add_action('wp_ajax_wpnm_get_current_post_id', [$this, 'getCurrentPostId']);
        add_action('wp_ajax_nopriv_wpnm_get_current_post_id', [$this, 'getCurrentPostId']);
    }
    
    /**
     * Add new note
     */
    public function addNote() {
        // Debug logging
        error_log('WP Notes Manager: AJAX addNote called');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            error_log('WP Notes Manager: Nonce verification failed');
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            error_log('WP Notes Manager: User does not have permission');
            wp_send_json_error(['message' => esc_html__('You do not have permission to add notes.', 'wp-notes-manager')]);
            return;
        }
        
        // Sanitize input
        $note_data = [
            'post_id' => isset($_POST['post_id']) ? absint($_POST['post_id']) : null,
            'note_type' => isset($_POST['note_type']) ? sanitize_text_field($_POST['note_type']) : 'dashboard',
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
            'priority' => isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : 'medium',
            // color removed
            'assigned_to' => isset($_POST['assigned_to']) ? absint($_POST['assigned_to']) : null,
            'stage_id' => isset($_POST['stage_id']) ? absint($_POST['stage_id']) : null,
            'deadline' => isset($_POST['deadline']) && !empty($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : null
        ];
        
        // Validate required fields
        if (empty($note_data['title']) || empty($note_data['content'])) {
            wp_send_json_error(['message' => esc_html__('Title and content are required.', 'wp-notes-manager')]);
            return;
        }
        
        // Validate note type
        $allowed_types = ['dashboard', 'post', 'page'];
        if (!in_array($note_data['note_type'], $allowed_types)) {
            wp_send_json_error(['message' => esc_html__('Invalid note type.', 'wp-notes-manager')]);
            return;
        }
        
        // Validate priority
        $allowed_priorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($note_data['priority'], $allowed_priorities)) {
            wp_send_json_error(['message' => esc_html__('Invalid priority level.', 'wp-notes-manager')]);
            return;
        }
        
        // Create note
        error_log('WP Notes Manager: Creating note with data: ' . print_r($note_data, true));
        $note_id = $this->getDatabase()->createNote($note_data);
        error_log('WP Notes Manager: Note creation result: ' . $note_id);
        
        if ($note_id) {
            // Get the created note
            $note = $this->getDatabase()->getNote($note_id);
            
            // Log the creation
            $audit_manager = wpnm()->getComponent('audit');
            if ($audit_manager) {
                $audit_manager->logAction($note_id, 'note_created', [
                    'title' => $note_data['title'],
                    'note_type' => $note_data['note_type'],
                    'assigned_to' => $note_data['assigned_to'],
                    'stage_id' => $note_data['stage_id']
                ]);
            }
            
            wp_send_json_success([
                'message' => esc_html__('Note added successfully!', 'wp-notes-manager'),
                'note' => $note
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to add note.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * Update note
     */
    public function updateNote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to update notes.', 'wp-notes-manager')]);
        }
        
        // Get note ID
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(['message' => esc_html__('Invalid note ID.', 'wp-notes-manager')]);
        }
        
        // Check if note exists
        $note = $this->getDatabase()->getNote($note_id);
        if (!$note) {
            wp_send_json_error(['message' => esc_html__('Note not found.', 'wp-notes-manager')]);
        }
        
        // Check if current user is the owner of the note
        if ($note->author_id != get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You can only edit your own notes.', 'wp-notes-manager')]);
        }
        
        // Sanitize input
        $note_data = [
            'post_id' => isset($_POST['post_id']) ? absint($_POST['post_id']) : $note->post_id,
            'note_type' => isset($_POST['note_type']) ? sanitize_text_field($_POST['note_type']) : $note->note_type,
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : $note->title,
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : $note->content,
            'priority' => isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : $note->priority,
            // color removed
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : $note->status,
            'assigned_to' => isset($_POST['assigned_to']) ? absint($_POST['assigned_to']) : $note->assigned_to,
            'stage_id' => isset($_POST['stage_id']) ? absint($_POST['stage_id']) : $note->stage_id,
            'deadline' => isset($_POST['deadline']) && !empty($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : $note->deadline
        ];
        
        // Validate required fields
        if (empty($note_data['title']) || empty($note_data['content'])) {
            wp_send_json_error(['message' => esc_html__('Title and content are required.', 'wp-notes-manager')]);
        }
        
        // Update note
        $result = $this->getDatabase()->updateNote($note_id, $note_data);
        
        if ($result) {
            // Get the updated note
            $updated_note = $this->getDatabase()->getNote($note_id);
            
            // Log the update
            $audit_manager = wpnm()->getComponent('audit');
            $changes = [];
            
            if ($note->title !== $note_data['title']) {
                $changes['title'] = ['old' => $note->title, 'new' => $note_data['title']];
            }
            if ($note->content !== $note_data['content']) {
                $changes['content'] = ['old' => $note->content, 'new' => $note_data['content']];
            }
            if ($note->priority !== $note_data['priority']) {
                $changes['priority'] = ['old' => $note->priority, 'new' => $note_data['priority']];
            }
            if ($note->assigned_to != $note_data['assigned_to']) {
                $changes['assignment'] = [
                    'old_assigned_to' => $note->assigned_to,
                    'new_assigned_to' => $note_data['assigned_to']
                ];
            }
            if ($note->stage_id != $note_data['stage_id']) {
                $changes['stage'] = [
                    'old_stage_id' => $note->stage_id,
                    'new_stage_id' => $note_data['stage_id']
                ];
            }
            
            if (!empty($changes)) {
                $audit_manager->logAction($note_id, 'note_updated', $changes);
            }
            
            wp_send_json_success([
                'message' => esc_html__('Note updated successfully!', 'wp-notes-manager'),
                'note' => $updated_note
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to update note.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * Delete note
     */
    public function deleteNote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to delete notes.', 'wp-notes-manager')]);
        }
        
        // Get note ID
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(['message' => esc_html__('Invalid note ID.', 'wp-notes-manager')]);
        }
        
        // Check if note exists
        $note = $this->getDatabase()->getNote($note_id);
        if (!$note) {
            wp_send_json_error(['message' => esc_html__('Note not found.', 'wp-notes-manager')]);
        }
        
        // Check if current user is the owner of the note
        if ($note->author_id != get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You can only delete your own notes.', 'wp-notes-manager')]);
        }
        
        // Delete note
        $result = $this->getDatabase()->deleteNote($note_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => esc_html__('Note deleted successfully!', 'wp-notes-manager')
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to delete note.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * Archive note
     */
    public function archiveNote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to archive notes.', 'wp-notes-manager')]);
        }
        
        // Get note ID
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(['message' => esc_html__('Invalid note ID.', 'wp-notes-manager')]);
        }
        
        // Check if note exists
        $note = $this->getDatabase()->getNote($note_id);
        if (!$note) {
            wp_send_json_error(['message' => esc_html__('Note not found.', 'wp-notes-manager')]);
        }
        
        // Check if current user is the owner of the note
        if ($note->author_id != get_current_user_id()) {
            wp_send_json_error(['message' => esc_html__('You can only archive your own notes.', 'wp-notes-manager')]);
        }
        
        // Archive note
        $result = $this->getDatabase()->archiveNote($note_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => esc_html__('Note archived successfully!', 'wp-notes-manager')
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to archive note.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * Restore note
     */
    public function restoreNote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to restore notes.', 'wp-notes-manager')]);
        }
        
        // Get note ID
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(['message' => esc_html__('Invalid note ID.', 'wp-notes-manager')]);
        }
        
        // Restore note
        $result = $this->getDatabase()->restoreNote($note_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => esc_html__('Note restored successfully!', 'wp-notes-manager')
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Failed to restore note.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * Get notes
     */
    public function getNotes() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        // Check user permissions
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view notes.', 'wp-notes-manager')]);
        }
        
        // Get parameters
        $note_type = isset($_POST['note_type']) ? sanitize_text_field($_POST['note_type']) : 'dashboard';
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : null;
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 20;
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        
        // Validate note type
        $allowed_types = ['dashboard', 'post', 'page'];
        if (!in_array($note_type, $allowed_types)) {
            wp_send_json_error(['message' => esc_html__('Invalid note type.', 'wp-notes-manager')]);
        }
        
        // Get notes
        $notes = $this->getDatabase()->getNotes($note_type, $post_id, $limit, $offset);
        
        wp_send_json_success([
            'notes' => $notes,
            'count' => count($notes)
        ]);
    }
    
    /**
     * Get single note
     */
    public function getNote() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        // Check user permissions
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view notes.', 'wp-notes-manager')]);
        }
        
        // Get note ID
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(['message' => esc_html__('Invalid note ID.', 'wp-notes-manager')]);
        }
        
        // Get note
        $note = $this->getDatabase()->getNote($note_id);
        
        if ($note) {
            wp_send_json_success([
                'note' => $note
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Note not found.', 'wp-notes-manager')]);
        }
    }
    
    /**
     * Get statistics
     */
    public function getStats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        // Check user permissions
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view statistics.', 'wp-notes-manager')]);
        }
        
        // Get statistics
        $stats = $this->getDatabase()->getStats();
        
        wp_send_json_success([
            'stats' => $stats
        ]);
    }
    
    /**
     * Save layout preference
     */
    public function saveLayoutPreference() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to save preferences.', 'wp-notes-manager')]);
            return;
        }
        
        $layout = sanitize_text_field($_POST['layout']);
        $valid_layouts = ['list', '2-columns', '3-columns'];
        
        if (!in_array($layout, $valid_layouts)) {
            wp_send_json_error(['message' => esc_html__('Invalid layout preference.', 'wp-notes-manager')]);
            return;
        }
        
        // Save to user meta
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'wpnm_notes_layout', $layout);
        
        wp_send_json_success([
            'message' => esc_html__('Layout preference saved successfully!', 'wp-notes-manager'),
            'layout' => $layout
        ]);
    }
    
    /**
     * Get current post ID from URL (for frontend)
     */
    public function getCurrentPostId() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_frontend_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        $url = sanitize_url($_POST['url']);
        
        // Try to get post ID from URL
        $post_id = url_to_postid($url);
        
        if ($post_id) {
            wp_send_json_success([
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ]);
        } else {
            wp_send_json_error(['message' => esc_html__('Could not determine post ID from URL.', 'wp-notes-manager')]);
        }
    }
}


<?php
/**
 * Audit Manager
 *
 * Handles audit logging for all note actions
 *
 * @package WPNotesManager
 * @subpackage Audit
 * @since 1.0.0
 */

namespace WPNotesManager\Audit;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Audit Manager Class
 */
class AuditManager {
    
    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Audit logs table name
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
        $this->table_name = $wpdb->prefix . 'wpnm_audit_logs';
    }
    
    /**
     * Initialize audit manager
     */
    public function init() {
        // Add AJAX handlers
        add_action('wp_ajax_wpnm_get_audit_logs', [$this, 'ajaxGetAuditLogs']);
        add_action('wp_ajax_wpnm_clear_audit_logs', [$this, 'ajaxClearAuditLogs']);
    }
    
    /**
     * Log an action
     *
     * @param int    $note_id   Note ID
     * @param string $action    Action performed
     * @param array  $details   Additional details
     * @param int    $user_id   User ID (optional, defaults to current user)
     * @return int|false Log ID on success, false on failure
     */
    public function logAction($note_id, $action, $details = [], $user_id = null) {
        $note_id = absint($note_id);
        $action = sanitize_text_field($action);
        
        if (!$note_id || !$action) {
            return false;
        }
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $user_id = absint($user_id);
        
        // Prepare log data
        $log_data = [
            'note_id' => $note_id,
            'user_id' => $user_id,
            'action' => $action,
            'old_value' => isset($details['old_value']) ? maybe_serialize($details['old_value']) : null,
            'new_value' => isset($details['new_value']) ? maybe_serialize($details['new_value']) : null,
            'details' => maybe_serialize($details),
            'ip_address' => $this->getClientIP(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : ''
        ];
        
        // Insert log entry
        $result = $this->wpdb->insert(
            $this->table_name,
            $log_data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get audit logs
     *
     * @param int $note_id Note ID (optional)
     * @param int $limit   Number of logs to retrieve
     * @param int $offset  Offset for pagination
     * @return array Array of log objects
     */
    public function getAuditLogs($note_id = null, $limit = 50, $offset = 0) {
        $limit = absint($limit);
        $offset = absint($offset);
        
        $where_conditions = [];
        $where_values = [];
        
        if ($note_id) {
            $where_conditions[] = "note_id = %d";
            $where_values[] = absint($note_id);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Build query with proper placeholders
        $query = "SELECT al.*, u.display_name as user_name, n.title as note_title 
                 FROM {$this->table_name} al
                 LEFT JOIN {$this->wpdb->users} u ON al.user_id = u.ID
                 LEFT JOIN {$this->wpdb->prefix}wpnm_notes n ON al.note_id = n.id
                 {$where_clause}
                 ORDER BY al.created_at DESC
                 LIMIT %d OFFSET %d";
        
        // Prepare query with all values
        $prepared_values = array_merge($where_values, [$limit, $offset]);
        $logs = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $prepared_values) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        );
        
        // Unserialize details
        foreach ($logs as $log) {
            $log->details = maybe_unserialize($log->details);
            $log->old_value = maybe_unserialize($log->old_value);
            $log->new_value = maybe_unserialize($log->new_value);
        }
        
        return $logs;
    }
    
    /**
     * Get audit logs count
     *
     * @param int $note_id Note ID (optional)
     * @return int Number of logs
     */
    public function getAuditLogsCount($note_id = null) {
        $where_conditions = [];
        $where_values = [];
        
        if ($note_id) {
            $where_conditions[] = "note_id = %d";
            $where_values[] = absint($note_id);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Build query
        $query = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        
        // Prepare query if we have values
        if (!empty($where_values)) {
            $count = $this->wpdb->get_var(
                $this->wpdb->prepare($query, $where_values) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
        } else {
            $count = $this->wpdb->get_var($query); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        
        return absint($count);
    }
    
    /**
     * Clear audit logs
     *
     * @param int $days Number of days to keep (optional)
     * @return int Number of logs deleted
     */
    public function clearAuditLogs($days = null) {
        if ($days === null) {
            // Clear all logs - use a safe query without variables
            $query = "DELETE FROM {$this->table_name}";
            $deleted = $this->wpdb->query($query); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        } else {
            // Clear logs older than specified days
            $days = absint($days);
            $query = "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)";
            $deleted = $this->wpdb->query(
                $this->wpdb->prepare($query, $days) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
        }
        
        return absint($deleted);
    }
    
    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $server_value = isset($_SERVER[$key]) ? sanitize_text_field(wp_unslash($_SERVER[$key])) : '';
                foreach (explode(',', $server_value) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    }
    
    /**
     * AJAX handler for getting audit logs
     */
    public function ajaxGetAuditLogs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to view audit logs.', 'wp-notes-manager')]);
        }
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        $note_id = isset($_POST['note_id']) ? absint($_POST['note_id']) : null;
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        
        $logs = $this->getAuditLogs($note_id, $limit, $offset);
        $total = $this->getAuditLogsCount($note_id);
        
        wp_send_json_success([
            'logs' => $logs,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ]);
    }
    
    /**
     * AJAX handler for clearing audit logs
     */
    public function ajaxClearAuditLogs() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to clear audit logs.', 'wp-notes-manager')]);
        }
        
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
        }
        
        $days = isset($_POST['days']) ? absint($_POST['days']) : null;
        $deleted = $this->clearAuditLogs($days);
        
        wp_send_json_success([
            // translators: %d is the number of deleted log entries
            'message' => sprintf(esc_html__('%d audit log entries cleared.', 'wp-notes-manager'), $deleted),
            'deleted' => $deleted
        ]);
    }
    
    /**
     * Format action for display
     *
     * @param string $action Action name
     * @param array  $details Action details
     * @return string Formatted action text
     */
    public function formatAction($action, $details) {
        switch ($action) {
            case 'note_created':
                return esc_html__('Note created', 'wp-notes-manager');
                
            case 'note_updated':
                return esc_html__('Note updated', 'wp-notes-manager');
                
            case 'note_deleted':
                return esc_html__('Note deleted', 'wp-notes-manager');
                
            case 'note_archived':
                return esc_html__('Note archived', 'wp-notes-manager');
                
            case 'note_restored':
                return esc_html__('Note restored', 'wp-notes-manager');
                
            case 'assignment_changed':
                $old_user = $details['old_assigned_to'] ? get_user_by('id', $details['old_assigned_to']) : null;
                $new_user = $details['new_assigned_to'] ? get_user_by('id', $details['new_assigned_to']) : null;
                
                if ($old_user && $new_user) {
                    // translators: %1$s is the old user name, %2$s is the new user name
                    return sprintf(esc_html__('Assignment changed from %1$s to %2$s', 'wp-notes-manager'), $old_user->display_name, $new_user->display_name);
                } elseif ($new_user) {
                    // translators: %s is the user name
                    return sprintf(esc_html__('Assigned to %s', 'wp-notes-manager'), $new_user->display_name);
                } elseif ($old_user) {
                    // translators: %s is the user name
                    return sprintf(esc_html__('Unassigned from %s', 'wp-notes-manager'), $old_user->display_name);
                }
                return esc_html__('Assignment changed', 'wp-notes-manager');
                
            case 'stage_changed':
                $old_stage = $details['old_stage'] ?? 'None';
                $new_stage = $details['new_stage'] ?? 'None';
                // translators: %1$s is the old stage name, %2$s is the new stage name
                return sprintf(esc_html__('Stage changed from %1$s to %2$s', 'wp-notes-manager'), $old_stage, $new_stage);
                
            default:
                return ucfirst(str_replace('_', ' ', $action));
        }
    }
}


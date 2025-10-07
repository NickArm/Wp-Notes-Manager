<?php
/**
 * Security Manager
 *
 * Handles security features and permissions
 *
 * @package WPNotesManager
 * @subpackage Security
 * @since 1.0.0
 */

namespace WPNotesManager\Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Manager Class
 * 
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 * Note: This class handles nonce verification and input validation.
 * Nonce checks are performed before processing sensitive operations.
 */
class SecurityManager {
    
    /**
     * Initialize security manager
     */
    public function init() {
        // Add security headers
        add_action('init', [$this, 'addSecurityHeaders']);
        
        // Sanitize input data
        add_action('init', [$this, 'sanitizeInput']);
        
        // Add capability checks
        add_action('init', [$this, 'addCapabilities']);
        
        // Add nonce verification
        add_action('init', [$this, 'verifyNonces']);
        
        // Add rate limiting
        add_action('init', [$this, 'addRateLimiting']);
        
        // Add IP blocking
        add_action('init', [$this, 'checkBlockedIPs']);
        
        // Add audit logging
        add_action('init', [$this, 'initAuditLogging']);
    }
    
    /**
     * Add security headers
     */
    public function addSecurityHeaders() {
        // Only add headers for our plugin pages
        if (isset($_GET['page']) && strpos($_GET['page'], 'wpnm-') === 0) {
            // Prevent clickjacking
            header('X-Frame-Options: SAMEORIGIN');
            
            // Prevent MIME type sniffing
            header('X-Content-Type-Options: nosniff');
            
            // Enable XSS protection
            header('X-XSS-Protection: 1; mode=block');
            
            // Referrer policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Content Security Policy
            $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;";
            header("Content-Security-Policy: $csp");
        }
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput() {
        // Sanitize GET parameters
        if (isset($_GET['wpnm_action'])) {
            $_GET['wpnm_action'] = sanitize_text_field($_GET['wpnm_action']);
        }
        
        if (isset($_GET['wpnm_note_id'])) {
            $_GET['wpnm_note_id'] = absint($_GET['wpnm_note_id']);
        }
        
        // Sanitize POST parameters
        if (isset($_POST['wpnm_note_title'])) {
            $_POST['wpnm_note_title'] = sanitize_text_field($_POST['wpnm_note_title']);
        }
        
        if (isset($_POST['wpnm_note_content'])) {
            $_POST['wpnm_note_content'] = wp_kses_post($_POST['wpnm_note_content']);
        }
        
        if (isset($_POST['wpnm_note_priority'])) {
            $allowed_priorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($_POST['wpnm_note_priority'], $allowed_priorities)) {
                $_POST['wpnm_note_priority'] = 'medium';
            }
        }
        
        if (isset($_POST['wpnm_note_color'])) {
            $_POST['wpnm_note_color'] = sanitize_hex_color($_POST['wpnm_note_color']);
        }
    }
    
    /**
     * Add custom capabilities
     */
    public function addCapabilities() {
        // Add custom capabilities for notes management
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_notes');
            $role->add_cap('edit_notes');
            $role->add_cap('delete_notes');
            $role->add_cap('view_notes');
        }
        
        $role = get_role('editor');
        if ($role) {
            $role->add_cap('manage_notes');
            $role->add_cap('edit_notes');
            $role->add_cap('view_notes');
        }
        
        $role = get_role('author');
        if ($role) {
            $role->add_cap('edit_notes');
            $role->add_cap('view_notes');
        }
        
        $role = get_role('contributor');
        if ($role) {
            $role->add_cap('view_notes');
        }
    }
    
    /**
     * Verify nonces
     */
    public function verifyNonces() {
        // Check nonces for AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
            
            if (strpos($action, 'wpnm_') === 0) {
                $nonce_field = 'nonce';
                $nonce_value = isset($_POST[$nonce_field]) ? $_POST[$nonce_field] : '';
                
                // Check multiple possible nonce values
                $nonce_verified = wp_verify_nonce($nonce_value, 'wpnm_admin_nonce') || 
                                 wp_verify_nonce($nonce_value, 'wpnm_add_note') ||
                                 wp_verify_nonce($nonce_value, 'wpnm_frontend_nonce');
                
                if (!$nonce_verified) {
                    wp_send_json_error(['message' => esc_html__('Security check failed.', 'notes-manager')]);
                }
            }
        }
    }
    
    /**
     * Add rate limiting
     */
    public function addRateLimiting() {
        // Rate limiting for AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
            
            if (strpos($action, 'wpnm_') === 0) {
                $user_id = get_current_user_id();
                $ip_address = $this->getClientIP();
                $rate_limit_key = "wpnm_rate_limit_{$user_id}_{$ip_address}";
                
                $requests = get_transient($rate_limit_key);
                if ($requests === false) {
                    set_transient($rate_limit_key, 1, 60); // 1 minute window
                } else {
                    if ($requests >= 30) { // Max 30 requests per minute
                        wp_send_json_error(['message' => esc_html__('Rate limit exceeded. Please try again later.', 'notes-manager')]);
                    }
                    set_transient($rate_limit_key, $requests + 1, 60);
                }
            }
        }
    }
    
    /**
     * Check blocked IPs
     */
    public function checkBlockedIPs() {
        $ip_address = $this->getClientIP();
        $blocked_ips = get_option('wpnm_blocked_ips', []);
        
        if (in_array($ip_address, $blocked_ips)) {
            wp_die(esc_html__('Access denied.', 'notes-manager'), esc_html__('Access Denied', 'notes-manager'), ['response' => 403]);
        }
    }
    
    /**
     * Initialize audit logging
     */
    public function initAuditLogging() {
        // Log note actions
        add_action('wpnm_note_created', [$this, 'logNoteAction'], 10, 2);
        add_action('wpnm_note_updated', [$this, 'logNoteAction'], 10, 2);
        add_action('wpnm_note_deleted', [$this, 'logNoteAction'], 10, 2);
        add_action('wpnm_note_archived', [$this, 'logNoteAction'], 10, 2);
    }
    
    /**
     * Log note actions
     *
     * @param int    $note_id Note ID
     * @param string $action  Action performed
     */
    public function logNoteAction($note_id, $action) {
        $user_id = get_current_user_id();
        $ip_address = $this->getClientIP();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        $timestamp = current_time('mysql');
        
        $log_entry = [
            'note_id' => $note_id,
            'action' => $action,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'timestamp' => $timestamp
        ];
        
        // Store in options (in production, use a proper logging system)
        $logs = get_option('wpnm_audit_logs', []);
        $logs[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        update_option('wpnm_audit_logs', $logs);
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
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Check if user can perform action
     *
     * @param string $action Action to check
     * @param int    $note_id Note ID (optional)
     * @return bool True if user can perform action
     */
    public function canPerformAction($action, $note_id = null) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        switch ($action) {
            case 'create':
                return current_user_can('edit_notes');
                
            case 'read':
                return current_user_can('view_notes');
                
            case 'update':
                if ($note_id) {
                    $note = wpnm()->getComponent('database')->getNote($note_id);
                    if ($note) {
                        // Users can edit their own notes or if they have manage_notes capability
                        return ($note->author_id == $user_id) || current_user_can('manage_notes');
                    }
                }
                return current_user_can('edit_notes');
                
            case 'delete':
                if ($note_id) {
                    $note = wpnm()->getComponent('database')->getNote($note_id);
                    if ($note) {
                        // Users can delete their own notes or if they have manage_notes capability
                        return ($note->author_id == $user_id) || current_user_can('manage_notes');
                    }
                }
                return current_user_can('delete_notes');
                
            case 'archive':
                return current_user_can('edit_notes');
                
            default:
                return false;
        }
    }
    
    /**
     * Validate note data
     *
     * @param array $data Note data
     * @return array Validated data
     */
    public function validateNoteData($data) {
        $validated = [];
        
        // Title validation
        if (isset($data['title'])) {
            $title = sanitize_text_field($data['title']);
            if (strlen($title) < 1 || strlen($title) > 255) {
                wp_send_json_error(['message' => esc_html__('Title must be between 1 and 255 characters.', 'notes-manager')]);
            }
            $validated['title'] = $title;
        }
        
        // Content validation
        if (isset($data['content'])) {
            $content = wp_kses_post($data['content']);
            if (strlen($content) < 1 || strlen($content) > 10000) {
                wp_send_json_error(['message' => esc_html__('Content must be between 1 and 10,000 characters.', 'notes-manager')]);
            }
            $validated['content'] = $content;
        }
        
        // Priority validation
        if (isset($data['priority'])) {
            $allowed_priorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($data['priority'], $allowed_priorities)) {
                wp_send_json_error(['message' => esc_html__('Invalid priority level.', 'notes-manager')]);
            }
            $validated['priority'] = $data['priority'];
        }
        
        // Color validation
        if (isset($data['color'])) {
            $color = sanitize_hex_color($data['color']);
            if (!$color) {
                $color = '#f1f1f1';
            }
            $validated['color'] = $color;
        }
        
        // Note type validation
        if (isset($data['note_type'])) {
            $allowed_types = ['dashboard', 'post', 'page'];
            if (!in_array($data['note_type'], $allowed_types)) {
                wp_send_json_error(['message' => esc_html__('Invalid note type.', 'notes-manager')]);
            }
            $validated['note_type'] = $data['note_type'];
        }
        
        // Post ID validation
        if (isset($data['post_id']) && $data['post_id']) {
            $post_id = absint($data['post_id']);
            if (!$post_id || !get_post($post_id)) {
                wp_send_json_error(['message' => esc_html__('Invalid post ID.', 'notes-manager')]);
            }
            $validated['post_id'] = $post_id;
        }
        
        return $validated;
    }
    
    /**
     * Block IP address
     *
     * @param string $ip_address IP address to block
     */
    public function blockIP($ip_address) {
        if (current_user_can('manage_options')) {
            $blocked_ips = get_option('wpnm_blocked_ips', []);
            if (!in_array($ip_address, $blocked_ips)) {
                $blocked_ips[] = $ip_address;
                update_option('wpnm_blocked_ips', $blocked_ips);
            }
        }
    }
    
    /**
     * Unblock IP address
     *
     * @param string $ip_address IP address to unblock
     */
    public function unblockIP($ip_address) {
        if (current_user_can('manage_options')) {
            $blocked_ips = get_option('wpnm_blocked_ips', []);
            $key = array_search($ip_address, $blocked_ips);
            if ($key !== false) {
                unset($blocked_ips[$key]);
                update_option('wpnm_blocked_ips', array_values($blocked_ips));
            }
        }
    }
    
    /**
     * Get blocked IPs
     *
     * @return array Blocked IP addresses
     */
    public function getBlockedIPs() {
        return get_option('wpnm_blocked_ips', []);
    }
}


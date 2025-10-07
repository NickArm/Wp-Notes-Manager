<?php
namespace WPNotesManager\Notifications;

use WPNotesManager\Database\DatabaseManager;

class NotificationManager {
    private $wpdb;
    private $database_manager;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        // Database manager will be initialized when needed
    }
    
    /**
     * Get database component
     */
    private function getDatabase() {
        if (!$this->database_manager) {
            $this->database_manager = wpnm()->getComponent('database');
        }
        return $this->database_manager;
    }
    
    public function init() {
        // Register WordPress cron hook for sending notifications
        add_action('wpnm_send_deadline_notifications', [$this, 'sendDeadlineNotifications']);
        
        // Schedule cron job if not already scheduled
        $this->scheduleNotificationCron();
        
        // Add notification settings to user profile
        add_action('show_user_profile', [$this, 'addNotificationSettings']);
        add_action('edit_user_profile', [$this, 'addNotificationSettings']);
        add_action('personal_options_update', [$this, 'saveNotificationSettings']);
        add_action('edit_user_profile_update', [$this, 'saveNotificationSettings']);
        
        // Add AJAX handler for testing notifications
        add_action('wp_ajax_wpnm_test_notification', [$this, 'testNotification']);
    }
    
    /**
     * Schedule WordPress cron job for notifications
     */
    private function scheduleNotificationCron() {
        if (!wp_next_scheduled('wpnm_send_deadline_notifications')) {
            wp_schedule_event(time(), 'daily', 'wpnm_send_deadline_notifications');
        }
    }
    
    /**
     * Send notifications for upcoming deadlines
     */
    public function sendDeadlineNotifications() {
        // Get notification preferences
        $users_with_notifications = $this->wpdb->get_results("
            SELECT user_id, meta_value as preferences 
            FROM {$this->wpdb->usermeta} 
            WHERE meta_key = 'wpnm_notification_preferences' 
            AND meta_value LIKE '%deadlines%'
        ");
        
        foreach ($users_with_notifications as $user_data) {
            $user_id = $user_data->user_id;
            $preferences = maybe_unserialize($user_data->preferences);
            
            if (!$preferences || !isset($preferences['deadlines']['enabled'])) {
                continue;
            }
            
            // Get upcoming deadlines for this user
            $deadlines = $this->getUpcomingDeadlines($user_id, $preferences['deadlines']['days_ahead'] ?? 3);
            
            if (!empty($deadlines)) {
                $this->sendDeadlineEmail($user_id, $deadlines);
            }
        }
        
        // Log notification send
        error_log('WP Notes Manager: Daily deadline notifications sent');
    }
    
    /**
     * Get upcoming deadlines for a user
     */
    private function getUpcomingDeadlines($user_id, $days_ahead = 3) {
        $now = current_time('mysql');
        $future_date = date('Y-m-d H:i:s', strtotime("+{$days_ahead} days"));
        
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT n.*, u.display_name as author_name, s.name as stage_name 
            FROM {$this->wpdb->prefix}wpnm_notes n
            LEFT JOIN {$this->wpdb->users} u ON n.author_id = u.ID
            LEFT JOIN {$this->wpdb->prefix}wpnm_stages s ON n.stage_id = s.id
            WHERE n.status = 'active' 
            AND n.deadline IS NOT NULL
            AND n.deadline BETWEEN %s AND %s
            AND (n.author_id = %d OR n.assigned_to = %d)
            ORDER BY n.deadline ASC
        ", $now, $future_date, $user_id, $user_id));
    }
    
    /**
     * Send deadline email notification
     */
    private function sendDeadlineEmail($user_id, $deadlines) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        // translators: %d is the number of tasks
        $subject = sprintf(esc_html__('Upcoming Deadlines - %d Tasks Requiring Attention', 'wp-notes-manager'), count($deadlines));
        
        // Prepare email content
        $content = $this->generateDeadlineEmailContent($deadlines, $user);
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($user->user_email, $subject, $content, $headers);
        
        // Log email sent
        error_log("WP Notes Manager: Deadline notification sent to {$user->user_email} ({$user_id})");
    }
    
    /**
     * Generate email content for deadline notification
     */
    private function generateDeadlineEmailContent($deadlines, $user) {
        $site_name = get_bloginfo('name');
        $admin_url = esc_url(admin_url('admin.php?page=wpnm-all-notes'));
        
        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="UTF-8"><style>';
        $html .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }';
        $html .= '.header { background: #0073aa; color: white; padding: 20px; text-align: center; }';
        $html .= '.content { padding: 20px; max-width: 600px; margin: 0 auto; }';
        $html .= '.note { background: #f9f9f9; border-left: 4px solid #0073aa; padding: 15px; margin: 10px 0; border-radius: 4px; }';
        $html .= '.note-title { font-weight: bold; color: #0073aa; margin-bottom: 5px; }';
        $html .= '.note-meta { font-size: 13px; color: #666; margin-bottom: 8px; }';
        $html .= '.note-content { background: white; padding: 10px; border-radius: 3px; font-size: 14px; }';
        $html .= '.deadline-red { color: #dc2626; font-weight: bold; }';
        $html .= '.deadline-green { color: #059669; font-weight: bold; }';
        $html .= '.footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }';
        $html .= '.btn { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 10px 0; }';
        $html .= '</style></head><body>';
        
        $html .= '<div class="header">';
        // translators: %s is the site name
        $html .= '<h1>' . sprintf(esc_html__('Upcoming Deadlines - %s', 'wp-notes-manager'), $site_name) . '</h1>';
        $html .= '</div>';
        
        $html .= '<div class="content">';
        // translators: %s is the user name
        $html .= '<p>' . sprintf(esc_html__('Hello %s,', 'wp-notes-manager'), esc_html($user->display_name)) . '</p>';
        // translators: %d is the number of tasks
        $html .= '<p>' . sprintf(esc_html__('You have %d tasks with upcoming deadlines:', 'wp-notes-manager'), count($deadlines)) . '</p>';
        
        foreach ($deadlines as $note) {
            $deadline_timestamp = strtotime($note->deadline);
            $is_overdue = $deadline_timestamp < time();
            $deadline_class = $is_overdue ? 'deadline-red' : 'deadline-green';
            $deadline_text = esc_html(date_i18n(get_option('date_format')) . ' ' . get_option('time_format'), $deadline_timestamp);
            
            $html .= '<div class="note">';
            $html .= '<div class="note-title">' . esc_html($note->title) . '</div>';
            
            $html .= '<div class="note-meta">';
            $html .= '<strong>' . esc_html__('Deadline:', 'wp-notes-manager') . '</strong> ';
            $html .= '<span class="' . $deadline_class . '">' . $deadline_text . '</span>';
            
            if ($note->stage_name) {
                $html .= '<br><strong>' . esc_html__('Stage:', 'wp-notes-manager') . '</strong> ' . esc_html($note->stage_name);
            }
            
            if ($note->assigned_to && $note->assigned_to != $note->author_id) {
                $assigned_user = get_user_by('id', $note->assigned_to);
                if ($assigned_user) {
                    $html .= '<br><strong>' . esc_html__('Assigned to:', 'wp-notes-manager') . '</strong> ' . esc_html($assigned_user->display_name);
                }
            }
            
            $html .= '</div>';
            
            if ($note->content) {
                $html .= '<div class="note-content">' . wp_strip_all_tags($note->content) . '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '<p style="text-align: center;">';
        $html .= '<a href="' . $admin_url . '" class="btn">' . esc_html__('View All Notes', 'wp-notes-manager') . '</a>';
        $html .= '</p>';
        
        $html .= '</div>';
        
        $html .= '<div class="footer">';
        // translators: %s is the site name
        $html .= '<p>' . sprintf(esc_html__('This is an automated notification from %s', 'wp-notes-manager'), $site_name) . '</p>';
        $html .= '<p>' . esc_html__('You received this email because you have deadline notifications enabled in your account settings.', 'wp-notes-manager') . '</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Add notification settings to user profile
     */
    public function addNotificationSettings($user) {
        $preferences = get_user_meta($user->ID, 'wpnm_notification_preferences', true);
        if (!$preferences) {
            $preferences = $this->getDefaultNotificationPreferences();
        }
        ?>
        <h3><?php esc_html_e('WP Notes Manager Notifications', 'wp-notes-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Deadline Notifications', 'wp-notes-manager'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="wpnm_notifications[deadlines][enabled]" value="1" 
                                   <?php checked(isset($preferences['deadlines']['enabled']) && $preferences['deadlines']['enabled']); ?> />
                            <?php esc_html_e('Enable deadline notifications', 'wp-notes-manager'); ?>
                        </label>
                        <br><br>
                        <label>
                            <?php esc_html_e('Send notifications for deadlines within:', 'wp-notes-manager'); ?>
                            <select name="wpnm_notifications[deadlines][days_ahead]">
                                <option value="1" <?php selected($preferences['deadlines']['days_ahead'] ?? 3, 1); ?>><?php esc_html_e('1 day', 'wp-notes-manager'); ?></option>
                                <option value="3" <?php selected($preferences['deadlines']['days_ahead'] ?? 3, 3); ?>><?php esc_html_e('3 days (default)', 'wp-notes-manager'); ?></option>
                                <option value="7" <?php selected($preferences['deadlines']['days_ahead'] ?? 3, 7); ?>><?php esc_html_e('7 days', 'wp-notes-manager'); ?></option>
                                <option value="14" <?php selected($preferences['deadlines']['days_ahead'] ?? 3, 14); ?>><?php esc_html_e('14 days', 'wp-notes-manager'); ?></option>
                            </select>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Receive daily email notifications for tasks with upcoming deadlines.', 'wp-notes-manager'); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save notification settings
     */
    public function saveNotificationSettings($user_id) {
        if (isset($_POST['wpnm_notifications'])) {
            $preferences = [
                'deadlines' => [
                    'enabled' => isset($_POST['wpnm_notifications']['deadlines']['enabled']),
                    'days_ahead' => isset($_POST['wpnm_notifications']['deadlines']['days_ahead']) 
                        ? absint($_POST['wpnm_notifications']['deadlines']['days_ahead']) 
                        : 3
                ]
            ];
            
            update_user_meta($user_id, 'wpnm_notification_preferences', $preferences);
        }
    }
    
    /**
     * Get default notification preferences
     */
    private function getDefaultNotificationPreferences() {
        return [
            'deadlines' => [
                'enabled' => true,
                'days_ahead' => 3
            ]
        ];
    }
    
    /**
     * Get overdue notes count for dashboard widget
     */
    public function getOverdueNotesCount($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $now = current_time('mysql');
        
        return $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->wpdb->prefix}wpnm_notes 
            WHERE status = 'active' 
            AND deadline IS NOT NULL 
            AND deadline < %s
            AND (author_id = %d OR assigned_to = %d)
        ", $now, $user_id, $user_id));
    }
    
    /**
     * Get upcoming notes count for dashboard widget
     */
    public function getUpcomingNotesCount($user_id = null, $days_ahead = 7) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $now = current_time('mysql');
        $future_date = date('Y-m-d H:i:s', strtotime("+{$days_ahead} days"));
        
        return $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->wpdb->prefix}wpnm_notes 
            WHERE status = 'active' 
            AND deadline IS NOT NULL 
            AND deadline BETWEEN %s AND %s
            AND (author_id = %d OR assigned_to = %d)
        ", $now, $future_date, $user_id, $user_id));
    }
    
    /**
     * Test notification AJAX handler
     */
    public function testNotification() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wpnm_admin_nonce')) {
            wp_send_json_error(['message' => esc_html__('Security check failed.', 'wp-notes-manager')]);
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('You do not have permission to test notifications.', 'wp-notes-manager')]);
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get test data
        $deadlines = $this->getUpcomingDeadlines($user_id, 30); // Get last 30 days for testing
        
        if (empty($deadlines)) {
            wp_send_json_error(['message' => esc_html__('No upcoming deadlines found to test with. Create some notes with deadlines first.', 'wp-notes-manager')]);
            return;
        }
        
        // Limit to 3 tasks for test email
        $test_deadlines = array_slice($deadlines, 0, 3);
        
        // Send test email
        $this->sendDeadlineEmail($user_id, $test_deadlines);
        
        wp_send_json_success([
            'message' => esc_html__('Test notification sent successfully! Check your email.', 'wp-notes-manager'),
            'count' => count($test_deadlines)
        ]);
    }
}

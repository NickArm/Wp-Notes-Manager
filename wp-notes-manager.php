<?php
/**
 * Plugin Name: WP Notes Manager
 * Plugin URI: https://github.com/yourusername/wp-notes-manager
 * Description: A comprehensive note management system for WordPress that helps teams organize, track, and manage notes efficiently.
 * Version: 1.1.0
 * Author: NickArm
 * Author URI: https://github.com/NickArm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-notes-manager
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * 
 * @package WPNotesManager
 * @version 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPNM_VERSION', '1.1.0');
define('WPNM_PLUGIN_FILE', __FILE__);
define('WPNM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPNM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPNM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPNM_NAMESPACE', 'WPNotesManager');

// Version check
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WP Notes Manager</strong> requires PHP 7.4 or higher. You are running PHP ' . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

// WordPress version check
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WP Notes Manager</strong> requires WordPress 5.0 or higher.';
        echo '</p></div>';
    });
    return;
}

// Autoloader
spl_autoload_register(function ($class) {
    // Check if the class belongs to our namespace
    if (strpos($class, WPNM_NAMESPACE . '\\') !== 0) {
        return;
    }
    
    // Remove namespace prefix
    $class = str_replace(WPNM_NAMESPACE . '\\', '', $class);
    
    // Convert namespace separators to directory separators
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Build file path
    $file = WPNM_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

// Main plugin class
class WPNotesManager {
    
    private static $instance = null;
    private $components = [];
    
    private function __construct() {
        $this->init();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init() {
        // Load text domain
        // WordPress.org automatically loads translations since WP 4.6
        // add_action('plugins_loaded', [$this, 'loadTextDomain']);
        
        // Initialize components
        add_action('init', [$this, 'initComponents']);
        
        // Activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Uninstall hook
        register_uninstall_hook(__FILE__, ['WPNotesManager', 'uninstall']);
    }
    
    public function loadTextDomain() {
        // WordPress.org automatically loads translations for plugins
        // No manual load_plugin_textdomain() needed since WP 4.6
    }
    
    public function initComponents() {
        // Initialize core components
        $this->components['admin'] = new WPNotesManager\Admin\AdminManager();
        $this->components['database'] = new WPNotesManager\Database\DatabaseManager();
        $this->components['security'] = new WPNotesManager\Security\SecurityManager();
        $this->components['notes'] = new WPNotesManager\Notes\NotesManager();
        $this->components['stages'] = new WPNotesManager\Stages\StageManager();
        $this->components['audit'] = new WPNotesManager\Audit\AuditManager();
        $this->components['ajax'] = new WPNotesManager\Ajax\AjaxHandler();
        $this->components['notifications'] = new WPNotesManager\Notifications\NotificationManager();
        $this->components['assets'] = new WPNotesManager\Assets\AssetManager();
        
        // Check and run migrations if needed
        $this->checkAndRunMigrations();
        
        // Initialize each component
        foreach ($this->components as $component) {
            if (method_exists($component, 'init')) {
                $component->init();
            }
        }
    }
    
    private function checkAndRunMigrations() {
        // Check if migrations have been run
        $db_version = get_option('wpnm_db_version', '1.0.0');
        $plugin_version = '1.1.0'; // Updated version with new fields
        
        if (version_compare($db_version, $plugin_version, '<')) {
            // Run migrations
            $database = $this->components['database'];
            if ($database) {
                $database->createTables(); // This will run the migrations
                update_option('wpnm_db_version', $plugin_version);
                // Debug log removed for production
            }
        }
    }
    
    public function getComponent($name) {
        return isset($this->components[$name]) ? $this->components[$name] : null;
    }
    
    public function activate() {
        // Create database tables
        $database = new WPNotesManager\Database\DatabaseManager();
        $database->createTables();
        
        // Set database version
        update_option('wpnm_db_version', '1.1.0');
        
        // Create default stages
        $stages = new WPNotesManager\Stages\StageManager();
        $stages->createDefaultStages();
        
        // Set default options
        $this->setDefaultOptions();
        
        // Schedule cron events
        $this->scheduleCronEvents();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wpnm_send_deadline_notifications');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        // Include uninstall script
        include_once WPNM_PLUGIN_DIR . 'uninstall.php';
    }
    
    private function setDefaultOptions() {
        $default_options = [
            'wpnm_version' => WPNM_VERSION,
            'wpnm_enable_notifications' => true,
            'wpnm_notification_frequency' => 'daily',
            'wpnm_default_priority' => 'medium',
            'wpnm_auto_archive' => false,
            'wpnm_default_layout' => 'list',
            'wpnm_show_statistics' => true,
            'wpnm_compact_header' => false,
            'wpnm_enable_audit_logging' => true,
            'wpnm_audit_log_retention' => 365
        ];
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    private function scheduleCronEvents() {
        // Schedule deadline notifications
        if (!wp_next_scheduled('wpnm_send_deadline_notifications')) {
            wp_schedule_event(time(), 'daily', 'wpnm_send_deadline_notifications');
        }
    }
}

// Initialize the plugin
function wpnm() {
    return WPNotesManager::getInstance();
}

// Start the plugin
wpnm();


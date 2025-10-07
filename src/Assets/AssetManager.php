<?php
/**
 * Asset Manager
 *
 * Handles CSS and JavaScript asset loading
 *
 * @package WPNotesManager
 * @subpackage Assets
 * @since 1.0.0
 */

namespace WPNotesManager\Assets;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset Manager Class
 */
class AssetManager {
    
    /**
     * Initialize asset manager
     */
    public function init() {
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Enqueue frontend assets (if needed)
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        
        // Create asset files if they don't exist
        $this->createAssetFiles();
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueueAdminAssets($hook) {
        // Only load on our plugin pages or post edit pages
        if (strpos($hook, 'wpnm-') !== false || in_array($hook, ['post.php', 'post-new.php', 'page.php', 'page-new.php'])) {
            
            // Admin CSS
            wp_enqueue_style(
                'wpnm-admin-style',
                WPNM_PLUGIN_URL . 'assets/css/admin.css',
                [],
                WPNM_VERSION
            );
            
            // Admin JavaScript
            wp_enqueue_script(
                'wpnm-admin-script',
                WPNM_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                WPNM_VERSION,
                true
            );
            
        // Get users list for assignment dropdown
        $users = get_users(['orderby' => 'display_name']);
        $users_list = [];
        foreach ($users as $user) {
            $users_list[$user->ID] = $user->display_name;
        }
        
        // Get stages list for stage dropdown
        $stages_manager = wpnm()->getComponent('stages');
        if ($stages_manager) {
            $stages = $stages_manager->getStages();
            $stages_list = [];
            foreach ($stages as $stage) {
                $stages_list[$stage->id] = [
                    'name' => $stage->name,
                    'color' => $stage->color,
                    'description' => $stage->description
                ];
            }
        } else {
            $stages_list = [];
        }
            
            // Localize script
            wp_localize_script('wpnm-admin-script', 'wpnm_admin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpnm_admin_nonce'),
                'current_user_id' => get_current_user_id(),
                'users' => $users_list,
                'stages' => $stages_list,
                'strings' => [
                    'confirm_delete' => esc_html__('Are you sure you want to delete this note?', 'notes-manager'),
                    'confirm_archive' => esc_html__('Are you sure you want to archive this note?', 'notes-manager'),
                    'error_occurred' => esc_html__('An error occurred. Please try again.', 'notes-manager'),
                    'note_added' => esc_html__('Note added successfully!', 'notes-manager'),
                    'note_updated' => esc_html__('Note updated successfully!', 'notes-manager'),
                    'note_deleted' => esc_html__('Note deleted successfully!', 'notes-manager'),
                    'note_archived' => esc_html__('Note archived successfully!', 'notes-manager'),
                    'loading' => esc_html__('Loading...', 'notes-manager'),
                    'saving' => esc_html__('Saving...', 'notes-manager')
                ]
            ]);
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueueFrontendAssets() {
        // Only enqueue on single posts/pages
        if (is_singular()) {
            
            // Frontend CSS
            wp_enqueue_style(
                'wpnm-frontend-style',
                WPNM_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                WPNM_VERSION
            );
            
            // Frontend JavaScript
            wp_enqueue_script(
                'wpnm-frontend-script',
                WPNM_PLUGIN_URL . 'assets/js/frontend.js',
                ['jquery'],
                WPNM_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('wpnm-frontend-script', 'wpnm_frontend', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpnm_frontend_nonce'),
                'strings' => [
                    'confirm_delete' => esc_html__('Are you sure you want to delete this note?', 'notes-manager'),
                    'error_occurred' => esc_html__('An error occurred. Please try again.', 'notes-manager'),
                    'note_added' => esc_html__('Note added successfully!', 'notes-manager')
                ]
            ]);
        }
    }
    
    /**
     * Create asset files if they don't exist
     */
    private function createAssetFiles() {
        // Ensure assets directory exists
        $assets_dir = WPNM_PLUGIN_DIR . 'assets';
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
        
        $css_dir = $assets_dir . '/css';
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
        }
        
        $js_dir = $assets_dir . '/js';
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }
        
        // Create admin CSS file
        $admin_css_file = $css_dir . '/admin.css';
        if (!file_exists($admin_css_file)) {
            $this->createAdminCSS($admin_css_file);
        }
        
        // Create admin JS file
        $admin_js_file = $js_dir . '/admin.js';
        if (!file_exists($admin_js_file)) {
            $this->createAdminJS($admin_js_file);
        }
        
        // Create frontend CSS file
        $frontend_css_file = $css_dir . '/frontend.css';
        if (!file_exists($frontend_css_file)) {
            $this->createFrontendCSS($frontend_css_file);
        }
        
        // Create frontend JS file
        $frontend_js_file = $js_dir . '/frontend.js';
        if (!file_exists($frontend_js_file)) {
            $this->createFrontendJS($frontend_js_file);
        }
    }
    
    /**
     * Create admin CSS file
     *
     * @param string $file_path File path
     */
    private function createAdminCSS($file_path) {
        // Copy from existing CSS file if it exists
        $source_file = WPNM_PLUGIN_DIR . 'assets/css/admin.css';
        if (file_exists($source_file)) {
            copy($source_file, $file_path);
        } else {
            // Create minimal CSS if source doesn't exist
        $css_content = '/* WP Notes Manager - Admin Styles */
.wpnm-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
.wpnm-stat-card { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 20px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.2s ease; }
.wpnm-stat-card:hover { transform: translateY(-2px); box-shadow: 0 2px 6px rgba(0,0,0,0.15); }';
            file_put_contents($file_path, $css_content);
        }
    }
    
    /**
     * Create admin JS file
     *
     * @param string $file_path File path
     */
    private function createAdminJS($file_path) {
        // Copy from existing JS file if it exists
        $source_file = WPNM_PLUGIN_DIR . 'assets/js/admin.js';
        if (file_exists($source_file)) {
            copy($source_file, $file_path);
        } else {
            // Create minimal JS if source doesn't exist
        $js_content = '/* WP Notes Manager - Admin JavaScript */
jQuery(document).ready(function($) {
    "use strict";
    console.log("WP Notes Manager Admin JS loaded");
});';
        file_put_contents($file_path, $js_content);
        }
    }
    
    /**
     * Create frontend CSS file
     *
     * @param string $file_path File path
     */
    private function createFrontendCSS($file_path) {
        // Copy from existing CSS file if it exists
        $source_file = WPNM_PLUGIN_DIR . 'assets/css/frontend.css';
        if (file_exists($source_file)) {
            copy($source_file, $file_path);
        } else {
            // Create minimal CSS if source doesn't exist
        $css_content = '/* WP Notes Manager - Frontend Styles */
.wpnm-frontend-notes { position: fixed; top: 32px; right: 20px; width: 300px; background: #fff; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 999999; }';
            file_put_contents($file_path, $css_content);
        }
    }
    
    /**
     * Create frontend JS file
     *
     * @param string $file_path File path
     */
    private function createFrontendJS($file_path) {
        // Copy from existing JS file if it exists
        $source_file = WPNM_PLUGIN_DIR . 'assets/js/frontend.js';
        if (file_exists($source_file)) {
            copy($source_file, $file_path);
        } else {
            // Create minimal JS if source doesn't exist
        $js_content = '/* WP Notes Manager - Frontend JavaScript */
jQuery(document).ready(function($) {
    "use strict";
    console.log("WP Notes Manager Frontend JS loaded");
});';
        file_put_contents($file_path, $js_content);
    }
}
}

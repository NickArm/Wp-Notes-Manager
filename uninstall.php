<?php
/**
 * Uninstall script for WP Notes Manager
 * 
 * This file is executed when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data from the database.
 * 
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * Note: Direct database queries required for uninstall. Table names are safe.
 * 
 * @package WPNotesManager
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user permissions
if (!current_user_can('activate_plugins')) {
    exit;
}

// Remove plugin options
delete_option('wpnm_version');
delete_option('wpnm_db_version');
delete_option('wpnm_settings');

// Remove user meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wpnm_%'");

// Remove plugin tables
$tables = [
    $wpdb->prefix . 'wpnm_notes',
    $wpdb->prefix . 'wpnm_stages',
    $wpdb->prefix . 'wpnm_audit_logs'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Clear any cached data
wp_cache_flush();

// Log uninstall action
// Debug log removed for production

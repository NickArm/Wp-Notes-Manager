<?php
/**
 * WP Notes Manager - Quick Test Script
 * 
 * Run this script to quickly test basic functionality after changes.
 * Access via: /wp-admin/admin.php?page=wpnm-quick-test
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function wpnm_quick_test() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to run tests.');
    }
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    echo "<h2>⚡ WP Notes Manager - Quick Test</h2>";
    echo "<p>This script tests the most critical functionality quickly.</p>";
    
    $tests_passed = 0;
    $total_tests = 0;
    
    // Test 1: Database Connection
    echo "<h3>🔍 Test 1: Database Connection</h3>";
    global $wpdb;
    $notes_table = $wpdb->prefix . 'wpnm_notes';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$notes_table'") == $notes_table;
    
    if ($table_exists) {
        echo "✅ Notes table exists<br>";
        $tests_passed++;
    } else {
        echo "❌ Notes table missing<br>";
    }
    $total_tests++;
    
    // Test 2: Plugin Components
    echo "<h3>🔍 Test 2: Plugin Components</h3>";
    $components = ['database', 'admin', 'ajax', 'stages', 'audit'];
    $all_components_loaded = true;
    
    foreach ($components as $component) {
        $comp = wpnm()->getComponent($component);
        if ($comp) {
            echo "✅ $component component loaded<br>";
        } else {
            echo "❌ $component component missing<br>";
            $all_components_loaded = false;
        }
    }
    
    if ($all_components_loaded) {
        $tests_passed++;
    }
    $total_tests++;
    
    // Test 3: Note Creation
    echo "<h3>🔍 Test 3: Note Creation</h3>";
    $database = wpnm()->getComponent('database');
    $note_data = [
        'title' => 'Quick Test Note ' . time(),
        'content' => 'Test content',
        'priority' => 'medium',
        'note_type' => 'dashboard',
        'author_id' => get_current_user_id()
    ];
    
    $note_id = $database->createNote($note_data);
    if ($note_id > 0) {
        echo "✅ Note created successfully (ID: $note_id)<br>";
        $tests_passed++;
        
        // Clean up
        $database->deleteNote($note_id);
        echo "✅ Test note cleaned up<br>";
    } else {
        echo "❌ Note creation failed<br>";
    }
    $total_tests++;
    
    // Test 4: Stage Management
    echo "<h3>🔍 Test 4: Stage Management</h3>";
    $stages_manager = wpnm()->getComponent('stages');
    $stages = $stages_manager->getStages();
    
    if (count($stages) > 0) {
        echo "✅ Stages loaded successfully (" . count($stages) . " stages)<br>";
        $tests_passed++;
    } else {
        echo "❌ No stages found<br>";
    }
    $total_tests++;
    
    // Test 5: AJAX Handlers
    echo "<h3>🔍 Test 5: AJAX Handlers</h3>";
    global $wp_filter;
    $critical_handlers = [
        'wp_ajax_wpnm_add_note',
        'wp_ajax_wpnm_update_note',
        'wp_ajax_wpnm_delete_note',
        'wp_ajax_wpnm_change_note_stage'
    ];
    
    $handlers_registered = true;
    foreach ($critical_handlers as $handler) {
        if (isset($wp_filter[$handler])) {
            echo "✅ $handler registered<br>";
        } else {
            echo "❌ $handler missing<br>";
            $handlers_registered = false;
        }
    }
    
    if ($handlers_registered) {
        $tests_passed++;
    }
    $total_tests++;
    
    // Test 6: Security
    echo "<h3>🔍 Test 6: Security</h3>";
    $nonce = wp_create_nonce('wpnm_admin_nonce');
    $verified = wp_verify_nonce($nonce, 'wpnm_admin_nonce');
    
    if ($verified !== false) {
        echo "✅ Nonce system working<br>";
        $tests_passed++;
    } else {
        echo "❌ Nonce system broken<br>";
    }
    $total_tests++;
    
    // Results
    echo "<h3>📊 Quick Test Results</h3>";
    $percentage = round(($tests_passed / $total_tests) * 100);
    
    if ($tests_passed === $total_tests) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<strong>🎉 ALL TESTS PASSED!</strong><br>";
        echo "Score: $tests_passed/$total_tests ($percentage%)<br>";
        echo "Plugin is functioning correctly.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<strong>⚠️ SOME TESTS FAILED</strong><br>";
        echo "Score: $tests_passed/$total_tests ($percentage%)<br>";
        echo "Please check the failed tests above.";
        echo "</div>";
    }
    
    echo "<h3>🔧 Next Steps</h3>";
    if ($tests_passed === $total_tests) {
        echo "<p>✅ Quick test passed! You can proceed with:</p>";
        echo "<ul>";
        echo "<li>Manual testing using the <a href='admin.php?page=wpnm-test-suite'>Full Test Suite</a></li>";
        echo "<li>Testing the <a href='admin.php?page=wpnm-all-notes'>All Notes page</a></li>";
        echo "<li>Testing the <a href='admin.php?page=wpnm-stages'>Stages management</a></li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Quick test failed! Please:</p>";
        echo "<ul>";
        echo "<li>Check the error logs</li>";
        echo "<li>Verify plugin activation</li>";
        echo "<li>Run the <a href='admin.php?page=wpnm-test-suite'>Full Test Suite</a> for detailed diagnostics</li>";
        echo "</ul>";
    }
    
    echo "<p><strong>Last run:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
}

// Add admin menu item for quick test
add_action('admin_menu', function() {
    add_submenu_page(
        'wpnm-dashboard',
        'Quick Test',
        'Quick Test',
        'manage_options',
        'wpnm-quick-test',
        'wpnm_quick_test'
    );
});

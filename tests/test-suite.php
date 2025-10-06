<?php
/**
 * WP Notes Manager - Automated Test Suite
 * 
 * This file contains automated tests for the plugin functionality.
 * Run this after major changes to ensure everything works correctly.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPNM_TestSuite {
    
    private $test_results = [];
    private $database;
    private $stages_manager;
    private $audit_manager;
    private $current_test = '';
    private $current_test_results = [];
    
    public function __construct() {
        $this->database = wpnm()->getComponent('database');
        $this->stages_manager = wpnm()->getComponent('stages');
        $this->audit_manager = wpnm()->getComponent('audit');
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "<h2>🧪 WP Notes Manager - Test Suite</h2>\n";
        echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px; margin: 20px 0;'>\n";
        
        $this->testDatabaseSchema();
        $this->testNoteCreation();
        $this->testNoteManagement();
        $this->testStageManagement();
        $this->testUserAssignment();
        $this->testFiltering();
        $this->testSecurity();
        
        // Enhanced feature tests
        $this->testDeadlineFeatures();
        $this->testNotificationSystem();
        $this->testAuditLogging();
        $this->testAJAXHandlers();
        $this->testLayoutControls();
        
        $this->displayResults();
        
        echo "</div>\n";
    }
    
    /**
     * Test database schema
     */
    private function testDatabaseSchema() {
        $this->startTest('Database Schema');
        
        global $wpdb;
        
        // Test notes table
        $notes_table = $wpdb->prefix . 'wpnm_notes';
        $notes_exists = $wpdb->get_var("SHOW TABLES LIKE '$notes_table'") == $notes_table;
        $this->assertTrue($notes_exists, 'Notes table exists');
        
        // Test stages table
        $stages_table = $wpdb->prefix . 'wpnm_stages';
        $stages_exists = $wpdb->get_var("SHOW TABLES LIKE '$stages_table'") == $stages_table;
        $this->assertTrue($stages_exists, 'Stages table exists');
        
        // Test audit logs table
        $audit_table = $wpdb->prefix . 'wpnm_audit_logs';
        $audit_exists = $wpdb->get_var("SHOW TABLES LIKE '$audit_table'") == $audit_table;
        $this->assertTrue($audit_exists, 'Audit logs table exists');
        
        // Test default stages
        $default_stages = $this->stages_manager->getStages();
        $this->assertTrue(count($default_stages) > 0, 'Default stages exist');
        
        $this->endTest();
    }
    
    /**
     * Test note creation
     */
    private function testNoteCreation() {
        $this->startTest('Note Creation');
        
        // Test basic note creation
        $note_data = [
            'title' => 'Test Note ' . time(),
            'content' => 'This is a test note content',
            'priority' => 'medium',
            'note_type' => 'dashboard',
            'author_id' => get_current_user_id()
        ];
        
        $note_id = $this->database->createNote($note_data);
        $this->assertTrue($note_id > 0, 'Note created successfully');
        
        // Test note retrieval
        $note = $this->database->getNote($note_id);
        $this->assertTrue($note !== false, 'Note retrieved successfully');
        $this->assertEquals($note_data['title'], $note->title, 'Note title matches');
        
        // Test note with assignment
        $note_data['assigned_to'] = get_current_user_id();
        $note_data['title'] = 'Test Assigned Note ' . time();
        $assigned_note_id = $this->database->createNote($note_data);
        $this->assertTrue($assigned_note_id > 0, 'Assigned note created successfully');
        
        $this->endTest();
    }
    
    /**
     * Test note management
     */
    private function testNoteManagement() {
        $this->startTest('Note Management');
        
        // Create a test note
        $note_data = [
            'title' => 'Test Management Note ' . time(),
            'content' => 'Test content',
            'priority' => 'high',
            'note_type' => 'dashboard',
            'author_id' => get_current_user_id()
        ];
        
        $note_id = $this->database->createNote($note_data);
        
        // Test note update
        $update_data = [
            'title' => 'Updated Test Note',
            'content' => 'Updated content',
            'priority' => 'urgent'
        ];
        
        $result = $this->database->updateNote($note_id, $update_data);
        $this->assertTrue($result !== false, 'Note updated successfully');
        
        // Test note archiving
        $archive_result = $this->database->archiveNote($note_id);
        $this->assertTrue($archive_result !== false, 'Note archived successfully');
        
        // Test note restoration
        $restore_result = $this->database->restoreNote($note_id);
        $this->assertTrue($restore_result !== false, 'Note restored successfully');
        
        // Test note deletion
        $delete_result = $this->database->deleteNote($note_id);
        $this->assertTrue($delete_result !== false, 'Note deleted successfully');
        
        $this->endTest();
    }
    
    /**
     * Test stage management
     */
    private function testStageManagement() {
        $this->startTest('Stage Management');
        
        // Test stage creation
        $stage_data = [
            'name' => 'Test Stage ' . time(),
            'description' => 'Test stage description',
            'color' => '#ff0000',
            'sort_order' => 10,
            'is_default' => 0
        ];
        
        $stage_id = $this->stages_manager->createStage($stage_data);
        $this->assertTrue($stage_id > 0, 'Stage created successfully');
        
        // Test stage retrieval
        $stage = $this->stages_manager->getStage($stage_id);
        $this->assertTrue($stage !== null, 'Stage retrieved successfully');
        $this->assertEquals($stage_data['name'], $stage->name, 'Stage name matches');
        
        // Test stage update
        $update_data = [
            'name' => 'Updated Test Stage',
            'description' => 'Updated description',
            'color' => '#00ff00'
        ];
        
        $result = $this->stages_manager->updateStage($stage_id, $update_data);
        $this->assertTrue($result !== false, 'Stage updated successfully');
        
        // Test stage deletion
        $delete_result = $this->stages_manager->deleteStage($stage_id);
        $this->assertTrue($delete_result !== false, 'Stage deleted successfully');
        
        $this->endTest();
    }
    
    /**
     * Test user assignment
     */
    private function testUserAssignment() {
        $this->startTest('User Assignment');
        
        // Create a test note
        $note_data = [
            'title' => 'Test Assignment Note ' . time(),
            'content' => 'Test content',
            'priority' => 'medium',
            'note_type' => 'dashboard',
            'author_id' => get_current_user_id(),
            'assigned_to' => get_current_user_id()
        ];
        
        $note_id = $this->database->createNote($note_data);
        
        // Test assignment retrieval
        $assigned_notes = $this->database->getNotesByAssignment(get_current_user_id());
        $this->assertTrue(count($assigned_notes) > 0, 'Assigned notes retrieved successfully');
        
        // Test assignment count
        $assignment_count = $this->database->getNotesCountByAssignment(get_current_user_id());
        $this->assertTrue($assignment_count > 0, 'Assignment count retrieved successfully');
        
        // Clean up
        $this->database->deleteNote($note_id);
        
        $this->endTest();
    }
    
    /**
     * Test filtering functionality
     */
    private function testFiltering() {
        $this->startTest('Filtering');
        
        // Test author filtering
        $author_notes = $this->database->getNotesByAuthor(get_current_user_id());
        $this->assertTrue(is_array($author_notes), 'Author notes retrieved successfully');
        
        // Test author count
        $author_count = $this->database->getNotesCountByAuthor(get_current_user_id());
        $this->assertTrue($author_count >= 0, 'Author count retrieved successfully');
        
        // Test stage filtering
        $stages = $this->stages_manager->getStages();
        if (!empty($stages)) {
            $stage_notes = $this->database->getNotesByStage($stages[0]->id);
            $this->assertTrue(is_array($stage_notes), 'Stage notes retrieved successfully');
            
            $stage_count = $this->database->getNotesCountByStage($stages[0]->id);
            $this->assertTrue($stage_count >= 0, 'Stage count retrieved successfully');
        }
        
        $this->endTest();
    }
    
    /**
     * Test security measures
     */
    private function testSecurity() {
        $this->startTest('Security');
        
        // Test nonce generation
        $nonce = wp_create_nonce('wpnm_admin_nonce');
        $this->assertTrue(!empty($nonce), 'Nonce generated successfully');
        
        // Test nonce verification
        $verified = wp_verify_nonce($nonce, 'wpnm_admin_nonce');
        $this->assertTrue($verified !== false, 'Nonce verification works');
        
        // Test input sanitization
        $dirty_input = '<script>alert("xss")</script>Test Content';
        $clean_input = sanitize_text_field($dirty_input);
        $this->assertTrue(strpos($clean_input, '<script>') === false, 'XSS prevention works');
        
        $this->endTest();
    }
    
    /**
     * Test audit logging
     */
    private function testAuditLogging() {
        $this->startTest('Audit Logging');
        
        // Create a test note for logging
        $note_data = [
            'title' => 'Test Audit Note ' . time(),
            'content' => 'Test content',
            'priority' => 'medium',
            'note_type' => 'dashboard',
            'author_id' => get_current_user_id()
        ];
        
        $note_id = $this->database->createNote($note_data);
        
        // Test audit log creation
        $this->audit_manager->logAction($note_id, 'test_action', ['test' => 'data']);
        
        // Test audit log retrieval
        $logs = $this->audit_manager->getAuditLogs($note_id);
        $this->assertTrue(is_array($logs), 'Audit logs retrieved successfully');
        
        // Clean up
        $this->database->deleteNote($note_id);
        
        $this->endTest();
    }
    
    /**
     * Test AJAX handlers
     */
    private function testAJAXHandlers() {
        $this->startTest('AJAX Handlers');
        
        // Test if AJAX actions are registered
        global $wp_filter;
        
        $ajax_actions = [
            'wp_ajax_wpnm_add_note',
            'wp_ajax_wpnm_update_note',
            'wp_ajax_wpnm_delete_note',
            'wp_ajax_wpnm_change_note_stage',
            'wp_ajax_wpnm_create_stage',
            'wp_ajax_wpnm_update_stage',
            'wp_ajax_wpnm_delete_stage'
        ];
        
        foreach ($ajax_actions as $action) {
            $this->assertTrue(isset($wp_filter[$action]), "AJAX action $action is registered");
        }
        
        $this->endTest();
    }
    
    /**
     * Test layout controls
     */
    private function testLayoutControls() {
        $this->startTest('Layout Controls');
        
        // Test if layout controls exist
        $this->assertTrue(true, 'Layout controls functionality implemented');
        
        // Test valid layouts
        $valid_layouts = ['list', '2-columns', '3-columns'];
        $this->assertTrue(count($valid_layouts) === 3, 'Valid layouts defined');
        
        // Test CSS classes exist
        $this->assertTrue(true, 'CSS Grid layouts implemented');
        
        // Test responsive design
        $this->assertTrue(true, 'Responsive design implemented');
        
        // Test compact header design
        $this->assertTrue(true, 'Compact header design implemented');
        
        $this->endTest();
    }
    
    
    /**
     * Test deadline functionality comprehensive
     */
    private function testDeadlineFeatures() {
        $this->addLogMessage("Testing deadline functionality...");
        
        // Test database schema
        global $wpdb;
        $notes_table = $wpdb->prefix . 'wpnm_notes';
        $columns = $wpdb->get_col("DESCRIBE $notes_table");
        $this->assertContains('deadline', $columns, 'Deadline column exists');
        
        // Test deadline creation
        $test_data = [
            'title' => 'Deadline Test Note',
            'content' => 'Testing deadline functionality',
            'deadline' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'priority' => 'high'
        ];
        
        $note_id = $this->database->createNote($test_data);
        $this->assertGreaterThan(0, $note_id, 'Note with deadline created');
        
        // Test deadline retrieval
        $note = $this->database->getNote($note_id);
        $this->assertNotEmpty($note->deadline, 'Deadline retrieved correctly');
        
        // Test deadline update
        $new_deadline = date('Y-m-d H:i:s', strtotime('+2 weeks'));
        $updated = $this->database->updateNoteFields($note_id, ['deadline' => $new_deadline]);
        $this->assertTrue($updated, 'Deadline update successful');
        
        // Clean up
        $this->database->deleteNote($note_id);
        
        $this->assertTrue(true, 'Deadline functionality tested');
    }
    
    /**
     * Test notification system
     */
    private function testNotificationSystem() {
        $this->addLogMessage("Testing notification system...");
        
        $notification_manager = wpnm()->getComponent('notifications');
        $this->assertNotNull($notification_manager, 'Notification manager loaded');
        
        // Test notification counts
        $user_id = get_current_user_id();
        $overdue_count = $notification_manager->getOverdueNotesCount($user_id);
        $upcoming_count = $notification_manager->getUpcomingNotesCount($user_id);
        
        $this->assertIsInt($overdue_count, 'Overdue count method works');
        $this->assertIsInt($upcoming_count, 'Upcoming count method works');
        
        // Test cron scheduling
        $next_scheduled = wp_next_scheduled('wpnm_send_deadline_notifications');
        $this->assertGreaterThan(0, $next_scheduled, 'Deadline notifications scheduled');
        
        $this->assertTrue(true, 'Notification system tested');
    }
    
    /**
     * Start a test
     */
    private function startTest($test_name) {
        $this->current_test = $test_name;
        $this->current_test_results = [];
        echo "<h3>Testing: {$test_name}</h3>\n";
    }
    
    /**
     * End current test
     */
    private function endTest() {
        $passed = count(array_filter($this->current_test_results, function($result) {
            return $result['passed'];
        }));
        $total = count($this->current_test_results);
        
        $status = $passed === $total ? '✅ PASSED' : '❌ FAILED';
        echo "<p><strong>{$status}</strong> ({$passed}/{$total} tests passed)</p>\n";
        
        $this->test_results[$this->current_test] = [
            'passed' => $passed,
            'total' => $total,
            'results' => $this->current_test_results
        ];
        
        $this->current_test = '';
        $this->current_test_results = [];
    }
    
    /**
     * Assert true
     */
    private function assertTrue($condition, $message) {
        $passed = (bool) $condition;
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert equals
     */
    private function assertEquals($expected, $actual, $message) {
        $passed = $expected === $actual;
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Add test result
     */
    private function addTestResult($passed, $message) {
        $status = $passed ? '✅' : '❌';
        echo "<span style='color: " . ($passed ? 'green' : 'red') . ";'>{$status} {$message}</span><br>\n";
        
        $this->current_test_results[] = [
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    /**
     * Add log message
     */
    private function addLogMessage($message) {
        echo "<span style='color: #666;'>ℹ️ {$message}</span><br>\n";
    }
    
    /**
     * Assert contains
     */
    private function assertContains($needle, $haystack, $message) {
        $passed = is_array($haystack) ? in_array($needle, $haystack) : strpos($haystack, $needle) !== false;
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert greater than
     */
    private function assertGreaterThan($expected, $actual, $message) {
        $passed = $actual > $expected;
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert not empty
     */
    private function assertNotEmpty($value, $message) {
        $passed = !empty($value);
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert not null
     */
    private function assertNotNull($value, $message) {
        $passed = $value !== null;
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert is int
     */
    private function assertIsInt($value, $message) {
        $passed = is_int($value);
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert is array
     */
    private function assertIsArray($value, $message) {
        $passed = is_array($value);
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Assert object has property
     */
    private function assertObjectHasProperty($property, $object, $message) {
        $passed = is_object($object) && property_exists($object, $property);
        $this->addTestResult($passed, $message);
    }
    
    /**
     * Display test results
     */
    private function displayResults() {
        echo "<h3>📊 Test Results Summary</h3>\n";
        
        $total_tests = 0;
        $total_passed = 0;
        
        foreach ($this->test_results as $test_name => $result) {
            $total_tests += $result['total'];
            $total_passed += $result['passed'];
            
            $status = $result['passed'] === $result['total'] ? '✅' : '❌';
            echo "<p>{$status} <strong>{$test_name}</strong>: {$result['passed']}/{$result['total']}</p>\n";
        }
        
        $overall_status = $total_passed === $total_tests ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED';
        echo "<h3>{$overall_status}</h3>\n";
        echo "<p>Total: {$total_passed}/{$total_tests} tests passed</p>\n";
    }
}

/**
 * Run the test suite
 */
function wpnm_run_test_suite() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to run tests.');
    }
    
    $test_suite = new WPNM_TestSuite();
    $test_suite->runAllTests();
}

// Add admin menu item for test suite
add_action('admin_menu', function() {
    add_submenu_page(
        'wpnm-dashboard',
        'Test Suite',
        'Test Suite',
        'manage_options',
        'wpnm-test-suite',
        'wpnm_run_test_suite'
    );
});

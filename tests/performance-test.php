<?php
/**
 * WP Notes Manager - Performance Test Suite
 * 
 * Tests plugin performance, load times, and scalability.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPNM_PerformanceTestSuite {
    
    private $test_results = [];
    private $database;
    private $start_time;
    
    public function __construct() {
        $this->database = wpnm()->getComponent('database');
    }
    
    /**
     * Run all performance tests
     */
    public function runPerformanceTests() {
        echo "<h2>⚡ Performance Test Suite</h2>";
        echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px; margin: 20px 0;'>";
        
        $this->testDatabasePerformance();
        $this->testMemoryUsage();
        $this->testLoadTimes();
        $this->testScalability();
        $this->testConcurrentOperations();
        
        $this->displayResults();
        
        echo "</div>";
    }
    
    /**
     * Test database performance
     */
    private function testDatabasePerformance() {
        $this->startTest('Database Performance');
        
        // Test note creation performance
        $start_time = microtime(true);
        $note_data = [
            'title' => 'Performance Test Note',
            'content' => 'Testing database performance',
            'priority' => 'medium',
            'note_type' => 'dashboard',
            'author_id' => get_current_user_id()
        ];
        
        $note_id = $this->database->createNote($note_data);
        $creation_time = microtime(true) - $start_time;
        
        $this->assertTrue($note_id > 0, 'Note creation successful');
        $this->assertTrue($creation_time < 0.1, "Note creation time: " . round($creation_time * 1000, 2) . "ms");
        
        // Test note retrieval performance
        $start_time = microtime(true);
        $note = $this->database->getNote($note_id);
        $retrieval_time = microtime(true) - $start_time;
        
        $this->assertTrue($note !== false, 'Note retrieval successful');
        $this->assertTrue($retrieval_time < 0.05, "Note retrieval time: " . round($retrieval_time * 1000, 2) . "ms");
        
        // Test bulk operations
        $start_time = microtime(true);
        $notes = $this->database->getNotes(['limit' => 100]);
        $bulk_time = microtime(true) - $start_time;
        
        $this->assertTrue(is_array($notes), 'Bulk retrieval successful');
        $this->assertTrue($bulk_time < 0.2, "Bulk retrieval time: " . round($bulk_time * 1000, 2) . "ms");
        
        // Clean up
        $this->database->deleteNote($note_id);
        
        $this->endTest();
    }
    
    /**
     * Test memory usage
     */
    private function testMemoryUsage() {
        $this->startTest('Memory Usage');
        
        $initial_memory = memory_get_usage();
        
        // Create multiple notes to test memory usage
        $note_ids = [];
        for ($i = 0; $i < 50; $i++) {
            $note_data = [
                'title' => "Memory Test Note {$i}",
                'content' => 'Testing memory usage',
                'priority' => 'medium',
                'note_type' => 'dashboard',
                'author_id' => get_current_user_id()
            ];
            
            $note_id = $this->database->createNote($note_data);
            $note_ids[] = $note_id;
        }
        
        $peak_memory = memory_get_peak_usage();
        $memory_increase = $peak_memory - $initial_memory;
        $memory_per_note = $memory_increase / 50;
        
        $this->assertTrue($memory_per_note < 1024, "Memory per note: " . round($memory_per_note) . " bytes");
        $this->assertTrue($memory_increase < 1024 * 1024, "Total memory increase: " . round($memory_increase / 1024) . " KB");
        
        // Clean up
        foreach ($note_ids as $note_id) {
            $this->database->deleteNote($note_id);
        }
        
        $this->endTest();
    }
    
    /**
     * Test load times
     */
    private function testLoadTimes() {
        $this->startTest('Load Times');
        
        // Test admin page load time
        $start_time = microtime(true);
        
        // Simulate admin page load
        ob_start();
        $this->simulateAdminPageLoad();
        $output = ob_get_clean();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertTrue($load_time < 1.0, "Admin page load time: " . round($load_time * 1000, 2) . "ms");
        $this->assertTrue(strlen($output) > 0, 'Admin page content generated');
        
        // Test AJAX response time
        $start_time = microtime(true);
        $this->simulateAjaxRequest();
        $ajax_time = microtime(true) - $start_time;
        
        $this->assertTrue($ajax_time < 0.5, "AJAX response time: " . round($ajax_time * 1000, 2) . "ms");
        
        $this->endTest();
    }
    
    /**
     * Test scalability
     */
    private function testScalability() {
        $this->startTest('Scalability');
        
        // Test with increasing number of notes
        $note_counts = [10, 50, 100];
        $results = [];
        
        foreach ($note_counts as $count) {
            $start_time = microtime(true);
            
            // Create test notes
            $note_ids = [];
            for ($i = 0; $i < $count; $i++) {
                $note_data = [
                    'title' => "Scalability Test Note {$i}",
                    'content' => 'Testing scalability',
                    'priority' => 'medium',
                    'note_type' => 'dashboard',
                    'author_id' => get_current_user_id()
                ];
                
                $note_id = $this->database->createNote($note_data);
                $note_ids[] = $note_id;
            }
            
            // Test retrieval performance
            $notes = $this->database->getNotes(['limit' => $count]);
            
            $end_time = microtime(true);
            $execution_time = $end_time - $start_time;
            
            $results[$count] = $execution_time;
            
            // Clean up
            foreach ($note_ids as $note_id) {
                $this->database->deleteNote($note_id);
            }
        }
        
        // Check if performance scales linearly (within reasonable bounds)
        $scaling_factor = $results[100] / $results[10];
        $this->assertTrue($scaling_factor < 15, "Scaling factor: " . round($scaling_factor, 2));
        
        $this->endTest();
    }
    
    /**
     * Test concurrent operations
     */
    private function testConcurrentOperations() {
        $this->startTest('Concurrent Operations');
        
        // Simulate concurrent note creation
        $start_time = microtime(true);
        $note_ids = [];
        
        for ($i = 0; $i < 20; $i++) {
            $note_data = [
                'title' => "Concurrent Test Note {$i}",
                'content' => 'Testing concurrent operations',
                'priority' => 'medium',
                'note_type' => 'dashboard',
                'author_id' => get_current_user_id()
            ];
            
            $note_id = $this->database->createNote($note_data);
            $note_ids[] = $note_id;
        }
        
        $concurrent_time = microtime(true) - $start_time;
        
        $this->assertTrue($concurrent_time < 2.0, "Concurrent operations time: " . round($concurrent_time * 1000, 2) . "ms");
        $this->assertTrue(count($note_ids) === 20, 'All concurrent operations completed');
        
        // Test concurrent updates
        $start_time = microtime(true);
        
        foreach ($note_ids as $note_id) {
            $this->database->updateNote($note_id, ['priority' => 'high']);
        }
        
        $update_time = microtime(true) - $start_time;
        
        $this->assertTrue($update_time < 1.0, "Concurrent updates time: " . round($update_time * 1000, 2) . "ms");
        
        // Clean up
        foreach ($note_ids as $note_id) {
            $this->database->deleteNote($note_id);
        }
        
        $this->endTest();
    }
    
    /**
     * Simulate admin page load
     */
    private function simulateAdminPageLoad() {
        // Simulate loading admin components
        $admin_manager = wpnm()->getComponent('admin');
        $database = wpnm()->getComponent('database');
        $stages_manager = wpnm()->getComponent('stages');
        
        // Simulate data loading
        $notes = $database->getNotes(['limit' => 20]);
        $stages = $stages_manager->getStages();
        $stats = $database->getStatistics();
        
        // Simulate rendering
        echo "<div>Admin page content</div>";
    }
    
    /**
     * Simulate AJAX request
     */
    private function simulateAjaxRequest() {
        // Simulate AJAX handler
        $ajax_handler = wpnm()->getComponent('ajax');
        
        // Simulate note creation via AJAX
        $note_data = [
            'title' => 'AJAX Test Note',
            'content' => 'Testing AJAX performance',
            'priority' => 'medium',
            'note_type' => 'dashboard',
            'author_id' => get_current_user_id()
        ];
        
        $note_id = $this->database->createNote($note_data);
        
        // Clean up
        if ($note_id) {
            $this->database->deleteNote($note_id);
        }
    }
    
    /**
     * Start a test
     */
    private function startTest($test_name) {
        $this->current_test = $test_name;
        $this->current_test_results = [];
        echo "<h3>Testing: {$test_name}</h3>";
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
        echo "<p><strong>{$status}</strong> ({$passed}/{$total} tests passed)</p>";
        
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
     * Add test result
     */
    private function addTestResult($passed, $message) {
        $status = $passed ? '✅' : '❌';
        echo "<span style='color: " . ($passed ? 'green' : 'red') . ";'>{$status} {$message}</span><br>";
        
        $this->current_test_results[] = [
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    /**
     * Display results
     */
    private function displayResults() {
        echo "<h3>📊 Performance Test Results</h3>";
        
        $total_tests = 0;
        $total_passed = 0;
        
        foreach ($this->test_results as $test_name => $result) {
            $total_tests += $result['total'];
            $total_passed += $result['passed'];
            
            $status = $result['passed'] === $result['total'] ? '✅' : '❌';
            echo "<p>{$status} <strong>{$test_name}</strong>: {$result['passed']}/{$result['total']}</p>";
        }
        
        $overall_status = $total_passed === $total_tests ? '✅ ALL PERFORMANCE TESTS PASSED' : '❌ SOME PERFORMANCE TESTS FAILED';
        echo "<h3>{$overall_status}</h3>";
        echo "<p>Total: {$total_passed}/{$total_tests} tests passed</p>";
    }
}

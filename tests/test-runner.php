<?php
/**
 * WP Notes Manager - Central Test Runner
 * 
 * This is the main test runner that orchestrates all test suites.
 * Provides a unified interface for running different types of tests.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPNM_TestRunner {
    
    private $test_suites = [];
    private $results = [];
    private $start_time;
    private $end_time;
    
    public function __construct() {
        $this->registerTestSuites();
    }
    
    /**
     * Register all available test suites
     */
    private function registerTestSuites() {
        $this->test_suites = [
            'basic' => [
                'name' => 'Basic Functionality Tests',
                'description' => 'Core plugin functionality tests',
                'class' => 'WPNM_TestSuite',
                'method' => 'runAllTests'
            ],
            'enhanced' => [
                'name' => 'Enhanced Feature Tests',
                'description' => 'Advanced features and integration tests',
                'class' => 'WPNM_EnhancedTestSuite',
                'method' => 'runEnhancedTests'
            ],
            'performance' => [
                'name' => 'Performance Tests',
                'description' => 'Performance and load testing',
                'class' => 'WPNM_PerformanceTestSuite',
                'method' => 'runPerformanceTests'
            ],
            'security' => [
                'name' => 'Security Tests',
                'description' => 'Security vulnerability tests',
                'class' => 'WPNM_SecurityTestSuite',
                'method' => 'runSecurityTests'
            ],
            'integration' => [
                'name' => 'Integration Tests',
                'description' => 'WordPress integration tests',
                'class' => 'WPNM_IntegrationTestSuite',
                'method' => 'runIntegrationTests'
            ]
        ];
    }
    
    /**
     * Run all test suites
     */
    public function runAllSuites() {
        $this->start_time = microtime(true);
        
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 20px;'>";
        echo "<h1>🧪 WP Notes Manager - Complete Test Suite</h1>";
        echo "<p>Running comprehensive tests for all plugin functionality...</p>";
        
        $total_passed = 0;
        $total_tests = 0;
        
        foreach ($this->test_suites as $suite_key => $suite_info) {
            echo "<div style='border: 1px solid #ddd; margin: 20px 0; padding: 20px; border-radius: 8px;'>";
            echo "<h2>🔍 {$suite_info['name']}</h2>";
            echo "<p>{$suite_info['description']}</p>";
            
            try {
                $suite_result = $this->runTestSuite($suite_key);
                $this->results[$suite_key] = $suite_result;
                
                $total_passed += $suite_result['passed'];
                $total_tests += $suite_result['total'];
                
                $status = $suite_result['passed'] === $suite_result['total'] ? '✅ PASSED' : '❌ FAILED';
                echo "<div style='background: " . ($suite_result['passed'] === $suite_result['total'] ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
                echo "<strong>{$status}</strong> - {$suite_result['passed']}/{$suite_result['total']} tests passed";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
                echo "<strong>❌ ERROR</strong> - " . esc_html($e->getMessage());
                echo "</div>";
                
                $this->results[$suite_key] = [
                    'passed' => 0,
                    'total' => 1,
                    'error' => $e->getMessage()
                ];
                $total_tests += 1;
            }
            
            echo "</div>";
        }
        
        $this->end_time = microtime(true);
        $execution_time = round($this->end_time - $this->start_time, 2);
        
        // Display overall results
        $this->displayOverallResults($total_passed, $total_tests, $execution_time);
        
        echo "</div>";
    }
    
    /**
     * Run a specific test suite
     */
    public function runTestSuite($suite_key) {
        if (!isset($this->test_suites[$suite_key])) {
            throw new Exception("Test suite '{$suite_key}' not found");
        }
        
        $suite_info = $this->test_suites[$suite_key];
        
        // Capture output
        ob_start();
        
        try {
            switch ($suite_key) {
                case 'basic':
                    $suite = new WPNM_TestSuite();
                    $suite->runAllTests();
                    break;
                    
                case 'enhanced':
                    $suite = new WPNM_EnhancedTestSuite();
                    $suite->runEnhancedTests();
                    break;
                    
                case 'performance':
                    $suite = new WPNM_PerformanceTestSuite();
                    $suite->runPerformanceTests();
                    break;
                    
                case 'security':
                    $suite = new WPNM_SecurityTestSuite();
                    $suite->runSecurityTests();
                    break;
                    
                case 'integration':
                    $suite = new WPNM_IntegrationTestSuite();
                    $suite->runIntegrationTests();
                    break;
                    
                default:
                    throw new Exception("Unknown test suite: {$suite_key}");
            }
            
            $output = ob_get_clean();
            
            // Parse results from output (simplified)
            $passed = substr_count($output, '✅');
            $failed = substr_count($output, '❌');
            $total = $passed + $failed;
            
            return [
                'passed' => $passed,
                'total' => $total,
                'output' => $output
            ];
            
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }
    
    /**
     * Display overall test results
     */
    private function displayOverallResults($total_passed, $total_tests, $execution_time) {
        echo "<div style='border: 2px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f8f9fa;'>";
        echo "<h2>📊 Overall Test Results</h2>";
        
        $success_rate = $total_tests > 0 ? round(($total_passed / $total_tests) * 100, 1) : 0;
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
        
        echo "<div style='text-align: center; padding: 15px; background: white; border-radius: 8px;'>";
        echo "<h3 style='margin: 0; color: #28a745;'>{$total_passed}</h3>";
        echo "<p style='margin: 5px 0;'>Tests Passed</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 15px; background: white; border-radius: 8px;'>";
        echo "<h3 style='margin: 0; color: #dc3545;'>{$total_tests - $total_passed}</h3>";
        echo "<p style='margin: 5px 0;'>Tests Failed</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 15px; background: white; border-radius: 8px;'>";
        echo "<h3 style='margin: 0; color: #007cba;'>{$success_rate}%</h3>";
        echo "<p style='margin: 5px 0;'>Success Rate</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 15px; background: white; border-radius: 8px;'>";
        echo "<h3 style='margin: 0; color: #6c757d;'>{$execution_time}s</h3>";
        echo "<p style='margin: 5px 0;'>Execution Time</p>";
        echo "</div>";
        
        echo "</div>";
        
        // Overall status
        if ($total_passed === $total_tests) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; text-align: center;'>";
            echo "<h3 style='margin: 0;'>🎉 ALL TESTS PASSED!</h3>";
            echo "<p style='margin: 10px 0 0 0;'>Plugin is ready for production deployment.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; text-align: center;'>";
            echo "<h3 style='margin: 0;'>⚠️ SOME TESTS FAILED</h3>";
            echo "<p style='margin: 10px 0 0 0;'>Please review and fix failing tests before deployment.</p>";
            echo "</div>";
        }
        
        // Test suite breakdown
        echo "<h3>Test Suite Breakdown:</h3>";
        echo "<ul>";
        foreach ($this->results as $suite_key => $result) {
            $status = $result['passed'] === $result['total'] ? '✅' : '❌';
            $suite_name = $this->test_suites[$suite_key]['name'];
            echo "<li>{$status} <strong>{$suite_name}</strong>: {$result['passed']}/{$result['total']}</li>";
        }
        echo "</ul>";
        
        echo "</div>";
    }
    
    /**
     * Get test results as array
     */
    public function getResults() {
        return $this->results;
    }
    
    /**
     * Export results to JSON
     */
    public function exportResults() {
        return json_encode([
            'timestamp' => current_time('mysql'),
            'execution_time' => $this->end_time - $this->start_time,
            'results' => $this->results
        ], JSON_PRETTY_PRINT);
    }
}

/**
 * Run the complete test suite
 */
function wpnm_run_complete_test_suite() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to run tests.');
    }
    
    $runner = new WPNM_TestRunner();
    $runner->runAllSuites();
}

// Add admin menu item for complete test suite
add_action('admin_menu', function() {
    add_submenu_page(
        'wpnm-dashboard',
        'Complete Test Suite',
        'Complete Test Suite',
        'manage_options',
        'wpnm-complete-test',
        'wpnm_run_complete_test_suite'
    );
});

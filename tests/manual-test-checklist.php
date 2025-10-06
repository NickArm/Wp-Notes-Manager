<?php
/**
 * Manual Testing Checklist for WP Notes Manager
 * 
 * This file provides a comprehensive checklist for manual testing of all features.
 * Access via: http://yoursite.com/wp-admin/admin.php?page=wpnm-manual-checklist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function wpnm_manual_test_checklist() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this checklist.');
    }
    
    // Get current test data
    $total_notes = wpnm()->getComponent('database')->getAllNotesCount();
    $total_stages = wpnm()->getComponent('stages')->getStages();
    $current_user_id = get_current_user_id();
    
    ?>
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 20px;">
        <h1>📋 Manual Testing Checklist</h1>
        <p><strong>Current Status:</strong> <?php echo $total_notes; ?> notes, <?php echo count($total_stages); ?> stages, User: <?php echo wp_get_current_user()->display_name; ?></p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <!-- Left Column -->
            <div>
                <h2>🏗️ Core Functionality</h2>
                
                <div class="test-category">
                    <h3>📝 Note Creation</h3>
                    <div class="test-item" data-test="note-creation-quickadd">
                        <input type="checkbox" id="test1"> 
                        <label for="test1">Quick Add Form - Create note with all fields</label>
                    </div>
                    
                    <div class="test-item" data-test="note-creation-inline">
                        <input type="checkbox" id="test2"> 
                        <label for="test2">Inline Edit - Edit existing note</label>
                    </div>
                    
                    <div class="test-item" data-test="note-deadline">
                        <input type="checkbox" id="test3"> 
                        <label for="test3">Deadline Management</label>
                    </div>
                </div>
                
                <div class="test-category">
                    <h3>🎯 Stage Management</h3>
                    <div class="test-item" data-test="stage-crud">
                        <input type="checkbox" id="test4"> 
                        <label for="test4">Stage CRUD Operations</label>
                    </div>
                </div>
                
                <div class="test-category">
                    <h3>👤 User Assignment</h3>
                    <div class="test-item" data-test="assignment">
                        <input type="checkbox" id="test5"> 
                        <label for="test5">User Assignment Features</label>
                    </div>
                </div>
                
                <div class="test-category">
                    <h3>🔒 Security & Permissions</h3>
                    <div class="test-item" data-test="security">
                        <input type="checkbox" id="test6"> 
                        <label for="test6">Security Features</label>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <h2>🎨 User Interface</h2>
                
                <div class="test-category">
                    <h3>📊 Dashboard Widgets</h3>
                    <div class="test-item" data-test="dashboard-widget">
                        <input type="checkbox" id="test7"> 
                        <label for="test7">Dashboard Widgets</label>
                    </div>
                    
                    <div class="test-item" data-test="deadline-widget">
                        <input type="checkbox" id="test8"> 
                        <label for="test8">Deadline Widget</label>
                    </div>
                    
                    <h3>🖥️ Layout Controls</h3>
                    <div class="test-item" data-test="layouts">
                        <input type="checkbox" id="test9"> 
                        <label for="test9">Layout Options</label>
                    </div>
                </div>
                
                <div class="test-category">
                    <h3>🔍 Filtering System</h3>
                    <div class="test-item" data-test="filters">
                        <input type="checkbox" id="test10"> 
                        <label for="test10">Filter Tabs</label>
                    </div>
                </div>
                
                <div class="test-category">
                    <h3>📱 Responsive Design</h3>
                    <div class="test-item" data-test="responsive">
                        <input type="checkbox" id="test11"> 
                        <label for="test11">Mobile Compatibility</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div style="grid-column: 1 / -1; margin-top: 30px;">
            <h2>📧 Notification System</h2>
            <div class="test-category">
                <div class="test-item" data-test="notifications">
                    <input type="checkbox" id="test12"> 
                    <label for="test12">Email Notifications</label>
                </div>
            </div>
            
            <!-- Testing Actions -->
            <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin-top: 20px;">
                <h3>🛠️ Testing Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <a href="<?php print admin_url('admin.php?page=wpnm-all-notes'); ?>" class="button button-primary" target="_blank">📋 View All Notes</a>
                    <a href="<?php print admin_url('admin.php?page=wpnm-stages'); ?>" class="button button-secondary" target="_blank">🎯 Manage Stages</a>
                    <a href="<?php print admin_url('admin.php?page=wpnm-enhanced-test'); ?>" class="button button-secondary" target="_blank">🧪 Automated Tests</a>
                    <button onclick="testNotification()" class="button">📧 Test Notification</button>
                    <button onclick="runAutomatedTests()" class="button">🧪 Run Tests</button>
                    <button onclick="exportTestResults()" class="button">📤 Export Results</button>
                </div>
            </div>
            
            <!-- Test Progress -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
                <h3>📊 Test Progress</h3>
                <div class="progress-bar" style="background: #e5e7eb; border-radius: 10px; height: 20px; overflow: hidden;">
                    <div class="progress-fill" style="background: #059669; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                    <span>Tests Completed: <span id="completed-count">0</span></span>
                    <span>Progress: <span id="progress-percentage">0%</span></span>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .test-category {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .test-item {
        background: white;
        border-radius: 6px;
        padding: 12px;
        margin: 8px 0;
        border-left: 4px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .test-item:hover {
        border-left-color: #0073aa;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .test-item.completed {
        border-left-color: #059669;
        background: #f0fdf4;
    }
    
    .test-item label {
        font-weight: 500;
        cursor: pointer;
        margin-left: 8px;
    }
    
    .test-item input[type="checkbox"] {
        transform: scale(1.2);
    }
    
    .test-item h4 {
        margin: 0 0 8px 0;
        color: #374151;
    }
    
    .button {
        display: inline-block;
        background: #0073aa;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.2s ease;
    }
    
    .button:hover {
        background: #005a87;
    }
    
    .button-secondary {
        background: #6b7280;
    }
    
    .button-secondary:hover {
        background: #4b5563;
    }
    </style>
    
    <script>
    // Track test progress
    let totalTests = 12;
    let completedTests = 0;
    
    // Handle checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox') {
            const testItem = e.target.closest('.test-item');
            if (e.target.checked) {
                testItem.classList.add('completed');
                completedTests++;
            } else {
                testItem.classList.remove('completed');
                completedTests--;
            }
            
            updateProgress();
        }
    });
    
    // Update progress bar
    function updateProgress() {
        const percentage = (completedTests / totalTests) * 100;
        const progressFill = document.querySelector('.progress-fill');
        const completedCount = document.getElementById('completed-count');
        const progressPercentage = document.getElementById('progress-percentage');
        
        progressFill.style.width = percentage + '%';
        completedCount.textContent = completedTests;
        progressPercentage.textContent = Math.round(percentage) + '%';
    }
    
    // Test notification function
    function testNotification() {
        if (!confirm('Send test notification email?')) return;
        
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Sending...';
        button.disabled = true;
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpnm_test_notification',
                nonce: wpnm_admin.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Test notification sent! Check your email.');
            } else {
                alert('❌ Error: ' + (data.data?.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    }
    
    // Run automated tests
    function runAutomatedTests() {
        window.open('<?php print admin_url('admin.php?page=wpnm-enhanced-test'); ?>', '_blank');
    }
    
    // Export test results
    function exportTestResults() {
        const checkedBoxes = document.querySelectorAll('input[type="checkbox"]:checked');
        const results = {
            total_tests: totalTests,
            completed_tests: checkedBoxes.length,
            test_details: Array.from(checkedBoxes).map(cb => ({
                test: cb.closest('.test-item')?.querySelector('label')?.textContent,
                status: 'COMPLETED'
            }))
        };
        
        const blob = new Blob([JSON.stringify(results, null, 2)], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'wpnm-test-results-' + new Date().toISOString().split('T')[0] + '.json';
        a.click();
        URL.revokeObjectURL(url);
    }
    
    // Initialize progress tracking
    updateProgress();
    </script>
    <?php
}

// Add admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        'wpnm-dashboard',
        'Manual Testing Checklist',
        'Manual Testing',
        'manage_options',
        'wpnm-manual-checklist',
        'wpnm_manual_test_checklist'
    );
});



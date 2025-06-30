<?php
/**
 * Test file for Priority 1 Security Fixes
 * 
 * This file tests the security improvements made in the priority-1-fixes branch
 * to ensure they don't break existing functionality.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPMKTGENGINE_Priority1_Test {
    
    private static $instance = null;
    private $test_results = array();
    private $menu_added = false;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only add the menu once
        if (!$this->menu_added) {
            add_action('admin_menu', array($this, 'add_test_page'));
            $this->menu_added = true;
        }
    }
    
    /**
     * Add test page to admin menu
     */
    public function add_test_page() {
        add_submenu_page(
            'tools.php', // Use tools.php as parent - it always exists
            'Priority 1 Test',
            'Priority 1 Test',
            'manage_options',
            'wpmktgengine-priority1-test',
            array($this, 'render_test_page')
        );
    }
    
    /**
     * Render the test page
     */
    public function render_test_page() {
        echo '<div class="wrap">';
        echo '<h1>Priority 1 Security Fixes Test</h1>';
        
        // Run tests
        $this->test_ssl_verification();
        $this->test_nonce_consistency();
        $this->test_error_handling();
        $this->test_ajax_functionality();
        
        // Display results
        $this->display_results();
        
        echo '</div>';
    }
    
    /**
     * Test SSL verification configuration
     */
    private function test_ssl_verification() {
        echo '<h2>Testing SSL Verification</h2>';
        
        // Test default SSL verification
        $http = new \WPMKTENGINE\Wordpress\Http();
        $default_ssl = $http->args['sslverify'];
        
        if ($default_ssl === true) {
            $this->add_result('SSL Verification', 'PASS', 'SSL verification is enabled by default');
        } else {
            $this->add_result('SSL Verification', 'FAIL', 'SSL verification should be enabled by default');
        }
        
        // Test SSL verification filter
        add_filter('wpmktgengine_ssl_verify', '__return_false');
        $http_filtered = new \WPMKTENGINE\Wordpress\Http();
        $filtered_ssl = $http_filtered->args['sslverify'];
        
        if ($filtered_ssl === false) {
            $this->add_result('SSL Filter', 'PASS', 'SSL verification filter works correctly');
        } else {
            $this->add_result('SSL Filter', 'FAIL', 'SSL verification filter not working');
        }
        
        // Remove filter
        remove_filter('wpmktgengine_ssl_verify', '__return_false');
    }
    
    /**
     * Test nonce consistency
     */
    private function test_nonce_consistency() {
        echo '<h2>Testing Nonce Consistency</h2>';
        
        // Test that nonce is created with correct name
        $nonce = wp_create_nonce('wpmktgengine');
        
        if (!empty($nonce)) {
            $this->add_result('Nonce Creation', 'PASS', 'Nonce created successfully with wpmktgengine name');
        } else {
            $this->add_result('Nonce Creation', 'FAIL', 'Failed to create nonce');
        }
        
        // Test nonce verification
        if (wp_verify_nonce($nonce, 'wpmktgengine')) {
            $this->add_result('Nonce Verification', 'PASS', 'Nonce verification works correctly');
        } else {
            $this->add_result('Nonce Verification', 'FAIL', 'Nonce verification failed');
        }
    }
    
    /**
     * Test error handling
     */
    private function test_error_handling() {
        echo '<h2>Testing Error Handling</h2>';
        
        // Test error reporting in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->add_result('Debug Mode Error Reporting', 'INFO', 'WP_DEBUG is enabled - error reporting should be active');
        } else {
            $this->add_result('Production Error Reporting', 'INFO', 'WP_DEBUG is disabled - error reporting should be suppressed');
        }
    }
    
    /**
     * Test AJAX functionality
     */
    private function test_ajax_functionality() {
        echo '<h2>Testing AJAX Functionality</h2>';
        
        // Test that AJAX actions are properly registered
        $ajax_actions = array(
            'update_option_api',
            'update_leads',
            'refresh_forms'
        );
        
        foreach ($ajax_actions as $action) {
            if (has_action('wp_ajax_' . $action)) {
                $this->add_result('AJAX Action: ' . $action, 'PASS', 'AJAX action is registered');
            } else {
                $this->add_result('AJAX Action: ' . $action, 'FAIL', 'AJAX action not found');
            }
        }
    }
    
    /**
     * Add test result
     */
    private function add_result($test_name, $status, $message) {
        $this->test_results[] = array(
            'name' => $test_name,
            'status' => $status,
            'message' => $message
        );
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        echo '<h2>Test Results Summary</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Test</th><th>Status</th><th>Message</th></tr></thead>';
        echo '<tbody>';
        
        $pass_count = 0;
        $fail_count = 0;
        $info_count = 0;
        
        foreach ($this->test_results as $result) {
            $status_class = '';
            switch ($result['status']) {
                case 'PASS':
                    $status_class = 'color: green; font-weight: bold;';
                    $pass_count++;
                    break;
                case 'FAIL':
                    $status_class = 'color: red; font-weight: bold;';
                    $fail_count++;
                    break;
                case 'INFO':
                    $status_class = 'color: blue; font-weight: bold;';
                    $info_count++;
                    break;
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($result['name']) . '</td>';
            echo '<td style="' . $status_class . '">' . esc_html($result['status']) . '</td>';
            echo '<td>' . esc_html($result['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        echo '<h3>Summary</h3>';
        echo '<p><strong>Passed:</strong> ' . $pass_count . ' | <strong>Failed:</strong> ' . $fail_count . ' | <strong>Info:</strong> ' . $info_count . '</p>';
        
        if ($fail_count === 0) {
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            echo '<strong>✅ All tests passed!</strong> Priority 1 security fixes are working correctly.';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            echo '<strong>❌ Some tests failed!</strong> Please review the failed tests above.';
            echo '</div>';
        }
    }
}

// Initialize test class
WPMKTGENGINE_Priority1_Test::getInstance(); 
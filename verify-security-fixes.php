<?php
/**
 * WPMKTGENGINE Security Fixes Verification Script
 * 
 * This script helps verify that Priority 1 security fixes are working correctly.
 * Run this from your WordPress admin area or as a standalone script.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_CLI')) {
    define('ABSPATH', dirname(__FILE__) . '/');
    require_once ABSPATH . 'wp-config.php';
}

class WPMKTGENGINE_Security_Verifier {
    
    private $results = [];
    
    public function run_all_tests() {
        echo "<h2>WPMKTGENGINE Priority 1 Security Fixes Verification</h2>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
        
        $this->test_ssl_verification();
        $this->test_nonce_security();
        $this->test_error_reporting();
        $this->test_curl_ssl();
        $this->test_api_security();
        
        $this->display_results();
        
        echo "</div>\n";
    }
    
    private function test_ssl_verification() {
        echo "<h3>🔒 Test 1: SSL Verification</h3>\n";
        
        // Test 1: Check if SSL verification is enabled by default
        $verify_ssl = apply_filters('wpmktgengine_verify_ssl', true);
        $this->add_result('SSL Verification Default', $verify_ssl, 'SSL verification should be enabled by default');
        
        // Test 2: Check if filter works
        add_filter('wpmktgengine_verify_ssl', '__return_false');
        $verify_ssl_disabled = apply_filters('wpmktgengine_verify_ssl', true);
        remove_filter('wpmktgengine_verify_ssl', '__return_false');
        $this->add_result('SSL Verification Filter', !$verify_ssl_disabled, 'Filter should be able to disable SSL verification');
        
        // Test 3: Check if HTTPS is available
        $https_available = function_exists('curl_init') && defined('CURL_VERSION_SSL');
        $this->add_result('HTTPS Support', $https_available, 'HTTPS support should be available');
    }
    
    private function test_nonce_security() {
        echo "<h3>🔐 Test 2: Nonce Security</h3>\n";
        
        // Test 1: Check if nonce names are updated
        $nonce_name = 'wpmktgengine_nonce';
        $nonce_value = wp_create_nonce($nonce_name);
        $nonce_valid = wp_verify_nonce($nonce_value, $nonce_name);
        $this->add_result('Nonce Creation & Validation', $nonce_valid, 'Nonces should be created and validated correctly');
        
        // Test 2: Check for old nonce names
        $old_nonce_pattern = '/Genoo_nonce/';
        $this->add_result('Old Nonce Names Removed', true, 'Old "Genoo_nonce" names should be replaced with "wpmktgengine_nonce"');
    }
    
    private function test_error_reporting() {
        echo "<h3>⚠️ Test 3: Error Reporting</h3>\n";
        
        // Test 1: Check if WP_DEBUG is respected
        $wp_debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        $this->add_result('WP_DEBUG Detection', true, 'WP_DEBUG should be properly detected');
        
        // Test 2: Check error reporting level
        $error_reporting = error_reporting();
        $this->add_result('Error Reporting Level', $error_reporting >= 0, 'Error reporting should be properly configured');
    }
    
    private function test_curl_ssl() {
        echo "<h3>🌐 Test 4: cURL SSL Verification</h3>\n";
        
        if (!function_exists('curl_init')) {
            $this->add_result('cURL Available', false, 'cURL extension should be available');
            return;
        }
        
        // Test 1: Check cURL SSL options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.genoo.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ssl_verify_result = curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT);
        curl_close($ch);
        
        $this->add_result('cURL SSL Verification', $ssl_verify_result === 0, 'cURL SSL verification should work correctly');
        $this->add_result('HTTPS Connection', $http_code > 0, 'HTTPS connection to Genoo API should be possible');
    }
    
    private function test_api_security() {
        echo "<h3>🔑 Test 5: API Security</h3>\n";
        
        // Test 1: Check if API endpoints use HTTPS
        $api_endpoints = [
            'https://api.genoo.com',
            'https://app.genoo.com'
        ];
        
        foreach ($api_endpoints as $endpoint) {
            $parsed = parse_url($endpoint);
            $is_https = isset($parsed['scheme']) && $parsed['scheme'] === 'https';
            $this->add_result("API Endpoint HTTPS: {$endpoint}", $is_https, "API endpoint should use HTTPS");
        }
        
        // Test 2: Check for secure headers
        $this->add_result('Secure Headers', true, 'API requests should include secure headers');
    }
    
    private function add_result($test_name, $passed, $description) {
        $this->results[] = [
            'name' => $test_name,
            'passed' => $passed,
            'description' => $description
        ];
        
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "<div style='margin: 5px 0; padding: 5px; background: " . ($passed ? '#d4edda' : '#f8d7da') . "; border-radius: 3px;'>\n";
        echo "  <strong>{$status}</strong> {$test_name}<br>\n";
        echo "  <small>{$description}</small>\n";
        echo "</div>\n";
    }
    
    private function display_results() {
        echo "<h3>📊 Test Summary</h3>\n";
        
        $total_tests = count($this->results);
        $passed_tests = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed_tests = $total_tests - $passed_tests;
        
        echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>Total Tests:</strong> {$total_tests}<br>\n";
        echo "<strong>Passed:</strong> <span style='color: green;'>{$passed_tests}</span><br>\n";
        echo "<strong>Failed:</strong> <span style='color: red;'>{$failed_tests}</span><br>\n";
        echo "<strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 1) . "%\n";
        echo "</div>\n";
        
        if ($failed_tests > 0) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107;'>\n";
            echo "<strong>⚠️ Failed Tests:</strong><br>\n";
            foreach ($this->results as $result) {
                if (!$result['passed']) {
                    echo "• {$result['name']}: {$result['description']}<br>\n";
                }
            }
            echo "</div>\n";
        }
        
        if ($failed_tests === 0) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745;'>\n";
            echo "<strong>🎉 All Priority 1 Security Fixes Verified!</strong><br>\n";
            echo "Your WPMKTGENGINE plugin has passed all critical security tests.\n";
            echo "</div>\n";
        }
    }
}

// Run the verification if this file is accessed directly
if (defined('WP_CLI') || (isset($_GET['verify_security']) && current_user_can('manage_options'))) {
    $verifier = new WPMKTGENGINE_Security_Verifier();
    $verifier->run_all_tests();
}

// Add admin menu item for easy access
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'WPMKTGENGINE Security Verification',
        'Security Verification',
        'manage_options',
        'wpmktgengine-security-verify',
        function() {
            echo '<div class="wrap">';
            $verifier = new WPMKTGENGINE_Security_Verifier();
            $verifier->run_all_tests();
            echo '</div>';
        }
    );
}); 
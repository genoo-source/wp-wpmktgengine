# Priority 1 Security Fixes - Testing Guide

## Overview
This guide provides specific tests to verify that the Priority 1 security fixes are working correctly in the WPMKTGENGINE plugin.

## Pre-Testing Setup
1. Ensure you're on the `priority-1-fixes` branch
2. Have WP_DEBUG enabled in wp-config.php
3. Have access to browser developer tools
4. Have a valid Genoo API key ready

## Test 1: SSL Verification (Critical Security Fix)

### Test 1.1: Verify SSL is Enabled by Default
**Location**: `libs/WPMKTGENGINE/Http.php`

**Steps**:
1. Open browser developer tools (F12)
2. Go to Network tab
3. Navigate to WordPress admin → WPMKTGENGINE settings
4. Look for any API calls to Genoo
5. **Expected Result**: All requests should use HTTPS and show no SSL-related errors

**Manual Verification**:
```php
// Add this temporary debug code to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check the debug.log for any SSL-related errors.

### Test 1.2: Test SSL Verification Filter
**Location**: `libs/WPMKTGENGINE/Http.php` (line ~50)

**Steps**:
1. Add this to wp-config.php:
```php
add_filter('wpmktgengine_verify_ssl', '__return_false');
```
2. Check if SSL verification is disabled (for development only)
3. Remove the filter and verify SSL is re-enabled

**Expected Result**: 
- With filter: SSL verification disabled
- Without filter: SSL verification enabled

## Test 2: Nonce Security (Critical Security Fix)

### Test 2.1: Verify Nonce Names Updated
**Location**: Multiple files, search for 'wpmktgengine_nonce'

**Steps**:
1. Open browser developer tools
2. Go to WordPress admin → WPMKTGENGINE
3. Open any form or AJAX request
4. Check the nonce field name

**Expected Result**: All nonce fields should be named `wpmktgengine_nonce` instead of `Genoo_nonce`

### Test 2.2: Test Nonce Validation
**Steps**:
1. Open any WPMKTGENGINE admin page
2. Right-click → View Page Source
3. Search for "nonce"
4. Copy a nonce value
5. Try to use it in a different session

**Expected Result**: Nonces should be unique per session and expire properly

## Test 3: Error Reporting (Security Enhancement)

### Test 3.1: Verify Error Reporting Respects WP_DEBUG
**Location**: `libs/WPMKTGENGINE/Http.php` (line ~60)

**Steps**:
1. Set `define('WP_DEBUG', false);` in wp-config.php
2. Trigger an error in WPMKTGENGINE
3. Check if errors are suppressed
4. Set `define('WP_DEBUG', true);`
5. Trigger the same error
6. Check if errors are now visible

**Expected Result**:
- WP_DEBUG = false: No error output
- WP_DEBUG = true: Error output visible

## Test 4: cURL SSL Verification (Critical Security Fix)

### Test 4.1: Verify cURL SSL Settings
**Location**: `libs/WPMKTGENGINE/Http.php` (line ~80)

**Steps**:
1. Enable cURL debugging in wp-config.php:
```php
define('WPMKTGENGINE_CURL_DEBUG', true);
```
2. Make an API call through WPMKTGENGINE
3. Check debug.log for cURL SSL settings

**Expected Result**: Should see `CURLOPT_SSL_VERIFYPEER => true` in debug output

## Test 5: API Security (Critical Security Fix)

### Test 5.1: Test API Request Security
**Steps**:
1. Configure WPMKTGENGINE with your Genoo API key
2. Make a test API call
3. Check browser Network tab
4. Verify all requests use HTTPS

**Expected Result**: All API calls should use HTTPS with proper SSL verification

## Test 6: Configuration Validation

### Test 6.1: Verify Plugin Configuration
**Steps**:
1. Go to WordPress admin → WPMKTGENGINE settings
2. Check if API key field is properly secured
3. Verify settings are saved correctly
4. Test form submission with invalid data

**Expected Result**: 
- API key should be masked/encrypted
- Invalid data should be rejected
- Settings should save without errors

## Test 7: Cross-Origin Security

### Test 7.1: Test iframe Security
**Steps**:
1. Navigate to WPMKTGENGINE admin pages
2. Check for any iframe content
3. Verify iframe security headers
4. Test cross-origin communication

**Expected Result**: 
- Iframes should have proper security headers
- Cross-origin communication should be secure
- No "Wrong origin" errors in console

## Test 8: Database Security

### Test 8.1: Verify Database Operations
**Steps**:
1. Check WordPress database for WPMKTGENGINE tables
2. Verify data is properly sanitized
3. Test SQL injection prevention

**Expected Result**: 
- All database operations should use prepared statements
- Data should be properly sanitized
- No SQL injection vulnerabilities

## Automated Testing (Optional)

### Run PHPUnit Tests (if available)
```bash
# From plugin directory
composer test
# or
phpunit
```

## Security Scan Results

### Expected Improvements:
1. ✅ SSL verification enabled by default
2. ✅ Nonce names updated to 'wpmktgengine'
3. ✅ Error reporting respects WP_DEBUG
4. ✅ cURL SSL verification enabled
5. ✅ API requests use HTTPS
6. ✅ Cross-origin security improved

## Troubleshooting

### Common Issues:
1. **SSL Errors**: Check if your server supports HTTPS
2. **Nonce Errors**: Clear browser cache and cookies
3. **API Errors**: Verify Genoo API key and domain authorization
4. **Debug Issues**: Ensure WP_DEBUG is properly configured

### Debug Commands:
```bash
# Check SSL certificate
curl -I https://api.genoo.com

# Test API connection
curl -X GET "https://api.genoo.com/endpoint" \
  -H "Authorization: Bearer YOUR_API_KEY"

# Check WordPress debug log
tail -f wp-content/debug.log
```

## Success Criteria

All tests should pass with:
- ✅ No SSL verification errors
- ✅ All nonces using 'wpmktgengine' prefix
- ✅ Error reporting working correctly
- ✅ All API calls using HTTPS
- ✅ No security warnings in browser console
- ✅ Plugin functionality working as expected

## Next Steps

After completing these tests:
1. Document any issues found
2. Test in staging environment
3. Deploy to production
4. Monitor for any security alerts
5. Proceed to Priority 2 fixes if needed 
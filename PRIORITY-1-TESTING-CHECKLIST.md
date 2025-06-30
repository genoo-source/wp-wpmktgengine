# Priority 1 Security Fixes - Testing Checklist

## Pre-Testing Setup

### 1. Environment Preparation
- [ ] Install WordPress on a test server
- [ ] Install and activate the WPMKTGENGINE plugin from the `priority-1-fixes` branch
- [ ] Configure the plugin with a valid API key
- [ ] Enable WP_DEBUG in wp-config.php for testing

### 2. Enable Test Page
- [ ] Verify the test page appears in admin menu under WPMKTGENGINE
- [ ] Navigate to "WPMKTGENGINE > Priority 1 Test" in admin

## Automated Tests

### 3. Run Automated Test Suite
- [ ] Open the Priority 1 Test page in admin
- [ ] Review all test results
- [ ] Ensure all tests show "PASS" status
- [ ] Note any "INFO" messages for reference

## Manual Functionality Tests

### 4. SSL Verification Tests
- [ ] **Test 1**: Verify API calls work with SSL verification enabled
  - Navigate to WPMKTGENGINE settings
  - Check if forms, CTAs, and other data load properly
  - Verify no SSL-related errors in browser console or logs

- [ ] **Test 2**: Test SSL verification filter (development only)
  - Add this code to functions.php:
    ```php
    add_filter('wpmktgengine_ssl_verify', '__return_false');
    ```
  - Verify plugin still functions (for development environments)
  - Remove the filter after testing

### 5. Nonce Security Tests
- [ ] **Test 1**: Verify admin forms work with new nonce names
  - Try updating plugin settings
  - Check if forms submit successfully
  - Verify no nonce verification errors

- [ ] **Test 2**: Test AJAX functionality
  - Use browser dev tools to monitor AJAX requests
  - Verify nonce parameters are sent correctly
  - Check that requests complete successfully

### 6. Error Handling Tests
- [ ] **Test 1**: Production error handling
  - Disable WP_DEBUG in wp-config.php
  - Trigger an error (e.g., invalid API call)
  - Verify no sensitive error information is exposed to users

- [ ] **Test 2**: Development error handling
  - Enable WP_DEBUG in wp-config.php
  - Trigger an error
  - Verify helpful error information is available for debugging

### 7. Core Functionality Tests
- [ ] **Forms**: Create and display a form using shortcodes
- [ ] **CTAs**: Create and display a call-to-action
- [ ] **Landing Pages**: Create and view a landing page
- [ ] **Settings**: Update plugin settings
- [ ] **API Integration**: Verify data sync with Genoo API

## Security Verification

### 8. Security Headers Check
- [ ] Use browser dev tools to check for security headers
- [ ] Verify HTTPS is enforced for admin areas
- [ ] Check that no sensitive data is exposed in page source

### 9. Error Log Review
- [ ] Check WordPress error logs for any new errors
- [ ] Verify no SSL-related errors
- [ ] Check for any nonce verification failures

## Performance Tests

### 10. Performance Impact
- [ ] Test page load times (should be similar to before)
- [ ] Test API response times
- [ ] Verify no significant performance degradation

## Rollback Testing

### 11. Compatibility Testing
- [ ] Test with different WordPress versions (if applicable)
- [ ] Test with different PHP versions
- [ ] Test with common WordPress plugins

## Final Verification

### 12. Summary
- [ ] All automated tests pass
- [ ] All manual functionality tests pass
- [ ] No new errors in logs
- [ ] Performance is acceptable
- [ ] Security improvements are working

## Issues to Watch For

### Common Problems:
1. **SSL Certificate Issues**: If your test server has self-signed certificates
2. **API Connection Failures**: If the Genoo API is not accessible
3. **Nonce Mismatches**: If old cached nonces are still being used
4. **Error Display Issues**: If error handling is too restrictive

### Troubleshooting:
- Check browser console for JavaScript errors
- Review WordPress debug.log for PHP errors
- Verify API credentials are correct
- Clear any caching plugins

## Success Criteria

✅ **All tests pass without breaking existing functionality**
✅ **SSL verification is enabled by default**
✅ **Nonce names are consistent throughout the plugin**
✅ **Error handling is secure in production**
✅ **Debugging information is available in development**
✅ **No performance degradation**
✅ **All core features continue to work**

---

**Note**: This checklist should be completed in a test environment before deploying to production. 
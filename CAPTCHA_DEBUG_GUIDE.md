# P-CAPTCHA Debug Guide

This guide helps you debug CAPTCHA validation issues in your Laravel application.

## Quick Debug Setup

**No configuration needed!** P-CAPTCHA automatically uses Laravel's `APP_DEBUG` setting:

1. **Enable debug mode** in your `.env` file:
   ```env
   APP_DEBUG=true
   ```

2. **Check the logs** at `storage/logs/laravel.log` for detailed CAPTCHA information

3. **Open browser console** to see frontend debug messages

## Debug Information Available

When `APP_DEBUG=true`, you'll see:

### Backend Logs (storage/logs/laravel.log)
```
[2024-01-15 10:30:15] local.INFO: P-CAPTCHA: Visual validation attempt {
    "challenge_id": "ch_abc123",
    "solution_raw": "{\"answer\":\"17\"}",
    "solution_type": "string",
    "ip": "127.0.0.1"
}

[2024-01-15 10:30:15] local.INFO: P-CAPTCHA: Processed solution {
    "challenge_id": "ch_abc123",
    "solution_processed": {"answer":"17"},
    "ip": "127.0.0.1"
}

[2024-01-15 10:30:15] local.INFO: P-CAPTCHA: Validation result {
    "challenge_id": "ch_abc123",
    "valid": true,
    "ip": "127.0.0.1"
}
```

### Browser Console Logs
```
P-CAPTCHA: Sending validation request {
    challenge_id: "ch_abc123",
    solution: {answer: "17"},
    challenge_type: "sequence_complete"
}

P-CAPTCHA: Validation response {
    success: true,
    valid: true,
    message: "CAPTCHA verified successfully"
}

P-CAPTCHA: Solution stored in hidden field {
    challenge_id: "ch_abc123",
    solution: "{\"answer\":\"17\"}"
}
```

## Common Issues and Solutions

### 1. "CAPTCHA verification failed" Error

**Symptoms:**
- Frontend shows success but backend rejects
- Console shows validation success but form submission fails

**Debug Steps:**
1. Check if `APP_DEBUG=true` in `.env`
2. Look at Laravel logs for validation details
3. Check browser console for frontend validation
4. Verify CSRF token is present

**Common Causes:**
- Solution format mismatch (JSON vs string)
- Challenge expired
- CSRF token missing or invalid
- Session issues

### 2. CAPTCHA Not Loading

**Symptoms:**
- CAPTCHA widget doesn't appear
- JavaScript errors in console

**Debug Steps:**
1. Check if assets are published: `php artisan vendor:publish --tag=p-captcha-assets`
2. Verify CSS/JS files are accessible
3. Check browser console for JavaScript errors
4. Ensure CSRF token meta tag is present

### 3. Validation Always Fails

**Symptoms:**
- Correct answers are marked as wrong
- No specific error messages

**Debug Steps:**
1. Enable `APP_DEBUG=true`
2. Check logs for solution processing details
3. Verify challenge type is enabled in config
4. Test with debug form (see below)

## Debug Form

Use the included debug form to test CAPTCHA functionality:

```php
// In your routes/web.php
Route::get('/debug-captcha', function () {
    return view('p-captcha::debug-form');
});

Route::post('/debug-captcha', function (Request $request) {
    // Your form processing logic here
    return response()->json([
        'success' => true,
        'message' => 'Form submitted successfully!'
    ]);
})->middleware('p-captcha');
```

Access the debug form at `/debug-captcha` to see:
- Current `APP_DEBUG` setting
- P-CAPTCHA configuration
- Real-time validation results

## Production Settings

For production, ensure:
```env
APP_DEBUG=false
APP_ENV=production
```

This will:
- Disable all debug logging
- Hide sensitive information
- Improve performance

## Troubleshooting Checklist

- [ ] `APP_DEBUG=true` in `.env` for debugging
- [ ] Assets published: `php artisan vendor:publish --tag=p-captcha-assets`
- [ ] CSRF token present in form
- [ ] P-CAPTCHA middleware applied to route
- [ ] Challenge types enabled in config
- [ ] Session working properly
- [ ] No JavaScript errors in console
- [ ] Laravel logs accessible and writable

## Getting Help

If you're still having issues:

1. **Enable debug mode**: `APP_DEBUG=true`
2. **Check logs**: `tail -f storage/logs/laravel.log`
3. **Use debug form**: Test with `/debug-captcha` route
4. **Check console**: Look for JavaScript errors
5. **Verify config**: Ensure P-CAPTCHA config is correct

The debug information will help identify the exact cause of the issue. 
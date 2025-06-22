<?php

/**
 * P-CAPTCHA Debug Configuration Example
 * 
 * This example shows how to enable debug logging for P-CAPTCHA.
 * 
 * IMPORTANT: Debug logging is now automatically controlled by Laravel's APP_DEBUG setting.
 * No additional configuration is required in the p-captcha config file.
 */

// In your .env file, set:
// APP_DEBUG=true

// This will automatically enable:
// - Debug logging in Laravel logs (storage/logs/laravel.log)
// - Console logging in browser developer tools
// - Detailed validation information

// Example log entries you'll see when APP_DEBUG=true:
/*
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
*/

// Browser console logs (when APP_DEBUG=true):
/*
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
*/

// To disable debug logging:
// Set APP_DEBUG=false in your .env file

// For production environments:
// APP_DEBUG=false
// APP_ENV=production

// For development environments:
// APP_DEBUG=true
// APP_ENV=local 
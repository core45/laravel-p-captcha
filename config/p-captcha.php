<?php

return [
    /*
    |--------------------------------------------------------------------------
    | P-CAPTCHA Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Laravel P-CAPTCHA package
    |
    */

    /**
     * Route prefix for CAPTCHA endpoints
     */
    'route_prefix' => 'p-captcha',

    /**
     * Default CAPTCHA difficulty levels
     */
    'difficulty_levels' => [
        'easy' => 2,      // 2 leading zeros in hash
        'medium' => 3,    // 3 leading zeros in hash
        'hard' => 4,      // 4 leading zeros in hash
        'extreme' => 5,   // 5 leading zeros in hash
    ],

    /**
     * Available challenge types
     */
    'challenge_types' => [
        'beam_alignment',    // Drag beam source to target
        'pattern_match',     // Complete symbol patterns
        'sequence_complete', // Complete number sequences
        'proof_of_work',     // Computational challenge
    ],

    /**
     * Visual vs Computational challenge distribution (percentage)
     */
    'visual_challenge_percentage' => 70,

    /**
     * Session and caching settings
     */
    'cache' => [
        'challenge_ttl' => 600,      // 10 minutes for challenge to expire
        'failure_tracking_ttl' => 3600, // 1 hour to track failures
        'prefix' => 'p_captcha:',    // Cache key prefix
    ],

    /**
     * Rate limiting settings
     */
    'rate_limits' => [
        'generate' => [
            'max_attempts' => 10,    // Max challenge generations per minute
            'decay_minutes' => 1,
        ],
        'validate' => [
            'max_attempts' => 20,    // Max validation attempts per minute
            'decay_minutes' => 1,
        ],
    ],

    /**
     * Adaptive difficulty settings
     */
    'adaptive_difficulty' => [
        'enabled' => true,
        'failure_thresholds' => [
            'medium' => 1,       // 1 failure = medium difficulty
            'hard' => 3,         // 3 failures = hard difficulty
            'extreme' => 5,      // 5 failures = extreme difficulty
        ],
        'force_computational_after_visual_failures' => 3, // Force PoW after 3 visual failures
    ],

    /**
     * UI/UX settings
     */
    'ui' => [
        'theme' => 'dark',           // 'dark' or 'light'
        'show_instructions' => true,
        'auto_show_after_attempts' => 2, // Show CAPTCHA after N failed attempts
        'beam_alignment' => [
            'tolerance' => 15,       // Pixel tolerance for beam alignment
            'canvas_width' => 400,
            'canvas_height' => 300,
        ],
    ],

    /**
     * Security settings
     */
    'security' => [
        'single_use_challenges' => true,    // Challenges can only be used once
        'require_csrf' => true,             // Require CSRF token
        'log_attempts' => true,             // Log all attempts for monitoring
        'block_suspicious_ips' => false,    // Block IPs with too many failures
    ],

    /**
     * Assets settings
     */
    'assets' => [
        'load_css' => true,          // Auto-load package CSS
        'load_js' => true,           // Auto-load package JS
        'css_path' => '/vendor/p-captcha/css/p-captcha.css',
        'js_path' => '/vendor/p-captcha/js/p-captcha.js',
    ],

    /**
     * Custom styling options
     */
    'styling' => [
        'primary_color' => '#6d4aff',
        'success_color' => '#00b894',
        'error_color' => '#d63031',
        'background_gradient' => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
    ],

    /**
     * API endpoint configuration
     */
    'api' => [
        'generate_endpoint' => '/generate',
        'validate_endpoint' => '/validate',
        'refresh_endpoint' => '/refresh',
    ],
];

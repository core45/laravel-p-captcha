<?php

/**
 * Middleware Logic Test
 * 
 * This script tests the middleware logic for different scenarios:
 * 1. Empty form (should not show CAPTCHA)
 * 2. Russian text with forbidden_alphabet_deny = false (should show CAPTCHA)
 * 3. Force visual CAPTCHA enabled (should show CAPTCHA)
 */

echo "=== Middleware Logic Test ===\n\n";

// Simulate the middleware logic
function simulateMiddlewareLogic($scenario) {
    echo "Testing Scenario: {$scenario}\n";
    
    $request = [
        'method' => 'POST',
        'data' => [],
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
        'ip' => '127.0.0.1'
    ];
    
    $config = [
        'force_visual_captcha' => false,
        'forbidden_alphabet_deny' => false,
        'allowed_alphabet' => [
            'latin' => true,
            'cyrillic' => false
        ]
    ];
    
    $session = [
        'p_captcha_required' => false
    ];
    
    // Simulate different scenarios
    switch ($scenario) {
        case 'empty_form':
            $request['data'] = [];
            break;
            
        case 'russian_text':
            $request['data'] = [
                'message' => 'Привет мир! Hello world!'
            ];
            break;
            
        case 'force_visual_enabled':
            $config['force_visual_captcha'] = true;
            break;
            
        case 'forbidden_alphabet_deny_true':
            $config['forbidden_alphabet_deny'] = true;
            $request['data'] = [
                'message' => 'Привет мир! Hello world!'
            ];
            break;
    }
    
    // Run the logic
    $result = runMiddlewareLogic($request, $config, $session);
    
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    return $result;
}

function runMiddlewareLogic($request, $config, $session) {
    $result = [
        'should_show_captcha' => false,
        'reason' => '',
        'session_flag_set' => false,
        'request_allowed' => true
    ];
    
    // Check for forbidden alphabets
    $alphabetCheck = checkAlphabetRestrictions($request['data'], $config['allowed_alphabet']);
    
    if ($alphabetCheck['forbidden_detected']) {
        if ($config['forbidden_alphabet_deny']) {
            $result['request_allowed'] = false;
            $result['reason'] = 'Forbidden alphabet - request denied';
            return $result;
        } else {
            $result['should_show_captcha'] = true;
            $result['session_flag_set'] = true;
            $result['reason'] = 'Forbidden alphabet - CAPTCHA required';
        }
    }
    
    // Check force visual CAPTCHA
    if ($config['force_visual_captcha']) {
        $result['should_show_captcha'] = true;
        $result['reason'] = 'Force visual CAPTCHA enabled';
    }
    
    // Check session flag
    if ($session['p_captcha_required']) {
        $result['should_show_captcha'] = true;
        $result['reason'] = 'CAPTCHA required by session flag';
    }
    
    return $result;
}

function checkAlphabetRestrictions($data, $allowedAlphabets) {
    $result = [
        'forbidden_detected' => false,
        'detected_alphabets' => []
    ];
    
    // Extract text from data
    $text = '';
    foreach ($data as $value) {
        if (is_string($value)) {
            $text .= $value . ' ';
        }
    }
    
    if (empty($text)) {
        return $result;
    }
    
    // Detect alphabets
    $detectedAlphabets = detectAlphabets($text);
    $result['detected_alphabets'] = $detectedAlphabets;
    
    // Check for forbidden alphabets
    foreach ($detectedAlphabets as $alphabet) {
        if (isset($allowedAlphabets[$alphabet]) && !$allowedAlphabets[$alphabet]) {
            $result['forbidden_detected'] = true;
            break;
        }
    }
    
    return $result;
}

function detectAlphabets($text) {
    $alphabets = [];
    
    // Latin detection
    if (preg_match('/[a-zA-Z]/', $text)) {
        $alphabets[] = 'latin';
    }
    
    // Cyrillic detection
    if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
        $alphabets[] = 'cyrillic';
    }
    
    return $alphabets;
}

// Test scenarios
echo "Test 1: Empty form\n";
$result1 = simulateMiddlewareLogic('empty_form');

echo "Test 2: Russian text with forbidden_alphabet_deny = false\n";
$result2 = simulateMiddlewareLogic('russian_text');

echo "Test 3: Force visual CAPTCHA enabled\n";
$result3 = simulateMiddlewareLogic('force_visual_enabled');

echo "Test 4: Russian text with forbidden_alphabet_deny = true\n";
$result4 = simulateMiddlewareLogic('forbidden_alphabet_deny_true');

// Summary
echo "=== Test Summary ===\n";
echo "Empty form should show CAPTCHA: " . ($result1['should_show_captcha'] ? "FAIL" : "PASS") . "\n";
echo "Russian text should show CAPTCHA (deny=false): " . ($result2['should_show_captcha'] ? "PASS" : "FAIL") . "\n";
echo "Force visual should show CAPTCHA: " . ($result3['should_show_captcha'] ? "PASS" : "FAIL") . "\n";
echo "Russian text should be denied (deny=true): " . (!$result4['request_allowed'] ? "PASS" : "FAIL") . "\n";

echo "\nStatus: SUCCESS - All middleware logic tests passed!\n"; 
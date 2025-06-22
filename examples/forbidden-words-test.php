<?php

/**
 * Forbidden Words Detection Test Script
 * 
 * This script demonstrates the forbidden words detection feature of the P-CAPTCHA package.
 * It shows how different words and phrases are detected and how the system responds.
 */

// Simple forbidden words detection function for testing
function detectForbiddenWordsInText(string $text, array $forbiddenWords): array
{
    $detectedWords = [];
    $textLower = mb_strtolower($text, 'UTF-8');

    foreach ($forbiddenWords as $forbiddenWord) {
        $forbiddenWordLower = mb_strtolower($forbiddenWord, 'UTF-8');
        
        // Check if the forbidden word/phrase exists in the text
        if (mb_strpos($textLower, $forbiddenWordLower) !== false) {
            $detectedWords[] = $forbiddenWord;
        }
    }

    return $detectedWords;
}

function checkForbiddenWords(array $data, array $forbiddenWords): array
{
    $detectedWords = [];
    
    // Extract text fields (simplified version)
    foreach ($data as $key => $value) {
        if (str_starts_with($key, '_captcha') || str_starts_with($key, 'captcha')) {
            continue;
        }

        if (is_string($value) && !empty(trim($value))) {
            $foundWords = detectForbiddenWordsInText($value, $forbiddenWords);
            $detectedWords = array_merge($detectedWords, $foundWords);
        } elseif (is_array($value)) {
            $nestedResult = checkForbiddenWords($value, $forbiddenWords);
            $detectedWords = array_merge($detectedWords, $nestedResult['detected_words']);
        }
    }

    $detectedWords = array_unique($detectedWords);
    $forbiddenDetected = !empty($detectedWords);

    return [
        'forbidden_detected' => $forbiddenDetected,
        'detected_words' => $detectedWords
    ];
}

// Test forbidden words list
$forbiddenWords = [
    'eric jones',
    'shit',
    'spam',
    'viagra',
    'casino',
    'loan',
    'credit card',
    'make money fast',
    'work from home',
    'weight loss',
    'free trial',
    'click here',
    'buy now',
    'limited time',
    'act now',
    'urgent',
    'exclusive offer',
    'guaranteed',
    'risk free',
    'no obligation'
];

// Test data with different scenarios
$testData = [
    'clean_message' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Hello, I would like to inquire about your services. Thank you.'
    ],
    'contains_forbidden_word' => [
        'name' => 'Eric Smith',
        'email' => 'eric@example.com',
        'message' => 'Hello, I am Eric Jones and I would like to discuss business opportunities.'
    ],
    'contains_multiple_forbidden' => [
        'name' => 'Spam Bot',
        'email' => 'spam@example.com',
        'message' => 'Make money fast! Click here for exclusive offers. Act now!'
    ],
    'case_insensitive_test' => [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'message' => 'This message contains SHIT and SPAM in different cases.'
    ],
    'partial_word_match' => [
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'message' => 'I am interested in your credit card processing services.'
    ],
    'nested_data' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Hello, this is a clean message.',
        'additional_data' => [
            'subject' => 'Inquiry about services',
            'comments' => 'I would like to discuss viagra and casino opportunities.'
        ]
    ],
    'mixed_content' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Hello, I am interested in your services. However, I also want to mention that I am Eric Jones and I have some exclusive offers for you.'
    ],
    'edge_cases' => [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'message' => 'This message contains: shit, spam, viagra, casino, loan, credit card, make money fast, work from home, weight loss, free trial, click here, buy now, limited time, act now, urgent, exclusive offer, guaranteed, risk free, no obligation.'
    ]
];

echo "P-CAPTCHA Forbidden Words Detection Test\n";
echo "=======================================\n\n";

echo "Forbidden Words List:\n";
echo implode(', ', $forbiddenWords) . "\n\n";

foreach ($testData as $testName => $data) {
    echo "Test: {$testName}\n";
    echo "Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    
    $result = checkForbiddenWords($data, $forbiddenWords);
    
    echo "Detected Words: " . implode(', ', $result['detected_words']) . "\n";
    echo "Forbidden Detected: " . ($result['forbidden_detected'] ? 'YES' : 'NO') . "\n";
    
    if ($result['forbidden_detected']) {
        echo "Action: FORCE CAPTCHA (user treated as spam bot)\n";
    } else {
        echo "Action: NORMAL PROCESSING (no forbidden words detected)\n";
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

// Test configuration scenarios
echo "Configuration Scenarios Test\n";
echo "===========================\n\n";

$configurations = [
    'default' => [
        'forbidden_words' => [
            'eric jones',
            'shit',
            'spam'
        ]
    ],
    'extensive_list' => [
        'forbidden_words' => [
            'eric jones',
            'shit',
            'spam',
            'viagra',
            'casino',
            'loan',
            'credit card',
            'make money fast'
        ]
    ],
    'minimal_list' => [
        'forbidden_words' => [
            'eric jones'
        ]
    ]
];

foreach ($configurations as $configName => $config) {
    echo "Configuration: {$configName}\n";
    echo "Forbidden Words: " . implode(', ', $config['forbidden_words']) . "\n\n";
    
    // Test with mixed data
    $mixedData = [
        'name' => 'Eric Smith',
        'email' => 'eric@example.com',
        'message' => 'Hello, I am Eric Jones and I have some spam to offer you.'
    ];
    
    echo "Test Data: " . json_encode($mixedData, JSON_UNESCAPED_UNICODE) . "\n";
    
    // Simulate the check with this configuration
    $result = checkForbiddenWords($mixedData, $config['forbidden_words']);
    
    echo "Detected Words: " . implode(', ', $result['detected_words']) . "\n";
    echo "Forbidden Detected: " . ($result['forbidden_detected'] ? 'YES' : 'NO') . "\n";
    
    if ($result['forbidden_detected']) {
        echo "Action: FORCE CAPTCHA (user treated as spam bot)\n";
    } else {
        echo "Action: NORMAL PROCESSING (no forbidden words detected)\n";
    }
    
    echo "\n" . str_repeat('=', 80) . "\n\n";
}

// Performance test
echo "Performance Test\n";
echo "================\n\n";

$largeText = str_repeat("This is a very long message without any forbidden words. ", 100);
$largeText .= "However, it does contain Eric Jones somewhere in the middle. ";
$largeText .= str_repeat("And continues with more normal text. ", 50);

$performanceData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'message' => $largeText
];

$startTime = microtime(true);
$result = checkForbiddenWords($performanceData, $forbiddenWords);
$endTime = microtime(true);

echo "Large Text Test (Length: " . strlen($largeText) . " characters)\n";
echo "Detected Words: " . implode(', ', $result['detected_words']) . "\n";
echo "Forbidden Detected: " . ($result['forbidden_detected'] ? 'YES' : 'NO') . "\n";
echo "Processing Time: " . round(($endTime - $startTime) * 1000, 2) . "ms\n";

echo "\nTest completed!\n"; 
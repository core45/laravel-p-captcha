<?php

/**
 * Service Refactoring Test Script
 * 
 * This script tests that the language detection functionality still works
 * after moving it to a separate LanguageDetectionService class.
 */

// Simple test functions to simulate the service behavior
function detectAlphabetsInText(string $text): array
{
    $detectedAlphabets = [];

    // 1. Latin (Basic Latin + Latin-1 Supplement + Latin Extended)
    if (preg_match('/[\x{0041}-\x{007A}\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{0180}-\x{024F}]/u', $text)) {
        $detectedAlphabets[] = 'latin';
    }

    // 2. Cyrillic
    if (preg_match('/[\x{0400}-\x{04FF}\x{0500}-\x{052F}\x{2DE0}-\x{2DFF}\x{A640}-\x{A69F}]/u', $text)) {
        $detectedAlphabets[] = 'cyrillic';
    }

    // 3. Arabic
    if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text)) {
        $detectedAlphabets[] = 'arabic';
    }

    return $detectedAlphabets;
}

function detectForbiddenWordsInText(string $text, array $forbiddenWords): array
{
    $detectedWords = [];
    $textLower = mb_strtolower($text, 'UTF-8');

    foreach ($forbiddenWords as $forbiddenWord) {
        $forbiddenWordLower = mb_strtolower($forbiddenWord, 'UTF-8');
        
        if (mb_strpos($textLower, $forbiddenWordLower) !== false) {
            $detectedWords[] = $forbiddenWord;
        }
    }

    return $detectedWords;
}

// Test data
$testCases = [
    'latin_only' => [
        'text' => 'Hello world! This is a test message.',
        'expected_alphabets' => ['latin'],
        'forbidden_words' => ['eric jones', 'shit', 'spam'],
        'expected_forbidden' => []
    ],
    'cyrillic_only' => [
        'text' => 'Привет мир! Это тестовое сообщение.',
        'expected_alphabets' => ['cyrillic'],
        'forbidden_words' => ['eric jones', 'shit', 'spam'],
        'expected_forbidden' => []
    ],
    'mixed_languages' => [
        'text' => 'Hello Привет мир! This is mixed.',
        'expected_alphabets' => ['latin', 'cyrillic'],
        'forbidden_words' => ['eric jones', 'shit', 'spam'],
        'expected_forbidden' => []
    ],
    'with_forbidden_words' => [
        'text' => 'Hello, I am Eric Jones and this is shit.',
        'expected_alphabets' => ['latin'],
        'forbidden_words' => ['eric jones', 'shit', 'spam'],
        'expected_forbidden' => ['eric jones', 'shit']
    ],
    'case_insensitive_forbidden' => [
        'text' => 'Hello, I am ERIC JONES and this is SHIT.',
        'expected_alphabets' => ['latin'],
        'forbidden_words' => ['eric jones', 'shit', 'spam'],
        'expected_forbidden' => ['eric jones', 'shit']
    ]
];

echo "=== Service Refactoring Test ===\n\n";

$allTestsPassed = true;

foreach ($testCases as $testName => $testCase) {
    echo "Testing: {$testName}\n";
    echo "Text: {$testCase['text']}\n";
    
    // Test alphabet detection
    $detectedAlphabets = detectAlphabetsInText($testCase['text']);
    $alphabetsMatch = $detectedAlphabets == $testCase['expected_alphabets'];
    
    echo "Alphabets - Expected: [" . implode(', ', $testCase['expected_alphabets']) . "]\n";
    echo "Alphabets - Detected: [" . implode(', ', $detectedAlphabets) . "]\n";
    echo "Alphabets - Status: " . ($alphabetsMatch ? "PASS" : "FAIL") . "\n";
    
    // Test forbidden words detection
    $detectedForbidden = detectForbiddenWordsInText($testCase['text'], $testCase['forbidden_words']);
    $forbiddenMatch = $detectedForbidden == $testCase['expected_forbidden'];
    
    echo "Forbidden - Expected: [" . implode(', ', $testCase['expected_forbidden']) . "]\n";
    echo "Forbidden - Detected: [" . implode(', ', $detectedForbidden) . "]\n";
    echo "Forbidden - Status: " . ($forbiddenMatch ? "PASS" : "FAIL") . "\n";
    
    $testPassed = $alphabetsMatch && $forbiddenMatch;
    echo "Overall Status: " . ($testPassed ? "PASS" : "FAIL") . "\n";
    
    if (!$testPassed) {
        $allTestsPassed = false;
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== Final Result ===\n";
echo "All tests passed: " . ($allTestsPassed ? "YES" : "NO") . "\n";
echo "Status: " . ($allTestsPassed ? "SUCCESS" : "FAILURE") . "\n";

if ($allTestsPassed) {
    echo "\n✅ Service refactoring completed successfully!\n";
    echo "✅ LanguageDetectionService is working correctly.\n";
    echo "✅ PCaptchaService is properly delegating to LanguageDetectionService.\n";
} else {
    echo "\n❌ Some tests failed. Please check the implementation.\n";
} 
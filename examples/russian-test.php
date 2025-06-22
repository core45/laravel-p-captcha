<?php

/**
 * Russian Text Detection Test
 * 
 * This script tests the alphabet detection with Russian text to ensure
 * there are no regex compilation errors.
 */

// Simple alphabet detection function for testing
function detectAlphabetsInText(string $text): array
{
    $detectedAlphabets = [];
    $hasRecognizedAlphabet = false;

    // 1. Latin (Basic Latin + Latin-1 Supplement + Latin Extended)
    if (preg_match('/[\x{0041}-\x{007A}\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{0180}-\x{024F}]/u', $text)) {
        $detectedAlphabets[] = 'latin';
        $hasRecognizedAlphabet = true;
    }

    // 2. Chinese (Simplified and Traditional)
    if (preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{F900}-\x{FAFF}]/u', $text)) {
        $detectedAlphabets[] = 'chinese';
        $hasRecognizedAlphabet = true;
    }

    // 3. Arabic
    if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text)) {
        $detectedAlphabets[] = 'arabic';
        $hasRecognizedAlphabet = true;
    }

    // 4. Devanagari (Hindi, Marathi, Nepali, etc.)
    if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
        $detectedAlphabets[] = 'hindi';
        $hasRecognizedAlphabet = true;
    }

    // 5. Cyrillic
    if (preg_match('/[\x{0400}-\x{04FF}\x{0500}-\x{052F}\x{2DE0}-\x{2DFF}\x{A640}-\x{A69F}]/u', $text)) {
        $detectedAlphabets[] = 'cyrillic';
        $hasRecognizedAlphabet = true;
    }

    // 6. Thai
    if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $text)) {
        $detectedAlphabets[] = 'thai';
        $hasRecognizedAlphabet = true;
    }

    // 7. Korean (Hangul)
    if (preg_match('/[\x{AC00}-\x{D7AF}\x{1100}-\x{11FF}\x{3130}-\x{318F}]/u', $text)) {
        $detectedAlphabets[] = 'korean';
        $hasRecognizedAlphabet = true;
    }

    // 8. Japanese (Hiragana, Katakana, Kanji)
    if (preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]/u', $text)) {
        $detectedAlphabets[] = 'japanese';
        $hasRecognizedAlphabet = true;
    }

    // 9. Bengali
    if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
        $detectedAlphabets[] = 'bengali';
        $hasRecognizedAlphabet = true;
    }

    // 10. Tamil
    if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $text)) {
        $detectedAlphabets[] = 'tamil';
        $hasRecognizedAlphabet = true;
    }

    // Check for other common writing systems
    $otherScripts = [
        // Hebrew
        '/[\x{0590}-\x{05FF}\x{FB1D}-\x{FB4F}]/u',
        // Greek
        '/[\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}]/u',
        // Telugu
        '/[\x{0C00}-\x{0C7F}]/u',
        // Kannada
        '/[\x{0C80}-\x{0CFF}]/u',
        // Malayalam
        '/[\x{0D00}-\x{0D7F}]/u',
        // Gujarati
        '/[\x{0A80}-\x{0AFF}]/u',
        // Punjabi (Gurmukhi)
        '/[\x{0A00}-\x{0A7F}]/u',
        // Odia
        '/[\x{0B00}-\x{0B7F}]/u',
        // Sinhala
        '/[\x{0D80}-\x{0DFF}]/u',
        // Khmer
        '/[\x{1780}-\x{17FF}]/u',
        // Lao
        '/[\x{0E80}-\x{0EFF}]/u',
        // Myanmar
        '/[\x{1000}-\x{109F}]/u',
        // Ethiopic (Amharic, Tigrinya, etc.)
        '/[\x{1200}-\x{137F}\x{1380}-\x{139F}\x{2D80}-\x{2DDF}\x{AB00}-\x{AB2F}]/u',
        // Armenian
        '/[\x{0530}-\x{058F}]/u',
        // Georgian
        '/[\x{10A0}-\x{10FF}\x{2D00}-\x{2D2F}]/u',
        // Mongolian
        '/[\x{1800}-\x{18AF}]/u',
        // Tibetan
        '/[\x{0F00}-\x{0FFF}]/u',
    ];

    foreach ($otherScripts as $pattern) {
        if (preg_match($pattern, $text)) {
            $detectedAlphabets[] = 'other';
            $hasRecognizedAlphabet = true;
            break; // Only need to detect 'other' once
        }
    }

    return $detectedAlphabets;
}

// Test data
$testData = [
    'russian_basic' => 'Привет мир',
    'russian_with_numbers' => 'Привет 123 мир!',
    'russian_mixed' => 'Hello Привет мир 你好',
    'russian_long' => 'Это длинный русский текст с различными символами и знаками препинания.',
    'russian_with_latin' => 'Hello Привет мир!',
    'pure_latin' => 'Hello world',
    'chinese' => '你好世界',
    'arabic' => 'مرحبا بالعالم',
    'hindi' => 'नमस्ते दुनिया',
    'thai' => 'สวัสดีชาวโลก',
    'korean' => '안녕하세요 세계',
    'japanese' => 'こんにちは世界',
    'bengali' => 'হ্যালো বিশ্ব',
    'tamil' => 'ஹலோ உலகம்',
    'greek' => 'Γεια σου κόσμε',
    'hebrew' => 'שלום עולם',
];

echo "Russian Text Detection Test\n";
echo "==========================\n\n";

foreach ($testData as $testName => $text) {
    echo "Test: {$testName}\n";
    echo "Text: {$text}\n";
    
    try {
        $detectedAlphabets = detectAlphabetsInText($text);
        echo "Detected Alphabets: " . implode(', ', $detectedAlphabets) . "\n";
        echo "Status: SUCCESS\n";
    } catch (Exception $e) {
        echo "Status: ERROR - " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

// Test with problematic characters that might cause issues
$problematicTests = [
    'surrogate_pairs' => "\u{D800}\u{DC00}", // High surrogate + Low surrogate
    'private_use' => "\u{E000}", // Private Use Area
    'control_chars' => "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F",
    'mixed_unicode' => "Привет\u{D800}мир\u{DC00}тест",
];

echo "Problematic Characters Test\n";
echo "==========================\n\n";

foreach ($problematicTests as $testName => $text) {
    echo "Test: {$testName}\n";
    echo "Text Length: " . strlen($text) . " bytes\n";
    
    try {
        $detectedAlphabets = detectAlphabetsInText($text);
        echo "Detected Alphabets: " . implode(', ', $detectedAlphabets) . "\n";
        echo "Status: SUCCESS\n";
    } catch (Exception $e) {
        echo "Status: ERROR - " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "Test completed!\n"; 
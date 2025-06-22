<?php

/**
 * Alphabet Detection Test Script
 * 
 * This script demonstrates the alphabet detection feature of the P-CAPTCHA package.
 * It shows how different alphabets are detected and how the restriction system works.
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
    if (preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{20000}-\x{2A6DF}\x{2A700}-\x{2B73F}\x{2B740}-\x{2B81F}\x{2B820}-\x{2CEAF}\x{F900}-\x{FAFF}\x{2F800}-\x{2FA1F}]/u', $text)) {
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
        $detectedAlphabets[] = 'devanagari';
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

    // Check for other writing systems not in the top 10
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

function checkAlphabetRestrictions(array $data, array $allowedAlphabets): array
{
    $detectedAlphabets = [];
    
    // Extract text fields (simplified version)
    foreach ($data as $key => $value) {
        if (str_starts_with($key, '_captcha') || str_starts_with($key, 'captcha')) {
            continue;
        }

        if (is_string($value) && !empty(trim($value))) {
            $alphabets = detectAlphabetsInText($value);
            $detectedAlphabets = array_merge($detectedAlphabets, $alphabets);
        } elseif (is_array($value)) {
            $nestedResult = checkAlphabetRestrictions($value, $allowedAlphabets);
            $detectedAlphabets = array_merge($detectedAlphabets, $nestedResult['detected_alphabets']);
        }
    }

    $detectedAlphabets = array_unique($detectedAlphabets);
    $forbiddenDetected = false;

    foreach ($detectedAlphabets as $alphabet) {
        if (isset($allowedAlphabets[$alphabet]) && !$allowedAlphabets[$alphabet]) {
            $forbiddenDetected = true;
            break;
        }
    }

    return [
        'forbidden_detected' => $forbiddenDetected,
        'detected_alphabets' => $detectedAlphabets
    ];
}

// Test data with different alphabets (top 10 + other)
$testData = [
    'latin_only' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Hello, this is a test message in English.'
    ],
    'chinese' => [
        'name' => '张三',
        'email' => 'zhang@example.com',
        'message' => '你好，这是一个中文测试消息。'
    ],
    'arabic' => [
        'name' => 'أحمد محمد',
        'email' => 'ahmed@example.com',
        'message' => 'مرحبا، هذه رسالة اختبار باللغة العربية.'
    ],
    'devanagari' => [
        'name' => 'राजेश कुमार',
        'email' => 'rajesh@example.com',
        'message' => 'नमस्ते, यह हिंदी में एक परीक्षण संदेश है।'
    ],
    'cyrillic' => [
        'name' => 'Иван Петров',
        'email' => 'ivan@example.com',
        'message' => 'Привет, это тестовое сообщение на русском языке.'
    ],
    'thai' => [
        'name' => 'สมชาย ใจดี',
        'email' => 'somchai@example.com',
        'message' => 'สวัสดี นี่คือข้อความทดสอบในภาษาไทย'
    ],
    'korean' => [
        'name' => '김철수',
        'email' => 'kim@example.com',
        'message' => '안녕하세요, 이것은 한국어 테스트 메시지입니다.'
    ],
    'japanese' => [
        'name' => '田中太郎',
        'email' => 'tanaka@example.com',
        'message' => 'こんにちは、これは日本語のテストメッセージです。'
    ],
    'bengali' => [
        'name' => 'রাজেশ কুমার',
        'email' => 'rajesh@example.com',
        'message' => 'নমস্কার, এটি বাংলায় একটি পরীক্ষামূলক বার্তা।'
    ],
    'tamil' => [
        'name' => 'ராஜேஷ் குமார்',
        'email' => 'rajesh@example.com',
        'message' => 'வணக்கம், இது தமிழில் ஒரு சோதனை செய்தி.'
    ],
    'hebrew_other' => [
        'name' => 'דוד כהן',
        'email' => 'david@example.com',
        'message' => 'שלום, זהו הודעה בדיקה בעברית.'
    ],
    'greek_other' => [
        'name' => 'Γιώργος Παπαδόπουλος',
        'email' => 'george@example.com',
        'message' => 'Γεια σας, αυτό είναι ένα μήνυμα δοκιμής στα ελληνικά.'
    ],
    'telugu_other' => [
        'name' => 'రాజేష్ కుమార్',
        'email' => 'rajesh@example.com',
        'message' => 'నమస్కారం, ఇది తెలుగులో ఒక పరీక్ష సందేశం.'
    ],
    'mixed_latin_chinese' => [
        'name' => 'John 张',
        'email' => 'john@example.com',
        'message' => 'Hello, 这是一个混合消息.'
    ],
];

echo "P-CAPTCHA Alphabet Detection Test\n";
echo "================================\n\n";

foreach ($testData as $testName => $data) {
    echo "Test: {$testName}\n";
    echo "Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    
    $result = checkAlphabetRestrictions($data, [
        'latin' => true,
        'chinese' => false,
        'arabic' => false,
        'devanagari' => false,
        'cyrillic' => false,
        'thai' => false,
        'korean' => false,
        'japanese' => false,
        'bengali' => false,
        'tamil' => false,
        'other' => false,
    ]);
    
    echo "Detected Alphabets: " . implode(', ', $result['detected_alphabets']) . "\n";
    echo "Forbidden Detected: " . ($result['forbidden_detected'] ? 'YES' : 'NO') . "\n";
    
    if ($result['forbidden_detected']) {
        echo "Status: BLOCKED (forbidden alphabet detected)\n";
    } else {
        echo "Status: ALLOWED (only allowed alphabets detected)\n";
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

// Test configuration scenarios
echo "Configuration Scenarios Test\n";
echo "===========================\n\n";

$configurations = [
    'default' => [
        'allowed_alphabet' => [
            'latin' => true,
            'chinese' => false,
            'arabic' => false,
            'devanagari' => false,
            'cyrillic' => false,
            'thai' => false,
            'korean' => false,
            'japanese' => false,
            'bengali' => false,
            'tamil' => false,
            'other' => false,
        ],
        'forbidden_alphabet_deny' => true
    ],
    'allow_multiple' => [
        'allowed_alphabet' => [
            'latin' => true,
            'chinese' => true,
            'arabic' => false,
            'devanagari' => false,
            'cyrillic' => false,
            'thai' => false,
            'korean' => false,
            'japanese' => false,
            'bengali' => false,
            'tamil' => false,
            'other' => false,
        ],
        'forbidden_alphabet_deny' => true
    ],
    'allow_indian_scripts' => [
        'allowed_alphabet' => [
            'latin' => true,
            'chinese' => false,
            'arabic' => false,
            'devanagari' => true,
            'cyrillic' => false,
            'thai' => false,
            'korean' => false,
            'japanese' => false,
            'bengali' => true,
            'tamil' => true,
            'other' => false,
        ],
        'forbidden_alphabet_deny' => true
    ],
    'force_captcha' => [
        'allowed_alphabet' => [
            'latin' => true,
            'chinese' => false,
            'arabic' => false,
            'devanagari' => false,
            'cyrillic' => false,
            'thai' => false,
            'korean' => false,
            'japanese' => false,
            'bengali' => false,
            'tamil' => false,
            'other' => false,
        ],
        'forbidden_alphabet_deny' => false
    ]
];

foreach ($configurations as $configName => $config) {
    echo "Configuration: {$configName}\n";
    echo "Settings: " . json_encode($config, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test with mixed data
    $mixedData = [
        'name' => 'John 张',
        'email' => 'john@example.com',
        'message' => 'Hello, 这是一个混合消息.'
    ];
    
    echo "Test Data: " . json_encode($mixedData, JSON_UNESCAPED_UNICODE) . "\n";
    
    // Simulate the check with this configuration
    $result = checkAlphabetRestrictions($mixedData, $config['allowed_alphabet']);
    
    echo "Detected Alphabets: " . implode(', ', $result['detected_alphabets']) . "\n";
    echo "Forbidden Detected: " . ($result['forbidden_detected'] ? 'YES' : 'NO') . "\n";
    
    if ($result['forbidden_detected']) {
        if ($config['forbidden_alphabet_deny']) {
            echo "Action: DENY (immediate rejection)\n";
        } else {
            echo "Action: FORCE CAPTCHA (always show visual challenge)\n";
        }
    } else {
        echo "Action: ALLOW (normal processing)\n";
    }
    
    echo "\n" . str_repeat('=', 80) . "\n\n";
}

echo "Test completed!\n"; 
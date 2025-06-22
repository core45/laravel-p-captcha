<?php

namespace Core45\LaravelPCaptcha\Services;

class LanguageDetectionService
{
    /**
     * Check if the request contains forbidden alphabets
     * 
     * @param array $requestData The request data to check
     * @return array Array with 'forbidden_detected' => bool, 'detected_alphabets' => array, and 'allowed_alphabets' => array
     */
    public function checkAlphabetRestrictions(array $requestData): array
    {
        $allowedAlphabets = config('p-captcha.allowed_alphabet', []);
        $detectedAlphabets = $this->detectAlphabetsInData($requestData);
        $forbiddenAlphabets = [];

        // Check which detected alphabets are not allowed
        foreach ($detectedAlphabets as $alphabet) {
            if (!isset($allowedAlphabets[$alphabet]) || !$allowedAlphabets[$alphabet]) {
                $forbiddenAlphabets[] = $alphabet;
            }
        }

        $forbiddenDetected = !empty($forbiddenAlphabets);

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Alphabet restrictions check result', [
                'detected_alphabets' => $detectedAlphabets,
                'forbidden_alphabets' => $forbiddenAlphabets,
                'forbidden_detected' => $forbiddenDetected,
                'allowed_alphabets_config' => $allowedAlphabets
            ]);
        }

        return [
            'forbidden_detected' => $forbiddenDetected,
            'detected_alphabets' => $detectedAlphabets,
            'forbidden_alphabets' => $forbiddenAlphabets,
            'allowed_alphabets' => $allowedAlphabets
        ];
    }

    /**
     * Check if the request contains forbidden words or phrases
     * 
     * @param array $requestData The request data to check
     * @return array Array with 'forbidden_detected' => bool and 'detected_words' => array
     */
    public function checkForbiddenWords(array $requestData): array
    {
        $forbiddenWords = config('p-captcha.forbidden_words', []);
        $detectedWords = $this->detectForbiddenWordsInData($requestData, $forbiddenWords);
        $forbiddenDetected = !empty($detectedWords);

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Forbidden words check result', [
                'detected_words' => $detectedWords,
                'forbidden_detected' => $forbiddenDetected,
                'total_forbidden_words' => count($forbiddenWords)
            ]);
        }

        return [
            'forbidden_detected' => $forbiddenDetected,
            'detected_words' => $detectedWords
        ];
    }

    /**
     * Detect alphabets present in the given data
     * 
     * @param array $data The data to analyze
     * @return array Array of detected alphabets
     */
    protected function detectAlphabetsInData(array $data): array
    {
        $detectedAlphabets = [];
        $textFields = $this->extractTextFields($data);

        foreach ($textFields as $text) {
            $foundAlphabets = $this->detectAlphabetsInText($text);
            $detectedAlphabets = array_merge($detectedAlphabets, $foundAlphabets);
        }

        return array_unique($detectedAlphabets);
    }

    /**
     * Extract text fields from request data
     * 
     * @param array $data The request data
     * @return array Array of text strings
     */
    protected function extractTextFields(array $data): array
    {
        $textFields = [];

        foreach ($data as $key => $value) {
            // Skip CAPTCHA-related fields
            if (str_starts_with($key, '_captcha') || str_starts_with($key, 'captcha')) {
                continue;
            }

            if (is_string($value) && !empty(trim($value))) {
                $textFields[] = $value;
            } elseif (is_array($value)) {
                // Recursively extract text from nested arrays
                $nestedTextFields = $this->extractTextFields($value);
                $textFields = array_merge($textFields, $nestedTextFields);
            }
        }

        return $textFields;
    }

    /**
     * Detect alphabets present in a text string
     * 
     * @param string $text The text to analyze
     * @return array Array of detected alphabets
     */
    protected function detectAlphabetsInText(string $text): array
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

    /**
     * Detect forbidden words present in the given data
     * 
     * @param array $data The data to analyze
     * @param array $forbiddenWords List of forbidden words/phrases
     * @return array Array of detected forbidden words
     */
    protected function detectForbiddenWordsInData(array $data, array $forbiddenWords): array
    {
        $detectedWords = [];
        $textFields = $this->extractTextFields($data);

        foreach ($textFields as $text) {
            $foundWords = $this->detectForbiddenWordsInText($text, $forbiddenWords);
            $detectedWords = array_merge($detectedWords, $foundWords);
        }

        return array_unique($detectedWords);
    }

    /**
     * Detect forbidden words present in a text string
     * 
     * @param string $text The text to analyze
     * @param array $forbiddenWords List of forbidden words/phrases
     * @return array Array of detected forbidden words
     */
    protected function detectForbiddenWordsInText(string $text, array $forbiddenWords): array
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
} 
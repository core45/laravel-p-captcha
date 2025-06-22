<?php

namespace Core45\LaravelPCaptcha\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PCaptchaService
{
    /**
     * Generate a new CAPTCHA challenge
     */
    public function generateChallenge(): array
    {
        $sessionId = Session::getId();
        $challengeId = Str::random(32);

        // Determine difficulty and type based on user history
        $difficulty = $this->calculateDifficulty($sessionId);
        $challengeType = $this->chooseChallengeType($sessionId);

        // Create base challenge structure
        $challenge = [
            'id' => $challengeId,
            'type' => $challengeType,
            'difficulty' => $difficulty,
            'created_at' => now()->toISOString(),
            'session_id' => $sessionId
        ];

        // Generate specific challenge data
        $challenge = array_merge($challenge, $this->generateChallengeData($challengeType));

        // Store in cache
        $ttl = config('p-captcha.cache.challenge_ttl', 600);
        Cache::put($this->getCacheKey('challenge', $challengeId), $challenge, $ttl);

        return $challenge;
    }

    /**
     * Generate challenge-specific data
     */
    protected function generateChallengeData(string $type): array
    {
        // Get available challenge types to validate
        $availableTypes = config('p-captcha.challenge_types', []);
        $validTypes = [];
        foreach ($availableTypes as $availableType) {
            if (is_string($availableType) && !empty(trim($availableType))) {
                $validTypes[] = trim($availableType);
            }
        }
        
        // If the requested type is not available, use the first available type
        if (!in_array($type, $validTypes)) {
            $type = !empty($validTypes) ? $validTypes[0] : 'beam_alignment';
        }
        
        switch ($type) {
            case 'beam_alignment':
                return $this->generateBeamAlignment();
            case 'sequence_complete':
                return $this->generateSequenceComplete();
            default:
                // Fallback to beam alignment if type is not supported
                return $this->generateBeamAlignment();
        }
    }

    /**
     * Generate beam alignment challenge
     */
    protected function generateBeamAlignment(): array
    {
        $config = config('p-captcha.ui.beam_alignment', []);
        $width = $config['canvas_width'] ?? 400;
        $height = $config['canvas_height'] ?? 300;
        $tolerance = $config['tolerance'] ?? 15;

        $sourceX = rand(50, 150);
        $sourceY = rand(100, $height - 100);
        $targetX = rand($width - 150, $width - 50);
        $targetY = rand(100, $height - 100);

        $correctOffsetX = $targetX - $sourceX;
        $correctOffsetY = $targetY - $sourceY;

        return [
            'challenge_data' => [
                'source' => ['x' => $sourceX, 'y' => $sourceY],
                'target' => ['x' => $targetX, 'y' => $targetY],
                'tolerance' => $tolerance,
                'canvas_width' => $width,
                'canvas_height' => $height
            ],
            'solution' => [
                'offset_x' => $correctOffsetX,
                'offset_y' => $correctOffsetY
            ],
            'instructions' => 'Align the beam source with the target by dragging the source to enable particle collision'
        ];
    }

    /**
     * Generate sequence completion challenge
     */
    protected function generateSequenceComplete(): array
    {
        $sequences = [
            // Simple arithmetic sequences (easy)
            ['type' => 'arithmetic', 'start' => 1, 'step' => 2, 'length' => 4],  // 1, 3, 5, 7
            ['type' => 'arithmetic', 'start' => 2, 'step' => 3, 'length' => 4],  // 2, 5, 8, 11
            ['type' => 'arithmetic', 'start' => 5, 'step' => 5, 'length' => 4],  // 5, 10, 15, 20
            ['type' => 'arithmetic', 'start' => 10, 'step' => 10, 'length' => 4], // 10, 20, 30, 40
            
            // Medium arithmetic sequences
            ['type' => 'arithmetic', 'start' => 1, 'step' => 4, 'length' => 4],  // 1, 5, 9, 13
            ['type' => 'arithmetic', 'start' => 3, 'step' => 7, 'length' => 4],  // 3, 10, 17, 24
            
            // Simple geometric sequences
            ['type' => 'geometric', 'start' => 2, 'ratio' => 2, 'length' => 4],  // 2, 4, 8, 16
            ['type' => 'geometric', 'start' => 3, 'ratio' => 2, 'length' => 4],  // 3, 6, 12, 24
            ['type' => 'geometric', 'start' => 1, 'ratio' => 3, 'length' => 4],  // 1, 3, 9, 27
        ];

        $seq = $sequences[array_rand($sequences)];
        $fullSequence = $this->generateSequence($seq);
        
        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Sequence generation details', [
                'sequence_config' => $seq,
                'full_sequence_before_pop' => $fullSequence,
                'sequence_length' => count($fullSequence)
            ]);
        }
        
        $correctAnswer = array_pop($fullSequence);
        $sequence = $fullSequence; // This is now the sequence without the answer

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Sequence after pop', [
                'correct_answer' => $correctAnswer,
                'sequence_after_pop' => $sequence,
                'sequence_length_after_pop' => count($sequence)
            ]);
        }

        // Generate wrong options (make them more realistic)
        $wrongOptions = [
            $correctAnswer + rand(1, 3),
            $correctAnswer - rand(1, 3),
            $correctAnswer + rand(5, 10),
            $correctAnswer - rand(5, 10)
        ];
        $choices = array_merge([$correctAnswer], array_slice($wrongOptions, 0, 3));
        shuffle($choices);

        // Generate helpful instruction based on sequence type
        $instruction = $this->generateSequenceInstruction($seq, $sequence);

        return [
            'challenge_data' => [
                'sequence' => $sequence,
                'choices' => $choices
            ],
            'solution' => $correctAnswer,
            'instructions' => $instruction
        ];
    }

    /**
     * Generate helpful instruction for sequence challenge
     */
    protected function generateSequenceInstruction(array $config, array $sequence): string
    {
        switch ($config['type']) {
            case 'arithmetic':
                $step = $config['step'];
                $lastNumber = end($sequence);
                $nextNumber = $lastNumber + $step;
                
                if ($step == 1) {
                    return "Add 1 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 2) {
                    return "Add 2 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 3) {
                    return "Add 3 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 4) {
                    return "Add 4 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 5) {
                    return "Add 5 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 7) {
                    return "Add 7 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 10) {
                    return "Add 10 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step > 0) {
                    return "Add {$step} to the last number ({$lastNumber}) to get the next number.";
                } else {
                    return "Subtract " . abs($step) . " from the last number ({$lastNumber}) to get the next number.";
                }
                
            case 'geometric':
                $ratio = $config['ratio'];
                $lastNumber = end($sequence);
                $nextNumber = $lastNumber * $ratio;
                
                if ($ratio == 2) {
                    return "Double the last number ({$lastNumber}) to get the next number.";
                } elseif ($ratio == 3) {
                    return "Triple the last number ({$lastNumber}) to get the next number.";
                } else {
                    return "Multiply the last number ({$lastNumber}) by {$ratio} to get the next number.";
                }
                
            default:
                return "Complete the sequence by selecting the next number.";
        }
    }

    /**
     * Generate mathematical sequence
     */
    protected function generateSequence(array $config): array
    {
        $sequence = [];

        switch ($config['type']) {
            case 'arithmetic':
                for ($i = 0; $i < $config['length']; $i++) {
                    $sequence[] = $config['start'] + ($i * $config['step']);
                }
                break;

            case 'geometric':
                for ($i = 0; $i < $config['length']; $i++) {
                    $sequence[] = $config['start'] * pow($config['ratio'], $i);
                }
                break;
        }

        return $sequence;
    }

    /**
     * Validate CAPTCHA solution
     */
    public function validateSolution(string $challengeId, array $solution, bool $consumeChallenge = true): bool
    {
        $challenge = Cache::get($this->getCacheKey('challenge', $challengeId));

        if (!$challenge) {
            return false; // Challenge expired or doesn't exist
        }

        $isValid = $this->validateSpecificChallenge($challenge, $solution);

        // Track result for adaptive difficulty
        $this->trackValidationResult($challenge['session_id'], $isValid, $challenge['type']);

        // Remove used challenge (single use) only if consumeChallenge is true
        if ($consumeChallenge && config('p-captcha.security.single_use_challenges', true)) {
            Cache::forget($this->getCacheKey('challenge', $challengeId));
        }

        return $isValid;
    }

    /**
     * Validate specific challenge type
     */
    protected function validateSpecificChallenge(array $challenge, array $solution): bool
    {
        switch ($challenge['type']) {
            case 'beam_alignment':
                return $this->validateBeamAlignment($challenge, $solution);
            case 'sequence_complete':
                return $this->validateSequenceComplete($challenge, $solution);
            default:
                return false;
        }
    }

    /**
     * Validate beam alignment solution
     */
    protected function validateBeamAlignment(array $challenge, array $solution): bool
    {
        if (!isset($challenge['solution'])) {
            return false;
        }

        $correctSolution = $challenge['solution'];
        $tolerance = $challenge['challenge_data']['tolerance'];

        $offsetX = $solution['offset_x'] ?? 0;
        $offsetY = $solution['offset_y'] ?? 0;

        $xDiff = abs($offsetX - $correctSolution['offset_x']);
        $yDiff = abs($offsetY - $correctSolution['offset_y']);

        return $xDiff <= $tolerance && $yDiff <= $tolerance;
    }

    /**
     * Validate sequence completion solution
     */
    protected function validateSequenceComplete(array $challenge, array $solution): bool
    {
        $correctAnswer = $challenge['solution'] ?? null;
        $userAnswer = $solution['answer'] ?? null;

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Sequence validation details', [
                'correct_answer' => $correctAnswer,
                'correct_answer_type' => gettype($correctAnswer),
                'user_answer' => $userAnswer,
                'user_answer_type' => gettype($userAnswer),
                'challenge_data' => $challenge['challenge_data'] ?? null,
                'solution_data' => $solution,
                'challenge_solution' => $challenge['solution'] ?? null
            ]);
        }

        // Handle type conversion for comparison
        if ($correctAnswer !== null && $userAnswer !== null) {
            // Convert both to the same type for comparison
            $correctAnswerInt = (int) $correctAnswer;
            $userAnswerInt = (int) $userAnswer;
            
            $isValid = $correctAnswerInt === $userAnswerInt;
            
            // Debug logging for result (only when APP_DEBUG is enabled)
            if (config('app.debug', false)) {
                \Log::info('P-CAPTCHA: Sequence validation result', [
                    'correct_answer_converted' => $correctAnswerInt,
                    'user_answer_converted' => $userAnswerInt,
                    'is_valid' => $isValid,
                    'comparison_type' => 'strict integer comparison'
                ]);
            }
            
            return $isValid;
        }

        // Debug logging for null values (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Sequence validation failed - null values', [
                'correct_answer_null' => $correctAnswer === null,
                'user_answer_null' => $userAnswer === null
            ]);
        }

        return false;
    }

    /**
     * Calculate difficulty based on user failures
     */
    protected function calculateDifficulty(string $sessionId): string
    {
        $failures = Cache::get($this->getCacheKey('failures', $sessionId), 0);
        $thresholds = config('p-captcha.adaptive_difficulty.failure_thresholds');

        if ($failures >= $thresholds['extreme']) {
            return 'extreme';
        } elseif ($failures >= $thresholds['hard']) {
            return 'hard';
        } elseif ($failures >= $thresholds['medium']) {
            return 'medium';
        }

        return 'easy';
    }

    /**
     * Choose challenge type based on user history
     */
    protected function chooseChallengeType(string $sessionId): string
    {
        $visualFailures = Cache::get($this->getCacheKey('visual_failures', $sessionId), 0);
        $forceComputationalThreshold = config('p-captcha.adaptive_difficulty.force_computational_after_visual_failures', 3);

        // Force computational if too many visual failures
        if ($visualFailures >= $forceComputationalThreshold) {
            return 'proof_of_work';
        }

        // Get available challenge types from config
        $allChallengeTypes = config('p-captcha.challenge_types', []);
        
        // Filter out any disabled or invalid types
        $availableTypes = [];
        foreach ($allChallengeTypes as $type) {
            if (is_string($type) && !empty(trim($type))) {
                $availableTypes[] = trim($type);
            }
        }
        
        // If no valid types are available, fallback to beam_alignment
        if (empty($availableTypes)) {
            return 'beam_alignment';
        }
        
        // Remove proof_of_work from visual types if it exists
        $visualTypes = array_diff($availableTypes, ['proof_of_work']);
        
        // If no visual types available, use the first available type
        if (empty($visualTypes)) {
            return $availableTypes[0];
        }

        // Normal distribution
        $visualPercentage = config('p-captcha.visual_challenge_percentage', 70);

        if (rand(1, 100) <= $visualPercentage) {
            return $visualTypes[array_rand($visualTypes)];
        }

        // If proof_of_work is available, use it, otherwise fallback to visual
        return in_array('proof_of_work', $availableTypes) ? 'proof_of_work' : $visualTypes[array_rand($visualTypes)];
    }

    /**
     * Track validation result for adaptive difficulty
     */
    protected function trackValidationResult(string $sessionId, bool $isValid, string $challengeType): void
    {
        $ttl = config('p-captcha.cache.failure_tracking_ttl', 3600);

        if (!$isValid) {
            // Increment total failures
            $failures = Cache::get($this->getCacheKey('failures', $sessionId), 0);
            Cache::put($this->getCacheKey('failures', $sessionId), $failures + 1, $ttl);
        } else {
            // Reset counters on success
            Cache::forget($this->getCacheKey('failures', $sessionId));
        }
    }

    /**
     * Get frontend-safe challenge data (removes solutions)
     */
    public function getChallengeForFrontend(string $challengeId): ?array
    {
        $challenge = Cache::get($this->getCacheKey('challenge', $challengeId));

        if (!$challenge) {
            return null;
        }

        // Create safe version for frontend
        $frontendChallenge = [
            'id' => $challenge['id'],
            'type' => $challenge['type'],
            'instructions' => $challenge['instructions'],
            'challenge_data' => $challenge['challenge_data']
        ];

        // Remove sensitive data
        if (isset($challenge['solution'])) {
            unset($frontendChallenge['solution']);
        }

        return $frontendChallenge;
    }

    /**
     * Render CAPTCHA HTML for Blade directive
     */
    public function renderCaptcha(string $options = ''): string
    {
        $optionsArray = $this->parseOptions($options);

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Options being passed to view', $optionsArray);
        }

        return view('p-captcha::captcha', [
            'options' => $optionsArray,
            'config' => config('p-captcha')
        ])->render();
    }

    /**
     * Parse options for Blade directive
     */
    protected function parseOptions(string $options): array
    {
        // All options are now controlled via config file
        // Parameters passed to @pcaptcha directive are ignored
        
        $theme = config('p-captcha.ui.theme', 'dark');
        $forceVisualCaptcha = config('p-captcha.force_visual_captcha', false);
        
        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Parsing options from config', [
                'theme' => $theme,
                'force_visual_captcha' => $forceVisualCaptcha,
                'force_visual_captcha_type' => gettype($forceVisualCaptcha),
                'force_visual_captcha_bool' => (bool) $forceVisualCaptcha,
                'config_path' => 'p-captcha'
            ]);
        }
        
        $result = [
            'id' => 'p-captcha-' . Str::random(8),
            'theme' => $theme,
            'auto_load' => (bool) $forceVisualCaptcha // Explicit boolean conversion
        ];
        
        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Final options array', $result);
        }
        
        return $result;
    }

    /**
     * Generate cache key with prefix
     */
    protected function getCacheKey(string $type, string $identifier): string
    {
        $prefix = config('p-captcha.cache.prefix', 'p_captcha:');
        return $prefix . $type . ':' . $identifier;
    }

    /**
     * Check if the request contains forbidden alphabets
     * 
     * @param array $requestData The request data to check
     * @return array Array with 'forbidden_detected' => bool and 'detected_alphabets' => array
     */
    public function checkAlphabetRestrictions(array $requestData): array
    {
        $allowedAlphabets = config('p-captcha.allowed_alphabet', []);
        $detectedAlphabets = $this->detectAlphabetsInData($requestData);
        $forbiddenDetected = false;

        foreach ($detectedAlphabets as $alphabet) {
            if (isset($allowedAlphabets[$alphabet]) && !$allowedAlphabets[$alphabet]) {
                $forbiddenDetected = true;
                break;
            }
        }

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Alphabet check result', [
                'detected_alphabets' => $detectedAlphabets,
                'forbidden_detected' => $forbiddenDetected,
                'allowed_alphabets' => $allowedAlphabets
            ]);
        }

        return [
            'forbidden_detected' => $forbiddenDetected,
            'detected_alphabets' => $detectedAlphabets
        ];
    }

    /**
     * Detect alphabets present in the given data
     * 
     * @param array $data The data to analyze
     * @return array Array of detected alphabet names
     */
    protected function detectAlphabetsInData(array $data): array
    {
        $detectedAlphabets = [];
        $textFields = $this->extractTextFields($data);

        foreach ($textFields as $text) {
            $alphabets = $this->detectAlphabetsInText($text);
            $detectedAlphabets = array_merge($detectedAlphabets, $alphabets);
        }

        return array_unique($detectedAlphabets);
    }

    /**
     * Extract all text fields from nested data array
     * 
     * @param array $data The data array
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
                $textFields = array_merge($textFields, $this->extractTextFields($value));
            }
        }

        return $textFields;
    }

    /**
     * Detect alphabets present in a text string
     * 
     * @param string $text The text to analyze
     * @return array Array of detected alphabet names
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
        // This covers: Hebrew, Greek, Telugu, Kannada, Malayalam, Gujarati, Punjabi, Odia, Sinhala, Khmer, Lao, Myanmar, Ethiopic, Armenian, Georgian, Mongolian, Tibetan, and any other Unicode scripts
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
            // General Unicode scripts (catch-all for any other scripts)
            '/[\x{2000}-\x{206F}]/u', // General Punctuation
            '/[\x{2100}-\x{214F}]/u', // Letterlike Symbols
            '/[\x{2200}-\x{22FF}]/u', // Mathematical Operators
            '/[\x{2300}-\x{23FF}]/u', // Miscellaneous Technical
            '/[\x{2400}-\x{243F}]/u', // Control Pictures
            '/[\x{2440}-\x{245F}]/u', // Optical Character Recognition
            '/[\x{2460}-\x{24FF}]/u', // Enclosed Alphanumerics
            '/[\x{2500}-\x{257F}]/u', // Box Drawing
            '/[\x{2580}-\x{259F}]/u', // Block Elements
            '/[\x{25A0}-\x{25FF}]/u', // Geometric Shapes
            '/[\x{2600}-\x{26FF}]/u', // Miscellaneous Symbols
            '/[\x{2700}-\x{27BF}]/u', // Dingbats
            '/[\x{2800}-\x{28FF}]/u', // Braille Patterns
            '/[\x{2900}-\x{297F}]/u', // Supplemental Arrows-B
            '/[\x{2980}-\x{29FF}]/u', // Miscellaneous Mathematical Symbols-B
            '/[\x{2A00}-\x{2AFF}]/u', // Supplemental Mathematical Operators
            '/[\x{2B00}-\x{2BFF}]/u', // Miscellaneous Symbols and Arrows
            '/[\x{2C00}-\x{2C5F}]/u', // Glagolitic
            '/[\x{2C60}-\x{2C7F}]/u', // Latin Extended-C
            '/[\x{2C80}-\x{2CFF}]/u', // Coptic
            '/[\x{2D00}-\x{2D2F}]/u', // Georgian Supplement
            '/[\x{2D30}-\x{2D7F}]/u', // Tifinagh
            '/[\x{2D80}-\x{2DDF}]/u', // Ethiopic Extended
            '/[\x{2DE0}-\x{2DFF}]/u', // Cyrillic Extended-A
            '/[\x{2E00}-\x{2E7F}]/u', // Supplemental Punctuation
            '/[\x{2E80}-\x{2EFF}]/u', // CJK Radicals Supplement
            '/[\x{2F00}-\x{2FDF}]/u', // Kangxi Radicals
            '/[\x{2FF0}-\x{2FFF}]/u', // Ideographic Description Characters
            '/[\x{3000}-\x{303F}]/u', // CJK Symbols and Punctuation
            '/[\x{3040}-\x{309F}]/u', // Hiragana
            '/[\x{30A0}-\x{30FF}]/u', // Katakana
            '/[\x{3100}-\x{312F}]/u', // Bopomofo
            '/[\x{3130}-\x{318F}]/u', // Hangul Compatibility Jamo
            '/[\x{3190}-\x{319F}]/u', // Kanbun
            '/[\x{31A0}-\x{31BF}]/u', // Bopomofo Extended
            '/[\x{31C0}-\x{31EF}]/u', // CJK Strokes
            '/[\x{31F0}-\x{31FF}]/u', // Katakana Phonetic Extensions
            '/[\x{3200}-\x{32FF}]/u', // Enclosed CJK Letters and Months
            '/[\x{3300}-\x{33FF}]/u', // CJK Compatibility
            '/[\x{3400}-\x{4DBF}]/u', // CJK Unified Ideographs Extension A
            '/[\x{4DC0}-\x{4DFF}]/u', // Yijing Hexagram Symbols
            '/[\x{4E00}-\x{9FFF}]/u', // CJK Unified Ideographs
            '/[\x{A000}-\x{A48F}]/u', // Yi Syllables
            '/[\x{A490}-\x{A4CF}]/u', // Yi Radicals
            '/[\x{A4D0}-\x{A4FF}]/u', // Lisu
            '/[\x{A500}-\x{A63F}]/u', // Vai
            '/[\x{A640}-\x{A69F}]/u', // Cyrillic Extended-B
            '/[\x{A6A0}-\x{A6FF}]/u', // Bamum
            '/[\x{A700}-\x{A71F}]/u', // Modifier Tone Letters
            '/[\x{A720}-\x{A7FF}]/u', // Latin Extended-D
            '/[\x{A800}-\x{A82F}]/u', // Syloti Nagri
            '/[\x{A830}-\x{A83F}]/u', // Common Indic Number Forms
            '/[\x{A840}-\x{A87F}]/u', // Phags-pa
            '/[\x{A880}-\x{A8DF}]/u', // Saurashtra
            '/[\x{A8E0}-\x{A8FF}]/u', // Devanagari Extended
            '/[\x{A900}-\x{A92F}]/u', // Kayah Li
            '/[\x{A930}-\x{A95F}]/u', // Rejang
            '/[\x{A960}-\x{A97F}]/u', // Hangul Jamo Extended-A
            '/[\x{A980}-\x{A9DF}]/u', // Javanese
            '/[\x{A9E0}-\x{A9FF}]/u', // Myanmar Extended-B
            '/[\x{AA00}-\x{AA5F}]/u', // Cham
            '/[\x{AA60}-\x{AA7F}]/u', // Myanmar Extended-A
            '/[\x{AA80}-\x{AADF}]/u', // Tai Viet
            '/[\x{AAE0}-\x{AAFF}]/u', // Meetei Mayek Extensions
            '/[\x{AB00}-\x{AB2F}]/u', // Ethiopic Extended-A
            '/[\x{AB30}-\x{AB6F}]/u', // Latin Extended-E
            '/[\x{AB70}-\x{ABBF}]/u', // Cherokee Supplement
            '/[\x{ABC0}-\x{ABFF}]/u', // Meetei Mayek
            '/[\x{AC00}-\x{D7AF}]/u', // Hangul Syllables
            '/[\x{D7B0}-\x{D7FF}]/u', // Hangul Jamo Extended-B
            '/[\x{D800}-\x{DB7F}]/u', // High Surrogates
            '/[\x{DB80}-\x{DBFF}]/u', // High Private Use Surrogates
            '/[\x{DC00}-\x{DFFF}]/u', // Low Surrogates
            '/[\x{E000}-\x{F8FF}]/u', // Private Use Area
            '/[\x{F900}-\x{FAFF}]/u', // CJK Compatibility Ideographs
            '/[\x{FB00}-\x{FB4F}]/u', // Alphabetic Presentation Forms
            '/[\x{FB50}-\x{FDFF}]/u', // Arabic Presentation Forms-A
            '/[\x{FE00}-\x{FE0F}]/u', // Variation Selectors
            '/[\x{FE10}-\x{FE1F}]/u', // Vertical Forms
            '/[\x{FE20}-\x{FE2F}]/u', // Combining Half Marks
            '/[\x{FE30}-\x{FE4F}]/u', // CJK Compatibility Forms
            '/[\x{FE50}-\x{FE6F}]/u', // Small Form Variants
            '/[\x{FE70}-\x{FEFF}]/u', // Arabic Presentation Forms-B
            '/[\x{FF00}-\x{FFEF}]/u', // Halfwidth and Fullwidth Forms
            '/[\x{FFF0}-\x{FFFF}]/u', // Specials
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
}

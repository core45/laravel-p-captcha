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
        $autoLoad = config('p-captcha.ui.auto_load', false);
        
        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Parsing options from config', [
                'theme' => $theme,
                'auto_load' => $autoLoad,
                'config_path' => 'p-captcha.ui'
            ]);
        }
        
        return [
            'id' => 'p-captcha-' . Str::random(8),
            'theme' => $theme,
            'auto_load' => $autoLoad
        ];
    }

    /**
     * Generate cache key with prefix
     */
    protected function getCacheKey(string $type, string $identifier): string
    {
        $prefix = config('p-captcha.cache.prefix', 'p_captcha:');
        return $prefix . $type . ':' . $identifier;
    }
}

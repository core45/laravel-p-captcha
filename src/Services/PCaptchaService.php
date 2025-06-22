<?php

namespace Core45\LaravelPCaptcha\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PCaptchaService
{
    protected LanguageDetectionService $languageDetectionService;

    public function __construct(LanguageDetectionService $languageDetectionService)
    {
        $this->languageDetectionService = $languageDetectionService;
    }

    /**
     * Generate a new CAPTCHA challenge
     */
    public function generateChallenge(): array
    {
        $sessionId = session()->getId();
        $challengeId = $this->generateChallengeId();
        $difficulty = $this->calculateDifficulty($sessionId);
        $challengeType = $this->chooseChallengeType($sessionId);

        $challengeData = $this->generateChallengeData($challengeType);
        $solution = $this->generateSolution($challengeType, $challengeData);

        $challenge = [
            'id' => $challengeId,
            'type' => $challengeType,
            'difficulty' => $difficulty,
            'challenge_data' => $challengeData,
            'solution' => $solution,
            'session_id' => $sessionId,
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(config('p-captcha.security.challenge_timeout', 10))->timestamp,
        ];

        // Store challenge in cache
        Cache::put(
            $this->getCacheKey('challenge', $challengeId),
            $challenge,
            config('p-captcha.security.challenge_timeout', 10) * 60
        );

        return $challenge;
    }

    /**
     * Generate challenge data based on type
     */
    protected function generateChallengeData(string $type): array
    {
        switch ($type) {
            case 'beam_alignment':
                return $this->generateBeamAlignment();
            case 'sequence_complete':
                return $this->generateSequenceComplete();
            default:
                return $this->generateBeamAlignment(); // Default fallback
        }
    }

    /**
     * Generate beam alignment challenge
     */
    protected function generateBeamAlignment(): array
    {
        $config = config('p-captcha.beam_alignment', []);
        $tolerance = $config['tolerance'] ?? 20;
        $gridSize = $config['grid_size'] ?? 300;
        $beamSize = $config['beam_size'] ?? 40;

        // Generate random positions for source and target
        $sourceX = rand($beamSize, $gridSize - $beamSize);
        $sourceY = rand($beamSize, $gridSize - $beamSize);
        $targetX = rand($beamSize, $gridSize - $beamSize);
        $targetY = rand($beamSize, $gridSize - $beamSize);

        return [
            'grid_size' => $gridSize,
            'beam_size' => $beamSize,
            'tolerance' => $tolerance,
            'source_x' => $sourceX,
            'source_y' => $sourceY,
            'target_x' => $targetX,
            'target_y' => $targetY,
        ];
    }

    /**
     * Generate sequence completion challenge
     */
    protected function generateSequenceComplete(): array
    {
        $config = config('p-captcha.sequence_complete', []);
        
        $sequence = $this->generateSequence($config);
        $instruction = $this->generateSequenceInstruction($config, $sequence);
        
        // Remove the last number to create the challenge
        $challengeSequence = array_slice($sequence, 0, -1);
        $correctAnswer = end($sequence);

        return [
            'sequence' => $challengeSequence,
            'instruction' => $instruction,
            'correct_answer' => $correctAnswer,
            'sequence_type' => $config['type'] ?? 'arithmetic',
        ];
    }

    /**
     * Generate solution for challenge
     */
    protected function generateSolution(string $type, array $challengeData): array
    {
        switch ($type) {
            case 'beam_alignment':
                return [
                    'offset_x' => $challengeData['target_x'] - $challengeData['source_x'],
                    'offset_y' => $challengeData['target_y'] - $challengeData['source_y'],
                ];
            case 'sequence_complete':
                return $challengeData['correct_answer'];
            default:
                return [];
        }
    }

    /**
     * Generate instruction for sequence completion
     */
    protected function generateSequenceInstruction(array $config, array $sequence): string
    {
        switch ($config['type'] ?? 'arithmetic') {
            case 'arithmetic':
                $step = $config['step'] ?? 1;
                $lastNumber = end($sequence);
                
                if ($step == 1) {
                    return "Add 1 to the last number ({$lastNumber}) to get the next number.";
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
                $availableTypes[] = $type;
            }
        }

        // If no valid types found, use default
        if (empty($availableTypes)) {
            $availableTypes = ['beam_alignment', 'sequence_complete'];
        }

        // Randomly select a challenge type
        return $availableTypes[array_rand($availableTypes)];
    }

    /**
     * Track validation result for adaptive difficulty
     */
    protected function trackValidationResult(string $sessionId, bool $isValid, string $challengeType): void
    {
        if (!$isValid) {
            // Increment failure counter
            $failures = Cache::get($this->getCacheKey('failures', $sessionId), 0);
            Cache::put($this->getCacheKey('failures', $sessionId), $failures + 1, 3600); // 1 hour

            // Track visual failures separately
            if (in_array($challengeType, ['beam_alignment', 'sequence_complete'])) {
                $visualFailures = Cache::get($this->getCacheKey('visual_failures', $sessionId), 0);
                Cache::put($this->getCacheKey('visual_failures', $sessionId), $visualFailures + 1, 3600);
            }
        } else {
            // Reset failure counters on success
            Cache::forget($this->getCacheKey('failures', $sessionId));
            Cache::forget($this->getCacheKey('visual_failures', $sessionId));
        }
    }

    /**
     * Get challenge data for frontend
     */
    public function getChallengeForFrontend(string $challengeId): ?array
    {
        $challenge = Cache::get($this->getCacheKey('challenge', $challengeId));

        if (!$challenge) {
            return null;
        }

        // Return only the data needed for frontend (exclude solution)
        return [
            'id' => $challenge['id'],
            'type' => $challenge['type'],
            'difficulty' => $challenge['difficulty'],
            'challenge_data' => $challenge['challenge_data'],
            'expires_at' => $challenge['expires_at'],
        ];
    }

    /**
     * Render CAPTCHA HTML
     */
    public function renderCaptcha(string $options = ''): string
    {
        $parsedOptions = $this->parseOptions($options);
        $challenge = $this->generateChallenge();

        $html = '<div class="p-captcha-container" data-challenge-id="' . $challenge['id'] . '">';
        $html .= '<input type="hidden" name="_captcha_token" value="' . $challenge['id'] . '">';
        
        // Add challenge-specific HTML
        switch ($challenge['type']) {
            case 'beam_alignment':
                $html .= $this->renderBeamAlignmentChallenge($challenge);
                break;
            case 'sequence_complete':
                $html .= $this->renderSequenceChallenge($challenge);
                break;
            default:
                $html .= '<p>Challenge type not supported</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Parse options string
     */
    protected function parseOptions(string $options): array
    {
        $parsed = [];
        
        if (empty($options)) {
            return $parsed;
        }

        $pairs = explode(' ', $options);
        foreach ($pairs as $pair) {
            if (strpos($pair, '=') !== false) {
                [$key, $value] = explode('=', $pair, 2);
                $parsed[$key] = $value;
            }
        }

        return $parsed;
    }

    /**
     * Render beam alignment challenge HTML
     */
    protected function renderBeamAlignmentChallenge(array $challenge): string
    {
        $data = $challenge['challenge_data'];
        
        $html = '<div class="beam-alignment-challenge">';
        $html .= '<p>Drag the beam source to align with the target</p>';
        $html .= '<div class="beam-grid" style="width: ' . $data['grid_size'] . 'px; height: ' . $data['grid_size'] . 'px;">';
        $html .= '<div class="beam-source" style="left: ' . $data['source_x'] . 'px; top: ' . $data['source_y'] . 'px;"></div>';
        $html .= '<div class="beam-target" style="left: ' . $data['target_x'] . 'px; top: ' . $data['target_y'] . 'px;"></div>';
        $html .= '</div>';
        $html .= '<input type="hidden" name="p_captcha_solution" value="">';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render sequence challenge HTML
     */
    protected function renderSequenceChallenge(array $challenge): string
    {
        $data = $challenge['challenge_data'];
        
        $html = '<div class="sequence-challenge">';
        $html .= '<p>' . htmlspecialchars($data['instruction']) . '</p>';
        $html .= '<div class="sequence-display">';
        $html .= implode(' → ', $data['sequence']) . ' → <input type="number" name="p_captcha_solution" required>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, string $identifier): string
    {
        return 'p_captcha_' . $type . '_' . $identifier;
    }

    /**
     * Generate unique challenge ID
     */
    protected function generateChallengeId(): string
    {
        return 'challenge_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Check alphabet restrictions using LanguageDetectionService
     */
    public function checkAlphabetRestrictions(array $requestData): array
    {
        return $this->languageDetectionService->checkAlphabetRestrictions($requestData);
    }

    /**
     * Check forbidden words using LanguageDetectionService
     */
    public function checkForbiddenWords(array $requestData): array
    {
        return $this->languageDetectionService->checkForbiddenWords($requestData);
    }
}

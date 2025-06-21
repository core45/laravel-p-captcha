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
        $challenge = array_merge($challenge, $this->generateChallengeData($challengeType, $difficulty));

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
        switch ($type) {
            case 'beam_alignment':
                return $this->generateBeamAlignment();
            case 'sequence_complete':
                return $this->generateSequenceComplete();
            default:
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
            ['type' => 'arithmetic', 'start' => 2, 'step' => 3, 'length' => 5],
            ['type' => 'arithmetic', 'start' => 5, 'step' => 7, 'length' => 5],
            ['type' => 'arithmetic', 'start' => 1, 'step' => 4, 'length' => 5],
            ['type' => 'geometric', 'start' => 2, 'ratio' => 2, 'length' => 5],
            ['type' => 'geometric', 'start' => 3, 'ratio' => 3, 'length' => 4],
        ];

        $seq = $sequences[array_rand($sequences)];
        $sequence = $this->generateSequence($seq);
        $correctAnswer = array_pop($sequence);

        // Generate wrong options
        $wrongOptions = [
            $correctAnswer + rand(1, 5),
            $correctAnswer - rand(1, 5),
            $correctAnswer * 2,
            $correctAnswer + 10
        ];
        $choices = array_merge([$correctAnswer], array_slice($wrongOptions, 0, 3));
        shuffle($choices);

        return [
            'challenge_data' => [
                'sequence' => $sequence,
                'choices' => $choices
            ],
            'solution' => $correctAnswer,
            'instructions' => 'Complete the sequence by selecting the next number'
        ];
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
    public function validateSolution(string $challengeId, array $solution): bool
    {
        $challenge = Cache::get($this->getCacheKey('challenge', $challengeId));

        if (!$challenge) {
            return false; // Challenge expired or doesn't exist
        }

        $isValid = $this->validateSpecificChallenge($challenge, $solution);

        // Track result for adaptive difficulty
        $this->trackValidationResult($challenge['session_id'], $isValid, $challenge['type']);

        // Remove used challenge (single use)
        if (config('p-captcha.security.single_use_challenges', true)) {
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

        return $correctAnswer === $userAnswer;
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

        // Normal distribution
        $visualPercentage = config('p-captcha.visual_challenge_percentage', 70);
        $challengeTypes = config('p-captcha.challenge_types');
        $visualTypes = array_diff($challengeTypes, ['proof_of_work']);

        if (rand(1, 100) <= $visualPercentage) {
            return $visualTypes[array_rand($visualTypes)];
        }

        return 'proof_of_work';
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
        $defaults = [
            'id' => 'p-captcha-' . Str::random(8),
            'theme' => config('p-captcha.ui.theme', 'dark'),
            'auto_load' => true
        ];

        if (empty($options)) {
            return $defaults;
        }

        // Parse simple key=value pairs
        $parsed = [];
        $pairs = explode(',', $options);

        foreach ($pairs as $pair) {
            if (strpos($pair, '=') !== false) {
                [$key, $value] = explode('=', trim($pair), 2);
                $parsed[trim($key)] = trim($value, '\'"');
            }
        }

        return array_merge($defaults, $parsed);
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

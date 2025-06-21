<?php

namespace Core45\LaravelPCaptcha\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Core45\LaravelPCaptcha\Services\PCaptchaService;
use Core45\LaravelPCaptcha\Middleware\ProtectWithPCaptcha;

/**
 * Helper trait for controllers that need to work with P-CAPTCHA
 *
 * This trait provides convenient methods for:
 * - Checking if CAPTCHA is required
 * - Manually validating CAPTCHA
 * - Resetting attempt counters
 * - Getting CAPTCHA status
 */
trait HandlesPCaptcha
{
    /**
     * Check if CAPTCHA is currently required for this session
     */
    protected function isCaptchaRequired(): bool
    {
        $sessionId = Session::getId();
        $attemptCount = Cache::get("form_attempts:{$sessionId}", 0);
        $threshold = config('p-captcha.ui.auto_show_after_attempts', 2);

        return $attemptCount >= $threshold;
    }

    /**
     * Get the current attempt count for this session
     */
    protected function getCaptchaAttemptCount(): int
    {
        $sessionId = Session::getId();
        return Cache::get("form_attempts:{$sessionId}", 0);
    }

    /**
     * Manually validate CAPTCHA from request
     *
     * @param Request $request
     * @return bool True if CAPTCHA is valid or not required
     */
    protected function validateCaptcha(Request $request): bool
    {
        // If CAPTCHA is not required, return true
        if (!$this->isCaptchaRequired()) {
            return true;
        }

        // Check if CAPTCHA data is present
        if (!$request->has('p_captcha_id') || !$request->has('p_captcha_solution')) {
            return false;
        }

        $service = app(PCaptchaService::class);
        $challengeId = $request->input('p_captcha_id');
        $solution = $request->input('p_captcha_solution', []);

        // Ensure solution is an array
        if (!is_array($solution)) {
            $solution = json_decode($solution, true) ?? [];
        }

        return $service->validateSolution($challengeId, $solution);
    }

    /**
     * Reset the CAPTCHA attempt counter (call after successful form submission)
     */
    protected function resetCaptchaAttempts(): void
    {
        ProtectWithPCaptcha::resetAttemptCount();
    }

    /**
     * Increment the CAPTCHA attempt counter (call after failed form submission)
     */
    protected function incrementCaptchaAttempts(): void
    {
        $sessionId = Session::getId();
        $cacheKey = "form_attempts:{$sessionId}";
        $currentCount = Cache::get($cacheKey, 0);

        Cache::put($cacheKey, $currentCount + 1, now()->addHour());
    }

    /**
     * Get CAPTCHA status information for the view
     *
     * @return array
     */
    protected function getCaptchaStatus(): array
    {
        return [
            'required' => $this->isCaptchaRequired(),
            'attempt_count' => $this->getCaptchaAttemptCount(),
            'threshold' => config('p-captcha.ui.auto_show_after_attempts', 2),
            'attempts_remaining' => max(0, config('p-captcha.ui.auto_show_after_attempts', 2) - $this->getCaptchaAttemptCount())
        ];
    }

    /**
     * Add CAPTCHA status to view data
     *
     * @param array $data
     * @return array
     */
    protected function withCaptchaStatus(array $data = []): array
    {
        return array_merge($data, [
            'captcha_status' => $this->getCaptchaStatus()
        ]);
    }

    /**
     * Generate a new CAPTCHA challenge manually
     *
     * @return array
     */
    protected function generateCaptchaChallenge(): array
    {
        $service = app(PCaptchaService::class);
        $challenge = $service->generateChallenge();

        return $service->getChallengeForFrontend($challenge['id']);
    }

    /**
     * Check if the current session has failed CAPTCHA challenges recently
     */
    protected function hasRecentCaptchaFailures(): bool
    {
        $sessionId = Session::getId();
        return Cache::has("p_captcha:failures:{$sessionId}");
    }

    /**
     * Get the current CAPTCHA difficulty level for this session
     */
    protected function getCaptchaDifficulty(): string
    {
        $sessionId = Session::getId();
        $failures = Cache::get("p_captcha:failures:{$sessionId}", 0);
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
     * Create a response with CAPTCHA error
     *
     * @param Request $request
     * @param string $message
     * @return \Illuminate\Http\Response
     */
    protected function captchaErrorResponse(Request $request, string $message = 'CAPTCHA verification failed')
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'captcha_required' => true,
                'captcha_status' => $this->getCaptchaStatus(),
                'errors' => [
                    'p_captcha' => [$message]
                ]
            ], 422);
        }

        return back()
            ->withErrors(['p_captcha' => $message])
            ->withInput($request->except(['p_captcha_id', 'p_captcha_solution']))
            ->with('captcha_status', $this->getCaptchaStatus());
    }

    /**
     * Create a successful response after CAPTCHA validation
     *
     * @param Request $request
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    protected function captchaSuccessResponse(Request $request, array $data = [])
    {
        // Reset attempt counter on success
        $this->resetCaptchaAttempts();

        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'success' => true,
                'message' => 'Form submitted successfully'
            ], $data));
        }

        return back()->with('success', 'Form submitted successfully');
    }

    /**
     * Validate request with CAPTCHA check
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @return array Validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateWithCaptcha(Request $request, array $rules, array $messages = []): array
    {
        // Add CAPTCHA validation rules if required
        if ($this->isCaptchaRequired()) {
            $rules['p_captcha_id'] = 'required|string';
            $rules['p_captcha_solution'] = 'required|array';

            $messages['p_captcha_id.required'] = 'CAPTCHA verification is required.';
            $messages['p_captcha_solution.required'] = 'CAPTCHA solution is required.';
        }

        $validated = $request->validate($rules, $messages);

        // Validate CAPTCHA if required
        if ($this->isCaptchaRequired()) {
            if (!$this->validateCaptcha($request)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'p_captcha' => 'CAPTCHA verification failed. Please try again.'
                ]);
            }
        }

        return $validated;
    }

    /**
     * Handle form submission with automatic CAPTCHA checking
     *
     * @param Request $request
     * @param callable $processCallback Callback to process the validated data
     * @param array $validationRules
     * @param array $validationMessages
     * @return \Illuminate\Http\Response
     */
    protected function handleFormWithCaptcha(
        Request $request,
        callable $processCallback,
        array $validationRules,
        array $validationMessages = []
    ) {
        try {
            // Validate request including CAPTCHA if required
            $validated = $this->validateWithCaptcha($request, $validationRules, $validationMessages);

            // Remove CAPTCHA data from validated data before processing
            unset($validated['p_captcha_id'], $validated['p_captcha_solution']);

            // Process the form
            $result = $processCallback($validated);

            // Return success response
            return $this->captchaSuccessResponse($request, is_array($result) ? $result : []);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Increment attempt counter on validation failure
            $this->incrementCaptchaAttempts();
            throw $e;
        } catch (\Exception $e) {
            // Increment attempt counter on any other failure
            $this->incrementCaptchaAttempts();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your request.',
                    'captcha_status' => $this->getCaptchaStatus()
                ], 500);
            }

            return back()
                ->withErrors(['general' => 'An error occurred while processing your request.'])
                ->withInput($request->except(['p_captcha_id', 'p_captcha_solution']))
                ->with('captcha_status', $this->getCaptchaStatus());
        }
    }
}

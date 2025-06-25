<?php

namespace Core45\LaravelPCaptcha\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Core45\LaravelPCaptcha\Services\PCaptchaService;

class ProtectWithPCaptcha
{
    protected PCaptchaService $captchaService;

    public function __construct(PCaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CAPTCHA validation for GET requests
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Check for suspicious words first (this will set session if detected)
        $suspiciousWordsDetected = $this->checkSuspiciousWords($request);
        
        // Determine if visual CAPTCHA is required
        $visualCaptchaRequired = $this->isVisualCaptchaRequired($request);

        if ($visualCaptchaRequired) {
            // Clear any existing CAPTCHA token to force new validation
            $request->session()->forget('p_captcha_token');
            
            if (config('app.debug', false)) {
                \Log::info('P-CAPTCHA: Visual CAPTCHA required', [
                    'suspicious_words_detected' => $suspiciousWordsDetected,
                    'bot_detected' => $this->detectBotBehavior($request),
                    'force_visual_config' => config('p-captcha.force_visual_captcha', false),
                    'force_visual_session' => session('p-captcha.force_visual_captcha', false)
                ]);
            }
        }

        // First check for bot behavior using hidden validation
        $botDetected = $this->detectBotBehavior($request);

        // Debug logging (only when APP_DEBUG is enabled)
        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: Middleware decision', [
                'bot_detected' => $botDetected,
                'suspicious_words_detected' => $suspiciousWordsDetected,
                'visual_captcha_required' => $visualCaptchaRequired,
                'force_visual_captcha' => config('p-captcha.force_visual_captcha', false),
                'has_hidden_data' => $this->hasHiddenCaptchaData($request),
                'has_visual_data' => $this->hasVisualCaptchaData($request),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        // If no CAPTCHA is required and no bot detected, allow through
        if (!$visualCaptchaRequired && !$botDetected) {
            // Normal user with no suspicious behavior - allow through
            return $next($request);
        }

        // If bot detected but no visual CAPTCHA provided, require it immediately
        if ($botDetected && !$this->hasVisualCaptchaData($request)) {
            return $this->requireVisualCaptcha($request, __('p-captcha::p-captcha.suspicious_activity_detected'));
        }

        // If bot detected and visual CAPTCHA data is provided, validate it
        if ($botDetected && $this->hasVisualCaptchaData($request)) {
            $isValid = $this->validateVisualCaptcha($request);
            if ($isValid) {
                // CAPTCHA passed, continue with request
                return $next($request);
            } else {
                // CAPTCHA failed
                return $this->handleCaptchaFailure($request);
            }
        }

        // Handle cases where visual CAPTCHA is required but not due to bot detection
        if ($visualCaptchaRequired && !$botDetected) {
            if ($this->hasVisualCaptchaData($request)) {
                $isValid = $this->validateVisualCaptcha($request);
                if ($isValid) {
                    return $next($request);
                } else {
                    return $this->handleCaptchaFailure($request);
                }
            } else {
                return $this->requireVisualCaptcha($request, __('p-captcha::p-captcha.please_complete_verification_challenge'));
            }
        }

        // Validate hidden CAPTCHA if present (only for non-bot cases)
        if (!$botDetected && $this->hasHiddenCaptchaData($request)) {
            if (!$this->validateHiddenCaptcha($request)) {
                // Hidden CAPTCHA failed - require visual CAPTCHA
                return $this->requireVisualCaptcha($request, __('p-captcha::p-captcha.please_complete_verification_challenge'));
            }
        }

        // If we get here and no bot detected, allow through (hidden CAPTCHA passed or not required)
        if (!$botDetected) {
            return $next($request);
        }

        // Fallback: if bot detected but we somehow got here, require visual CAPTCHA
        return $this->requireVisualCaptcha($request, __('p-captcha::p-captcha.suspicious_activity_detected'));
    }

    /**
     * Detect bot behavior using hidden validation techniques
     */
    protected function detectBotBehavior(Request $request): bool
    {
        // Check honeypot field
        $honeypotDetected = $this->checkHoneypot($request);
        if ($honeypotDetected) {
            if (config('app.debug', false)) {
                \Log::info('P-CAPTCHA: Bot detected - honeypot field filled');
            }
            return true;
        }

        // Check timing (if hidden CAPTCHA data is present)
        $timingDetected = $this->hasHiddenCaptchaData($request) && $this->checkTiming($request);
        if ($timingDetected) {
            if (config('app.debug', false)) {
                \Log::info('P-CAPTCHA: Bot detected - timing violation');
            }
            return true;
        }

        // Check if JavaScript was disabled (missing token)
        $jsDisabled = $this->checkJavaScriptRequired($request);
        if ($jsDisabled) {
            if (config('app.debug', false)) {
                \Log::info('P-CAPTCHA: Bot detected - JavaScript disabled');
            }
            return true;
        }

        // Check user agent patterns
        $suspiciousUA = $this->checkSuspiciousUserAgent($request);
        if ($suspiciousUA) {
            if (config('app.debug', false)) {
                \Log::info('P-CAPTCHA: Bot detected - suspicious user agent', [
                    'user_agent' => $request->userAgent()
                ]);
            }
            return true;
        }

        if (config('app.debug', false)) {
            \Log::info('P-CAPTCHA: No bot behavior detected');
        }

        return false;
    }

    /**
     * Check honeypot field for bot activity
     */
    protected function checkHoneypot(Request $request): bool
    {
        // Check if honeypot field is filled (bots typically fill all fields)
        $honeypotFields = ['website', 'url', 'homepage', 'search_username'];

        foreach ($honeypotFields as $field) {
            if ($request->filled($field)) {
                return true; // Bot detected
            }
        }

        return false;
    }

    /**
     * Check if form was submitted too quickly
     */
    protected function checkTiming(Request $request): bool
    {
        $hiddenToken = $request->input('_captcha_token');
        if (!$hiddenToken) {
            return false;
        }

        try {
            $tokenData = decrypt($hiddenToken);
            $formLoadTime = $tokenData['timestamp'] ?? 0;
            $minTime = config('p-captcha.hidden.min_submit_time', 2); // 2 seconds minimum

            if ((time() - $formLoadTime) < $minTime) {
                return true; // Submitted too quickly - likely bot
            }
        } catch (\Exception $e) {
            return true; // Invalid token - suspicious
        }

        return false;
    }

    /**
     * Check if JavaScript validation is missing
     */
    protected function checkJavaScriptRequired(Request $request): bool
    {
        // If form has hidden CAPTCHA fields but missing JS-generated token
        if ($request->has('_captcha_field') && !$request->has('_captcha_token')) {
            return true; // JavaScript likely disabled - suspicious
        }

        return false;
    }

    /**
     * Check for suspicious user agents
     */
    protected function checkSuspiciousUserAgent(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        // Common bot user agents
        $botPatterns = [
            '/bot/i', '/crawler/i', '/spider/i', '/scraper/i', '/curl/i', '/wget/i',
            '/python/i', '/java/i', '/perl/i', '/ruby/i', '/php/i', '/go-http-client/i'
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if suspicious words are detected in form fields
     */
    private function checkSuspiciousWords(Request $request): bool
    {
        $suspiciousWords = config('p-captcha.suspicious_words', []);
        
        if (empty($suspiciousWords)) {
            return false;
        }

        $formData = array_merge(
            $request->all(),
            $request->input() ?? []
        );

        foreach ($formData as $fieldValue) {
            if (!is_string($fieldValue)) {
                continue;
            }

            foreach ($suspiciousWords as $word) {
                if (strcasecmp(trim($fieldValue), trim($word)) === 0) {
                    // Store in session to force visual captcha
                    session(['p-captcha.force_visual_captcha' => true]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if visual CAPTCHA is required
     */
    private function isVisualCaptchaRequired(Request $request): bool
    {
        // Check if force_visual_captcha is set in config or session
        if (config('p-captcha.force_visual_captcha', false) || 
            session('p-captcha.force_visual_captcha', false)) {
            return true;
        }

        // Check for bot behavior
        return $this->detectBotBehavior($request);
    }

    /**
     * Check if request contains hidden CAPTCHA data
     */
    protected function hasHiddenCaptchaData(Request $request): bool
    {
        return $request->has('_captcha_token') && $request->has('_captcha_field');
    }

    /**
     * Check if request contains visual CAPTCHA data
     */
    protected function hasVisualCaptchaData(Request $request): bool
    {
        return $request->has('p_captcha_id') && $request->has('p_captcha_solution');
    }

    /**
     * Clear the force visual captcha session flag
     */
    private function clearForceVisualCaptchaSession(): void
    {
        session()->forget('p-captcha.force_visual_captcha');
    }

    /**
     * Validate hidden CAPTCHA
     */
    protected function validateHiddenCaptcha(Request $request): bool
    {
        $token = $request->input('_captcha_token');
        $field = $request->input('_captcha_field');

        if (!$token || !$field) {
            return false;
        }

        try {
            $decrypted = decrypt($token);
            $expectedField = $decrypted['field'] ?? '';
            $timestamp = $decrypted['timestamp'] ?? 0;
            $expiry = config('p-captcha.hidden.token_expiry', 3600); // 1 hour default

            // Check if token is expired
            if ((time() - $timestamp) > $expiry) {
                if (config('app.debug', false)) {
                    \Log::info('P-CAPTCHA: Hidden CAPTCHA token expired');
                }
                return false;
            }

            // For hidden CAPTCHA, we validate that the field value matches what's expected
            $isValid = ($expectedField === $field);
            
            if ($isValid) {
                // Clear force visual captcha session flag on successful validation
                $this->clearForceVisualCaptchaSession();
            }

            return $isValid;
        } catch (\Exception $e) {
            if (config('app.debug', false)) {
                \Log::error('P-CAPTCHA: Hidden CAPTCHA validation error', ['error' => $e->getMessage()]);
            }
            return false;
        }
    }

    /**
     * Validate visual CAPTCHA
     */
    protected function validateVisualCaptcha(Request $request): bool
    {
        $captchaId = $request->input('p_captcha_id');
        $solution = $request->input('p_captcha_solution');

        if (!$captchaId || !$solution) {
            return false;
        }

        // Handle different solution formats
        if (is_string($solution)) {
            // Try to decode JSON string
            $decoded = json_decode($solution, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $solution = $decoded;
            } else {
                // If not valid JSON, treat as single value
                $solution = ['answer' => $solution];
            }
        }

        // Ensure solution is an array
        if (!is_array($solution)) {
            $solution = ['answer' => $solution];
        }

        $isValid = $this->captchaService->validateSolution($captchaId, $solution);
        
        if ($isValid) {
            // Clear force visual captcha session flag on successful validation
            $this->clearForceVisualCaptchaSession();
        }

        return $isValid;
    }

    /**
     * Require hidden CAPTCHA completion
     */
    protected function requireHiddenCaptcha(Request $request)
    {
        // For normal users, just increment attempt count and allow through
        // The hidden CAPTCHA will be included in the form on next page load
        $this->incrementAttemptCount();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => __('p-captcha::p-captcha.please_complete_form_validation'),
                'hidden_captcha_required' => true
            ], 422);
        }

        return redirect()->back()
            ->withErrors(['form' => __('p-captcha::p-captcha.please_complete_form_validation')])
            ->withInput();
    }

    /**
     * Require visual CAPTCHA completion
     */
    protected function requireVisualCaptcha(Request $request, string $message = 'Please complete the verification challenge.')
    {
        $this->incrementAttemptCount();

        if ($request->expectsJson()) {
            return new JsonResponse([
                'success' => false,
                'message' => $message,
                'visual_captcha_required' => true,
                'errors' => [
                    'captcha' => [$message]
                ]
            ], 422);
        }

        return back()
            ->withErrors(['captcha' => $message])
            ->withInput($request->except(['p_captcha_id', 'p_captcha_solution', '_captcha_token', '_captcha_field']));
    }

    /**
     * Handle CAPTCHA failure
     */
    protected function handleCaptchaFailure(Request $request)
    {
        $this->incrementAttemptCount();

        if ($request->expectsJson()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'CAPTCHA verification failed. Please try again.',
                'captcha_failed' => true,
                'errors' => [
                    'captcha' => ['CAPTCHA verification failed. Please try again.']
                ]
            ], 422);
        }

        return back()
            ->withErrors(['captcha' => 'CAPTCHA verification failed. Please try again.'])
            ->withInput($request->except(['p_captcha_id', 'p_captcha_solution', '_captcha_token', '_captcha_field']));
    }

    /**
     * Increment attempt count for this session
     */
    protected function incrementAttemptCount(): void
    {
        $sessionId = Session::getId();
        $cacheKey = "form_attempts:{$sessionId}";
        $currentCount = Cache::get($cacheKey, 0);

        Cache::put($cacheKey, $currentCount + 1, now()->addHour());
    }

    /**
     * Reset attempt count (called after successful form submission)
     */
    public static function resetAttemptCount(): void
    {
        $sessionId = Session::getId();
        Cache::forget("form_attempts:{$sessionId}");
    }
}

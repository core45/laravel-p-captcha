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

        // First check for bot behavior using hidden validation
        $botDetected = $this->detectBotBehavior($request);

        // Check if visual CAPTCHA validation is required
        $visualCaptchaRequired = $this->isVisualCaptchaRequired($request, $botDetected);

        if (!$visualCaptchaRequired && !$botDetected) {
            // Normal user with no suspicious behavior - allow through
            return $next($request);
        }

        if ($botDetected && !$this->hasVisualCaptchaData($request)) {
            // Bot detected but no visual CAPTCHA provided - require it
            return $this->requireVisualCaptcha($request, 'Suspicious activity detected. Please complete verification.');
        }

        // Validate hidden CAPTCHA if present
        if ($this->hasHiddenCaptchaData($request)) {
            if (!$this->validateHiddenCaptcha($request)) {
                // Hidden CAPTCHA failed - require visual CAPTCHA
                return $this->requireVisualCaptcha($request, 'Please complete the verification challenge.');
            }
        }

        // Validate visual CAPTCHA if present
        if ($this->hasVisualCaptchaData($request)) {
            $isValid = $this->validateVisualCaptcha($request);

            if ($isValid) {
                // CAPTCHA passed, continue with request
                return $next($request);
            } else {
                // CAPTCHA failed
                return $this->handleCaptchaFailure($request);
            }
        }

        // If we get here, some form of CAPTCHA is required
        if ($botDetected) {
            return $this->requireVisualCaptcha($request, 'Please complete the verification challenge.');
        } else {
            return $this->requireHiddenCaptcha($request);
        }
    }

    /**
     * Detect bot behavior using hidden validation techniques
     */
    protected function detectBotBehavior(Request $request): bool
    {
        // Check honeypot field
        if ($this->checkHoneypot($request)) {
            return true;
        }

        // Check timing (if hidden CAPTCHA data is present)
        if ($this->hasHiddenCaptchaData($request) && $this->checkTiming($request)) {
            return true;
        }

        // Check if JavaScript was disabled (missing token)
        if ($this->checkJavaScriptRequired($request)) {
            return true;
        }

        // Check user agent patterns
        if ($this->checkSuspiciousUserAgent($request)) {
            return true;
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

        $suspiciousPatterns = [
            'bot', 'crawl', 'spider', 'scrape', 'curl', 'wget', 'python', 'perl', 'java'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if visual CAPTCHA is required
     */
    protected function isVisualCaptchaRequired(Request $request, bool $botDetected): bool
    {
        // Check if visual CAPTCHA is forced in config
        if (config('p-captcha.force_visual_captcha', false)) {
            return true;
        }

        if ($botDetected) {
            return true;
        }

        $sessionId = Session::getId();
        $attemptCount = Cache::get("form_attempts:{$sessionId}", 0);
        $threshold = config('p-captcha.ui.auto_show_after_attempts', 3); // Increased since we have hidden validation

        return $attemptCount >= $threshold;
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
     * Validate hidden CAPTCHA
     */
    protected function validateHiddenCaptcha(Request $request): bool
    {
        try {
            $token = $request->input('_captcha_token');
            $fieldValue = $request->input('_captcha_field');

            if (!$token || !$fieldValue) {
                return false;
            }

            $tokenData = decrypt($token);

            // Validate token components
            if (!isset($tokenData['timestamp'], $tokenData['session_id'], $tokenData['ip'],
                $tokenData['user_agent'], $tokenData['field_name'])) {
                return false;
            }

            // Check session, IP, user agent
            if ($tokenData['session_id'] !== Session::getId() ||
                $tokenData['ip'] !== $request->ip() ||
                $tokenData['user_agent'] !== $request->userAgent()) {
                return false;
            }

            // Check field name matches
            $expectedFieldName = $tokenData['field_name'];
            if (!$request->has($expectedFieldName) ||
                $request->input($expectedFieldName) !== $fieldValue) {
                return false;
            }

            // Check time limits
            $now = time();
            $minTime = config('p-captcha.hidden.min_submit_time', 2);
            $maxTime = config('p-captcha.hidden.max_submit_time', 1200);

            $elapsed = $now - $tokenData['timestamp'];
            if ($elapsed < $minTime || $elapsed > $maxTime) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate visual CAPTCHA
     */
    protected function validateVisualCaptcha(Request $request): bool
    {
        $challengeId = $request->input('p_captcha_id');
        $solution = $request->input('p_captcha_solution', []);

        // Ensure solution is an array
        if (!is_array($solution)) {
            $solution = json_decode($solution, true) ?? [];
        }

        return $this->captchaService->validateSolution($challengeId, $solution);
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
            return new JsonResponse([
                'success' => false,
                'message' => 'Please complete the form validation.',
                'hidden_captcha_required' => true
            ], 422);
        }

        return back()
            ->withErrors(['form' => 'Please complete the form validation.'])
            ->withInput($request->except(['_captcha_token', '_captcha_field']));
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

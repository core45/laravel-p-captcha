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

        // Check if CAPTCHA validation is required
        if (!$this->isCaptchaRequired($request)) {
            return $next($request);
        }

        // Validate CAPTCHA if present
        if ($this->hasCaptchaData($request)) {
            $isValid = $this->validateCaptcha($request);

            if ($isValid) {
                // CAPTCHA passed, continue with request
                return $next($request);
            } else {
                // CAPTCHA failed
                return $this->handleCaptchaFailure($request);
            }
        }

        // CAPTCHA required but not provided
        return $this->requireCaptcha($request);
    }

    /**
     * Check if CAPTCHA is required for this request
     */
    protected function isCaptchaRequired(Request $request): bool
    {
        $sessionId = Session::getId();
        $attemptCount = Cache::get("form_attempts:{$sessionId}", 0);

        // Require CAPTCHA after configured number of attempts
        $threshold = config('p-captcha.ui.auto_show_after_attempts', 2);

        return $attemptCount >= $threshold;
    }

    /**
     * Check if request contains CAPTCHA data
     */
    protected function hasCaptchaData(Request $request): bool
    {
        return $request->has('p_captcha_id') && $request->has('p_captcha_solution');
    }

    /**
     * Validate the submitted CAPTCHA
     */
    protected function validateCaptcha(Request $request): bool
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
     * Handle CAPTCHA validation failure
     */
    protected function handleCaptchaFailure(Request $request)
    {
        $this->incrementAttemptCount();

        if ($request->expectsJson()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid CAPTCHA solution. Please try again.',
                'captcha_required' => true,
                'errors' => [
                    'p_captcha' => ['The CAPTCHA solution is invalid.']
                ]
            ], 422);
        }

        return back()
            ->withErrors(['p_captcha' => 'The CAPTCHA solution is invalid.'])
            ->withInput($request->except(['p_captcha_id', 'p_captcha_solution']));
    }

    /**
     * Require CAPTCHA completion
     */
    protected function requireCaptcha(Request $request)
    {
        $this->incrementAttemptCount();

        if ($request->expectsJson()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'CAPTCHA verification required.',
                'captcha_required' => true,
                'errors' => [
                    'p_captcha' => ['CAPTCHA verification is required.']
                ]
            ], 422);
        }

        return back()
            ->withErrors(['p_captcha' => 'CAPTCHA verification is required.'])
            ->withInput($request->except(['p_captcha_id', 'p_captcha_solution']));
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

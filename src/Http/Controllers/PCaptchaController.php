<?php

namespace Core45\LaravelPCaptcha\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Core45\LaravelPCaptcha\Services\PCaptchaService;

class PCaptchaController extends Controller
{
    protected PCaptchaService $captchaService;

    public function __construct(PCaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    /**
     * Generate a new CAPTCHA challenge
     *
     * Route: POST /p-captcha/generate
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            // Rate limiting
            $key = 'p-captcha-generate:' . $request->ip();
            $rateLimits = config('p-captcha.rate_limits.generate');

            if (RateLimiter::tooManyAttempts($key, $rateLimits['max_attempts'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please wait before generating new challenges.'
                ], 429);
            }

            RateLimiter::hit($key, $rateLimits['decay_minutes'] * 60);

            // Generate challenge
            $challenge = $this->captchaService->generateChallenge();
            $frontendChallenge = $this->captchaService->getChallengeForFrontend($challenge['id']);

            // Log for monitoring
            if (config('p-captcha.security.log_attempts', true)) {
                Log::info('P-CAPTCHA challenge generated', [
                    'challenge_id' => $challenge['id'],
                    'type' => $challenge['type'],
                    'difficulty' => $challenge['difficulty'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            return response()->json([
                'success' => true,
                'challenge' => $frontendChallenge
            ]);

        } catch (\Exception $e) {
            Log::error('P-CAPTCHA generation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate CAPTCHA challenge'
            ], 500);
        }
    }

    /**
     * Validate CAPTCHA solution
     *
     * Route: POST /p-captcha/validate
     */
    public function validate(Request $request): JsonResponse
    {
        try {
            // Rate limiting
            $key = 'p-captcha-validate:' . $request->ip();
            $rateLimits = config('p-captcha.rate_limits.validate');

            if (RateLimiter::tooManyAttempts($key, $rateLimits['max_attempts'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many validation attempts. Please wait.'
                ], 429);
            }

            RateLimiter::hit($key, $rateLimits['decay_minutes'] * 60);

            // Validate request
            $validated = $request->validate([
                'challenge_id' => [
                    'required',
                    'string',
                    'min:10',
                    'max:100',
                    'regex:/^[a-zA-Z0-9]+$/'
                ],
                'solution' => [
                    'required',
                    'array'
                ]
            ]);

            $challengeId = $validated['challenge_id'];
            $solution = $validated['solution'];

            // Log validation attempt
            if (config('p-captcha.security.log_attempts', true)) {
                Log::info('P-CAPTCHA validation attempt', [
                    'challenge_id' => $challengeId,
                    'ip' => $request->ip(),
                    'solution_keys' => array_keys($solution)
                ]);
            }

            // Validate solution
            $isValid = $this->captchaService->validateSolution($challengeId, $solution);

            // Log result
            if (config('p-captcha.security.log_attempts', true)) {
                Log::info('P-CAPTCHA validation result', [
                    'challenge_id' => $challengeId,
                    'valid' => $isValid,
                    'ip' => $request->ip()
                ]);
            }

            return response()->json([
                'success' => true,
                'valid' => $isValid,
                'message' => $isValid
                    ? 'CAPTCHA solved successfully'
                    : 'Invalid CAPTCHA solution'
            ]);

        } catch (ValidationException $e) {
            Log::warning('P-CAPTCHA validation - invalid request', [
                'errors' => $e->errors(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('P-CAPTCHA validation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate CAPTCHA'
            ], 500);
        }
    }

    /**
     * Refresh challenge (alias for generate)
     *
     * Route: POST /p-captcha/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        return $this->generate($request);
    }

    /**
     * Generate hidden CAPTCHA token
     *
     * Route: POST /p-captcha/generate-token
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            // Rate limiting
            $key = 'p-captcha-token:' . $request->ip();
            $rateLimits = config('p-captcha.rate_limits.generate');

            if (RateLimiter::tooManyAttempts($key, $rateLimits['max_attempts'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please wait.'
                ], 429);
            }

            RateLimiter::hit($key, $rateLimits['decay_minutes'] * 60);

            // Validate request
            $validated = $request->validate([
                'timestamp' => 'required|integer',
                'session_id' => 'required|string',
                'user_agent' => 'required|string',
                'field_name' => 'required|string|alpha_dash',
                'field_value' => 'required|string'
            ]);

            // Create token data
            $tokenData = [
                'timestamp' => $validated['timestamp'],
                'session_id' => $validated['session_id'],
                'ip' => $request->ip(),
                'user_agent' => $validated['user_agent'],
                'field_name' => $validated['field_name'],
                'field_value' => $validated['field_value']
            ];

            // Encrypt the token
            $encryptedToken = encrypt($tokenData);

            if (config('p-captcha.security.log_attempts', true)) {
                Log::info('P-CAPTCHA hidden token generated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            return response()->json([
                'success' => true,
                'token' => $encryptedToken
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('P-CAPTCHA token generation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token'
            ], 500);
        }
    }

    /**
     * Get CAPTCHA widget HTML
     *
     * Route: GET /p-captcha/widget
     */
    public function widget(Request $request): string
    {
        $options = $request->query('options', '');
        return $this->captchaService->renderCaptcha($options);
    }
}

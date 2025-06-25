<?php

namespace Core45\LaravelPCaptcha\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Core45\LaravelPCaptcha\Middleware\ProtectWithPCaptcha;
use Core45\LaravelPCaptcha\Services\PCaptchaService;
use Core45\LaravelPCaptcha\Providers\PCaptchaServiceProvider;

class ProtectWithPCaptchaTest extends TestCase
{
    protected ProtectWithPCaptcha $middleware;
    protected PCaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PCaptchaService();
        $this->middleware = new ProtectWithPCaptcha($this->service);

        // Mock session
        Session::shouldReceive('getId')->andReturn('test-session-123');
    }

    protected function getPackageProviders($app)
    {
        return [PCaptchaServiceProvider::class];
    }

    /** @test */
    public function it_allows_get_requests_without_captcha()
    {
        $request = Request::create('/test', 'GET');
        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_allows_post_requests_when_captcha_not_required()
    {
        $request = Request::create('/test', 'POST');
        $next = function ($req) {
            return response('Success');
        };

        // Ensure no previous attempts
        Cache::forget('form_attempts:test-session-123');

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_requires_captcha_after_threshold_attempts()
    {
        $request = Request::create('/test', 'POST');
        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        // Should redirect back with errors for web requests
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_json_error_for_ajax_requests_requiring_captcha()
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('Accept', 'application/json');
        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertTrue($data['captcha_required']);
    }

    /** @test */
    public function it_validates_correct_captcha_solution()
    {
        // Generate a challenge
        config(['p-captcha.challenge_types' => ['pattern_match']]);
        $challenge = $this->service->generateChallenge();

        $request = Request::create('/test', 'POST', [
            'p_captcha_id' => $challenge['id'],
            'p_captcha_solution' => ['answer' => $challenge['solution']]
        ]);

        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold to require CAPTCHA
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_incorrect_captcha_solution()
    {
        // Generate a challenge
        config(['p-captcha.challenge_types' => ['pattern_match']]);
        $challenge = $this->service->generateChallenge();

        $request = Request::create('/test', 'POST', [
            'p_captcha_id' => $challenge['id'],
            'p_captcha_solution' => ['answer' => 'wrong_answer']
        ]);

        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold to require CAPTCHA
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        // Should redirect back with errors
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function it_increments_attempt_count_on_failure()
    {
        $request = Request::create('/test', 'POST');
        $next = function ($req) {
            return response('Success');
        };

        // Start with 2 attempts (at threshold)
        Cache::put('form_attempts:test-session-123', 2, 3600);

        $this->middleware->handle($request, $next);

        // Should increment to 3
        $attemptCount = Cache::get('form_attempts:test-session-123');
        $this->assertEquals(3, $attemptCount);
    }

    /** @test */
    public function it_handles_json_solution_string()
    {
        // Generate a challenge
        config(['p-captcha.challenge_types' => ['pattern_match']]);
        $challenge = $this->service->generateChallenge();

        $request = Request::create('/test', 'POST', [
            'p_captcha_id' => $challenge['id'],
            'p_captcha_solution' => json_encode(['answer' => $challenge['solution']])
        ]);

        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold to require CAPTCHA
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_handles_invalid_challenge_id()
    {
        $request = Request::create('/test', 'POST', [
            'p_captcha_id' => 'invalid-challenge-id',
            'p_captcha_solution' => ['answer' => 'any']
        ]);

        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold to require CAPTCHA
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        // Should redirect back with errors
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function reset_attempt_count_works()
    {
        // Set attempt count
        Cache::put('form_attempts:test-session-123', 5, 3600);

        ProtectWithPCaptcha::resetAttemptCount();

        $this->assertFalse(Cache::has('form_attempts:test-session-123'));
    }

    /** @test */
    public function it_preserves_form_input_on_failure()
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
            'p_captcha_id' => 'invalid-id',
            'p_captcha_solution' => ['answer' => 'wrong']
        ]);

        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count above threshold to require CAPTCHA
        Cache::put('form_attempts:test-session-123', 3, 3600);

        $response = $this->middleware->handle($request, $next);

        // Check that sensitive CAPTCHA data is excluded from input
        $session = $response->getSession();
        $oldInput = $session->getOldInput();

        $this->assertEquals('John Doe', $oldInput['name']);
        $this->assertEquals('john@example.com', $oldInput['email']);
        $this->assertEquals('Test message', $oldInput['message']);
        $this->assertArrayNotHasKey('p_captcha_id', $oldInput);
        $this->assertArrayNotHasKey('p_captcha_solution', $oldInput);
    }

    /** @test */
    public function it_respects_configuration_for_attempt_threshold()
    {
        config(['p-captcha.ui.auto_show_after_attempts' => 5]);

        $request = Request::create('/test', 'POST');
        $next = function ($req) {
            return response('Success');
        };

        // Set attempt count to 4 (below new threshold)
        Cache::put('form_attempts:test-session-123', 4, 3600);

        $response = $this->middleware->handle($request, $next);

        // Should allow through
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_detects_suspicious_words_in_form_fields()
    {
        config(['p-captcha.suspicious_words' => ['Eric Jones', 'Vaigra']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'message' => 'Hello Eric Jones, how are you?'
        ]);

        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        // Should require visual CAPTCHA due to suspicious word
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function it_detects_suspicious_words_case_insensitive()
    {
        config(['p-captcha.suspicious_words' => ['Eric Jones', 'Vaigra']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'message' => 'Hello eric jOnes, how are you?'
        ]);

        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        // Should require visual CAPTCHA due to suspicious word (case insensitive)
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function it_does_not_trigger_on_partial_matches()
    {
        config(['p-captcha.suspicious_words' => ['Eric Jones', 'Vaigra']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'message' => 'Hello Eric, how are you?'
        ]);

        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        // Should allow through as only "Eric" is present, not "Eric Jones"
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_skips_suspicious_words_check_when_force_visual_captcha_is_true()
    {
        config(['p-captcha.suspicious_words' => ['Eric Jones', 'Vaigra']]);
        config(['p-captcha.force_visual_captcha' => true]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'message' => 'Hello Eric Jones, how are you?'
        ]);

        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        // Should require visual CAPTCHA due to force_visual_captcha being true, not suspicious words
        $this->assertEquals(302, $response->getStatusCode());
    }

    /** @test */
    public function it_ignores_captcha_fields_when_checking_suspicious_words()
    {
        config(['p-captcha.suspicious_words' => ['Eric Jones', 'Vaigra']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'message' => 'Hello, how are you?',
            '_captcha_token' => 'Eric Jones token',
            'p_captcha_solution' => 'Vaigra solution'
        ]);

        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        // Should allow through as suspicious words are only in CAPTCHA fields
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_handles_empty_suspicious_words_config()
    {
        config(['p-captcha.suspicious_words' => []]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'message' => 'Hello Eric Jones, how are you?'
        ]);

        $next = function ($req) {
            return response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        // Should allow through as no suspicious words are configured
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_suspicious_words_detection_requires_visual_captcha()
    {
        // Configure suspicious words
        config(['p-captcha.suspicious_words' => ['spam', 'test', 'bot']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'spam' // This should trigger suspicious words detection
        ]);

        $middleware = new ProtectWithPCaptcha($this->service);
        $response = $middleware->handle($request, function ($req) {
            return response('success');
        });

        // Should require visual CAPTCHA due to suspicious words
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('p-captcha-container', $response->getContent());
        $this->assertStringContainsString('Please complete the verification challenge', $response->getContent());
        
        // Check that session flag was set
        $this->assertTrue(session('p-captcha.force_visual_captcha', false));
    }

    public function test_suspicious_words_detection_case_insensitive()
    {
        // Configure suspicious words
        config(['p-captcha.suspicious_words' => ['SPAM', 'Test', 'BOT']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'spam' // Should match 'SPAM' (case-insensitive)
        ]);

        $middleware = new ProtectWithPCaptcha($this->service);
        $response = $middleware->handle($request, function ($req) {
            return response('success');
        });

        // Should require visual CAPTCHA due to suspicious words
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('p-captcha-container', $response->getContent());
        
        // Check that session flag was set
        $this->assertTrue(session('p-captcha.force_visual_captcha', false));
    }

    public function test_suspicious_words_partial_match_does_not_trigger()
    {
        // Configure suspicious words
        config(['p-captcha.suspicious_words' => ['spam', 'test', 'bot']]);
        config(['p-captcha.force_visual_captcha' => false]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'spammy message' // Contains 'spam' but not exact match
        ]);

        $middleware = new ProtectWithPCaptcha($this->service);
        $response = $middleware->handle($request, function ($req) {
            return response('success');
        });

        // Should not require visual CAPTCHA for partial matches
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('p-captcha-container', $response->getContent());
        
        // Check that session flag was not set
        $this->assertFalse(session('p-captcha.force_visual_captcha', false));
    }

    public function test_suspicious_words_ignored_when_force_visual_captcha_true()
    {
        // Configure suspicious words and force visual captcha
        config(['p-captcha.suspicious_words' => ['spam', 'test', 'bot']]);
        config(['p-captcha.force_visual_captcha' => true]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'spam' // This would normally trigger suspicious words detection
        ]);

        $middleware = new ProtectWithPCaptcha($this->service);
        $response = $middleware->handle($request, function ($req) {
            return response('success');
        });

        // Should require visual CAPTCHA due to force_visual_captcha config, not suspicious words
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('p-captcha-container', $response->getContent());
        
        // Session flag should not be set when force_visual_captcha is true in config
        $this->assertFalse(session('p-captcha.force_visual_captcha', false));
    }

    public function test_suspicious_words_session_flag_cleared_after_successful_validation()
    {
        // Configure suspicious words
        config(['p-captcha.suspicious_words' => ['spam', 'test', 'bot']]);
        config(['p-captcha.force_visual_captcha' => false]);

        // Set session flag manually
        session(['p-captcha.force_visual_captcha' => true]);

        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'john@example.com',
            'message' => 'Hello world',
            'p_captcha_id' => 'test-id',
            'p_captcha_solution' => 'test-solution'
        ]);

        // Mock successful validation
        $this->service->shouldReceive('validateSolution')
            ->with('test-id', ['answer' => 'test-solution'])
            ->andReturn(true);

        $middleware = new ProtectWithPCaptcha($this->service);
        $response = $middleware->handle($request, function ($req) {
            return response('success');
        });

        // Should succeed and clear session flag
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('p-captcha-container', $response->getContent());
        
        // Check that session flag was cleared
        $this->assertFalse(session('p-captcha.force_visual_captcha', false));
    }
}

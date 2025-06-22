<?php

namespace Core45\LaravelPCaptcha\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Core45\LaravelPCaptcha\Services\PCaptchaService;
use Core45\LaravelPCaptcha\Providers\PCaptchaServiceProvider;

class PCaptchaServiceTest extends TestCase
{
    protected PCaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PCaptchaService();

        // Mock session
        Session::shouldReceive('getId')->andReturn('test-session-123');
    }

    protected function getPackageProviders($app)
    {
        return [PCaptchaServiceProvider::class];
    }

    /** @test */
    public function it_can_generate_a_challenge()
    {
        $challenge = $this->service->generateChallenge();

        $this->assertIsArray($challenge);
        $this->assertArrayHasKey('id', $challenge);
        $this->assertArrayHasKey('type', $challenge);
        $this->assertArrayHasKey('instructions', $challenge);
        $this->assertArrayHasKey('challenge_data', $challenge);
        $this->assertStringMatchesFormat('%s', $challenge['id']);
        $this->assertEquals(32, strlen($challenge['id']));
    }

    /** @test */
    public function it_can_generate_beam_alignment_challenge()
    {
        // Force beam alignment challenge
        config(['p-captcha.challenge_types' => ['beam_alignment']]);

        $challenge = $this->service->generateChallenge();

        $this->assertEquals('beam_alignment', $challenge['type']);
        $this->assertArrayHasKey('source', $challenge['challenge_data']);
        $this->assertArrayHasKey('target', $challenge['challenge_data']);
        $this->assertArrayHasKey('tolerance', $challenge['challenge_data']);
        $this->assertArrayHasKey('solution', $challenge);
        $this->assertArrayHasKey('offset_x', $challenge['solution']);
        $this->assertArrayHasKey('offset_y', $challenge['solution']);
    }

    /** @test */
    public function it_can_generate_sequence_complete_challenge()
    {
        config(['p-captcha.challenge_types' => ['sequence_complete']]);

        $challenge = $this->service->generateChallenge();

        $this->assertEquals('sequence_complete', $challenge['type']);
        $this->assertArrayHasKey('sequence', $challenge['challenge_data']);
        $this->assertArrayHasKey('choices', $challenge['challenge_data']);
        $this->assertIsInt($challenge['solution']);
        $this->assertContains($challenge['solution'], $challenge['challenge_data']['choices']);
    }

    /** @test */
    public function it_validates_beam_alignment_solution_correctly()
    {
        // Generate beam alignment challenge
        config(['p-captcha.challenge_types' => ['beam_alignment']]);
        $challenge = $this->service->generateChallenge();

        // Test correct solution
        $correctSolution = $challenge['solution'];
        $isValid = $this->service->validateSolution($challenge['id'], $correctSolution);
        $this->assertTrue($isValid);

        // Test incorrect solution
        $incorrectSolution = ['offset_x' => 999, 'offset_y' => 999];

        // Re-generate challenge since it's consumed
        $challenge2 = $this->service->generateChallenge();
        $isValid = $this->service->validateSolution($challenge2['id'], $incorrectSolution);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_validates_sequence_complete_solution_correctly()
    {
        config(['p-captcha.challenge_types' => ['sequence_complete']]);
        $challenge = $this->service->generateChallenge();

        // Test correct solution
        $correctSolution = ['answer' => $challenge['solution']];
        $isValid = $this->service->validateSolution($challenge['id'], $correctSolution);
        $this->assertTrue($isValid);

        // Test incorrect solution
        $challenge2 = $this->service->generateChallenge();
        $incorrectSolution = ['answer' => 999999];
        $isValid = $this->service->validateSolution($challenge2['id'], $incorrectSolution);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_stores_and_retrieves_challenges_from_cache()
    {
        $challenge = $this->service->generateChallenge();
        $challengeId = $challenge['id'];

        // Check that challenge is stored in cache
        $cacheKey = 'p_captcha:challenge:' . $challengeId;
        $this->assertTrue(Cache::has($cacheKey));

        $cachedChallenge = Cache::get($cacheKey);
        $this->assertEquals($challenge, $cachedChallenge);
    }

    /** @test */
    public function it_removes_used_challenges_after_validation()
    {
        config(['p-captcha.security.single_use_challenges' => true]);

        $challenge = $this->service->generateChallenge();
        $challengeId = $challenge['id'];
        $cacheKey = 'p_captcha:challenge:' . $challengeId;

        // Verify challenge exists before validation
        $this->assertTrue(Cache::has($cacheKey));

        // Validate solution (doesn't matter if correct or not)
        $this->service->validateSolution($challengeId, ['dummy' => 'solution']);

        // Verify challenge is removed after validation
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_returns_false_for_expired_challenges()
    {
        $nonExistentChallengeId = 'non-existent-challenge-id';
        $solution = ['answer' => 'any'];

        $isValid = $this->service->validateSolution($nonExistentChallengeId, $solution);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_provides_frontend_safe_challenge_data()
    {
        config(['p-captcha.challenge_types' => ['beam_alignment']]);
        $challenge = $this->service->generateChallenge();

        $frontendChallenge = $this->service->getChallengeForFrontend($challenge['id']);

        $this->assertArrayHasKey('id', $frontendChallenge);
        $this->assertArrayHasKey('type', $frontendChallenge);
        $this->assertArrayHasKey('instructions', $frontendChallenge);
        $this->assertArrayHasKey('challenge_data', $frontendChallenge);

        // Ensure sensitive data is removed
        $this->assertArrayNotHasKey('solution', $frontendChallenge);
        $this->assertArrayNotHasKey('session_id', $frontendChallenge);
    }

    /** @test */
    public function it_renders_captcha_html()
    {
        $html = $this->service->renderCaptcha('theme=light,id=test-captcha');

        $this->assertStringContainsString('p-captcha-container', $html);
        $this->assertStringContainsString('test-captcha', $html);
        $this->assertStringContainsString('data-theme="light"', $html);
    }

    /** @test */
    public function it_resets_failure_counters_on_success()
    {
        $sessionId = 'test-session-123';

        // Set failure counter
        Cache::put('p_captcha:failures:' . $sessionId, 3, 3600);

        // Generate and solve a challenge successfully
        config(['p-captcha.challenge_types' => ['sequence_complete']]);
        $challenge = $this->service->generateChallenge();
        $correctSolution = ['answer' => $challenge['solution']];
        $this->service->validateSolution($challenge['id'], $correctSolution);

        // Verify counter is reset
        $this->assertFalse(Cache::has('p_captcha:failures:' . $sessionId));
    }
}

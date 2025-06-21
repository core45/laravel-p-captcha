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
        $this->assertArrayHasKey('difficulty', $challenge);
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
    public function it_can_generate_pattern_match_challenge()
    {
        config(['p-captcha.challenge_types' => ['pattern_match']]);

        $challenge = $this->service->generateChallenge();

        $this->assertEquals('pattern_match', $challenge['type']);
        $this->assertArrayHasKey('pattern', $challenge['challenge_data']);
        $this->assertArrayHasKey('choices', $challenge['challenge_data']);
        $this->assertArrayHasKey('missing_index', $challenge['challenge_data']);
        $this->assertIsString($challenge['solution']);
        $this->assertContains($challenge['solution'], $challenge['challenge_data']['choices']);
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
    public function it_can_generate_proof_of_work_challenge()
    {
        config(['p-captcha.challenge_types' => ['proof_of_work']]);

        $challenge = $this->service->generateChallenge();

        $this->assertEquals('proof_of_work', $challenge['type']);
        $this->assertArrayHasKey('challenge_string', $challenge['challenge_data']);
        $this->assertArrayHasKey('target_zeros', $challenge['challenge_data']);
        $this->assertArrayHasKey('prefix_required', $challenge['challenge_data']);
        $this->assertIsString($challenge['challenge_data']['challenge_string']);
        $this->assertIsInt($challenge['challenge_data']['target_zeros']);
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
    public function it_validates_pattern_match_solution_correctly()
    {
        config(['p-captcha.challenge_types' => ['pattern_match']]);
        $challenge = $this->service->generateChallenge();

        // Test correct solution
        $correctSolution = ['answer' => $challenge['solution']];
        $isValid = $this->service->validateSolution($challenge['id'], $correctSolution);
        $this->assertTrue($isValid);

        // Test incorrect solution
        $challenge2 = $this->service->generateChallenge();
        $incorrectSolution = ['answer' => 'wrong_symbol'];
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
    public function it_validates_proof_of_work_solution_correctly()
    {
        config(['p-captcha.challenge_types' => ['proof_of_work']]);
        config(['p-captcha.difficulty_levels.easy' => 1]); // Make it easier for testing

        $challenge = $this->service->generateChallenge();
        $challengeString = $challenge['challenge_data']['challenge_string'];
        $targetZeros = $challenge['challenge_data']['target_zeros'];

        // Find a valid nonce (brute force for testing)
        $validNonce = null;
        for ($i = 0; $i < 10000; $i++) {
            $hash = hash('sha256', $challengeString . $i);
            if (str_starts_with($hash, str_repeat('0', $targetZeros))) {
                $validNonce = $i;
                break;
            }
        }

        if ($validNonce !== null) {
            $correctSolution = ['nonce' => (string)$validNonce];
            $isValid = $this->service->validateSolution($challenge['id'], $correctSolution);
            $this->assertTrue($isValid);
        } else {
            $this->markTestSkipped('Could not find valid nonce within reasonable time');
        }

        // Test incorrect solution
        $challenge2 = $this->service->generateChallenge();
        $incorrectSolution = ['nonce' => '999999'];
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
    public function it_adapts_difficulty_based_on_failures()
    {
        $sessionId = 'test-session-123';

        // Simulate failures by setting cache
        Cache::put('p_captcha:failures:' . $sessionId, 1, 3600);

        $challenge1 = $this->service->generateChallenge();
        $this->assertEquals('medium', $challenge1['difficulty']);

        // Simulate more failures
        Cache::put('p_captcha:failures:' . $sessionId, 3, 3600);

        $challenge2 = $this->service->generateChallenge();
        $this->assertEquals('hard', $challenge2['difficulty']);

        // Simulate even more failures
        Cache::put('p_captcha:failures:' . $sessionId, 5, 3600);

        $challenge3 = $this->service->generateChallenge();
        $this->assertEquals('extreme', $challenge3['difficulty']);
    }

    /** @test */
    public function it_forces_computational_challenges_after_visual_failures()
    {
        $sessionId = 'test-session-123';

        // Simulate many visual failures
        Cache::put('p_captcha:visual_failures:' . $sessionId, 3, 3600);

        $challenge = $this->service->generateChallenge();
        $this->assertEquals('proof_of_work', $challenge['type']);
    }

    /** @test */
    public function it_resets_failure_counters_on_success()
    {
        $sessionId = 'test-session-123';

        // Set failure counters
        Cache::put('p_captcha:failures:' . $sessionId, 3, 3600);
        Cache::put('p_captcha:visual_failures:' . $sessionId, 2, 3600);

        // Generate and solve a challenge successfully
        config(['p-captcha.challenge_types' => ['pattern_match']]);
        $challenge = $this->service->generateChallenge();
        $correctSolution = ['answer' => $challenge['solution']];
        $this->service->validateSolution($challenge['id'], $correctSolution);

        // Verify counters are reset
        $this->assertFalse(Cache::has('p_captcha:failures:' . $sessionId));
        $this->assertFalse(Cache::has('p_captcha:visual_failures:' . $sessionId));
    }
}

<?php

namespace Core45\LaravelPCaptcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * P-CAPTCHA Facade
 *
 * @method static array generateChallenge()
 * @method static bool validateSolution(string $challengeId, array $solution)
 * @method static array|null getChallengeForFrontend(string $challengeId)
 * @method static string renderCaptcha(string $options = '')
 *
 * @see \Core45\LaravelPCaptcha\Services\PCaptchaService
 */
class PCaptcha extends Facade
{
    /**
     * Get the registered name of the component
     */
    protected static function getFacadeAccessor(): string
    {
        return 'p-captcha';
    }
}

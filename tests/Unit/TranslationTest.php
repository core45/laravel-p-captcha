<?php

namespace Core45\LaravelPCaptcha\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationTest extends TestCase
{
    /**
     * Test that English translations are loaded correctly
     */
    public function test_english_translations_are_loaded()
    {
        $this->app->setLocale('en');
        
        $this->assertEquals(
            'Human Verification',
            __('p-captcha::p-captcha.human_verification')
        );
        
        $this->assertEquals(
            'Loading challenge...',
            __('p-captcha::p-captcha.loading_challenge')
        );
        
        $this->assertEquals(
            'Validate',
            __('p-captcha::p-captcha.validate')
        );
    }

    /**
     * Test that Polish translations are loaded correctly
     */
    public function test_polish_translations_are_loaded()
    {
        $this->app->setLocale('pl');
        
        $this->assertEquals(
            'Weryfikacja Człowieka',
            __('p-captcha::p-captcha.human_verification')
        );
        
        $this->assertEquals(
            'Ładowanie wyzwania...',
            __('p-captcha::p-captcha.loading_challenge')
        );
        
        $this->assertEquals(
            'Waliduj',
            __('p-captcha::p-captcha.validate')
        );
    }

    /**
     * Test that Spanish translations are loaded correctly
     */
    public function test_spanish_translations_are_loaded()
    {
        $this->app->setLocale('es');
        
        $this->assertEquals(
            'Verificación Humana',
            __('p-captcha::p-captcha.human_verification')
        );
        
        $this->assertEquals(
            'Cargando desafío...',
            __('p-captcha::p-captcha.loading_challenge')
        );
        
        $this->assertEquals(
            'Validar',
            __('p-captcha::p-captcha.validate')
        );
    }

    /**
     * Test that translation parameters work correctly
     */
    public function test_translation_parameters_work()
    {
        $this->app->setLocale('en');
        
        $this->assertEquals(
            'Add 1 to the last number (5) to get the next number.',
            __('p-captcha::p-captcha.add_1_to_last_number', ['number' => 5])
        );
        
        $this->assertEquals(
            'Triple the last number (9) to get the next number.',
            __('p-captcha::p-captcha.triple_last_number', ['number' => 9])
        );
    }

    /**
     * Test that fallback to English works when translation is missing
     */
    public function test_fallback_to_english_when_translation_missing()
    {
        $this->app->setLocale('fr'); // French not supported
        
        // Should fallback to English
        $this->assertEquals(
            'Human Verification',
            __('p-captcha::p-captcha.human_verification')
        );
    }
} 
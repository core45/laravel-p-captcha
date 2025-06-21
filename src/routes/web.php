<?php

use Illuminate\Support\Facades\Route;
use Core45\LaravelPCaptcha\Http\Controllers\PCaptchaController;

/*
|--------------------------------------------------------------------------
| P-CAPTCHA Package Routes
|--------------------------------------------------------------------------
|
| These routes handle CAPTCHA generation, validation, and widget rendering
|
*/

// Main CAPTCHA API routes
Route::post('/generate', [PCaptchaController::class, 'generate'])->name('p-captcha.generate');
Route::post('/validate', [PCaptchaController::class, 'validate'])->name('p-captcha.validate');
Route::post('/refresh', [PCaptchaController::class, 'refresh'])->name('p-captcha.refresh');

// Hidden CAPTCHA token generation
Route::post('/generate-token', [PCaptchaController::class, 'generateToken'])->name('p-captcha.generate-token');

// Widget rendering route
Route::get('/widget', [PCaptchaController::class, 'widget'])->name('p-captcha.widget');

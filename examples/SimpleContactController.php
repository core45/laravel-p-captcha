<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Core45\LaravelPCaptcha\Traits\HandlesPCaptcha;

/**
 * Simple Contact Controller with P-CAPTCHA
 * 
 * This is the minimal implementation needed for CAPTCHA to work.
 * The middleware handles all CAPTCHA validation automatically.
 */
class SimpleContactController extends Controller
{
    use HandlesPCaptcha;

    /**
     * Show the contact form
     */
    public function create()
    {
        return view('contact.create');
    }

    /**
     * Handle form submission
     * 
     * Route: Route::post('/contact', [SimpleContactController::class, 'store'])
     *        ->middleware('p-captcha')
     *        ->name('contact.store');
     * 
     * The middleware automatically validates CAPTCHA before this method is called.
     * If CAPTCHA fails, the middleware returns an error response and this method
     * is never executed.
     */
    public function store(Request $request)
    {
        // Validate form data (CAPTCHA already validated by middleware)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Process the form (e.g., save to database, send email, etc.)
        // Your business logic here...
        
        // Reset CAPTCHA attempt counter after successful submission
        $this->resetCaptchaAttempts();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We\'ll get back to you soon.'
            ]);
        }

        return redirect()->route('contact.create')
            ->with('success', 'Thank you for your message! We\'ll get back to you soon.');
    }
} 
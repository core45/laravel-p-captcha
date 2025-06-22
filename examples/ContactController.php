<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Contact;
use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Core45\LaravelPCaptcha\Traits\HandlesPCaptcha;

/**
 * Example Contact Controller showing P-CAPTCHA integration
 *
 * This controller demonstrates three different approaches:
 * 1. Using middleware protection (recommended)
 * 2. Using the helper trait for manual control
 * 3. Using the trait's automatic form handling
 */
class ContactController extends Controller
{
    use HandlesPCaptcha;

    /**
     * Show the contact form
     */
    public function create()
    {
        // Pass CAPTCHA status to the view for conditional rendering
        return view('contact.create', $this->withCaptchaStatus([
            'page_title' => 'Contact Us'
        ]));
    }

    /**
     * APPROACH 1: Using Middleware Protection (Recommended)
     *
     * Route definition:
     * Route::post('/contact', [ContactController::class, 'store'])
     *     ->middleware('p-captcha')
     *     ->name('contact.store');
     *
     * The middleware handles CAPTCHA validation automatically.
     * This is the simplest and most secure approach.
     */
    public function store(Request $request)
    {
        // P-CAPTCHA middleware automatically handles:
        // - Bot detection
        // - Alphabet restrictions
        // - Forbidden words detection
        // - Visual CAPTCHA validation
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string|max:1000',
        ]);

        // If we reach this point, the user has passed all CAPTCHA checks
        // including forbidden words detection (if any were found, CAPTCHA was required)
        
        Contact::create($validated);

        return redirect()->back()->with('success', 'Message sent successfully!');
    }

    /**
     * APPROACH 2: Manual CAPTCHA Validation
     *
     * For cases where you need more control over the validation process.
     * Don't use middleware with this approach.
     */
    public function storeManual(Request $request)
    {
        // First validate basic form data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Check if CAPTCHA validation is required and valid
        if ($this->isCaptchaRequired()) {
            if (!$this->validateCaptcha($request)) {
                return $this->captchaErrorResponse($request, 'Please complete the CAPTCHA verification.');
            }
        }

        // Process the form (same as above)
        $contact = Contact::create($validated);
        Mail::to(config('mail.contact_address'))->send(new ContactFormMail($contact));

        return $this->captchaSuccessResponse($request, [
            'message' => 'Thank you for your message! We\'ll get back to you soon.',
            'contact_id' => $contact->id
        ]);
    }

    /**
     * APPROACH 3: Automatic Form Handling with Trait
     *
     * The most convenient approach using the trait's automatic handling.
     * Perfect for simple forms.
     */
    public function storeAutomatic(Request $request)
    {
        return $this->handleFormWithCaptcha(
            $request,
            function (array $validated) {
                // This callback receives validated data (without CAPTCHA fields)
                $contact = Contact::create($validated);

                // Send email
                Mail::to(config('mail.contact_address'))->send(new ContactFormMail($contact));

                // Return any additional data for the response
                return [
                    'message' => 'Thank you for your message! We\'ll get back to you soon.',
                    'contact_id' => $contact->id
                ];
            },
            [
                // Validation rules
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:2000',
            ],
            [
                // Custom validation messages (optional)
                'name.required' => 'Please enter your name.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
            ]
        );
    }

    /**
     * Show CAPTCHA status page (for debugging/admin)
     */
    public function captchaStatus()
    {
        $status = $this->getCaptchaStatus();
        $difficulty = $this->getCaptchaDifficulty();
        $hasFailures = $this->hasRecentCaptchaFailures();

        return view('contact.captcha-status', compact('status', 'difficulty', 'hasFailures'));
    }

    /**
     * Reset CAPTCHA for current session (admin function)
     */
    public function resetCaptcha()
    {
        $this->resetCaptchaAttempts();

        return response()->json([
            'success' => true,
            'message' => 'CAPTCHA status reset successfully.'
        ]);
    }

    /**
     * Generate a new CAPTCHA challenge for AJAX requests
     */
    public function generateCaptcha()
    {
        $challenge = $this->generateCaptchaChallenge();

        return response()->json([
            'success' => true,
            'challenge' => $challenge
        ]);
    }

    /**
     * Example of conditional form processing based on CAPTCHA status
     */
    public function newsletter(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:newsletter_subscribers,email',
        ];

        // For newsletter signup, only require CAPTCHA if there have been recent failures
        // This provides a better UX for legitimate users
        if ($this->hasRecentCaptchaFailures()) {
            return $this->handleFormWithCaptcha(
                $request,
                function (array $validated) {
                    // Subscribe to newsletter
                    \App\Models\NewsletterSubscriber::create($validated);
                    return ['message' => 'Successfully subscribed to newsletter!'];
                },
                $rules
            );
        } else {
            // No CAPTCHA required for clean sessions
            $validated = $request->validate($rules);
            \App\Models\NewsletterSubscriber::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully subscribed to newsletter!'
                ]);
            }

            return back()->with('success', 'Successfully subscribed to newsletter!');
        }
    }
}

/*
=============================================================================
CORRESPONDING BLADE TEMPLATE (resources/views/contact/create.blade.php)
=============================================================================

@extends('layouts.app')

@section('title', $page_title)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ $page_title }}</div>

                <div class="card-body">
                    {{-- Show success message --}}
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Show CAPTCHA status (for debugging) --}}
                    @if(config('app.debug') && isset($captcha_status))
                        <div class="alert alert-info">
                            <strong>CAPTCHA Status:</strong>
                            {{ $captcha_status['required'] ? 'Required' : 'Not Required' }}
                            ({{ $captcha_status['attempt_count'] }}/{{ $captcha_status['threshold'] }} attempts)
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.store') }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   id="subject"
                                   name="subject"
                                   value="{{ old('subject') }}"
                                   required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control @error('message') is-invalid @enderror"
                                      id="message"
                                      name="message"
                                      rows="5"
                                      required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- P-CAPTCHA Widget --}}
                        {{-- This will automatically show after 2 failed attempts --}}
                        @pcaptcha

                        {{-- Show CAPTCHA errors --}}
                        @error('p_captcha')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

=============================================================================
ROUTES CONFIGURATION (routes/web.php)
=============================================================================

use App\Http\Controllers\ContactController;

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');

// Approach 1: Using middleware (recommended)
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('p-captcha')
    ->name('contact.store');

// Approach 2: Manual validation (no middleware)
Route::post('/contact/manual', [ContactController::class, 'storeManual'])
    ->name('contact.store.manual');

// Approach 3: Automatic handling (no middleware)
Route::post('/contact/auto', [ContactController::class, 'storeAutomatic'])
    ->name('contact.store.auto');

// Additional utility routes
Route::get('/contact/captcha-status', [ContactController::class, 'captchaStatus'])
    ->name('contact.captcha.status');

Route::post('/contact/captcha/reset', [ContactController::class, 'resetCaptcha'])
    ->name('contact.captcha.reset');

Route::post('/contact/captcha/generate', [ContactController::class, 'generateCaptcha'])
    ->name('contact.captcha.generate');

// Newsletter with conditional CAPTCHA
Route::post('/newsletter/subscribe', [ContactController::class, 'newsletter'])
    ->name('newsletter.subscribe');

*/

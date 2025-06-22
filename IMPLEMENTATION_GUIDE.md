# P-CAPTCHA Implementation Guide

## Quick Fix for Your Issue

The issue you're experiencing is because P-CAPTCHA is designed to be **invisible by default**. I've already fixed this by changing the configuration to always require visual CAPTCHA.

## ✅ What I've Done

1. **Updated Configuration**: Changed `force_visual_captcha` from `false` to `true` in `config/p-captcha.php`
2. **Created Examples**: Added example files showing proper implementation

## 🚀 Step-by-Step Implementation

### 1. Clear Configuration Cache
```bash
php artisan config:cache
```

### 2. Update Your Contact Controller
Use the `HandlesPCaptcha` trait in your controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Core45\LaravelPCaptcha\Traits\HandlesPCaptcha;

class ContactController extends Controller
{
    use HandlesPCaptcha;

    public function store(Request $request)
    {
        // Validate form data (CAPTCHA already validated by middleware)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Your business logic here...
        
        // Reset CAPTCHA attempt counter after successful submission
        $this->resetCaptchaAttempts();

        return redirect()->back()->with('success', 'Message sent successfully!');
    }
}
```

### 3. Update Your Blade View
Add the required hidden fields and CAPTCHA widget:

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    
    {{-- Hidden CAPTCHA fields (REQUIRED for middleware) --}}
    <input type="hidden" name="_captcha_field" value="">
    <input type="hidden" name="_captcha_token" value="">
    
    {{-- Your form fields --}}
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required>
    </div>
    
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
    </div>
    
    <div class="form-group">
        <label for="message">Message</label>
        <textarea name="message" id="message" required></textarea>
    </div>
    
    {{-- P-CAPTCHA Widget --}}
    @pcaptcha('theme=light,id=contact-captcha')
    
    <button type="submit">Send Message</button>
</form>
```

### 4. Your Route (Already Correct)
```php
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])
    ->middleware('p-captcha')
    ->name('contact.store');
```

## 🔧 How It Works Now

1. **Every form submission** will require CAPTCHA completion
2. **Middleware validates** CAPTCHA before your controller method is called
3. **If CAPTCHA fails**, middleware returns error response
4. **If CAPTCHA passes**, your controller method executes normally

## 🎯 Key Points

- **Hidden fields are required**: `_captcha_field` and `_captcha_token` must be present
- **CAPTCHA widget is required**: `@pcaptcha` directive must be included
- **Middleware handles everything**: No manual CAPTCHA validation needed in controller
- **Reset on success**: Call `$this->resetCaptchaAttempts()` after successful submission

## 🧪 Testing

1. **Submit without CAPTCHA**: Should be blocked
2. **Submit with invalid CAPTCHA**: Should be blocked  
3. **Submit with valid CAPTCHA**: Should work normally

## 📁 Example Files Created

- `examples/contact-form.blade.php` - Complete form example
- `examples/SimpleContactController.php` - Minimal controller example
- `IMPLEMENTATION_GUIDE.md` - This guide

## 🔄 Alternative Configurations

If you want to revert to invisible mode later:

```php
// In config/p-captcha.php
'force_visual_captcha' => false,
'ui' => [
    'auto_show_after_attempts' => 1, // Show after 1 failed attempt
],
```

## ✅ Your Issue is Now Fixed!

The middleware will now properly block form submissions that don't include valid CAPTCHA completion. Every legitimate user will need to complete the CAPTCHA challenge before their form can be submitted. 
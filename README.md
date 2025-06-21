# Laravel P-CAPTCHA

A sophisticated CAPTCHA package for Laravel applications featuring adaptive difficulty, multiple challenge types including beam alignment, pattern matching, and proof-of-work challenges.

## Features

- **Multiple Challenge Types**: Beam alignment, pattern matching, sequence completion, and proof-of-work
- **Adaptive Difficulty**: Automatically adjusts difficulty based on user performance
- **Modern UI**: Dark/light themes with smooth animations
- **Mobile Friendly**: Touch-optimized for mobile devices
- **High Performance**: Efficient caching and rate limiting
- **Security First**: Single-use challenges, CSRF protection, and comprehensive logging
- **Easy Integration**: Simple Blade directive and middleware

## Installation

Install the package via Composer:

```bash
composer require core45/laravel-p-captcha
```

**Publish assets (required for frontend functionality):**

```bash
php artisan p-captcha:install
```

**Note:** The install command is required for the CAPTCHA widget to display and function properly. Without it, you'll have unstyled/broken frontend components, though the backend validation will still work.

This command will:
- Publish the configuration file to `config/p-captcha.php`
- Publish CSS and JavaScript assets to `public/vendor/p-captcha/` (required)
- Create necessary directories
- Optionally update your `.gitignore` file

**Advanced: Selective publishing (optional):**

```bash
# Publish only configuration
php artisan p-captcha:install --config

# Publish only assets (minimum required)
php artisan p-captcha:install --assets

# Publish only views (for customization)
php artisan p-captcha:install --views

# Force overwrite existing files
php artisan p-captcha:install --force
```

## Quick Start

### 1. Add CAPTCHA to Your Form

Simply add the `@pcaptcha` directive to any Blade template:

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
    </div>
    
    <div class="form-group">
        <label for="message">Message</label>
        <textarea name="message" id="message" required></textarea>
    </div>
    
    {{-- Add P-CAPTCHA widget --}}
    @pcaptcha
    
    <button type="submit">Send Message</button>
</form>
```

### 2. Protect Your Route

Add the middleware to your route:

```php
use Core45\LaravelPCaptcha\Middleware\ProtectWithPCaptcha;

Route::post('/contact/store', [ContactController::class, 'store'])
    ->middleware('p-captcha')
    ->name('contact.store');
```

### 3. Handle Success in Controller

Optionally reset attempt counter after successful submission:

```php
use YourVendor\LaravelPCaptcha\Middleware\ProtectWithPCaptcha;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Your form validation and processing
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string|max:1000',
        ]);
        
        // Process the form...
        Contact::create($validated);
        
        // Reset CAPTCHA attempt counter (optional)
        ProtectWithPCaptcha::resetAttemptCount();
        
        return redirect()->back()->with('success', 'Message sent successfully!');
    }
}
```

That's it! The CAPTCHA will automatically appear after 2 failed form submissions.

## Advanced Usage

### Custom CAPTCHA Options

You can customize the CAPTCHA widget with options:

```blade
@pcaptcha('theme=light,id=my-captcha')
```

Available options:
- `theme`: `dark` (default) or `light`
- `id`: Custom container ID
- `auto_load`: `true` (default) or `false`

### Manual CAPTCHA Control

For more control, you can manually initialize and validate:

```blade
<div id="manual-captcha" class="p-captcha-container" data-auto-load="false">
    <!-- CAPTCHA content will be loaded here -->
</div>

<script>
// Initialize manually
const captcha = PCaptcha.init('manual-captcha');

// Check if verified
if (PCaptcha.isVerified('manual-captcha')) {
    console.log('CAPTCHA completed!');
}

// Listen for verification events
document.addEventListener('p-captcha-verified', function(event) {
    console.log('CAPTCHA verified:', event.detail);
});
</script>
```

### Using the Facade

You can interact with the CAPTCHA service directly:

```php
use Core45\LaravelPCaptcha\Facades\PCaptcha;

// Generate a challenge
$challenge = PCaptcha::generateChallenge();

// Validate a solution
$isValid = PCaptcha::validateSolution($challengeId, $solution);

// Render CAPTCHA HTML
$html = PCaptcha::renderCaptcha('theme=light');
```

## Configuration

The package configuration is located in `config/p-captcha.php`. Key settings include:

### Difficulty Levels

```php
'difficulty_levels' => [
    'easy' => 2,      // 2 leading zeros in hash
    'medium' => 3,    // 3 leading zeros in hash
    'hard' => 4,      // 4 leading zeros in hash
    'extreme' => 5,   // 5 leading zeros in hash
],
```

### Challenge Types

```php
'challenge_types' => [
    'beam_alignment',    // Drag beam source to target
    'pattern_match',     // Complete symbol patterns
    'sequence_complete', // Complete number sequences
    'proof_of_work',     // Computational challenge
],
```

### Adaptive Difficulty

```php
'adaptive_difficulty' => [
    'enabled' => true,
    'failure_thresholds' => [
        'medium' => 1,   // 1 failure = medium difficulty
        'hard' => 3,     // 3 failures = hard difficulty
        'extreme' => 5,  // 5 failures = extreme difficulty
    ],
],
```

### UI Customization

```php
'ui' => [
    'theme' => 'dark',           // 'dark' or 'light'
    'auto_show_after_attempts' => 2, // Show after N failed attempts
    'beam_alignment' => [
        'tolerance' => 15,       // Pixel tolerance
        'canvas_width' => 400,
        'canvas_height' => 300,
    ],
],
```

## Challenge Types

### 1. Beam Alignment
An innovative challenge where users drag a beam source to align with a target.

- **Difficulty**: Visual coordination
- **Mobile**: Touch-optimized
- **Accessibility**: Keyboard navigation support

### 2. Pattern Matching
Users must identify and complete visual patterns using symbols.

- **Patterns**: Geometric shapes, arrows, card suits
- **Difficulty**: Pattern complexity varies
- **Cognitive**: Tests pattern recognition

### 3. Sequence Completion
Mathematical sequence completion challenges.

- **Types**: Arithmetic, geometric sequences
- **Difficulty**: Number complexity varies
- **Educational**: Engaging for users

### 4. Proof of Work
Computational challenges requiring hash calculations.

- **Security**: Resistant to automated attacks
- **Scalable**: Difficulty adjusts automatically
- **Performance**: Balances security vs user experience

## Security Features

- **Single-Use Challenges**: Each challenge can only be used once
- **Rate Limiting**: Prevents brute force attacks
- **CSRF Protection**: Integrated with Laravel's CSRF system
- **Session Tracking**: Adaptive difficulty per user session
- **Comprehensive Logging**: All attempts logged for monitoring
- **IP-based Protection**: Optional IP blocking for suspicious activity

## Customization

### Custom Themes

Create custom themes by overriding CSS variables:

```css
.p-captcha-container[data-theme="custom"] {
    --primary-color: #your-color;
    --background: your-gradient;
    --text-color: #your-text;
}
```

### Custom Challenge Types

Extend the service to add your own challenge types:

```php
class CustomPCaptchaService extends PCaptchaService
{
    protected function generateCustomChallenge(): array
    {
        // Your custom challenge logic
        return [
            'challenge_data' => [...],
            'solution' => [...],
            'instructions' => 'Your instructions'
        ];
    }
}
```

## API Endpoints

The package provides several API endpoints:

- `POST /p-captcha/generate` - Generate new challenge
- `POST /p-captcha/validate` - Validate solution
- `POST /p-captcha/refresh` - Get new challenge
- `GET /p-captcha/widget` - Get widget HTML

## Events

Listen for CAPTCHA events in JavaScript:

```javascript
// CAPTCHA verified successfully
document.addEventListener('p-captcha-verified', function(event) {
    console.log('Verified:', event.detail.containerId);
});

// CAPTCHA failed
document.addEventListener('p-captcha-failed', function(event) {
    console.log('Failed:', event.detail.containerId);
});
```

## Testing

Run the package tests:

```bash
vendor/bin/phpunit
```

Test specific challenge types:

```php
use Core45\LaravelPCaptcha\Services\PCaptchaService;

public function test_beam_alignment_challenge()
{
    $service = new PCaptchaService();
    $challenge = $service->generateChallenge();
    
    $this->assertArrayHasKey('challenge_data', $challenge);
    $this->assertArrayHasKey('solution', $challenge);
}
```

## Performance

### Caching

The package uses Laravel's cache system:

- **Challenge Storage**: 10 minutes (configurable)
- **Failure Tracking**: 1 hour (configurable)
- **Rate Limiting**: 1 minute windows

### Optimization Tips

1. **Use Redis**: For better performance in production
2. **CDN Assets**: Serve CSS/JS from CDN
3. **Cache Warming**: Pre-generate challenges during low traffic
4. **Monitor Logs**: Track performance metrics

## Troubleshooting

### Common Issues

**CAPTCHA not showing:**
- Ensure assets are published: `php artisan vendor:publish --tag=p-captcha-assets`
- Check JavaScript console for errors
- Verify CSRF token is present

**Validation always fails:**
- Check session configuration
- Verify cache is working
- Review server logs

**Styling issues:**
- Publish and customize views: `php artisan vendor:publish --tag=p-captcha-views`
- Override CSS variables for themes
- Check for CSS conflicts

### Debug Mode

Enable debug logging in config:

```php
'security' => [
    'log_attempts' => true,
],
```

## Requirements

- PHP 8.1+
- Laravel 9.0+
- OpenSSL extension

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to [security@yourpackage.com](mailto:security@yourpackage.com).

## Credits

- Advanced CAPTCHA system implementation
- Built for Laravel by [Core45](https://github.com/core45)

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

# Laravel P-CAPTCHA

A sophisticated CAPTCHA package for Laravel applications featuring adaptive difficulty, multiple challenge types including beam alignment, pattern matching, and proof-of-work challenges.

## Features

- **Hidden Bot Detection**: Invisible CAPTCHA with honeypot fields, timing validation, and JavaScript checks
- **Smart Visual Challenges**: Beam alignment and sequence completion only shown when bots are detected
- **Alphabet Restrictions**: Control which writing systems are allowed in form submissions
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

## Basic Usage

### 1. Add the CAPTCHA to your Blade views

```blade
<form method="POST" action="/contact">
    @csrf
    
    <!-- Your form fields -->
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    
    <!-- P-CAPTCHA Widget (no parameters needed) -->
    @pcaptcha
    
    <button type="submit">Submit</button>
</form>
```

**Note:** The `@pcaptcha` directive no longer accepts parameters. All configuration is controlled via the config file (`config/p-captcha.php`).

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

That's it! **Most users will never see a CAPTCHA challenge.** The system uses hidden bot detection techniques, and only shows visual challenges when suspicious behavior is detected.

## How It Works

**Hidden Bot Detection (Invisible to Users):**
- **Honeypot Fields**: Hidden form fields that bots fill but humans don't see
- **Timing Validation**: Detects forms submitted too quickly (likely bots)
- **JavaScript Validation**: Ensures browser executes JavaScript properly
- **Session Validation**: Checks session consistency and user agent

**Visual Challenges (Only When Needed):**
- **Beam Alignment**: Drag-and-drop challenge for suspected bots
- **Sequence Completion**: Mathematical pattern recognition
- **Adaptive Difficulty**: Gets harder with repeated failures

**User Experience:**
1. **Normal Users**: Complete forms without any CAPTCHA (invisible protection)
2. **Suspected Bots**: Must complete visual challenge to proceed
3. **Failed Attempts**: More challenges required for suspicious behavior

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

All CAPTCHA behavior is controlled via the configuration file. Publish the config file:

```bash
php artisan vendor:publish --provider="Core45\LaravelPCaptcha\Providers\PCaptchaServiceProvider" --tag="config"
```

### Key Configuration Options

```php
// config/p-captcha.php

return [
    // Force visual CAPTCHA to always show (bypasses bot detection)
    'force_visual_captcha' => false, // Set to true to always show visual challenges
    
    // UI/UX settings
    'ui' => [
        'theme' => 'light',           // 'dark' or 'light'
        'show_instructions' => true,
        'auto_show_after_attempts' => 3,
    ],
    
    // Available challenge types
    'challenge_types' => [
        'beam_alignment',    // Drag beam source to target
        'sequence_complete', // Complete number sequences
    ],
    
    // ... other options
];
```

### Important Settings

- **`force_visual_captcha`**: 
  - `false` (default): CAPTCHA only shows when bot detection triggers it or after failed attempts
  - `true`: CAPTCHA always shows, bypassing bot detection
- **`ui.theme`**: Choose between `'light'` and `'dark'` themes.

## Bot Detection Methods

The package uses multiple invisible techniques to detect bots before showing visual challenges:

### 1. Honeypot Fields
Hidden form fields that are invisible to users but filled by bots:
```html
<!-- These fields are positioned off-screen -->
<input type="text" name="website" style="position:absolute;left:-10000px;">
<input type="email" name="search_username" style="position:absolute;left:-10000px;">
```

### 2. Timing Validation
Detects forms submitted suspiciously fast:
- **Too Fast**: Forms submitted in less than 2 seconds (likely bot)
- **Too Slow**: Forms submitted after 20 minutes (expired token)
- **Normal**: 2 seconds to 20 minutes (human behavior)

### 3. JavaScript Validation
Ensures browser properly executes JavaScript:
- Generates encrypted tokens via AJAX
- Validates session consistency
- Checks user agent matching

### 4. Session Validation
Verifies session integrity:
- Session ID consistency
- IP address validation
- User agent matching
- CSRF token validation

### When Visual CAPTCHAs Appear

**Visual challenges only appear when:**
1. **Bot behavior detected** (honeypot filled, timing suspicious, etc.)
2. **Multiple failed attempts** (after 3 failures for legitimate users)
3. **JavaScript disabled** (suspicious behavior)
4. **Invalid tokens** (tampered requests)

**Normal users experience:**
- No visible CAPTCHA challenges
- Seamless form submission
- Invisible protection

## Force Visual CAPTCHA (Optional)

You can force the visual CAPTCHA to always appear by setting this in your config:

```php
// In config/p-captcha.php
'force_visual_captcha' => true,
```

**When to use this:**
- **Testing**: Always see the CAPTCHA during development
- **High Security**: Always require visual verification regardless of bot detection
- **Demo Purposes**: Show the CAPTCHA functionality to clients
- **Compliance**: Meet specific security requirements that mandate visible verification

**With this setting enabled:**
- All users will see visual challenges immediately
- Hidden bot detection still runs in the background
- Useful for extra security or testing scenarios

**Environment-specific configuration:**
```php
// Always show in development for testing
'force_visual_captcha' => env('APP_ENV') === 'local',

// Or use a specific environment variable
'force_visual_captcha' => env('PCAPTCHA_FORCE_VISUAL', false),
```

Then in your `.env` file:
```bash
# For testing/development
PCAPTCHA_FORCE_VISUAL=true

# For production (default)
PCAPTCHA_FORCE_VISUAL=false
```

## Visual Challenge Types

When bot behavior is detected, users may encounter these challenges:

### 1. Beam Alignment
An innovative challenge where users drag a beam source to align with a target.

- **Difficulty**: Visual coordination
- **Mobile**: Touch-optimized
- **Accessibility**: Keyboard navigation support

### 2. Sequence Completion
Mathematical sequence completion challenges.

- **Types**: Arithmetic, geometric sequences
- **Difficulty**: Number complexity varies
- **Educational**: Engaging for users

## Security Features

- **Single-Use Challenges**: Each challenge can only be used once
- **Rate Limiting**: Prevents brute force attacks
- **CSRF Protection**: Integrated with Laravel's CSRF system
- **Session Tracking**: Adaptive difficulty per user session
- **Comprehensive Logging**: All attempts logged for monitoring
- **IP-based Protection**: Optional IP blocking for suspicious activity

## Alphabet Restrictions

The package can detect and handle requests containing text in specific alphabets. This is useful for filtering out spam from certain regions or languages.

### Configuration

```php
// config/p-captcha.php
'allowed_alphabet' => [
    'latin' => true,      // Allow Latin alphabet
    'cyrillic' => false,  // Block Cyrillic alphabet
    'arabic' => false,    // Block Arabic alphabet
    // ... other alphabets
],
'forbidden_alphabet_deny' => true, // Whether to deny requests with forbidden alphabets
```

### Behavior

- **When `forbidden_alphabet_deny = true`**: Requests with forbidden alphabets are immediately denied with the message "Form submission contains forbidden characters."
- **When `forbidden_alphabet_deny = false`**: Requests with forbidden alphabets are allowed but require visual CAPTCHA completion.

### Example Usage

```php
// This would trigger alphabet detection:
$data = [
    'name' => 'Иван Петров',
    'email' => 'ivan@example.com',
    'message' => 'Привет мир!'
];
// Detected: 'cyrillic' (if not allowed)
```

### Supported Alphabets

- `latin` - Latin alphabet (A-Z, a-z)
- `cyrillic` - Cyrillic alphabet (А-Я, а-я)
- `arabic` - Arabic alphabet
- `hebrew` - Hebrew alphabet
- `greek` - Greek alphabet
- `thai` - Thai alphabet
- `hindi` - Hindi alphabet
- `chinese` - Chinese characters
- `japanese` - Japanese characters
- `korean` - Korean characters
- `other` - Any other unlisted script

## Forbidden Words Detection

The package can automatically detect and challenge users who use specific words or phrases commonly associated with spam.

### Configuration

```php
// config/p-captcha.php
'forbidden_words' => [
    'eric jones',
    'shit',
    'spam',
    'viagra',
    'casino',
    'loan',
    'credit card',
    'make money fast',
    'work from home',
    'weight loss',
    'free trial',
    'click here',
    'buy now',
    'limited time',
    'act now',
    'urgent',
    'exclusive offer',
    'guaranteed',
    'risk free',
    'no obligation'
],
```

### Behavior

When forbidden words are detected in any form field:
- The user is automatically treated as a spam bot
- Visual CAPTCHA is always required
- The detection is case-insensitive
- Works with both single words and phrases
- Searches through all form fields (name, email, message, etc.)

### Examples

```php
// This would trigger forbidden words detection:
$data = [
    'name' => 'Eric Smith',
    'email' => 'eric@example.com',
    'message' => 'Hello, I am Eric Jones and I have exclusive offers for you.'
];
// Detected: 'eric jones', 'exclusive offer'
```

For detailed information about forbidden words detection and advanced usage, see [FORBIDDEN_WORDS.md](FORBIDDEN_WORDS.md).

## Challenge Types

When bot behavior is detected, users may encounter these challenges:

### 1. Beam Alignment
An innovative challenge where users drag a beam source to align with a target.

- **Difficulty**: Visual coordination
- **Mobile**: Touch-optimized
- **Accessibility**: Keyboard navigation support

### 2. Sequence Completion
Mathematical sequence completion challenges.

- **Types**: Arithmetic, geometric sequences
- **Difficulty**: Number complexity varies
- **Educational**: Engaging for users

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

## Service Architecture

The P-CAPTCHA package uses a modular service architecture for better maintainability and separation of concerns:

### Core Services

- **PCaptchaService**: Main service handling CAPTCHA generation, validation, and rendering
- **LanguageDetectionService**: Dedicated service for alphabet and forbidden words detection

### Service Responsibilities

#### PCaptchaService
- CAPTCHA challenge generation and validation
- Challenge type selection and difficulty calculation
- Frontend rendering and HTML generation
- Session management and caching
- Delegates language detection to `LanguageDetectionService`

#### LanguageDetectionService
- Alphabet detection in form submissions
- Forbidden words/phrases detection
- Text field extraction from nested data structures
- Unicode-aware text analysis
- Case-insensitive pattern matching

### Dependency Injection

The services are properly registered with Laravel's service container:

```php
// LanguageDetectionService is automatically injected into PCaptchaService
$captchaService = app('p-captcha');
```

## Configuration

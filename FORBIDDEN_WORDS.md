# Forbidden Words Detection

The P-CAPTCHA package includes a powerful forbidden words detection system that automatically identifies and challenges users who use specific words or phrases commonly associated with spam.

## Overview

This feature scans all form submissions for predefined forbidden words and phrases. When detected, the user is automatically treated as a spam bot and required to complete a visual CAPTCHA challenge.

## Configuration

### Basic Setup

Add forbidden words to your `config/p-captcha.php`:

```php
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

### Common Spam Patterns

Here are categories of forbidden words you might want to include:

#### 1. Known Spammer Names
```php
'eric jones',
'john smith spam',
'viagra sales',
```

#### 2. Inappropriate Language
```php
'shit',
'fuck',
'asshole',
```

#### 3. Pharmaceutical Spam
```php
'viagra',
'cialis',
'levitra',
'pharmacy',
'medication',
```

#### 4. Gambling/Casino
```php
'casino',
'poker',
'betting',
'lottery',
'jackpot',
```

#### 5. Financial Scams
```php
'loan',
'credit card',
'make money fast',
'work from home',
'investment opportunity',
'get rich quick',
```

#### 6. Marketing Spam
```php
'free trial',
'click here',
'buy now',
'limited time',
'act now',
'urgent',
'exclusive offer',
'guaranteed',
'risk free',
'no obligation',
```

#### 7. Weight Loss Scams
```php
'weight loss',
'diet pill',
'burn fat',
'lose weight fast',
```

## How It Works

### Detection Process

1. **Text Extraction**: All form fields are scanned for text content
2. **Case-Insensitive Matching**: Words are matched regardless of case
3. **Phrase Detection**: Both single words and multi-word phrases are supported
4. **Nested Data Support**: Works with complex form structures and arrays

### Behavior

When forbidden words are detected:

- **Automatic CAPTCHA**: User is forced to complete visual CAPTCHA
- **Spam Classification**: User is treated as a potential spam bot
- **Logging**: Detection is logged when `APP_DEBUG` is enabled
- **No Blocking**: Unlike alphabet restrictions, users are not blocked, just challenged

### Examples

#### Example 1: Single Forbidden Word
```php
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'message' => 'Hello, I would like to discuss viagra options.'
];
// Result: 'viagra' detected → Force CAPTCHA
```

#### Example 2: Multiple Forbidden Words
```php
$data = [
    'name' => 'Eric Smith',
    'email' => 'eric@example.com',
    'message' => 'Hello, I am Eric Jones and I have exclusive offers for you.'
];
// Result: 'eric jones', 'exclusive offer' detected → Force CAPTCHA
```

#### Example 3: Case Variations
```php
$data = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'message' => 'This message contains SHIT and SPAM in different cases.'
];
// Result: 'shit', 'spam' detected → Force CAPTCHA
```

#### Example 4: Nested Data
```php
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'message' => 'Hello, this is a clean message.',
    'additional_data' => [
        'subject' => 'Inquiry about services',
        'comments' => 'I would like to discuss viagra and casino opportunities.'
    ]
];
// Result: 'viagra', 'casino' detected → Force CAPTCHA
```

## Performance Considerations

### Optimization

- **Fast Matching**: Uses efficient string matching algorithms
- **Early Exit**: Stops searching once forbidden words are found
- **Minimal Overhead**: Lightweight detection process
- **Caching**: Results are not cached to ensure real-time detection

### Performance Test Results

Testing with a 7,611 character message containing forbidden words:
- **Processing Time**: ~0.1ms
- **Memory Usage**: Minimal
- **Scalability**: Handles large forms efficiently

## Debugging

### Enable Debug Logging

Set `APP_DEBUG=true` in your `.env` file to enable detailed logging:

```php
// Logs will show:
[
    'detected_words' => ['eric jones', 'exclusive offer'],
    'forbidden_detected' => true,
    'total_forbidden_words' => 20,
    'ip' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...'
]
```

### Test Script

Use the provided test script to verify your configuration:

```bash
php examples/forbidden-words-test.php
```

This script tests various scenarios and shows exactly what would be detected.

## Best Practices

### 1. Start Conservative

Begin with a small list of obvious spam words and expand based on your needs:

```php
'forbidden_words' => [
    'eric jones',  // Known spammer
    'viagra',      // Pharmaceutical spam
    'casino',      // Gambling spam
],
```

### 2. Monitor False Positives

Watch your logs for legitimate users being flagged and adjust your list accordingly.

### 3. Use Specific Phrases

Prefer specific phrases over generic words to reduce false positives:

```php
// Good - specific
'make money fast',
'work from home',

// Avoid - too generic
'money',
'work',
```

### 4. Regular Updates

Update your forbidden words list regularly based on new spam patterns you observe.

### 5. Combine with Other Features

Use forbidden words detection alongside other P-CAPTCHA features:

- **Alphabet Restrictions**: Block certain writing systems
- **Bot Detection**: Automatic bot behavior detection
- **Visual CAPTCHA**: Manual verification when needed

## Integration Examples

### Laravel Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // P-CAPTCHA middleware handles forbidden words detection automatically
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string|max:1000',
        ]);

        // If forbidden words were detected, user already completed CAPTCHA
        // Process the form normally
        Contact::create($validated);

        return redirect()->back()->with('success', 'Message sent successfully!');
    }
}
```

### Blade View

```php
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    
    <!-- P-CAPTCHA directive automatically handles forbidden words -->
    @pcaptcha
    
    <div>
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required>
    </div>

    <div>
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
    </div>

    <div>
        <label for="message">Message</label>
        <textarea name="message" id="message" required></textarea>
    </div>

    <button type="submit">Send Message</button>
</form>
```

## Troubleshooting

### Common Issues

#### 1. Words Not Being Detected

- Check that words are exactly as configured (case doesn't matter)
- Verify the word is in the `forbidden_words` array
- Ensure the form field contains the word (not just similar text)

#### 2. Too Many False Positives

- Make your forbidden words more specific
- Use phrases instead of single words
- Monitor logs to see what's being detected

#### 3. Performance Issues

- Reduce the size of your forbidden words list
- Use more specific phrases to reduce matching overhead
- Consider using alphabet restrictions for broader filtering

### Debug Commands

```bash
# Test forbidden words detection
php examples/forbidden-words-test.php

# Check configuration
php artisan config:show p-captcha

# View logs (when APP_DEBUG=true)
tail -f storage/logs/laravel.log | grep "P-CAPTCHA"
```

## Security Considerations

### Privacy

- Forbidden words detection happens server-side
- No forbidden words are sent to the client
- Detection results are logged only when debugging is enabled

### Evasion Attempts

The system handles common evasion attempts:

- **Case Variations**: `ERIC JONES`, `eric jones`, `Eric Jones`
- **Partial Matches**: Detects phrases within larger text
- **Nested Data**: Searches through complex form structures

### Recommendations

1. **Regular Updates**: Keep your forbidden words list current
2. **Monitoring**: Watch for new spam patterns
3. **Backup Plan**: Always have visual CAPTCHA as fallback
4. **User Experience**: Don't make the list too aggressive

## Conclusion

The forbidden words detection feature provides an effective first line of defense against spam while maintaining a good user experience for legitimate users. When combined with other P-CAPTCHA features, it creates a comprehensive spam protection system. 
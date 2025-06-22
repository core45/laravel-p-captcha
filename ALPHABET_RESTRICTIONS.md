# Alphabet Restrictions Feature

The P-CAPTCHA package now includes a powerful alphabet restriction system that allows you to control which writing systems are permitted in form submissions. This feature is particularly useful for:

- Preventing spam from specific regions or languages
- Ensuring form submissions are in your preferred language
- Adding an additional layer of security against automated attacks
- Controlling the user experience based on language preferences

## Configuration

The alphabet restrictions are configured in the `config/p-captcha.php` file:

```php
/**
 * Alphabet restrictions
 * 
 * Control which alphabets are allowed in form submissions.
 * When a forbidden alphabet is detected:
 * - If 'forbidden_alphabet_deny' is true: CAPTCHA fails and blocks the data
 * - If 'forbidden_alphabet_deny' is false: CAPTCHA is always shown to challenge the user
 * 
 * Top 10 most popular writing systems plus 'other' for unlisted scripts:
 * 1. Latin (English, French, German, Spanish, etc.)
 * 2. Chinese (Simplified/Traditional Chinese)
 * 3. Arabic (Arabic, Persian, Urdu, etc.)
 * 4. Devanagari (Hindi, Marathi, Nepali, etc.)
 * 5. Cyrillic (Russian, Bulgarian, Serbian, etc.)
 * 6. Thai (Thai language)
 * 7. Korean (Korean Hangul)
 * 8. Japanese (Hiragana, Katakana, Kanji)
 * 9. Bengali (Bengali, Assamese)
 * 10. Tamil (Tamil language)
 * 11. Other (all unlisted writing systems)
 */
'allowed_alphabet' => [
    'latin' => true,        // Latin script (English, French, German, Spanish, etc.)
    'chinese' => false,     // Chinese characters (Simplified/Traditional)
    'arabic' => false,      // Arabic script (Arabic, Persian, Urdu, etc.)
    'devanagari' => false,  // Devanagari script (Hindi, Marathi, Nepali, etc.)
    'cyrillic' => false,    // Cyrillic script (Russian, Bulgarian, Serbian, etc.)
    'thai' => false,        // Thai script
    'korean' => false,      // Korean Hangul
    'japanese' => false,    // Japanese characters (Hiragana, Katakana, Kanji)
    'bengali' => false,     // Bengali script
    'tamil' => false,       // Tamil script
    'other' => false,       // All other unlisted writing systems
],
'forbidden_alphabet_deny' => true,  // Whether to deny requests with forbidden alphabets
```

## Behavior Modes

The system supports two modes of operation based on the `forbidden_alphabet_deny` setting:

### Mode 1: Deny Forbidden Alphabets (Default)
When `forbidden_alphabet_deny` is `true`:
- Requests containing forbidden alphabets are immediately rejected
- A clear error message is returned to the user
- The form data is not processed
- No CAPTCHA challenge is shown

### Mode 2: Force CAPTCHA for Forbidden Alphabets
When `forbidden_alphabet_deny` is `false`:
- Requests containing forbidden alphabets always trigger a visual CAPTCHA
- The user must complete the CAPTCHA challenge to proceed
- This allows legitimate users with non-Latin names to still submit forms
- Provides an additional verification step for suspicious submissions

## Supported Writing Systems

The system detects the following writing systems:

| Alphabet | Scripts/Languages | Unicode Ranges |
|----------|------------------|----------------|
| `latin` | English, French, German, Spanish, etc. | Basic Latin, Latin-1 Supplement, Latin Extended |
| `chinese` | Simplified Chinese, Traditional Chinese | CJK Unified Ideographs |
| `arabic` | Arabic, Persian, Urdu, etc. | Arabic, Arabic Extended |
| `devanagari` | Hindi, Marathi, Nepali, etc. | Devanagari |
| `cyrillic` | Russian, Bulgarian, Serbian, etc. | Cyrillic, Cyrillic Extended |
| `thai` | Thai | Thai |
| `korean` | Korean (Hangul) | Hangul Syllables, Hangul Jamo |
| `japanese` | Japanese (Hiragana, Katakana, Kanji) | Hiragana, Katakana, CJK Unified Ideographs |
| `bengali` | Bengali, Assamese | Bengali |
| `tamil` | Tamil | Tamil |
| `other` | All other writing systems | Hebrew, Greek, Telugu, Kannada, Malayalam, Gujarati, Punjabi, Odia, Sinhala, Khmer, Lao, Myanmar, Ethiopic, Armenian, Georgian, Mongolian, Tibetan, and any other Unicode scripts |

## Usage Examples

### Example 1: Allow Only Latin Characters
```php
'allowed_alphabet' => [
    'latin' => true,
    'chinese' => false,
    'arabic' => false,
    'devanagari' => false,
    'cyrillic' => false,
    'thai' => false,
    'korean' => false,
    'japanese' => false,
    'bengali' => false,
    'tamil' => false,
    'other' => false,
],
'forbidden_alphabet_deny' => true,
```

### Example 2: Allow Latin and Chinese
```php
'allowed_alphabet' => [
    'latin' => true,
    'chinese' => true,
    'arabic' => false,
    'devanagari' => false,
    'cyrillic' => false,
    'thai' => false,
    'korean' => false,
    'japanese' => false,
    'bengali' => false,
    'tamil' => false,
    'other' => false,
],
'forbidden_alphabet_deny' => true,
```

### Example 3: Allow Indian Scripts
```php
'allowed_alphabet' => [
    'latin' => true,
    'chinese' => false,
    'arabic' => false,
    'devanagari' => true,  // Hindi, Marathi, Nepali
    'cyrillic' => false,
    'thai' => false,
    'korean' => false,
    'japanese' => false,
    'bengali' => true,     // Bengali, Assamese
    'tamil' => true,       // Tamil
    'other' => false,
],
'forbidden_alphabet_deny' => true,
```

### Example 4: Force CAPTCHA for Non-Latin
```php
'allowed_alphabet' => [
    'latin' => true,
    'chinese' => false,
    'arabic' => false,
    'devanagari' => false,
    'cyrillic' => false,
    'thai' => false,
    'korean' => false,
    'japanese' => false,
    'bengali' => false,
    'tamil' => false,
    'other' => false,
],
'forbidden_alphabet_deny' => false, // Force CAPTCHA instead of denying
```

## Error Messages

When a forbidden alphabet is detected, users receive clear error messages:

### JSON Response (API)
```json
{
    "success": false,
    "message": "Form submission contains forbidden alphabets: cyrillic. Only Latin characters are allowed.",
    "alphabet_violation": true,
    "forbidden_alphabets": ["cyrillic"],
    "errors": {
        "alphabet": ["Form submission contains forbidden alphabets: cyrillic. Only Latin characters are allowed."]
    }
}
```

### Form Response (Web)
- Error message: "Form submission contains forbidden alphabets: cyrillic. Only Latin characters are allowed."
- Form data is preserved (except CAPTCHA fields)
- User can correct the input and resubmit

## Testing

You can test the alphabet detection feature using the provided test script:

```bash
php packages/core45/laravel-p-captcha/examples/alphabet-test.php
```

This script demonstrates:
- Detection of various alphabets
- Different configuration scenarios
- Expected behavior for each mode

## Implementation Details

### Detection Process
1. **Data Extraction**: All text fields from the request are extracted (excluding CAPTCHA-related fields)
2. **Alphabet Analysis**: Each text field is analyzed using Unicode range matching
3. **Configuration Check**: Detected alphabets are checked against the allowed configuration
4. **Action Decision**: Based on `forbidden_alphabet_deny`, the system either denies the request or forces CAPTCHA

### Performance Considerations
- Alphabet detection uses efficient regex patterns with Unicode support
- Only text fields are analyzed (CAPTCHA fields are excluded)
- Detection happens early in the middleware process
- Results are logged when `APP_DEBUG` is enabled

### Security Features
- Comprehensive Unicode range coverage for accurate detection
- Configurable per-alphabet permissions
- Clear error messages for transparency
- Debug logging for monitoring and troubleshooting
- Integration with existing CAPTCHA system

## Best Practices

1. **Start Conservative**: Begin with only Latin characters allowed and expand as needed
2. **Monitor Usage**: Use debug logging to understand which alphabets are being used
3. **User Communication**: Clearly communicate language requirements to users
4. **Graceful Degradation**: Consider using `forbidden_alphabet_deny => false` for better user experience
5. **Regular Review**: Periodically review and adjust alphabet permissions based on legitimate user needs

## Troubleshooting

### Common Issues

1. **False Positives**: Some characters may be detected as multiple alphabets
   - Solution: Review the Unicode ranges and adjust if necessary

2. **Performance Impact**: Large forms with many text fields
   - Solution: The system is optimized for typical form sizes

3. **User Complaints**: Legitimate users being blocked
   - Solution: Consider using `forbidden_alphabet_deny => false` to force CAPTCHA instead

### Debug Information

When `APP_DEBUG` is enabled, the system logs:
- Detected alphabets for each request
- Configuration settings being applied
- Decision-making process
- Alphabet violation details

Check the Laravel logs for detailed information about alphabet detection behavior. 
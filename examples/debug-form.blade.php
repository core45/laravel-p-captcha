{{-- Debug Form for CAPTCHA Validation --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P-CAPTCHA Debug Form</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .debug-info h3 { margin-top: 0; }
        .debug-info pre { background: white; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #005a87; }
        .result { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <h1>P-CAPTCHA Debug Form</h1>
    
    <div class="debug-info">
        <h3>Debug Configuration</h3>
        <p><strong>APP_DEBUG:</strong> {{ config('app.debug') ? 'TRUE' : 'FALSE' }}</p>
        <p><strong>Current Environment:</strong> {{ app()->environment() }}</p>
        <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
        
        <h4>P-CAPTCHA Configuration:</h4>
        <pre>{{ json_encode(config('p-captcha'), JSON_PRETTY_PRINT) }}</pre>
    </div>

    <form id="debugForm" method="POST" action="/debug-captcha">
        @csrf
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="Debug User" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="debug@example.com" required>
        </div>
        
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="4" required>This is a debug message to test CAPTCHA functionality.</textarea>
        </div>
        
        {{-- P-CAPTCHA will be inserted here --}}
        <div id="captcha-container"></div>
        
        <button type="submit">Submit Form</button>
    </form>
    
    <div id="result"></div>

    <script>
        // Load P-CAPTCHA assets
        document.addEventListener('DOMContentLoaded', function() {
            // Load CSS
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = '/vendor/p-captcha/css/p-captcha.css';
            document.head.appendChild(cssLink);
            
            // Load JS
            const script = document.createElement('script');
            script.src = '/vendor/p-captcha/js/p-captcha.js';
            script.onload = function() {
                if (typeof PCaptcha !== 'undefined') {
                    PCaptcha.init('captcha-container');
                }
            };
            document.head.appendChild(script);
        });
        
        // Form submission handler
        document.getElementById('debugForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            try {
                const response = await fetch('/debug-captcha', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '<h3>Success!</h3><p>' + data.message + '</p>';
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '<h3>Error!</h3><p>' + data.message + '</p>';
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<h3>Network Error!</h3><p>' + error.message + '</p>';
            }
        });
    </script>
</body>
</html> 
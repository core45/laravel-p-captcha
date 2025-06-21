{{-- P-CAPTCHA Widget --}}
<div id="{{ $options['id'] }}" class="p-captcha-container"
     data-theme="{{ $options['theme'] }}"
     data-auto-load="{{ $options['auto_load'] ? 'true' : 'false' }}">

    {{-- CAPTCHA Header --}}
    <div class="p-captcha-header">
        <h3 class="p-captcha-title">Human Verification</h3>
        <p class="p-captcha-instructions" id="{{ $options['id'] }}-instructions">
            Loading challenge...
        </p>
    </div>

    {{-- Challenge Container --}}
    <div class="p-captcha-challenge" id="{{ $options['id'] }}-challenge">
        <div class="p-captcha-loading">
            <div class="p-captcha-spinner"></div>
            <div>Initializing secure challenge...</div>
        </div>
    </div>

    {{-- Controls --}}
    <div class="p-captcha-controls">
        <button type="button" class="p-captcha-btn p-captcha-btn-secondary"
                onclick="PCaptcha.refresh('{{ $options['id'] }}')">
            New Challenge
        </button>
        <button type="button" class="p-captcha-btn p-captcha-btn-primary"
                id="{{ $options['id'] }}-validate"
                onclick="PCaptcha.validate('{{ $options['id'] }}')"
                disabled>
            Validate
        </button>
    </div>

    {{-- Status Messages --}}
    <div class="p-captcha-status" id="{{ $options['id'] }}-status"></div>

    {{-- Hidden Form Fields --}}
    <input type="hidden" name="p_captcha_id" id="{{ $options['id'] }}-challenge-id" value="">
    <input type="hidden" name="p_captcha_solution" id="{{ $options['id'] }}-solution" value="">
</div>

{{-- Load CSS if enabled --}}
@if($config['assets']['load_css'])
    <link rel="stylesheet" href="{{ asset($config['assets']['css_path']) }}">
@endif

{{-- Load JavaScript if enabled --}}
@if($config['assets']['load_js'])
    <script src="{{ asset($config['assets']['js_path']) }}"></script>
@endif

{{-- Initialize CAPTCHA --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof PCaptcha !== 'undefined') {
            @if($options['auto_load'])
            PCaptcha.init('{{ $options['id'] }}');
            @endif
        } else {
            console.error('P-CAPTCHA: JavaScript not loaded. Make sure to publish and include the assets.');
        }
    });
</script>

<style>
    {{-- Inline critical CSS --}}
.p-captcha-container {
        border: 2px solid {{ $config['styling']['primary_color'] }};
        border-radius: 12px;
        background: {{ $config['styling']['background_gradient'] }};
        color: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 8px 32px rgba(109, 74, 255, 0.3);
        padding: 20px;
        margin: 20px 0;
        max-width: 500px;
    }

    .p-captcha-container[data-theme="light"] {
        background: white;
        color: #333;
        border-color: #ddd;
    }

    .p-captcha-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .p-captcha-title {
        color: {{ $config['styling']['primary_color'] }};
        font-size: 1.2em;
        font-weight: bold;
        margin: 0 0 8px 0;
    }

    .p-captcha-instructions {
        font-size: 0.9em;
        color: #ccc;
        margin: 0;
    }

    .p-captcha-container[data-theme="light"] .p-captcha-instructions {
        color: #666;
    }

    .p-captcha-challenge {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        min-height: 200px;
        backdrop-filter: blur(10px);
    }

    .p-captcha-container[data-theme="light"] .p-captcha-challenge {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }

    .p-captcha-loading {
        text-align: center;
        padding: 40px 20px;
    }

    .p-captcha-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid {{ $config['styling']['primary_color'] }};
        border-radius: 50%;
        animation: p-captcha-spin 1s linear infinite;
        margin: 0 auto 15px;
    }

    @keyframes p-captcha-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .p-captcha-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        gap: 10px;
    }

    .p-captcha-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
    }

    .p-captcha-btn-primary {
        background: {{ $config['styling']['primary_color'] }};
        color: white;
    }

    .p-captcha-btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid {{ $config['styling']['primary_color'] }};
    }

    .p-captcha-container[data-theme="light"] .p-captcha-btn-secondary {
        background: #f8f9fa;
        color: #333;
        border-color: #ddd;
    }

    .p-captcha-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .p-captcha-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .p-captcha-status {
        margin-top: 15px;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
        font-weight: 500;
        display: none;
    }

    .p-captcha-status.success {
        background: {{ $config['styling']['success_color'] }};
        color: white;
    }

    .p-captcha-status.error {
        background: {{ $config['styling']['error_color'] }};
        color: white;
    }

    .p-captcha-status.show {
        display: block;
    }

    {{-- Beam Alignment Styles --}}
.beam-canvas {
        width: 100%;
        height: 300px;
        background: #0a0a0a;
        border: 1px solid #333;
        border-radius: 4px;
        position: relative;
        overflow: hidden;
        cursor: crosshair;
    }

    .beam-source, .beam-target {
        position: absolute;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .beam-source {
        background: radial-gradient(circle, #ff6b6b 0%, #ee5a52 100%);
        box-shadow: 0 0 20px #ff6b6b;
        cursor: grab;
    }

    .beam-source:active {
        cursor: grabbing;
    }

    .beam-target {
        background: radial-gradient(circle, #4ecdc4 0%, #44a08d 100%);
        box-shadow: 0 0 20px #4ecdc4;
    }

    .beam-line {
        position: absolute;
        height: 2px;
        background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
        box-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
        transform-origin: left center;
        transition: all 0.1s ease;
    }

    {{-- Pattern Match Styles --}}
.pattern-display {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin: 20px 0;
        font-size: 2em;
    }

    .pattern-choices {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 20px;
    }

    .pattern-choice {
        padding: 15px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid {{ $config['styling']['primary_color'] }};
        border-radius: 8px;
        text-align: center;
        font-size: 1.5em;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pattern-choice:hover {
        background: rgba(109, 74, 255, 0.3);
        transform: scale(1.05);
    }

    .pattern-choice.selected {
        background: {{ $config['styling']['primary_color'] }};
        box-shadow: 0 0 15px rgba(109, 74, 255, 0.5);
    }

    {{-- Sequence Styles --}}
.sequence-display {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
        font-size: 1.5em;
        font-weight: bold;
    }

    .sequence-number {
        padding: 10px 15px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        min-width: 50px;
        text-align: center;
    }

    .sequence-choices {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 20px;
    }

    .sequence-choice {
        padding: 15px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid {{ $config['styling']['primary_color'] }};
        border-radius: 8px;
        text-align: center;
        font-size: 1.2em;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .sequence-choice:hover {
        background: rgba(109, 74, 255, 0.3);
        transform: scale(1.05);
    }

    .sequence-choice.selected {
        background: {{ $config['styling']['primary_color'] }};
        box-shadow: 0 0 15px rgba(109, 74, 255, 0.5);
    }

    {{-- Proof of Work Styles --}}
.pow-container {
        text-align: center;
    }

    .pow-input {
        width: 100%;
        max-width: 200px;
        padding: 12px;
        margin: 20px 0;
        border: 1px solid {{ $config['styling']['primary_color'] }};
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        text-align: center;
        font-size: 1.1em;
    }

    .pow-input::placeholder {
        color: #ccc;
    }

    .pow-hint {
        font-size: 0.8em;
        color: #ccc;
        margin-top: 10px;
    }
</style>

{{-- Example Contact Form with P-CAPTCHA --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Contact Us</h2>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.store') }}" id="contact-form">
                        @csrf
                        
                        {{-- Hidden CAPTCHA fields (required for middleware) --}}
                        <input type="hidden" name="_captcha_field" value="">
                        <input type="hidden" name="_captcha_token" value="">
                        
                        <div class="form-group mb-3">
                            <label for="name">Name *</label>
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
                            <label for="email">Email *</label>
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
                            <label for="subject">Subject *</label>
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
                            <label for="message">Message *</label>
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
                        <div class="mb-3">
                            @pcaptcha('theme=light,id=contact-captcha')
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript to handle form submission and CAPTCHA --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const submitBtn = document.getElementById('submit-btn');
    
    form.addEventListener('submit', function(e) {
        // Check if CAPTCHA is completed
        if (typeof PCaptcha !== 'undefined' && !PCaptcha.isVerified('contact-captcha')) {
            e.preventDefault();
            alert('{{ __("p-captcha::p-captcha.please_complete_challenge_first") }}');
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
    });
    
    // Re-enable submit button if form validation fails
    form.addEventListener('invalid', function() {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';
    }, true);
});
</script>
@endsection 
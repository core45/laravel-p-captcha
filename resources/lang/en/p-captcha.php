<?php

return [
    // UI Elements
    'human_verification' => 'Human Verification',
    'loading_challenge' => 'Loading challenge...',
    'initializing_secure_challenge' => 'Initializing secure challenge...',
    'new_challenge' => 'New Challenge',
    'validate' => 'Validate',
    'complete_challenge_below' => 'Complete the challenge below',
    
    // Status Messages
    'beam_aligned' => 'Beam aligned! Click Validate to continue.',
    'validating' => 'Validating...',
    'captcha_verified_successfully' => 'CAPTCHA verified successfully!',
    'captcha_solved_successfully' => 'CAPTCHA solved successfully',
    'invalid_captcha_solution' => 'Invalid CAPTCHA solution',
    
    // Error Messages
    'failed_to_load_challenge' => 'Failed to load challenge. Please refresh the page.',
    'unknown_challenge_type' => 'Unknown challenge type: :type',
    'please_complete_challenge_first' => 'Please complete the challenge first',
    'invalid_solution_try_again' => 'Invalid solution. Please try again.',
    'network_error_try_again' => 'Network error. Please try again.',
    
    // Challenge Instructions
    'align_beam_source_target' => 'Align the beam source with the target by dragging the source to enable particle collision',
    'complete_sequence_select_next' => 'Complete the sequence by selecting the next number.',
    
    // Sequence Instructions
    'add_1_to_last_number' => 'Add 1 to the last number (:number) to get the next number.',
    'add_2_to_last_number' => 'Add 2 to the last number (:number) to get the next number.',
    'add_3_to_last_number' => 'Add 3 to the last number (:number) to get the next number.',
    'add_4_to_last_number' => 'Add 4 to the last number (:number) to get the next number.',
    'add_5_to_last_number' => 'Add 5 to the last number (:number) to get the next number.',
    'add_7_to_last_number' => 'Add 7 to the last number (:number) to get the next number.',
    'add_10_to_last_number' => 'Add 10 to the last number (:number) to get the next number.',
    'add_step_to_last_number' => 'Add :step to the last number (:number) to get the next number.',
    'subtract_step_from_last_number' => 'Subtract :step from the last number (:number) to get the next number.',
    'double_last_number' => 'Double the last number (:number) to get the next number.',
    'triple_last_number' => 'Triple the last number (:number) to get the next number.',
    'multiply_last_number_by_ratio' => 'Multiply the last number (:number) by :ratio to get the next number.',
    
    // API Messages
    'too_many_requests_wait' => 'Too many requests. Please wait :seconds seconds before generating new challenges.',
    'too_many_validation_attempts_wait' => 'Too many validation attempts. Please wait.',
    'failed_to_generate_captcha_challenge' => 'Failed to generate CAPTCHA challenge',
    'invalid_request_data' => 'Invalid request data',
    'failed_to_validate_captcha' => 'Failed to validate CAPTCHA',
    'too_many_requests_wait_general' => 'Too many requests. Please wait.',
    'failed_to_generate_token' => 'Failed to generate token',
    
    // Middleware Messages
    'suspicious_activity_detected' => 'Suspicious activity detected. Please complete verification.',
    'please_complete_verification_challenge' => 'Please complete the verification challenge.',
    'please_complete_form_validation' => 'Please complete the form validation.',
    
    // Form Validation Messages
    'there_were_errors_with_submission' => 'There were :count error(s) with your submission',
    'captcha_verification_failed_try_again' => 'CAPTCHA verification failed. Please try again.',
]; 
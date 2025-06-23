<?php

return [
    // UI Elements
    'human_verification' => 'Udowodnij, że jesteś człowiekiem',
    'loading_challenge' => 'Ładowanie wyzwania...',
    'initializing_secure_challenge' => 'Inicjalizacja bezpiecznego wyzwania...',
    'new_challenge' => 'Nowe Wyzwanie',
    'validate' => 'Waliduj',
    'complete_challenge_below' => 'Ukończ wyzwanie poniżej',

    // Status Messages
    'beam_aligned' => 'Promień wyrównany! Kliknij Waliduj, aby kontynuować.',
    'validating' => 'Walidacja...',
    'captcha_verified_successfully' => 'CAPTCHA została pomyślnie zweryfikowana!',
    'captcha_solved_successfully' => 'CAPTCHA rozwiązana pomyślnie',
    'invalid_captcha_solution' => 'Nieprawidłowe rozwiązanie CAPTCHA',

    // Error Messages
    'failed_to_load_challenge' => 'Nie udało się załadować wyzwania. Odśwież stronę.',
    'unknown_challenge_type' => 'Nieznany typ wyzwania: :type',
    'please_complete_challenge_first' => 'Najpierw ukończ wyzwanie',
    'invalid_solution_try_again' => 'Nieprawidłowe rozwiązanie. Spróbuj ponownie.',
    'network_error_try_again' => 'Błąd sieci. Spróbuj ponownie.',

    // Challenge Instructions
    'align_beam_source_target' => 'Wyrównaj połączone kółka, przeciągając czerwone tak by znalazło się na zielonym.',
    'complete_sequence_select_next' => 'Ukończ sekwencję, wybierając następną liczbę.',

    // Sequence Instructions
    'add_1_to_last_number' => 'Dodaj 1 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_2_to_last_number' => 'Dodaj 2 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_3_to_last_number' => 'Dodaj 3 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_4_to_last_number' => 'Dodaj 4 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_5_to_last_number' => 'Dodaj 5 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_7_to_last_number' => 'Dodaj 7 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_10_to_last_number' => 'Dodaj 10 do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'add_step_to_last_number' => 'Dodaj :step do ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'subtract_step_from_last_number' => 'Odejmij :step od ostatniej liczby (:number), aby otrzymać następną liczbę.',
    'double_last_number' => 'Podwoj ostatnią liczbę (:number), aby otrzymać następną liczbę.',
    'triple_last_number' => 'Potroi ostatnią liczbę (:number), aby otrzymać następną liczbę.',
    'multiply_last_number_by_ratio' => 'Pomnoż ostatnią liczbę (:number) przez :ratio, aby otrzymać następną liczbę.',

    // API Messages
    'too_many_requests_wait' => 'Zbyt wiele żądań. Poczekaj przed generowaniem nowych wyzwań.',
    'too_many_validation_attempts_wait' => 'Zbyt wiele prób walidacji. Poczekaj.',
    'failed_to_generate_captcha_challenge' => 'Nie udało się wygenerować wyzwania CAPTCHA',
    'invalid_request_data' => 'Nieprawidłowe dane żądania',
    'failed_to_validate_captcha' => 'Nie udało się zwalidować CAPTCHA',
    'too_many_requests_wait_general' => 'Zbyt wiele żądań. Poczekaj.',
    'failed_to_generate_token' => 'Nie udało się wygenerować tokenu',

    // Middleware Messages
    'suspicious_activity_detected' => 'Wykryto podejrzaną aktywność. Ukończ weryfikację.',
    'please_complete_verification_challenge' => 'Ukończ wyzwanie weryfikacyjne.',
    'please_complete_form_validation' => 'Ukończ walidację formularza.',

    // Form Validation Messages
    'there_were_errors_with_submission' => 'Wystąpił błąd z Twoim zgłoszeniem|Wystąpiły :count błędy z Twoim zgłoszeniem',
    'captcha_verification_failed_try_again' => 'Weryfikacja CAPTCHA nie powiodła się. Spróbuj ponownie.',
];

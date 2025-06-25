<?php

return [
    // UI Elements
    'human_verification' => '¿Eres humano?',
    'loading_challenge' => 'Cargando desafío...',
    'initializing_secure_challenge' => 'Inicializando desafío seguro...',
    'new_challenge' => 'Nuevo Desafío',
    'validate' => 'Validar',
    'complete_challenge_below' => 'Completa el desafío a continuación',
    
    // Status Messages
    'beam_aligned' => '¡Haz alineado! Haz clic en Validar para continuar.',
    'validating' => 'Validando...',
    'captcha_verified_successfully' => '¡CAPTCHA verificado exitosamente!',
    'captcha_solved_successfully' => 'CAPTCHA resuelto exitosamente',
    'invalid_captcha_solution' => 'Solución CAPTCHA inválida',
    
    // Error Messages
    'failed_to_load_challenge' => 'Error al cargar el desafío. Actualiza la página.',
    'unknown_challenge_type' => 'Tipo de desafío desconocido: :type',
    'please_complete_challenge_first' => 'Por favor completa el desafío primero',
    'invalid_solution_try_again' => 'Solución inválida. Inténtalo de nuevo.',
    'network_error_try_again' => 'Error de red. Inténtalo de nuevo.',
    
    // Challenge Instructions
    'align_beam_source_target' => 'Alinea la fuente del haz con el objetivo arrastrando la fuente para habilitar la colisión de partículas',
    'complete_sequence_select_next' => 'Completa la secuencia seleccionando el siguiente número.',
    
    // Sequence Instructions
    'add_1_to_last_number' => 'Suma 1 al último número (:number) para obtener el siguiente número.',
    'add_2_to_last_number' => 'Suma 2 al último número (:number) para obtener el siguiente número.',
    'add_3_to_last_number' => 'Suma 3 al último número (:number) para obtener el siguiente número.',
    'add_4_to_last_number' => 'Suma 4 al último número (:number) para obtener el siguiente número.',
    'add_5_to_last_number' => 'Suma 5 al último número (:number) para obtener el siguiente número.',
    'add_7_to_last_number' => 'Suma 7 al último número (:number) para obtener el siguiente número.',
    'add_10_to_last_number' => 'Suma 10 al último número (:number) para obtener el siguiente número.',
    'add_step_to_last_number' => 'Suma :step al último número (:number) para obtener el siguiente número.',
    'subtract_step_from_last_number' => 'Resta :step del último número (:number) para obtener el siguiente número.',
    'double_last_number' => 'Duplica el último número (:number) para obtener el siguiente número.',
    'triple_last_number' => 'Triplica el último número (:number) para obtener el siguiente número.',
    'multiply_last_number_by_ratio' => 'Multiplica el último número (:number) por :ratio para obtener el siguiente número.',
    
    // API Messages
    'too_many_requests_wait' => 'Demasiadas solicitudes. Espera antes de generar nuevos desafíos.',
    'too_many_validation_attempts_wait' => 'Demasiados intentos de validación. Espera.',
    'failed_to_generate_captcha_challenge' => 'Error al generar el desafío CAPTCHA',
    'invalid_request_data' => 'Datos de solicitud inválidos',
    'failed_to_validate_captcha' => 'Error al validar CAPTCHA',
    'too_many_requests_wait_general' => 'Demasiadas solicitudes. Espera.',
    'failed_to_generate_token' => 'Error al generar token',
    
    // Middleware Messages
    'suspicious_activity_detected' => 'Actividad sospechosa detectada. Por favor complete la verificación.',
    'please_complete_verification_challenge' => 'Por favor complete el desafío de verificación.',
    'please_complete_form_validation' => 'Por favor complete la validación del formulario.',
    'visual_captcha_required' => 'Verificación visual requerida. Por favor complete el desafío.',
    'invalid_captcha_response' => 'Respuesta de verificación inválida. Por favor intente de nuevo.',
    
    // Form Validation Messages
    'there_were_errors_with_submission' => 'Hubo un error con tu envío|Hubo :count errores con tu envío',
    'captcha_verification_failed_try_again' => 'La verificación CAPTCHA falló. Inténtalo de nuevo.',
]; 
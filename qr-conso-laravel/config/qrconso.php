<?php

return [

    // Adresse imprimée en clair sous chaque QR (§9, lutte contre le quishing).
    'public_host' => env('QR_PUBLIC_HOST', 'qr.consommateurs.ma'),
    'base_url' => env('QR_BASE_URL', env('APP_URL', 'http://localhost:8000')),

    // 'log' écrit le code OTP dans les logs plutôt que d'envoyer un SMS : pas de
    // contrat opérateur nécessaire pour la démonstration.
    'sms_driver' => env('SMS_DRIVER', 'log'),

    'otp' => [
        'ttl_minutes' => 10,
        'validity_minutes' => 30,
        'max_attempts' => 5,
    ],

    'ai' => [
        'enabled' => env('AI_ENABLED', true),
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:7b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    ],

    /*
     * Transcription vocale et OCR (§8), délégués à Python : Ollama ne fait que
     * du texte, la parole et l'image demandent d'autres modèles.
     *
     * `python` doit pointer sur un interpréteur où faster-whisper et pytesseract
     * sont installés — pas nécessairement celui du PATH.
     */
    'extraction' => [
        'enabled' => env('EXTRACTION_ENABLED', true),
        'python' => env('PYTHON_BIN', 'py'),
        'whisper_model' => env('WHISPER_MODEL', 'small'),
        // Large, car le tout premier appel télécharge le modèle Whisper.
        'timeout' => (int) env('EXTRACTION_TIMEOUT', 600),
    ],

    'uploads' => [
        'max_size_kb' => 10240,
        'max_per_draft' => 6,
    ],

];

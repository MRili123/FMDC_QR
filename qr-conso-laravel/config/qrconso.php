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

    'uploads' => [
        'max_size_kb' => 10240,
        'max_per_draft' => 6,
    ],

];

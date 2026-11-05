<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Provider utama yang digunakan: 'gemini' (rekomendasi, hemat & akurat)
    | atau 'openai' (GPT-4o / GPT-4o-mini / DeepSeek / Groq).
    */
    'provider' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    | API Key gratis bisa diperoleh di: https://aistudio.google.com/app/apikey
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI / Compatible API Configuration
    |--------------------------------------------------------------------------
    | Mendukung OpenAI, Groq, DeepSeek, atau OpenRouter.
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],
];


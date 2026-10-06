<?php

return [
    // none | gemini (testing only — JANGAN aktifkan production tanpa review)
    'driver' => env('ASK_LLM_DRIVER', 'none'),
    'key' => env('GOOGLE_GEMINI_API_KEY', ''),
    'model' => env('ASK_LLM_MODEL', 'gemini-3.8-flash'),
    'base_url' => env('ASK_LLM_BASE_URL', 'https://generativelanguage.googleapis.com'),
    'timeout' => (int) env('ASK_LLM_TIMEOUT', 25),
];

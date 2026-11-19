<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'sender_email' => env('BREVO_SENDER_EMAIL', env('MAIL_FROM_ADDRESS')),
        'sender_name' => env('BREVO_SENDER_NAME', env('MAIL_FROM_NAME', 'PassPermis')),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // A local Piper installation keeps neural narration on the application
    // machine. No TTS key or browser-exposed secret is required.
    'piper' => [
        'binary' => env('PIPER_BINARY'),
        'french_model' => env('PIPER_FRENCH_MODEL', storage_path('app/tts/voices/fr_FR-siwis-medium.onnx')),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        // Absolute path to the private service-account JSON file. Never place
        // this file in public/ or expose it through Vite variables.
        'service_account_json' => env('FIREBASE_SERVICE_ACCOUNT_JSON'),
    ],

];

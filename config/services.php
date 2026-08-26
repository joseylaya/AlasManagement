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

    'paymongo' => [
        'mode' => env('PAYMONGO_MODE', 'test'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'test_secret_key' => env('PAYMONGO_TEST_SECRET_KEY'),
        'test_public_key' => env('PAYMONGO_TEST_PUBLIC_KEY'),
        'live_secret_key' => env('PAYMONGO_LIVE_SECRET_KEY'),
        'live_public_key' => env('PAYMONGO_LIVE_PUBLIC_KEY'),
        'api_url' => env('PAYMONGO_API_URL', 'https://api.paymongo.com/v1'),
        'success_url' => env('PAYMONGO_SUCCESS_URL', 'http://localhost:3000/checkout/success'),
        'cancel_url' => env('PAYMONGO_CANCEL_URL', 'http://localhost:3000/checkout'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'test_webhook_secret' => env('PAYMONGO_TEST_WEBHOOK_SECRET'),
        'live_webhook_secret' => env('PAYMONGO_LIVE_WEBHOOK_SECRET'),
        'webhook_tolerance' => (int) env('PAYMONGO_WEBHOOK_TOLERANCE', 300),
    ],

    'storefront_sandbox' => [
        'enabled' => env('STOREFRONT_SANDBOX_ENABLED', false),
        'token' => env('STOREFRONT_SANDBOX_TOKEN'),
    ],

    'storefront' => [
        'url' => env('STOREFRONT_URL', 'http://localhost:3004'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
        'model' => env('AI_MODEL', 'gemini-3.7-flash'),
        'fallback_model' => env('AI_FALLBACK_MODEL', 'gemini-3.1-flash-lite'),
        'models' => env('AI_MODELS', 'gemini-3.7-flash,gemini-3.6-flash,gemini-3.5-flash,gemini-3.5-flash-lite,gemini-3.1-flash-lite'),
        'api_key' => env('AI_API_KEY'),
        'api_url' => env('AI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'embedding_model' => env('EMBEDDING_MODEL', 'gemini-embedding-2'),
        'embedding_dimension' => (int) env('EMBEDDING_DIMENSION', 1536),
        'timeout' => (int) env('AI_PROVIDER_TIMEOUT', 20),
        'minimum_similarity' => (float) env('AI_MINIMUM_SIMILARITY', 0.55),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'product_images_bucket' => env('SUPABASE_PRODUCT_IMAGES_BUCKET', 'product-images'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'web_push' => [
        'subject' => env('WEB_PUSH_VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key' => env('WEB_PUSH_VAPID_PUBLIC_KEY'),
        'private_key' => env('WEB_PUSH_VAPID_PRIVATE_KEY'),
    ],

];

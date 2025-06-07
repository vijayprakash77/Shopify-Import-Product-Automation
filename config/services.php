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
    // ADD SHOPIFY CONFIGURATION HERE
    'shopify' => [
        'shop_url' => env('SHOPIFY_SHOP_URL'),
        'shopify_api_key' => env('SHOPIFY_API_KEY'),
        'shopify_collection_id' => env('SHOPIFY_COLLECTION_ID'),
        'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
        'api_version' => env('SHOPIFY_API_VERSION', '2023-10'),
        'scopes' => env('SHOPIFY_SCOPES', 'read_products,write_products,read_orders,write_orders'),
        'timeout' => env('SHOPIFY_TIMEOUT', 30),
        'retry_attempts' => env('SHOPIFY_RETRY_ATTEMPTS', 3),
    ],

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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

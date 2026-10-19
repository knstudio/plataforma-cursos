<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mercadopago' => [
        'base_url' => 'https://api.mercadopago.com',
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        // Secret from "Webhooks" in the Mercado Pago panel, used to check x-signature.
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        // Defaults to the webhook route; must be a public HTTPS URL for Mercado Pago to reach it.
        'notification_url' => env('MERCADOPAGO_NOTIFICATION_URL'),
        'statement_descriptor' => env('MERCADOPAGO_STATEMENT_DESCRIPTOR', 'JULIANABALOES'),
    ],

    'hotmart' => [
        // "Hottok" shown in Hotmart > Ferramentas > Webhook.
        'hottok' => env('HOTMART_HOTTOK'),
    ],

    'evolution' => [
        // WhatsApp funnels: Evolution API server and its global API key.
        'url' => env('EVOLUTION_URL'),
        'api_key' => env('EVOLUTION_API_KEY'),
    ],

];

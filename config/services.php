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

    'mram_sms' => [
        'enabled' => env('MRAM_SMS_ENABLED', false),
        'api_key' => env('MRAM_SMS_API_KEY'),
        'sender_id' => env('MRAM_SMS_SENDER_ID'),
        'label' => env('MRAM_SMS_LABEL', 'transactional'),
        'type' => env('MRAM_SMS_TYPE', 'unicode'),
        'api_url' => env('MRAM_SMS_API_URL', 'https://sms.mram.com.bd/smsapi'),
    ],

    'google' => [
        'client_ids' => env('GOOGLE_CLIENT_IDS'),
    ],

    'fcm' => [
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
        'project_id' => env('FCM_PROJECT_ID'),
    ],

    'medicine_payment' => [
        'bkash_number' => env('MEDICINE_BKASH_NUMBER'),
        'nagad_number' => env('MEDICINE_NAGAD_NUMBER'),
        'instructions' => env('MEDICINE_PAYMENT_INSTRUCTIONS'),
    ],

];

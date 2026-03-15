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

    'bpjs_v3' => [
        'api_ver'    => env('BPJS_V3_API_VER', '3'),
        'base_url'   => env('BPJS_V3_BASE_URL'),
        'cons_id'    => env('BPJS_V3_CONS_ID'),
        'secret_key' => env('BPJS_V3_SECRET_KEY'),
        'user_key'   => env('BPJS_V3_USER_KEY'),
    ],

    'satusehat' => [
        'env' => env('SATUSEHAT_ENV', 'DEV'),

        'auth_dev' => env('SATUSEHAT_AUTH_DEV'),
        'fhir_dev' => env('SATUSEHAT_FHIR_DEV'),
        'client_id_dev' => env('SATUSEHAT_CLIENTID_DEV'),
        'client_secret_dev' => env('SATUSEHAT_CLIENTSECRET_DEV'),
        'org_id_dev' => env('SATUSEHAT_ORGID_DEV'),

        'auth_prod' => env('SATUSEHAT_AUTH_PROD'),
        'fhir_prod' => env('SATUSEHAT_FHIR_PROD'),
        'client_id_prod' => env('SATUSEHAT_CLIENTID_PROD'),
        'client_secret_prod' => env('SATUSEHAT_CLIENTSECRET_PROD'),
        'org_id_prod' => env('SATUSEHAT_ORGID_PROD'),
    ],

];

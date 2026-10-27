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

    'bpjs_antrean' => [
        'base_url'   => env('BPJS_ANTREAN_BASE_URL', env('BPJS_V3_BASE_URL')),
        'cons_id'    => env('BPJS_ANTREAN_CONS_ID', env('BPJS_V3_CONS_ID')),
        'secret_key' => env('BPJS_ANTREAN_SECRET_KEY', env('BPJS_V3_SECRET_KEY')),
        'user_key'   => env('BPJS_ANTREAN_USER_KEY', env('BPJS_V3_USER_KEY')),
        'kd_ppk'     => env('BPJS_ANTREAN_KD_PPK', '0112R001'),
    ],

    'bpjs_medical_record' => [
        'api_ver' => env('BPJS_MEDICAL_RECORD_API_VER', env('BPJS_V3_API_VER', '2')),
        'base_url' => env('BPJS_MEDICAL_RECORD_BASE_URL', env('BPJS_V3_BASE_URL')),
        'cons_id' => env('BPJS_MEDICAL_RECORD_CONS_ID', env('BPJS_V3_CONS_ID')),
        'secret_key' => env('BPJS_MEDICAL_RECORD_SECRET_KEY', env('BPJS_V3_SECRET_KEY')),
        'user_key' => env('BPJS_MEDICAL_RECORD_USER_KEY', env('BPJS_V3_USER_KEY')),
        'rs_code' => env('BPJS_MEDICAL_RECORD_RS_CODE', env('BPJS_MEDICAL_RECORD_PROVIDER_CODE')),
        'kemenkes_code' => env('BPJS_MEDICAL_RECORD_KEMENKES_CODE', env('BPJS_MEDICAL_RECORD_PROVIDER_CODE')),
        'provider_code' => env('BPJS_MEDICAL_RECORD_PROVIDER_CODE'),
        'sample_path' => base_path(env('BPJS_MEDICAL_RECORD_SAMPLE_PATH', 'sampling.normalized.json')),
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

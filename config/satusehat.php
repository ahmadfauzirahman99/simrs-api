<?php

return [
    'env' => env('SATUSEHAT_ENV', 'DEV'),   // 'DEV' atau 'PROD'

    'dev' => [
        'auth_url'      => env('SATUSEHAT_DEV_AUTH_URL', 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1'),
        'fhir_url'      => env('SATUSEHAT_DEV_FHIR_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1'),
        'client_id'     => env('SATUSEHAT_DEV_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_DEV_CLIENT_SECRET'),
        'org_id'        => env('SATUSEHAT_DEV_ORG_ID'),
        'bpjs_org_id'   => env('SATUSEHAT_DEV_BPJS_ORG_ID'),
    ],

    'prod' => [
        'auth_url'      => env('SATUSEHAT_PROD_AUTH_URL', 'https://api-satusehat.dto.kemkes.go.id/oauth2/v1'),
        'fhir_url'      => env('SATUSEHAT_PROD_FHIR_URL', 'https://api-satusehat.dto.kemkes.go.id/fhir-r4/v1'),
        'client_id'     => env('SATUSEHAT_PROD_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_PROD_CLIENT_SECRET'),
        'org_id'        => env('SATUSEHAT_PROD_ORG_ID'),
        'bpjs_org_id'   => env('SATUSEHAT_PROD_BPJS_ORG_ID'),
    ],
];

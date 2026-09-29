# API Config Reference — Satu Sehat

## Endpoints

| Env | Auth URL | FHIR URL |
|-----|----------|----------|
| DEV (Sandbox) | `https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1` | `https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1` |
| PROD | `https://api-satusehat.dto.kemkes.go.id/oauth2/v1` | `https://api-satusehat.dto.kemkes.go.id/fhir-r4/v1` |

---

## config/satusehat.php (Struktur Wajib)

```php
<?php

return [
    'env' => env('SATUSEHAT_ENV', 'DEV'),   // 'DEV' atau 'PROD'

    'dev' => [
        'auth_url'      => env('SATUSEHAT_DEV_AUTH_URL', 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1'),
        'fhir_url'      => env('SATUSEHAT_DEV_FHIR_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1'),
        'client_id'     => env('SATUSEHAT_DEV_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_DEV_CLIENT_SECRET'),
        'org_id'        => env('SATUSEHAT_DEV_ORG_ID'),  // UUID dari portal DEV
    ],

    'prod' => [
        'auth_url'      => env('SATUSEHAT_PROD_AUTH_URL', 'https://api-satusehat.dto.kemkes.go.id/oauth2/v1'),
        'fhir_url'      => env('SATUSEHAT_PROD_FHIR_URL', 'https://api-satusehat.dto.kemkes.go.id/fhir-r4/v1'),
        'client_id'     => env('SATUSEHAT_PROD_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_PROD_CLIENT_SECRET'),
        'org_id'        => env('SATUSEHAT_PROD_ORG_ID'),  // UUID dari portal PROD
    ],
];
```

### Key wajib per env
`auth_url`, `fhir_url`, `client_id`, `client_secret`, `org_id`  
Config::credentials() melempar `RuntimeException` jika ada yang kosong.

---

## .env Variables

```dotenv
SATUSEHAT_ENV=DEV

SATUSEHAT_DEV_CLIENT_ID=your-dev-client-id
SATUSEHAT_DEV_CLIENT_SECRET=your-dev-client-secret
SATUSEHAT_DEV_ORG_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx

SATUSEHAT_PROD_CLIENT_ID=your-prod-client-id
SATUSEHAT_PROD_CLIENT_SECRET=your-prod-client-secret
SATUSEHAT_PROD_ORG_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

> **PENTING**: `org_id` harus UUID (bukan nomor seperti `100072815`). Ambil dari portal Kemkes tab DEV/PROD → Organization ID.

---

## OAuth2 Token Endpoint

```
POST {auth_url}/accesstoken?grant_type=client_credentials
Content-Type: application/x-www-form-urlencoded

client_id=...&client_secret=...
```

Response:
```json
{
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "expires_in": 3599
}
```

---

## FHIR API Pattern

```
GET  {fhir_url}/{ResourceType}?{params}      — Search
GET  {fhir_url}/{ResourceType}/{id}           — Read
POST {fhir_url}/{ResourceType}                — Create
PUT  {fhir_url}/{ResourceType}/{id}           — Update

Authorization: Bearer {access_token}
Content-Type: application/json
```

### Search Patient by NIK
```
GET {fhir_url}/Patient?identifier=https://fhir.kemkes.go.id/id/nik|{NIK}
```

### Search Practitioner by NIP
```
GET {fhir_url}/Practitioner?identifier=https://fhir.kemkes.go.id/id/nip|{NIP}
```

---

## Common Error Codes

| HTTP | Code | Penyebab | Solusi |
|------|------|----------|--------|
| 401 | — | Token expired/invalid | Auto-retry dengan flushToken() |
| 403 | — | App belum subscribe API product | Portal Kemkes → Aplikasi → subscribe FHIR-R4 |
| 422 | 10447 | org_id Invalid identifier system | Pastikan org_id adalah UUID, bukan angka |
| 422 | — | Validasi payload gagal | Cek field wajib di FHIR resource |

---

## Portal Kemkes (Untuk Setup)

- **DEV/Sandbox**: [https://api-satusehat-dev.dto.kemkes.go.id/](https://api-satusehat-dev.dto.kemkes.go.id/)  
  → Daftarkan Aplikasi → Subscribe ke **FHIR-R4-Sandbox** API Product
- **PROD**: [https://api-satusehat.dto.kemkes.go.id/](https://api-satusehat.dto.kemkes.go.id/)  
  → Subscribe ke **FHIR-R4** API Product

---

## Token Cache Location (Laravel)

Default: `storage/framework/cache/satusehat_token.json`

Format file:
```json
{
  "access_token": "eyJ...",
  "expires_at": 1712345678
}
```

Untuk ganti storage (Redis, DB), implement `TokenStoreInterface`:
```php
interface TokenStoreInterface
{
    public function get(): ?array;
    public function put(array $tokenData): void;
    public function flush(): void;
}
```

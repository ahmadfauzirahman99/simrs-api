# SATUSEHAT Integration

Project ini menyediakan integrasi sederhana dengan API **SATUSEHAT Kemenkes** menggunakan Laravel Service Class.

Fitur yang tersedia saat ini:

- Generate OAuth2 Token SATUSEHAT
- Kirim data Encounter ke SATUSEHAT
- Support environment DEV dan PROD
- Token caching di database
- Auto refresh token jika expired

---

# API Routes

Endpoint tersedia melalui prefix:


### 1. Get Token SATUSEHAT


Digunakan untuk mengambil **Access Token SATUSEHAT**.

Response:

```json
{
  "metaData": {
    "code": "200",
    "message": "Sukses"
  },
  "response": {
    "token": "access_token_here",
    "organization_id": "ORG_ID",
    "base_url": "https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1",
    "environment": "DEV"
  }
}



### 2. POST /api/satu-sehat/encounter/send

Digunakan untuk mengirim data Encounter FHIR R4 ke SATUSEHAT.




SATUSEHAT_ENV=DEV

SATUSEHAT_AUTH_DEV=https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1
SATUSEHAT_FHIR_DEV=https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1
SATUSEHAT_CLIENTID_DEV=isi_client_id_dev
SATUSEHAT_CLIENTSECRET_DEV=isi_client_secret_dev
SATUSEHAT_ORGID_DEV=isi_org_id_dev

SATUSEHAT_AUTH_PROD=https://api-satusehat.kemkes.go.id/oauth2/v1
SATUSEHAT_FHIR_PROD=https://api-satusehat.kemkes.go.id/fhir-r4/v1
SATUSEHAT_CLIENTID_PROD=isi_client_id_prod
SATUSEHAT_CLIENTSECRET_PROD=isi_client_secret_prod
SATUSEHAT_ORGID_PROD=isi_org_id_prod



SIMRS
   │
   │
   ▼
Laravel API
   │
   │ get token
   ▼
SATUSEHAT OAuth2
   │
   │
   ▼
FHIR API
   │
   ▼
SATUSEHAT Server

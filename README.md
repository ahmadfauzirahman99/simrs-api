# SIMRS API

Backend API berbasis `Laravel 12` untuk kebutuhan integrasi `SIMRS`, `BPJS`, dan `SATUSEHAT`.

Project ini ditujukan sebagai fondasi yang bisa dipakai ulang oleh programmer Indonesia untuk membangun bridging layanan kesehatan yang rapi, konsisten, dan mudah dikembangkan.

## Visi Project

Repositori ini tidak hanya ditujukan untuk kebutuhan satu implementasi internal, tetapi diarahkan menjadi:

- base project integrasi `BPJS`
- base project integrasi `SATUSEHAT`
- referensi arsitektur backend bridging untuk `SIMRS`
- starter kit pengembangan API layanan kesehatan di Indonesia

## Fokus Saat Ini

Saat ini project masih berfokus pada lapisan `API backend`, dengan ruang pengembangan untuk:

- integrasi `BPJS VClaim`
- integrasi `BPJS Antrean/Antrol`
- integrasi `SATUSEHAT`
- standardisasi format response
- dokumentasi implementasi integrasi
- fondasi modular untuk pertumbuhan project skala besar

## Fitur yang Sudah Ada

- Integrasi `BPJS VClaim`
- Referensi master `BPJS`
- Monitoring kunjungan dan klaim `BPJS`
- Pengelolaan `SEP`
- Pengelolaan `Surat Kontrol` dan `SPRI`
- Endpoint dasar `Antrol`
- Generate dan cache token `SATUSEHAT`
- Kirim `Encounter` ke `SATUSEHAT`
- Format response API konsisten
- Dukungan environment terpisah untuk kredensial integrasi

## Tech Stack

- `PHP 8.2`
- `Laravel 12`
- `Laravel Sanctum`
- `Spatie Laravel Permission`
- `Vite`
- `SQLite` / `MySQL`

## Struktur Project Saat Ini

```text
simrs-api/
├── app/
│   ├── Helpers/
│   ├── Http/Controllers/Api/
│   │   ├── Antrol/
│   │   ├── Bpjs/
│   │   └── SatuSehat/
│   ├── Models/
│   └── Services/
│       ├── Antrian/
│       ├── Bpjs/
│       └── SatuSehat/
├── config/
├── database/
├── docs/
├── routes/
├── tests/
├── .env.example
├── artisan
├── composer.json
└── package.json
```

Struktur ini masih mengikuti pola standar Laravel, sehingga mudah dipahami, tetapi juga sudah mulai dipisahkan berdasarkan domain integrasi.

## Endpoint yang Sudah Tersedia

Base URL lokal:

```text
http://127.0.0.1:8000/api/v1
```

### Root API

```http
GET /api/v1
```

### BPJS

```http
GET    /api/v1/bpjs/peserta
GET    /api/v1/bpjs/sep
GET    /api/v1/bpjs/sep-riwayat
POST   /api/v1/bpjs/sep
PUT    /api/v1/bpjs/sep
DELETE /api/v1/bpjs/sep

GET    /api/v1/bpjs/monitoring-kunjungan
GET    /api/v1/bpjs/monitoring-klaim

GET    /api/v1/bpjs/referensi/poli
GET    /api/v1/bpjs/referensi/diagnosa
GET    /api/v1/bpjs/referensi/faskes
GET    /api/v1/bpjs/referensi/dokter-dpjp
GET    /api/v1/bpjs/referensi/provinsi
GET    /api/v1/bpjs/referensi/kabupaten
GET    /api/v1/bpjs/referensi/kecamatan
GET    /api/v1/bpjs/referensi/prosedur

POST   /api/v1/bpjs/surat-kontrol/insert
POST   /api/v1/bpjs/surat-kontrol/update
POST   /api/v1/bpjs/spri/insert
POST   /api/v1/bpjs/spri/update
```

### Antrol

```http
GET /api/v1/antrol/antrean
```

### SATUSEHAT

```http
GET  /api/v1/satu-sehat/token
POST /api/v1/satu-sehat/encounter/send
```

## Format Response

Success response:

```json
{
  "metaData": {
    "code": "200",
    "message": "Sukses"
  },
  "response": {}
}
```

Error response:

```json
{
  "metaData": {
    "code": "400",
    "message": "Pesan error"
  },
  "response": {}
}
```

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/ahmadfauzirahman99/simrs-api.git
cd simrs-api
```

### 2. Pindah ke branch utama project

```bash
git checkout pmaster
```

### 3. Install dependency backend

```bash
composer install
```

### 4. Install dependency frontend

```bash
npm install
```

### 5. Siapkan file environment

```bash
cp .env.example .env
php artisan key:generate
```

### 6. Jalankan migrasi

```bash
php artisan migrate
```

### 7. Jalankan project

Mode cepat:

```bash
composer run dev
```

Mode manual:

```bash
php artisan serve
npm run dev
```

## Konfigurasi Environment

Contoh dasar:

```env
APP_NAME="SIMRS API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
```

### BPJS VClaim

```env
BPJS_V3_API_VER=3
BPJS_V3_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest
BPJS_V3_CONS_ID=your_cons_id
BPJS_V3_SECRET_KEY=your_secret_key
BPJS_V3_USER_KEY=your_user_key
```

### SATUSEHAT

```env
SATUSEHAT_ENV=DEV

SATUSEHAT_AUTH_DEV=https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1
SATUSEHAT_FHIR_DEV=https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1
SATUSEHAT_CLIENTID_DEV=your_client_id_dev
SATUSEHAT_CLIENTSECRET_DEV=your_client_secret_dev
SATUSEHAT_ORGID_DEV=your_org_id_dev

SATUSEHAT_AUTH_PROD=https://api-satusehat.kemkes.go.id/oauth2/v1
SATUSEHAT_FHIR_PROD=https://api-satusehat.kemkes.go.id/fhir-r4/v1
SATUSEHAT_CLIENTID_PROD=your_client_id_prod
SATUSEHAT_CLIENTSECRET_PROD=your_client_secret_prod
SATUSEHAT_ORGID_PROD=your_org_id_prod
```

## Arah Pengembangan

Project ini diarahkan untuk menjadi fondasi jangka panjang. Target pengembangan berikutnya:

- modularisasi domain `BPJS`, `SATUSEHAT`, dan shared components
- dokumentasi endpoint yang lebih lengkap dan mudah diikuti
- penguatan `testing`
- logging dan observability integrasi eksternal
- contoh implementasi alur bridging dari `SIMRS/HIS`
- penambahan resource `FHIR` lain selain `Encounter`

Blueprint arsitektur pengembangan tersedia di:

- [docs/architecture-blueprint.md](/Users/ahmadfauzirahman/Documents/project/simrs-api/docs/architecture-blueprint.md)

## Dokumentasi Tambahan

- [docs/api-documentation.md](/Users/ahmadfauzirahman/Documents/project/simrs-api/docs/api-documentation.md)
- [docs/satusehat.md](/Users/ahmadfauzirahman/Documents/project/simrs-api/docs/satusehat.md)
- [docs/postman/000001 - SIMRS NEW API.postman_collection.json](/Users/ahmadfauzirahman/Documents/project/simrs-api/docs/postman/000001%20-%20SIMRS%20NEW%20API.postman_collection.json)

## Catatan

README ini sekarang difokuskan sebagai pintu masuk project. Dokumentasi endpoint yang lebih detail akan lebih baik jika dipisah per domain agar repo tetap mudah dipelajari.

## Author

**Ahmad Fauzi Rahman**

Backend Developer  
SIMRS Developer  
Healthcare Technology Enthusiast

## License

Project ini menggunakan lisensi `MIT`.

Selama copyright notice dan lisensi tetap disertakan, project ini bebas digunakan untuk:

- penggunaan pribadi
- penggunaan komersial
- modifikasi source code
- distribusi ulang
- integrasi dengan sistem lain

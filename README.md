<p align="center">
  <img src="https://raw.githubusercontent.com/ahmadfauzirahman99/simrs-api/feat/client-portal-dan-deploy/public/favicon.svg" width="80" alt="SIMRS Gateway Logo" />
</p>

<h1 align="center">SIMRS Gateway & Simulator BPJS / SATUSEHAT</h1>

<p align="center">
  <strong>Platform Open-Source Bridging Layanan Kesehatan (BPJS VClaim, Antrol, Rekam Medis & Kemenkes SATUSEHAT) Siap Pakai untuk Rumah Sakit & Faskes Indonesia.</strong>
</p>

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square" alt="MIT License" /></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12" /></a>
  <a href="https://vuejs.org"><img src="https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3" /></a>
  <a href="https://saweria.co/fauzirahman05"><img src="https://img.shields.io/badge/Saweria-Traktir%20Kopi-FF813F?style=flat-square&logo=kofi&logoColor=white" alt="Dukung via Saweria" /></a>
  <img src="https://img.shields.io/badge/Endpoints-209%20Tested-0284c7?style=flat-square" alt="209 Endpoints" />
  <img src="https://img.shields.io/badge/Regulasi-PMK%2024%20%2F%20UU%20PDP-059669?style=flat-square" alt="Kepatuhan PMK 24" />
</p>

<p align="center">
  <a href="https://haurakirana.my.id" target="_blank"><strong>🌐 Live Demo & Web Simulator: haurakirana.my.id</strong></a>
</p>

<p align="center">
  <a href="#-live-demo--preview-aplikasi">🌐 Live Demo</a> •
  <a href="#-unduh-langsung-gratis">📦 Unduh Gratis</a> •
  <a href="#-fitur-utama">✨ Fitur</a> •
  <a href="#-antrean-online--layar-sentuh-apm-kiosk">🖥️ APM & Display TV</a> •
  <a href="#-arsitektur--kepatuhan-pmk-24">🏛️ Arsitektur</a> •
  <a href="#-instalasi-cepat">🚀 Instalasi</a> •
  <a href="#-dukung-proyek-saweria">☕ Donasi</a>
</p>

---

## 🌐 Live Demo & Preview Aplikasi

Mau langsung melihat dan mencoba aplikasi yang sudah berjalan tanpa perlu instalasi di komputer Anda? Kunjungi tautan live berikut:

👉 **[https://haurakirana.my.id](https://haurakirana.my.id)**

| Layanan / Halaman | Tautan Langsung | Keterangan |
|---|---|---|
| **Landing Page Utama** | [haurakirana.my.id](https://haurakirana.my.id) | Katalog modul, arsitektur, dan tautan unduh |
| **Studio Testing BPJS & SatuSehat** | [haurakirana.my.id/login](https://haurakirana.my.id/login) | Simulator 209 endpoint BPJS & SatuSehat |
| **Display TV Antrean Poli** | [haurakirana.my.id/display/antrean](https://haurakirana.my.id/display/antrean) | Layar ruang tunggu TV dengan pemanggilan suara |
| **Layar Sentuh APM Mandiri** | [haurakirana.my.id/apm](https://haurakirana.my.id/apm) | Kiosk anjungan check-in Mobile JKN & pendaftaran pasien |
| **Layar Panggil Dokter/Perawat** | [haurakirana.my.id/poli/panggil](https://haurakirana.my.id/poli/panggil) | Kontrol pemanggilan antrean per poli |

---
## 🎯 Mengapa SIMRS Gateway?

Bridging API BPJS Kesehatan dan SATUSEHAT sering menjadi momok bagi tim IT Rumah Sakit:
1. **Kompleksitas Enkripsi**: Menghitung HMAC-SHA256 timestamp signature, enkripsi AES-256-CBC, dan dekompresi LZ-string yang rentan gagal.
2. **Kepatuhan Task ID Antrean**: Pelaporan waktu tunggu pasien poliklinik (Task 1 sampai Task 7) ke BPJS Mobile JKN.
3. **Kepatuhan Privasi (UU PDP & PMK 24/2022)**: Data rekam medis pasien **wajib berada di server lokal (On-Premise)**, bukan di cloud pihak ketiga.

**SIMRS Gateway menyelesaikan semua masalah tersebut:** Anda cukup mengirim dan menerima **JSON biasa** ke gateway lokal ini, dan seluruh enkripsi, dekompresi, bridging, hingga simulator pengujian diurus secara otomatis!

---

## 📦 Unduh Langsung (Gratis)

| Paket | Format | Tautan Unduhan |
|---|---|---|
| **Full Source Code (v3.0)** | `.zip` | 👉 [**Download Source Code (.ZIP)**](https://github.com/ahmadfauzirahman99/simrs-api/archive/refs/heads/pmaster.zip) |
| **Postman Collection (209 Endpoint)** | `.json` | 👉 [**Download Postman Collection**](https://haurakirana.my.id/downloads/postman-collection) |
| **Koleksi Resmi GitHub** | `git clone` | `https://github.com/ahmadfauzirahman99/simrs-api.git` |

---

## ✨ Fitur Utama

### 1. Modul BPJS Kesehatan Terlengkap (209 Endpoint)
- **VClaim v2 & v3**: Registrasi SEP, riwayat rujukan, Surat Kontrol, SPRI, monitoring klaim & diagnosa ICD-10/ICD-9.
- **Antrean Farmasi & Poli (Antrol)**: Pendaftaran antrean poliklinik, pembatalan, pembaruan waktu tunggu, dan monitoring sisa kuota.
- **Rekam Medis Elektronik (E-Klaim / Sharing Rekam Medis)**: Ekstraksi dan enkripsi bundle resume medis terpadu sesuai standar BPJS.
- **Auto Cipher Engine**: HMAC-SHA256 signature generator + AES-256-CBC cipher + LZ-string decompressor transparan.

### 2. Modul Kemenkes SATUSEHAT (FHIR R4)
- **Token Manager**: Generate & cache token OAuth2 otomatis sebelum kedaluwarsa.
- **Resource FHIR Submissions**: `Encounter`, `Condition`, `Observation`, `Procedure`, `MedicationRequest`, `MedicationDispense`, `AllergyIntolerance`, `Immunization`, `ServiceRequest`, `DiagnosticReport`, `ChargeItem`, `Invoice`, dan `Coverage`.

### 3. Simulator Interaktif & Studio Testing UI
- Antarmuka web modern (Inertia Vue 3) untuk menguji seluruh 209 endpoint tanpa perlu pasien riil.
- Mode **Live API** (koneksi langsung ke server dev/prod BPJS) dan Mode **Mock Preview** (simulasi dummy realistis untuk demonstrasi UAT Kantor Cabang BPJS).

---

## 🖥️ Antrean Online & Layar Sentuh APM Kiosk

Selain API backend, SIMRS Gateway kini dilengkapi antarmuka frontend siap pakai untuk operasional rumah sakit:

1. **Layar Sentuh Anjungan Pendaftaran Mandiri (`/apm`)**
   - Check-in mandiri kode booking Mobile JKN via scan barcode atau input kode.
   - Pendaftaran pasien baru / umum secara mandiri di anjungan sentuh lobby RS.
2. **Display TV Antrean Poliklinik (`/display/antrean`)**
   - Layar ruang tunggu TV dengan palet klinis modern (navy & teal, tanpa elemen teks berjalan yang mengganggu).
   - Dilengkapi pemanggilan suara (Text-to-Speech) nomor antrean otomatis.
3. **Layar Panggil Dokter & Perawat (`/poli/panggil`)**
   - Kontrol pemanggilan pasien masuk ruang periksa, tombol lewati, dan panggilan ulang.
4. **Sinkronisasi Task ID 1-7 Otomatis (`simrs:sync-bpjs`)**
   - Command CLI untuk sinkronisasi antrean lokal RS ke endpoint `updatewaktu` BPJS Antrol secara berkala via Cron.

---

## 🏛️ Arsitektur & Kepatuhan PMK 24

```text
[ Komputer Klien / EMR RS ]
             │  (JSON Biasa / Intranet Lokal)
             ▼
┌────────────────────────────────────────────────────────┐
│     SIMRS GATEWAY (Server On-Premise Rumah Sakit)      │
│  - Laravel 12 + SQLite/MySQL                           │
│  - Mesin Otomatis HMAC-SHA256, AES-256, LZ-String      │
│  - Simulator & Web UI Studio Testing                   │
└────────────────────────────────────────────────────────┘
             │  (HTTPS Terenkripsi Keluar)
             ├───► [ Server BPJS Kesehatan (VClaim / Antrol) ]
             └───► [ Server Kemenkes RI (SATUSEHAT FHIR) ]
```

> **Catatan Kepatuhan**: SIMRS Gateway diinstal di server lokal rumah sakit Anda. Data pasien tidak pernah disimpan atau transit di server pihak ketiga, menjamin kepatuhan penuh terhadap **UU Perlindungan Data Pribadi (UU PDP)** dan **PMK 24 Tahun 2022**.

---

## 🚀 Instalasi Cepat

### Prasyarat
- PHP 8.2 atau lebih tinggi (ekstensi `curl`, `openssl`, `mbstring`, `pdo_sqlite` / `pdo_mysql`)
- Composer
- Node.js & NPM

### Langkah Instalasi
```bash
# 1. Clone repositori
git clone https://github.com/ahmadfauzirahman99/simrs-api.git
cd simrs-api

# 2. Checkout branch terkini
git checkout feat/client-portal-dan-deploy

# 3. Install dependency backend & frontend
composer install
npm install

# 4. Siapkan file konfigurasi
cp .env.example .env
php artisan key:generate

# 5. Jalankan migrasi database
php artisan migrate --seed

# 6. Kompilasi aset & jalankan server
npm run build
php artisan serve
```

Akses aplikasi di peramban:
- **Landing Page**: `http://127.0.0.1:8000/`
- **Dashboard Studio Tester**: `http://127.0.0.1:8000/login`
- **Display TV Antrean**: `http://127.0.0.1:8000/display/antrean`
- **Kiosk APM Mandiri**: `http://127.0.0.1:8000/apm`
- **Layar Panggil Poli**: `http://127.0.0.1:8000/poli/panggil`

---

## ⚙️ Konfigurasi Kredensial (.env)

```env
# Kredensial BPJS Kesehatan (VClaim & Antrol)
BPJS_V3_API_VER=3
BPJS_V3_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest
BPJS_V3_CONS_ID=your_cons_id
BPJS_V3_SECRET_KEY=your_secret_key
BPJS_V3_USER_KEY=your_user_key

# Kredensial SATUSEHAT Kemenkes RI
SATUSEHAT_ENV=DEV
SATUSEHAT_AUTH_DEV=https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1
SATUSEHAT_FHIR_DEV=https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1
SATUSEHAT_CLIENTID_DEV=your_client_id
SATUSEHAT_CLIENTSECRET_DEV=your_client_secret
SATUSEHAT_ORGID_DEV=your_organization_id
```

---

## ☕ Dukung Proyek (Saweria)

Proyek ini dikembangkan secara independen dan dibagikan **100% Bebas & Terbuka** untuk memajukan digitalisasi layanan kesehatan di Indonesia.

Jika SIMRS Gateway menghemat waktu riset atau mempermudah integrasi bridging di rumah sakit Anda, Anda dapat memberikan dukungan sukarela untuk biaya server demo publik dan riset pembaruan API melalui:

<p align="center">
  <a href="https://saweria.co/fauzirahman05">
    <img src="https://img.shields.io/badge/Saweria-Traktir%20Kopi%20Pengembang-FF813F?style=for-the-badge&logo=kofi&logoColor=white" alt="Traktir Kopi di Saweria" />
  </a>
  <br>
  👉 <strong><a href="https://saweria.co/fauzirahman05">saweria.co/fauzirahman05</a></strong><br>
  <em>(Mendukung QRIS, GoPay, OVO, DANA, ShopeePay, dan Transfer Bank)</em>
</p>

---

## 👨‍💻 Author & Jasa Konsultasi RS

**Ahmad Fauzi Rahman**  
*SIMRS & Healthcare Systems Developer*  
Telegram: [@FauziRahman95](https://t.me/FauziRahman95)

Bagi fasilitas kesehatan atau vendor SIMRS yang memerlukan asistensi profesional:
- 🛠️ **Instalasi & Deployment On-Premise** di server intranet RS.
- 🔄 **Kustomisasi Bridging** ke database SIMRS lama (SQL Server, MySQL, Postgres, dsb).
- 📋 **Pendampingan Teknis UAT** langsung bersama tim IT Kantor Cabang BPJS Kesehatan.

Silakan menghubungi via tautan Telegram di atas.

---

## 📄 Lisensi

Didistribusikan di bawah lisensi terbuka [MIT License](LICENSE). Bebas digunakan, dimodifikasi, dan didistribusikan untuk fasilitas kesehatan di seluruh Indonesia.

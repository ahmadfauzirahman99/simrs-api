#!/usr/bin/env bash
set -e

# ==============================================================================
# Script Export Source Code SIMRS Gateway - Edisi Single Hospital (Beli Putus)
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
STAGE_DIR="/tmp/simrs-gateway-hospital-export"
ZIP_NAME="simrs-gateway-hospital-v3.0.zip"
ZIP_PATH="${DIST_DIR}/${ZIP_NAME}"

echo "==> Menyiapkan folder export..."
mkdir -p "${DIST_DIR}"
rm -rf "${STAGE_DIR}"
mkdir -p "${STAGE_DIR}"

echo "==> Menyalin source code bersih (tanpa git, vendor, node_modules, cache)..."
rsync -av --quiet \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='.idea/' \
  --exclude='.vscode/' \
  --exclude='satusehat-laravel-skill/' \
  --exclude='.DS_Store' \
  --exclude='*/.DS_Store' \
  --exclude='node_modules/' \
  --exclude='vendor/' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='database/database.sqlite' \
  --exclude='.phpunit.result.cache' \
  --exclude='storage/logs/*.log' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/app/public/payment_proofs/*' \
  --exclude='storage/app/private/payment_proofs/*' \
  --exclude='deploy/' \
  --exclude='deploy.sh' \
  --exclude='public/hot' \
  --exclude='public/storage' \
  --exclude='dist/' \
  --exclude='scripts/' \
  --exclude='sampling.json' \
  --exclude='sampling.normalized.json' \
  --exclude='docs/PROSES_BISNIS_DAN_MONETISASI.md' \
  --exclude='docs/PANDUAN_PENDAFTARAN_DAN_REQUEST_CLIENT.md' \
  "${ROOT_DIR}/" "${STAGE_DIR}/"

# Buat .env.example khusus Edisi Standalone Rumah Sakit
cat << 'EOF' > "${STAGE_DIR}/.env.example"
APP_NAME="SIMRS Bridging Gateway"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8000

# Mode Standalone: Otomatis nonaktifkan limit kuota SaaS & cek langganan bulanan
STANDALONE_MODE=true

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=simrs_gateway
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database

# ==============================================================================
# KREDENSIAL BPJS KESEHATAN RS
# ==============================================================================
BPJS_VCLAIM_CONS_ID=
BPJS_VCLAIM_SECRET_KEY=
BPJS_VCLAIM_USER_KEY=
BPJS_VCLAIM_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev/

BPJS_ANTREAN_CONS_ID=
BPJS_ANTREAN_SECRET_KEY=
BPJS_ANTREAN_USER_KEY=
BPJS_ANTREAN_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/

BPJS_APOTEK_CONS_ID=
BPJS_APOTEK_SECRET_KEY=
BPJS_APOTEK_USER_KEY=
BPJS_APOTEK_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/apotek-rest-dev/

BPJS_APLICARES_CONS_ID=
BPJS_APLICARES_SECRET_KEY=
BPJS_APLICARES_USER_KEY=
BPJS_APLICARES_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/aplicaresws/

BPJS_ICARE_CONS_ID=
BPJS_ICARE_SECRET_KEY=
BPJS_ICARE_USER_KEY=
BPJS_ICARE_BASE_URL=https://apijkn-dev.bpjs-kesehatan.go.id/ihs_dev/

# ==============================================================================
# KREDENSIAL KEMENKES SATUSEHAT RS
# ==============================================================================
SATUSEHAT_ENV=staging
SATUSEHAT_ORG_ID=
SATUSEHAT_CLIENT_ID=
SATUSEHAT_CLIENT_SECRET=
EOF

# Buat README instalasi resmi untuk IT Rumah Sakit
cat << 'EOF' > "${STAGE_DIR}/README.md"
# SIMRS Bridging Gateway (Edisi On-Premise Rumah Sakit)

Gateway integrasi mandiri untuk menghubungkan SIMRS Lokal Rumah Sakit dengan:
1. **BPJS Kesehatan**: VClaim, Antrean RS, Apotek Pelayanan, Aplicares, Rekam Medis (FRM), i-Care JKN.
2. **Kemenkes SATUSEHAT**: Standar FHIR R4 (Encounter, Condition, Observation, Procedure, Medication, dll).

Sistem menangani enkripsi AES-256-CBC, kalkulasi signature HMAC-SHA256, dan dekripsi LZ-String otomatis, sehingga SIMRS Anda cukup mengirim dan menerima data JSON bersih.

---

## Spesifikasi Server Minimal
* **OS**: Linux (Ubuntu 22.04 LTS / Debian) atau Windows Server.
* **PHP**: Versi 8.2 atau lebih tinggi (ekstensi: `curl`, `mbstring`, `openssl`, `pdo_sqlite` / `pdo_mysql`, `fileinfo`).
* **Composer**: Versi 2.x.
* **Database**: SQLite (default) atau MySQL / MariaDB.

---

## Langkah Instalasi Cepat (4 Menit)

### 1. Salin Environment
```bash
cp .env.example .env
```

### 2. Install Dependensi PHP
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Generate App Key & Jalankan Migrasi Database
```bash
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed --force
```

### 4. Konfigurasi Kredensial BPJS & SATUSEHAT
Buka file `.env` dan masukkan Cons ID, Secret Key, dan User Key RS Anda:
```dotenv
BPJS_VCLAIM_CONS_ID=12345
BPJS_VCLAIM_SECRET_KEY=rahasia123
BPJS_VCLAIM_USER_KEY=userKeyBPJS
```

### 5. Jalankan Service
Untuk pengujian lokal:
```bash
php artisan serve --port=8000
```
Untuk produksi di server intranet RS, gunakan Web Server **Nginx + PHP-FPM** atau Daemon **Supervisor / Systemd**.

---

## Akun Login Tester Studio
* **URL**: `http://ip-server:8000/login`
* **Email**: `admin@simrs.local`
* **Password**: `password`

## Dokumentasi API & Postman
* Download koleksi Postman siap pakai di: `http://ip-server:8000/downloads/postman-collection`
* Endpoint API: `http://ip-server:8000/api/v1/bpjs/...`
EOF

echo "==> Mengompres ke berkas ZIP..."
cd "${STAGE_DIR}"
rm -f "${ZIP_PATH}"
zip -q -r "${ZIP_PATH}" .

rm -rf "${STAGE_DIR}"

ZIP_SIZE=$(du -h "${ZIP_PATH}" | awk '{print $1}')
echo ""
echo "=============================================================================="
echo "✓ ZIP BERHASIL DIBUAT!"
echo "  Lokasi: ${ZIP_PATH}"
echo "  Ukuran: ${ZIP_SIZE}"
echo "=============================================================================="

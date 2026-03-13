Jika repository ini membantu pengembangan **SIMRS atau sistem rumah sakit**, kamu bisa mendukung pengembangan project ini.

Dukungan dari kamu akan membantu:

- pengembangan fitur baru
- update integrasi BPJS
- maintenance repository
- peningkatan dokumentasi

Support project melalui Saweria:

👉 https://saweria.co/fauzirahman05

Terima kasih untuk semua yang mendukung pengembangan teknologi kesehatan di Indonesia 🙏


# SIMRS API

Backend API untuk integrasi **Sistem Informasi Manajemen Rumah Sakit (SIMRS)** dengan layanan **BPJS VClaim** menggunakan **Laravel 12**.

Project ini dibuat sebagai fondasi layanan API internal untuk kebutuhan bridging SIMRS seperti:

- pencarian peserta BPJS
- pengelolaan SEP
- monitoring kunjungan
- referensi master BPJS
- integrasi layanan rumah sakit

Repository ini dirancang agar mudah dikembangkan menjadi **backend service SIMRS skala besar**.

---

# ✨ Fitur Utama

- Integrasi **BPJS VClaim V3**
- Pencarian data peserta BPJS
- Pencarian dan pengelolaan SEP
- Monitoring kunjungan BPJS
- Monitoring klaim BPJS
- Referensi BPJS:
  - Poli
  - Diagnosa
  - Faskes
  - Dokter DPJP
  - Provinsi
  - Kabupaten
  - Kecamatan
  - Prosedur
- Format response API konsisten
- Konfigurasi environment terpisah

---

# 🏗️ Tech Stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum
- Spatie Permission
- Vite
- SQLite / MySQL

---

# 📁 Struktur Project

```
simrs-api/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│
├── .env.example
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

Struktur ini mengikuti standar Laravel sehingga memudahkan pengembangan API secara modular.

---

# 🚀 Instalasi

## 1 Clone repository

```
git clone https://github.com/ahmadfauzirahman99/simrs-api.git
cd simrs-api
```

---

## 2 Gunakan branch utama

```
git checkout pmaster
```

---

## 3 Install dependency

```
composer install
```

---

## 4 Install dependency frontend

```
npm install
```

---

## 5 Copy environment file

```
cp .env.example .env
```

---

## 6 Generate application key

```
php artisan key:generate
```

---

## 7 Jalankan migrasi

```
php artisan migrate
```

---

## 8 Jalankan aplikasi

```
php artisan serve
```

Untuk development asset:

```
npm run dev
```

---

# ⚙️ Konfigurasi Environment

Edit file `.env`

```
APP_NAME=SIMRS API
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
```

Konfigurasi BPJS:

```
BPJS_V3_API_VER=2
BPJS_V3_BASE_URL=URL_BPJS_V3
BPJS_V3_CONS_ID=CONS_ID_BPJS_V3
BPJS_V3_SECRET_KEY=SECRET_KEY_BP
BPJS_V3_USER_KEY=USER_KEY_BPJS_V3
```

---

# ▶️ Menjalankan Project

Mode development cepat

```
composer run dev
```

Mode manual

```
php artisan serve
npm run dev
```

---

# 📡 Base API

Contoh base URL lokal

```
http://127.0.0.1:8000/api/v1
```

Endpoint root

```
GET /api/v1
```

Response:

```
{
  "metaData": {
    "code": "200",
    "message": "Sukses"
  },
  "response": {
    "app": "SIMRS API",
    "version": "v1",
    "status": "active"
  }
}
```

---

# 📚 Daftar Endpoint

## Cek status API

```
GET /api/v1
```

---

## BPJS VClaim

### Cari peserta

```
GET /api/v1/bpjs/peserta
```

Parameter

```
nomor
tipe
```

---

### Cari SEP

```
GET /api/v1/bpjs/sep
```

Parameter

```
no_sep
```

---

### Monitoring kunjungan

```
GET /api/v1/bpjs/monitoring-kunjungan
```

Parameter

```
tanggal
tipe
```

---

### Monitoring klaim

```
GET /api/v1/bpjs/monitoring-klaim
```

Parameter

```
tanggal
tipe
status
```

---

# 🧾 Format Response

Success response

```
{
  "metaData": {
    "code": "200",
    "message": "Sukses"
  },
  "response": {}
}
```

Error response

```
{
  "metaData": {
    "code": "400",
    "message": "Pesan error"
  },
  "response": {}
}
```

---


---

# 👨‍💻 Author

**Ahmad Fauzi Rahman**

Backend Developer  
SIMRS Developer  
Healthcare Technology Enthusiast

GitHub:  


# LICENSE

MIT License

Copyright (c) 2026 Ahmad Fauzi Rahman

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software.

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND.

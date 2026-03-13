# SIMRS API Documentation

## Overview

SIMRS New API adalah kumpulan endpoint backend berbasis **Laravel** yang
digunakan untuk mendukung integrasi Sistem Informasi Manajemen Rumah
Sakit (SIMRS).

API ini mencakup beberapa layanan utama seperti:

-   Bridging **BPJS VClaim**
-   Data **Referensi BPJS**
-   **Monitoring Kunjungan**
-   **Monitoring Klaim**
-   Integrasi modul **Surat Kontrol (SUKON)**

API dirancang untuk memudahkan integrasi dengan:

-   SIMRS
-   Mobile Application
-   Web Frontend
-   Sistem eksternal lainnya

------------------------------------------------------------------------

# Endpoint: Insert Surat Kontrol

Endpoint ini digunakan untuk membuat **Surat Kontrol BPJS** melalui
bridging SIMRS.

## Endpoint URL

POST

    /v1/bpjs/surat-kontrol/insert

------------------------------------------------------------------------

## Headers

  Key            Value
  -------------- ------------------
  Content-Type   application/json
  Accept         application/json

------------------------------------------------------------------------

## Request Body

Body request harus menggunakan format **request object** seperti
berikut:

``` json
{
  "request": {
    "noSEP": "0301R0111018V000006",
    "kodeDokter": "12345",
    "poliKontrol": "INT",
    "tglRencanaKontrol": "2021-03-20",
    "user": "ws"
  }
}
```

------------------------------------------------------------------------

## Parameter

  Field               Type     Required   Description
  ------------------- -------- ---------- --------------------------------
  noSEP               string   Yes        Nomor SEP pasien
  kodeDokter          string   Yes        Kode dokter BPJS
  poliKontrol         string   Yes        Kode poli tujuan kontrol
  tglRencanaKontrol   date     Yes        Tanggal rencana kontrol pasien
  user                string   Yes        User pembuat surat kontrol

------------------------------------------------------------------------

## Response Success

``` json
{
  "metaData": {
    "code": "200",
    "message": "Sukses"
  },
  "response": {
    "noSuratKontrol": "0301R0110321K000002"
  }
}
```

------------------------------------------------------------------------

## Response Error

``` json
{
  "metaData": {
    "code": "400",
    "message": "Data tidak valid"
  },
  "response": {}
}
```

------------------------------------------------------------------------

## Catatan

Body request **harus menggunakan object `request`**, bukan langsung
field di root JSON.

Contoh yang benar:

``` json
{
  "request": { ... }
}
```

Contoh yang salah:

``` json
{
  "noSEP": "..."
}
```

------------------------------------------------------------------------

# Struktur API SIMRS

API SIMRS dibagi menjadi beberapa kategori utama:

### VCLAIM

Digunakan untuk integrasi layanan BPJS seperti:

-   Peserta
-   SEP
-   Riwayat Pelayanan

### REFERENSI

Digunakan untuk mengambil data referensi BPJS:

-   Provinsi
-   Kabupaten
-   Poli
-   Diagnosa

### MONITORING

Digunakan untuk monitoring data layanan:

-   Monitoring kunjungan
-   Monitoring klaim

### SUKON (Surat Kontrol)

Digunakan untuk pengelolaan surat kontrol BPJS:

-   Insert Surat Kontrol
-   Update Surat Kontrol

------------------------------------------------------------------------

# Best Practice Integrasi

Saat menggunakan API SIMRS:

1.  Selalu kirim header `application/json`
2.  Gunakan environment variable pada Postman
3.  Gunakan struktur body sesuai dokumentasi
4.  Tangani response error dengan benar

------------------------------------------------------------------------

# Author

SIMRS API\
Backend Integration for Hospital Information System

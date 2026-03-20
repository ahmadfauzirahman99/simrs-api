# Architecture Blueprint

Dokumen ini menjadi arah pengembangan `SIMRS API` agar dapat tumbuh dari project integrasi API menjadi fondasi backend bridging kesehatan yang modular, stabil, dan mudah dipakai ulang.

## Tujuan

Blueprint ini dibuat untuk memastikan project:

- mudah dipahami programmer baru
- mudah dikembangkan tanpa merusak integrasi lama
- cocok untuk skala project rumah sakit dan vendor software
- siap menampung integrasi `BPJS`, `SATUSEHAT`, dan layanan kesehatan lain

## Prinsip Arsitektur

1. Pisahkan berdasarkan domain bisnis, bukan hanya jenis file.
2. Bedakan dengan jelas lapisan `HTTP`, `Application`, `Domain`, dan `Infrastructure`.
3. Semua integrasi eksternal harus punya wrapper/service sendiri.
4. Response API internal harus konsisten.
5. Logging, validation, dan error handling harus menjadi bagian fondasi, bukan tambahan belakangan.

## Struktur Target

Contoh struktur target yang direkomendasikan:

```text
app/
├── Domain/
│   ├── Bpjs/
│   │   ├── VClaim/
│   │   ├── Referensi/
│   │   ├── Antrean/
│   │   └── Shared/
│   ├── SatuSehat/
│   │   ├── Auth/
│   │   ├── Fhir/
│   │   ├── Encounter/
│   │   └── Shared/
│   └── Shared/
├── Application/
│   ├── Bpjs/
│   ├── SatuSehat/
│   └── Shared/
├── Infrastructure/
│   ├── Http/
│   ├── Persistence/
│   ├── Logging/
│   └── Security/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
└── Support/
    ├── Api/
    ├── Exceptions/
    └── Helpers/
```

Struktur ini tidak harus diterapkan sekaligus. Migrasi bisa dilakukan bertahap dari struktur Laravel saat ini.

## Pemetaan dari Struktur Saat Ini

Kondisi repo saat ini sudah punya pemisahan awal yang bagus:

- `app/Http/Controllers/Api/Bpjs` untuk controller domain BPJS
- `app/Http/Controllers/Api/SatuSehat` untuk controller SATUSEHAT
- `app/Services/Bpjs` untuk service integrasi BPJS
- `app/Services/SatuSehat` untuk service integrasi SATUSEHAT

Langkah berikutnya adalah membuat batas tanggung jawab yang lebih tegas:

- controller hanya menerima request dan mengembalikan response
- validasi dipindah ke `Form Request`
- penyusunan payload dipindah ke service/action
- komunikasi HTTP eksternal dipindah ke client khusus
- mapping response eksternal dipisahkan dari controller

## Domain Utama

### 1. BPJS

Domain `BPJS` sebaiknya dipisah menjadi beberapa modul:

- `VClaim`
- `Referensi`
- `SEP`
- `Monitoring`
- `SuratKontrol`
- `SPRI`
- `Antrean`

Setiap modul idealnya punya:

- controller
- request validation
- action/service
- external client
- DTO atau payload mapper
- test

### 2. SATUSEHAT

Domain `SATUSEHAT` sebaiknya dibagi menjadi:

- `Auth`
- `FHIR Client`
- `Encounter`
- `Patient`
- `Practitioner`
- `Location`
- `Observation`
- `Condition`
- `Procedure`

Untuk tahap awal, `Encounter` bisa menjadi pola referensi untuk resource FHIR lain.

### 3. Shared

Komponen bersama yang dipakai lintas domain:

- response formatter
- exception handler integrasi
- http client base
- request/response logger
- correlation id
- config resolver
- helper normalisasi tanggal dan timezone

## Alur Request yang Direkomendasikan

```text
Client
  -> Controller
  -> Form Request Validation
  -> Action / Application Service
  -> Integration Client
  -> External Service
  -> Mapper / Transformer
  -> ApiResponse
```

Tujuannya agar controller tetap tipis dan mudah dibaca.

## Standar Penulisan Endpoint

Rekomendasi standar:

- gunakan prefix versi: `/api/v1`
- kelompokkan route per domain
- nama endpoint mengikuti resource, bukan nama fungsi internal
- bedakan endpoint `read`, `create`, `update`, dan `delete` secara konsisten

Contoh:

```text
/api/v1/bpjs/sep
/api/v1/bpjs/referensi/poli
/api/v1/satu-sehat/token
/api/v1/satu-sehat/encounters
```

## Standar Error Handling

Semua integrasi eksternal perlu memiliki error handling yang seragam:

- timeout
- authentication failure
- invalid payload
- upstream service unavailable
- response format mismatch

Rekomendasi tambahan:

- simpan error code internal
- bedakan error dari aplikasi dan error dari vendor eksternal
- log detail teknis tanpa membocorkan data sensitif ke client

## Logging dan Observability

Karena project ini berbicara dengan layanan eksternal, logging harus diperlakukan sebagai fitur inti.

Minimal yang perlu dicatat:

- nama integrasi
- endpoint eksternal
- method HTTP
- request id / correlation id
- durasi request
- status code
- ringkasan error

Hindari menyimpan data sensitif secara mentah jika tidak diperlukan.

## Testing Strategy

Target minimal:

- `Feature Test` untuk endpoint publik
- `Unit Test` untuk payload builder dan mapper
- fake HTTP untuk simulasi `BPJS` dan `SATUSEHAT`
- test validasi request
- test format response sukses dan gagal

Strategi ini penting agar project tetap aman saat contributor mulai bertambah.

## Dokumentasi yang Perlu Dijaga

Supaya repo ini benar-benar membantu programmer Indonesia, dokumentasi sebaiknya dibagi menjadi:

- `README.md` untuk pintu masuk project
- `docs/bpjs/*.md` untuk panduan per modul BPJS
- `docs/satusehat/*.md` untuk panduan resource FHIR
- `docs/examples/*.md` untuk contoh request/response
- `docs/contributing.md` untuk panduan kontribusi
- `docs/architecture-blueprint.md` untuk arah desain project

## Roadmap yang Direkomendasikan

### Fase 1: Fondasi

- rapikan README
- sinkronkan dokumentasi dengan implementasi
- tambah `Form Request`
- seragamkan response API
- rapikan konfigurasi `.env`

### Fase 2: Stabilitas

- tambah test dasar
- tambah logging integrasi
- buat base HTTP client untuk domain eksternal
- pisahkan action/service dari controller

### Fase 3: Skalabilitas

- modularisasi domain
- dokumentasi per resource
- tambah contributor guide
- tambah contoh implementasi end-to-end

### Fase 4: Ekspansi Integrasi

- resource FHIR tambahan untuk `SATUSEHAT`
- modul BPJS tambahan
- webhook/event processing
- queue untuk proses sinkronisasi asinkron

## Keputusan Penting untuk Project Ini

Jika project ini memang ingin menjadi acuan komunitas, maka identitasnya perlu konsisten:

- bukan hanya project API internal
- bukan hanya wrapper endpoint BPJS
- tetapi fondasi integrasi kesehatan Indonesia yang praktis dan bisa diadopsi

Dengan arah itu, setiap penambahan fitur sebaiknya selalu mempertimbangkan:

- kemudahan onboarding
- konsistensi struktur
- kestabilan kontrak API
- kemudahan testing
- kemudahan dokumentasi

# BPJS Medical Record Database Design

Dokumen ini menjelaskan fondasi database yang disarankan untuk kebutuhan pengiriman data EMR ke layanan rekam medis BPJS.

## Tujuan Desain

Desain ini memisahkan data menjadi tiga lapisan:

- data operasional EMR yang berasal dari sistem rumah sakit
- bundle rekam medis yang dibentuk khusus untuk kebutuhan BPJS
- log pengiriman dan response integrasi

Pendekatan ini dipilih agar:

- data klinis utama tetap mudah diquery dan diupdate
- bundle BPJS dapat digenerate ulang kapan saja
- proses audit pengiriman lebih mudah dilakukan
- perubahan format payload BPJS tidak merusak data inti

## Arsitektur Penyimpanan

### 1. Data Operasional EMR

Data utama tetap disimpan di tabel relasional internal aplikasi, misalnya:

- `patients`
- `encounters`
- `organizations`
- `practitioners`
- `medical_record_compositions`
- `medical_record_sections`
- `medical_record_conditions`
- `medical_record_procedures`
- `medical_record_medications`
- `medical_record_diagnostic_reports`
- `medical_record_observations`

Tabel-tabel ini adalah sumber data utama untuk membentuk bundle rekam medis.

### 2. Snapshot Integrasi BPJS

Saat akan mengirim data ke BPJS, sistem membentuk bundle dari data operasional, lalu menyimpannya sebagai snapshot payload integrasi.

Tabel yang digunakan:

- `bpjs_medical_record_jobs`
- `bpjs_medical_record_payloads`

### 3. Log Pengiriman

Setelah payload dibentuk, sistem menyimpan:

- bundle JSON mentah
- bundle JSON yang sudah dinormalisasi
- payload hasil kompresi/enkripsi
- request akhir ke BPJS
- response dari BPJS
- status pengiriman

## Tabel Integrasi yang Diimplementasikan di Repo Ini

### `bpjs_medical_record_jobs`

Fungsi:
- menandai satu proses pengiriman rekam medis ke BPJS

Field:
- `id`
- `encounter_id`
- `patient_id`
- `sep_no`
- `jns_pelayanan`
- `bulan`
- `tahun`
- `status`
- `last_error`
- `sent_at`
- `timestamps`

Keterangan:
- `encounter_id` dan `patient_id` saat ini masih bertipe string agar fleksibel terhadap sistem sumber
- `status` dapat menggunakan nilai seperti `draft`, `generated`, `sent`, `failed`

### `bpjs_medical_record_payloads`

Fungsi:
- menyimpan seluruh snapshot payload untuk satu job pengiriman

Field:
- `id`
- `job_id`
- `bundle_json`
- `normalized_bundle_json`
- `gzip_payload`
- `encrypted_payload`
- `request_json`
- `response_json`
- `bpjs_response_code`
- `bpjs_response_message`
- `timestamps`

Keterangan:
- `bundle_json` menyimpan hasil bundle mentah
- `normalized_bundle_json` menyimpan bundle yang sudah dirapikan
- `gzip_payload` menyimpan hasil kompresi sebelum enkripsi bila ingin diaudit
- `encrypted_payload` menyimpan nilai akhir yang akan dipakai sebagai `dataMR`

## Relasi

```text
bpjs_medical_record_jobs
    id
    └── hasMany bpjs_medical_record_payloads.job_id
```

## Saran Tabel Operasional Jangka Lanjut

Jika nanti project ini ingin benar-benar mengelola data EMR sebelum dibentuk ke bundle BPJS, tabel minimal yang disarankan adalah:

### `patients`

- `id`
- `mrn`
- `bpjs_card_no`
- `nik`
- `name`
- `gender`
- `birth_date`
- `phone`
- `address_text`
- `city`
- `district`
- `state`
- `postal_code`
- `is_active`

### `encounters`

- `id`
- `patient_id`
- `registration_no`
- `sep_no`
- `care_type`
- `class_code`
- `class_display`
- `status`
- `start_at`
- `end_at`
- `organization_id`
- `attending_practitioner_id`

### `organizations`

- `id`
- `code_bpjs`
- `code_kemkes`
- `name`
- `alias`
- `type_code`
- `type_display`
- `phone`
- `address_text`
- `city`
- `district`
- `state`
- `postal_code`
- `country`

### `practitioners`

- `id`
- `name`
- `nik`
- `specialty_code`
- `specialty_name`
- `phone`
- `organization_id`

### `medical_record_compositions`

- `id`
- `encounter_id`
- `patient_id`
- `author_practitioner_id`
- `title`
- `document_type_code`
- `document_type_display`
- `status`
- `document_date`
- `confidentiality`

### `medical_record_sections`

- `id`
- `composition_id`
- `section_order`
- `title`
- `code_system`
- `code`
- `code_display`
- `text_status`
- `text_div`
- `mode`
- `entry_reference`

### `medical_record_conditions`

- `id`
- `encounter_id`
- `patient_id`
- `clinical_status`
- `verification_status`
- `category_code`
- `category_display`
- `diagnosis_code`
- `diagnosis_display`
- `onset_at`

### `medical_record_procedures`

- `id`
- `encounter_id`
- `patient_id`
- `performer_practitioner_id`
- `status`
- `procedure_code`
- `procedure_display`
- `performed_start_at`
- `performed_end_at`
- `reason_text`
- `note_text`

### `medical_record_medications`

- `id`
- `encounter_id`
- `patient_id`
- `requester_practitioner_id`
- `organization_id`
- `prescription_no`
- `medication_code`
- `medication_display`
- `status`
- `intent`
- `dose_value`
- `dose_unit`
- `route_code`
- `route_display`
- `frequency`
- `period`
- `period_unit`
- `instruction_text`
- `updated_at_source`

### `medical_record_diagnostic_reports`

- `id`
- `encounter_id`
- `patient_id`
- `organization_id`
- `report_no`
- `category_code`
- `category_display`
- `status`
- `issued_at`
- `effective_at`
- `conclusion`

### `medical_record_observations`

- `id`
- `diagnostic_report_id`
- `performer_practitioner_id`
- `observation_code`
- `observation_display`
- `status`
- `value`
- `value_unit`
- `interpretation_code`
- `interpretation_display`
- `reference_low`
- `reference_high`
- `issued_at`
- `effective_at`
- `conclusion`
- `narrative_text`

## Alur Implementasi yang Disarankan

1. Data pasien dan klinis disimpan di tabel operasional.
2. Sistem membentuk bundle rekam medis BPJS dari encounter tertentu.
3. Bundle mentah dan bundle normalisasi disimpan ke `bpjs_medical_record_payloads`.
4. Bundle diubah menjadi `dataMR` melalui proses kompresi dan enkripsi.
5. Request dikirim ke BPJS.
6. Response dan status pengiriman disimpan untuk audit.

## Service yang Sudah Disiapkan di Repo Ini

Service dasar yang sudah ditambahkan:

- `GenerateBpjsMedicalRecordBundleService`
  - membaca bundle sample dari `sampling.normalized.json`
  - membuat snapshot ringkas resource

- `EncodeBpjsMedicalRecordPayloadService`
  - mengubah bundle menjadi JSON
  - mengompres dengan `gzip`
  - mengenkripsi payload menjadi `dataMR`

- `BuildBpjsMedicalRecordRequestService`
  - membentuk wrapper request BPJS:
    - `noSep`
    - `jnsPelayanan`
    - `bulan`
    - `tahun`
    - `dataMR`

Konfigurasi environment yang disarankan:

- `BPJS_MEDICAL_RECORD_PROVIDER_CODE`
- `BPJS_MEDICAL_RECORD_SAMPLE_PATH`

# DOKUMENTASI LENGKAP ALIRAN DATA TASK ID (1 S/D 7) BPJS ANTREAN ONLINE & APM

Dokumen ini menjelaskan arsitektur, siklus hidup, dan integrasi teknis **Task ID 1 s/d 7** antara **Anjungan Pasien Mandiri (APM)**, **SIMRS Lokal**, dan server **BPJS Kesehatan (Antrean Online / Antrol)**.

---

## 1. Arsitektur Aliran Data (End-to-End)

```
[ PASIEN ]
    │
    ▼ (Datang ke RS)
┌────────────────────────────────────────────────────────┐
│              ANJUNGAN PASIEN MANDIRI (APM)             │
│  - Check-In Kode Booking (Mobile JKN)                  │
│  - Atau Daftar Mandiri Go-Show (Ketik NIK/No BPJS)     │
└───────────┬────────────────────────────────────────────┘
            │ 
            │ (1) Memicu Task 1 & 2 (Admisi)
            │ (2) Memicu Task 3 (Selesai Admisi / Menuju Poli)
            ▼
┌────────────────────────────────────────────────────────┐
│            GATEWAY SIMRS (simrs-api)                   │
│  - Catat timestamp milidetik ke tabel `antreans`       │
│  - Kirim HTTP POST ke BPJS `/antrean/updatewaktu`      │
└───────────┬────────────────────────────────────────────┘
            │
            ├────────────────────────────────────────────┐
            ▼ (Di Ruang Tunggu Poli)                     ▼ (Kirim ke Server BPJS)
┌───────────────────────────────┐        ┌───────────────────────────────┐
│   SIMRS POLIKLINIK (DOKTER)   │        │     SERVER BPJS KESEHATAN     │
│  - Pasien Dipanggil -> Task 4 │        │  - Endpoint Antrean RS        │
│  - Resep Selesai    -> Task 5 │        │  - Dashboard Waktu Tunggu RS  │
└───────────┬───────────────────┘        └───────────────────────────────┘
            │
            ▼ (Menuju Farmasi)
┌───────────────────────────────┐
│     SIMRS INSTALASI FARMASI   │
│  - Obat Diracik     -> Task 6 │
│  - Obat Diserahkan  -> Task 7 │
└───────────────────────────────┘
```

---

## 2. Rincian 7 Task ID BPJS Kesehatan

Setiap task dikirim ke endpoint BPJS Antrol:
- **URL**: `{BASE_URL}/antrean/updatewaktu`
- **Method**: `POST`
- **Format Timestamp**: Milidetik UNIX Epoch (`time() * 1000`)

| Task ID | Deskripsi Status | Siapa yang Memicu | Kapan Dikirim |
|---|---|---|---|
| **Task 1** | Mulai Waktu Tunggu Admisi | APM / Mesin Antrean | Saat pasien mengambil nomor antrean admisi di lobby. |
| **Task 2** | Mulai Pelayanan Admisi | APM / Petugas Loket | Saat pasien mulai diproses pendaftarannya di loket/mesin. |
| **Task 3** | Selesai Admisi / Menuju Poli | **APM Kiosk saat Check-In** | Pasien selesai daftar/check-in, mencetak karcis, dan berjalan menuju poli. |
| **Task 4** | Pasien Mulai Dilayani Dokter | Komputer Poli Dokter | Dokter menekan tombol "Panggil Pasien" di SIMRS Poli. |
| **Task 5** | Selesai Pelayanan Poli | Komputer Poli Dokter | Dokter selesai input diagnosa, tindakan, dan kirim resep obat elektronik. |
| **Task 6** | Mulai Pelayanan Resep Farmasi | Komputer Farmasi / Apotek | Petugas apotek menerima resep dan mulai meracik obat. |
| **Task 7** | Obat Selesai & Diserahkan | Komputer Farmasi / Kasir | Pasien menerima obat dan diedukasi aturan minumnya (Alur tuntas). |

---

## 3. Format Payload Pengiriman ke BPJS

### Request Header (Enkripsi BPJS V3)
```http
POST /antrean/updatewaktu HTTP/1.1
Host: apijkn.bpjs-kesehatan.go.id
Content-Type: application/json
x-cons-id: 12345
x-timestamp: 1729864200
x-signature: aW52YWxpZHNpZ25hdHVyZQ==
user_key: 8a7b6c5d4e3f2a1b0c9d
```

### Request Body (JSON)
```json
{
   "kodebooking": "JKN261001",
   "taskid": 3,
   "waktu": 1729864200000,
   "keterangan": "Pasien Check-In Mandiri di Kiosk APM"
}
```

### Format Respon BPJS
```json
{
   "metadata": {
      "code": 200,
      "message": "Ok"
   }
}
```

---

## 4. Rumus Perhitungan Indikator Mutu Waktu Tunggu (SPM RS)

Berdasarkan regulasi Kemenkes dan KPI BPJS Kesehatan:

1. **Waktu Tunggu Admisi**:
   $$\Delta T_{\text{admisi}} = \text{Task 2} - \text{Task 1}$$
   *(Target SPM: $\le 10$ menit)*

2. **Waktu Layan Admisi**:
   $$\Delta T_{\text{layan admisi}} = \text{Task 3} - \text{Task 2}$$
   *(Target SPM: $\le 5$ menit di APM / $\le 8$ menit di Loket)*

3. **Waktu Tunggu Pelayanan Poli**:
   $$\Delta T_{\text{tunggu poli}} = \text{Task 4} - \text{Task 3}$$
   *(Target SPM: $\le 60$ menit)*

4. **Waktu Pemeriksaan Dokter**:
   $$\Delta T_{\text{dokter}} = \text{Task 5} - \text{Task 4}$$
   *(Target SPM: 10 - 20 menit)*

5. **Waktu Tunggu Farmasi**:
   $$\Delta T_{\text{tunggu farmasi}} = \text{Task 6} - \text{Task 5}$$

6. **Waktu Peracikan & Penyerahan Obat**:
   $$\Delta T_{\text{obat}} = \text{Task 7} - \text{Task 6}$$
   *(Target SPM: Non-Racikan $\le 30$ menit, Racikan $\le 60$ menit)*

7. **Total Durasi Kunjungan Rawat Jalan (Total Lead Time)**:
   $$\text{Total Waktu} = \text{Task 7} - \text{Task 1}$$

---

## 5. Struktur Kolom Database (`antreans`)

Tabel `antreans` menyimpan histori lengkap:
- `kode_booking` (VARCHAR 50)
- `nomorantrean` (VARCHAR 20)
- `kodepoli` & `namapoli`
- `kodedokter` & `namadokter`
- `tanggalperiksa` (DATE)
- `status` ('Menunggu', 'Check-In', 'Dipanggil Poli', 'Selesai Layanan Dokter', 'Selesai')
- `task1_waktu` (BIGINT NULL - ms)
- `task2_waktu` (BIGINT NULL - ms)
- `task3_waktu` (BIGINT NULL - ms)
- `task4_waktu` (BIGINT NULL - ms)
- `task5_waktu` (BIGINT NULL - ms)
- `task6_waktu` (BIGINT NULL - ms)
- `task7_waktu` (BIGINT NULL - ms)

---

## 6. Endpoint API di SIMRS Gateway (`simrs-api`)

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/apm-api/check-in` | Pasien Mobile JKN check-in -> Otomatis mencatat **Task 3**. |
| `POST` | `/api/apm-api/daftar-mandiri` | Pasien go-show -> Mencatat **Task 1, 2, dan 3**. |
| `POST` | `/api/apm-api/update-task` | Mengirim pergerakan **Task 4, 5, 6, atau 7** ke lokal DB dan server BPJS. |
| `GET`  | `/api/apm-api/status-antrean` | Mengambil rekap status antrean hari ini. |
| `GET`  | `/admin/antrol-monitor` | Portal Dashboard visual real-time untuk memantau aliran Task 1 s/d 7 per pasien. |

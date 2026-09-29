# Release Notes: Admin Integration Tester Update

`SIMRS API` tidak lagi hanya berfungsi sebagai backend API untuk integrasi layanan kesehatan. Pada update ini, project berkembang menjadi base project integrasi `BPJS` dan `SATUSEHAT` yang sudah dilengkapi dashboard tester berbasis web.

## Highlight

- menambahkan dashboard admin tester untuk `BPJS`
- menambahkan dashboard admin tester untuk `SATUSEHAT`
- menambahkan mode `Live API` dan `Mock Preview`
- menambahkan login admin untuk akses integration console
- menambahkan validasi request berbasis `Form Request`
- menambahkan integration-style tests untuk endpoint utama
- menambahkan mock response realistis untuk berbagai endpoint `BPJS`
- menambahkan tampilan debug request:
  - URL lokal yang dipanggil
  - URL vendor `BPJS`
  - request dari UI
  - final outbound payload
  - final query params untuk request `GET`
  - header `BPJS` yang aman untuk debugging

## Endpoint BPJS yang Ditambahkan ke Dashboard

- `Cari Peserta`
- `Cari SEP`
- `Riwayat Pelayanan Peserta`
- `Monitoring Kunjungan`
- `Monitoring Klaim`
- `Referensi Poli`
- `Referensi Diagnosa`
- `Referensi Faskes`
- `Referensi Dokter DPJP`
- `Referensi Provinsi`
- `Referensi Kabupaten`
- `Referensi Kecamatan`
- `Referensi Prosedur`
- `List Rencana Kontrol by Tanggal`
- `List Rencana Kontrol by No Kartu`
- `Cari Nomor Surat Kontrol`
- `Cari SEP Surat Kontrol`
- `Insert Surat Kontrol`
- `Update Surat Kontrol`
- `Insert SPRI`
- `Update SPRI`
- `Insert SEP`
- `Update SEP`
- `Hapus SEP`

## SATUSEHAT

Dashboard SATUSEHAT sudah memiliki fondasi awal untuk:

- cek token `SATUSEHAT`
- kirim `Encounter`
- mock preview response `FHIR`
- visualisasi resource `FHIR` seperti `Encounter`, `Patient`, dan `Observation`

## Tujuan Update

Update ini difokuskan untuk membantu:

- programmer IT rumah sakit di Indonesia
- vendor dan developer `SIMRS`
- pembelajar integrasi `BPJS` dan `SATUSEHAT`
- tim yang membutuhkan starter kit integrasi health tech Indonesia

## Disclaimer

Project ini dibuat untuk pembelajaran, pengembangan, dan pengujian integrasi. Penggunaan layanan `BPJS` dan `SATUSEHAT` secara `live` hanya dapat dilakukan oleh fasilitas kesehatan atau institusi yang telah memiliki kerja sama resmi, kredensial aktif, dan akses yang sah.

# AGENTS.md

## Project

Nama proyek:

Sistem Informasi Manajemen Donor dan Persediaan Darah pada satu Unit Donor Darah (UDD)

Proyek ini merupakan proyek mata kuliah Basis Data.

Tujuan utama implementasi adalah membuat aplikasi web Laravel yang dapat mendemonstrasikan:
- implementasi basis data relasional;
- CRUD sesuai kebutuhan masing-masing pengguna;
- relasi antarentitas;
- aturan bisnis utama;
- alur pelayanan donor sampai pengelolaan persediaan darah.

Prioritas proyek adalah ketepatan basis data dan fungsi aplikasi, bukan desain frontend yang kompleks.

---

## Technology Stack

Gunakan:

- PHP 8.3
- Laravel 13
- MySQL 8.4
- Blade
- HTML/CSS
- Bootstrap sederhana jika diperlukan
- Eloquent ORM
- Composer

Jangan menambahkan teknologi frontend atau framework lain tanpa instruksi eksplisit.

Jangan menggunakan:
- React
- Vue
- Livewire
- Inertia
- SPA architecture

Frontend cukup sederhana, konsisten, dan dapat digunakan untuk demonstrasi CRUD.

---

## Source of Truth

Spesifikasi proyek disimpan di folder `docs/`.

Sebelum mengerjakan perubahan yang berkaitan dengan fitur, basis data, hak akses, aturan bisnis, atau keputusan teknis, baca dokumen yang relevan di folder tersebut.

Dokumen utama:

- `docs/01_PROJECT_CONTEXT.md`
- `docs/02_DATABASE_SCHEMA.md`
- `docs/03_ROLE_AND_UI_SCOPE.md`
- `docs/04_BUSINESS_RULES.md`
- `docs/05_IMPLEMENTATION_PLAN.md`
- `docs/06_DEMO_FLOW.md`
- `docs/07_COURSE_SCOPE_AND_REFERENCES.md`

Dokumen `01` sampai `06` menentukan requirement dan perilaku proyek.

Dokumen `07_COURSE_SCOPE_AND_REFERENCES.md` menentukan batas tingkat kompleksitas, keterkaitan dengan materi kuliah, dan prinsip modernisasi implementasi. Dokumen `07` tidak boleh digunakan untuk mengubah requirement yang sudah dikunci pada dokumen `01` sampai `06`.

Dokumen spesifikasi memiliki prioritas lebih tinggi daripada asumsi implementasi.

Jangan mengubah spesifikasi untuk menyesuaikan kode.

Jika kode bertentangan dengan spesifikasi, kode yang harus diperbaiki.

Jika requirement tidak jelas atau terdapat konflik antarspesifikasi:
1. jangan menebak;
2. jangan menambah fitur sendiri;
3. jangan mengubah schema sendiri;
4. berhenti dan laporkan bagian yang membutuhkan keputusan pengguna.

---

## Course Alignment and Modernization

Sebelum memilih pendekatan implementasi, baca `docs/07_COURSE_SCOPE_AND_REFERENCES.md`.

Gunakan prinsip:

- konsep, cara berpikir, dan tingkat kompleksitas tetap dekat dengan materi yang sudah terverifikasi;
- sintaks, API, struktur framework, dan tooling harus mengikuti versi teknologi proyek yang benar-benar digunakan;
- jangan menyalin API atau struktur lama secara literal jika sudah deprecated atau tidak kompatibel;
- gunakan ekuivalen modern yang supported tanpa memperluas scope;
- jangan memperkenalkan arsitektur yang lebih kompleks hanya karena secara teknis memungkinkan;
- jika solusi sederhana dengan Laravel MVC, Eloquent/Query Builder, validation, Blade, dan constraint database sudah cukup, prioritaskan solusi tersebut;
- View, Stored Procedure, Trigger, dan custom index bukan kewajiban; jangan menambahkannya sebagai improvisasi;
- jika sebuah teknik baru akan mengubah schema, business rule, stack utama, atau scope proyek, berhenti dan minta keputusan pengguna terlebih dahulu.

Jangan mengklaim sebuah teknik sebagai bagian materi kuliah jika tidak didukung oleh `docs/07_COURSE_SCOPE_AND_REFERENCES.md` atau sumber baru yang diberikan pengguna.

---

## Database Rules

Basis data utama:

`db_donor_darah_udd`

Database menggunakan MySQL.

Skema basis data final didokumentasikan pada:

`docs/02_DATABASE_SCHEMA.md`

Aturan keras:

- Jangan menambah tabel tanpa instruksi.
- Jangan menghapus tabel tanpa instruksi.
- Jangan menambah field tanpa instruksi.
- Jangan menghapus field tanpa instruksi.
- Jangan mengganti tipe data tanpa instruksi.
- Jangan mengubah primary key, foreign key, UNIQUE constraint, nullable, enum, atau kardinalitas tanpa instruksi.
- Jangan membuat tabel hanya karena sebuah data dapat dihitung dari tabel lain.
- Jangan melakukan denormalisasi tanpa instruksi.
- Jangan menambahkan custom index, View, Stored Procedure, atau Trigger sebagai improvisasi di luar task dan spesifikasi.

Skema final terdiri dari 15 tabel bisnis.

Tidak menggunakan tabel `users` Laravel sebagai pengganti tabel akun proyek.

Autentikasi harus mengikuti tabel `akun` yang terdapat pada skema proyek.

---

## Important Database Design Decisions

Keputusan berikut sudah dikunci:

- Admin bukan entitas/tabel terpisah.
- Admin direpresentasikan melalui `akun.peran = ADMIN`.
- Pendonor memiliki profil pada tabel `pendonor`.
- Petugas memiliki profil pada tabel `petugas`.
- Tidak ada tabel `persediaan`.
- Jumlah persediaan dihitung dari unit komponen darah.
- Tidak ada tabel kelayakan donor terpisah.
- Kelayakan donor ulang berdasarkan riwayat dihitung dari data penyumbangan.
- Kedaluwarsa bukan nilai pada enum `status_unit`.
- Kondisi kedaluwarsa ditentukan dari `tanggal_kedaluwarsa`.
- Check-in bukan tabel terpisah.
- Data check-in disimpan pada pemesanan donor.
- Jenis komponen darah merupakan master data.
- Golongan darah merupakan master data.
- Pemberitahuan hanya merupakan pemberitahuan di dalam aplikasi.

---

## User Roles

Terdapat tiga peran:

### PENDONOR

Pendonor dapat mengakses fungsi yang berkaitan dengan dirinya sendiri, antara lain:
- registrasi dan login;
- profil;
- jadwal donor;
- pemesanan donor;
- kuesioner pradonasi;
- kode check-in;
- riwayat donor;
- informasi donor berikutnya;
- pemberitahuan.

Pendonor tidak boleh melakukan fungsi operasional petugas.

### PETUGAS

Petugas UDD menangani proses operasional, antara lain:
- check-in;
- melihat kuesioner;
- seleksi donor;
- pencatatan penyumbangan;
- pencatatan unit komponen darah;
- pelulusan unit;
- distribusi unit;
- pemantauan persediaan;
- kondisi persediaan rendah;
- identifikasi pendonor;
- pemberitahuan kepada pendonor.

### ADMIN

Admin hanya menangani konfigurasi dan administrasi:
- akun petugas;
- jadwal pelayanan;
- pertanyaan kuesioner;
- ambang batas persediaan.

Admin bukan pengganti petugas operasional.

---

## UI Scope Rule

Gunakan prinsip:

"Semua yang terlihat dan dapat diklik harus berfungsi."

Jangan membuat:
- menu placeholder;
- tombol tanpa aksi;
- halaman kosong untuk fitur masa depan;
- CRUD untuk setiap tabel hanya karena tabel tersebut ada.

Tidak semua tabel basis data membutuhkan menu tersendiri.

UI harus mengikuti kebutuhan pengguna, bukan struktur tabel secara mentah.

---

## Business Scope

Sistem hanya mencakup proses internal satu UDD.

Fitur di luar cakupan:

- data pasien;
- permintaan darah rumah sakit;
- pencocokan donor dengan pasien;
- crossmatch;
- proses transfusi;
- koordinasi antar-UDD;
- proses laboratorium secara rinci;
- metode pemeriksaan IMLTD secara rinci;
- alat dan reagen laboratorium;
- proses teknis pemisahan komponen;
- cold chain;
- pemantauan suhu penyimpanan;
- distribusi secara rinci;
- pembayaran;
- QR code;
- barcode;
- SMS;
- WhatsApp;
- email notification;
- apheresis.

Jangan mengimplementasikan fitur di luar cakupan tanpa instruksi eksplisit.

---

## Development Strategy

Kerjakan proyek secara bertahap.

Jangan mencoba menyelesaikan seluruh aplikasi dalam satu pekerjaan.

Urutan umum:

1. migrations dan constraint;
2. models dan relationships;
3. seeders master data;
4. authentication;
5. role-based access;
6. fitur Admin;
7. fitur Pendonor;
8. fitur Petugas;
9. aturan bisnis turunan;
10. pengujian alur end-to-end;
11. perapihan frontend untuk demonstrasi.

Setiap tahap harus selesai dan diperiksa sebelum berpindah ke tahap berikutnya.

---

## Coding Rules

- Gunakan konvensi Laravel selama tidak bertentangan dengan schema final.
- Gunakan API dan struktur Laravel yang supported pada versi proyek.
- Jaga controller tetap fokus.
- Gunakan Form Request atau validasi Laravel yang wajar jika diperlukan.
- Gunakan Eloquent relationship sesuai foreign key pada schema.
- Gunakan Query Builder atau SQL yang tetap sederhana jika lebih tepat untuk suatu query.
- Hindari query atau logika duplikat jika dapat dibuat sederhana.
- Jangan membuat abstraction yang berlebihan untuk proyek kuliah ini.
- Jangan melakukan redesign besar tanpa kebutuhan.
- Jangan memasang package tambahan tanpa alasan yang jelas dan instruksi pengguna.
- Jika contoh materi menggunakan sintaks/API lama, pertahankan konsepnya tetapi implementasikan dengan ekuivalen modern yang compatible dengan stack proyek.

---

## Testing and Reporting

Setelah mengerjakan suatu task:

1. jalankan pengecekan yang relevan;
2. laporkan file yang dibuat atau diubah;
3. jelaskan secara singkat perubahan yang dilakukan;
4. laporkan command pengujian yang dijalankan;
5. laporkan error atau hal yang belum selesai;
6. jangan diam-diam mengubah requirement untuk membuat test berhasil.

Jika task hanya mencakup satu tahap, jangan mengerjakan tahap berikutnya tanpa diminta.

---

## Current Priority

Saat ini prioritas proyek adalah menghasilkan prototype yang:
- menggunakan Laravel dan MySQL;
- mengikuti ERD final;
- memiliki CRUD yang benar-benar berfungsi;
- menerapkan hak akses tiga peran;
- dapat menjalankan alur donor utama untuk demonstrasi;
- tetap dapat dijelaskan menggunakan konsep yang terverifikasi pada `docs/07_COURSE_SCOPE_AND_REFERENCES.md`.

Frontend tidak perlu kompleks atau sangat menarik secara visual.
Fungsionalitas dan kesesuaian dengan rancangan basis data lebih penting.

# 02 - Database Schema

## Status Dokumen

Dokumen ini merupakan source of truth untuk struktur basis data proyek.

Codex atau developer tidak boleh:

- menambah tabel;
- menghapus tabel;
- menambah field;
- menghapus field;
- mengganti tipe data;
- mengubah nullable;
- mengubah primary key;
- mengubah foreign key;
- mengubah UNIQUE constraint;
- mengubah enum;
- mengubah kardinalitas;

tanpa instruksi eksplisit.

Database:

`db_donor_darah_udd`

DBMS:

`MySQL 8.4`

Jumlah tabel bisnis:

`15`

Jumlah field:

`97`

---

# Aturan Umum Implementasi

1. Gunakan nama tabel dan field persis seperti dokumen ini.
2. Jangan menambahkan kolom `created_at` atau `updated_at` jika tidak tercantum.
3. Jangan menggunakan `$table->timestamps()` secara otomatis.
4. Model Eloquent yang tabelnya tidak memiliki timestamps harus menggunakan `public $timestamps = false;`.
5. Jangan membuat tabel `users`.
6. Autentikasi proyek menggunakan tabel `akun`.
7. Jangan menambahkan soft delete kecuali diperintahkan.
8. Jangan menambahkan cascade delete tanpa instruksi.
9. Jangan menambahkan foreign key atau index yang tidak tercantum tanpa persetujuan.
10. Nullable berarti field diperbolehkan bernilai `NULL`.
11. Tidak ada UNIQUE `(id_pendonor, id_jadwal)` pada `pemesanan_donor`.
12. Kedaluwarsa bukan nilai enum `status_unit`.


---

# Laravel Guardrails

Bagian ini tidak mengubah ERD atau schema bisnis. Bagian ini hanya mencegah Laravel/Codex menambahkan struktur bawaan yang tidak termasuk rancangan proyek.

## 1. Tabel metadata migration Laravel

Laravel boleh membuat tabel metadata:

`migrations`

Tabel `migrations` bukan bagian dari 15 tabel bisnis dan tidak dihitung dalam jumlah field bisnis.

Karena itu, setelah seluruh migration dijalankan, keberadaan `migrations` tidak dianggap sebagai pelanggaran terhadap ERD.

## 2. Jangan membuat tabel bawaan Laravel yang tidak digunakan

Jangan membuat tabel berikut:

- `users`
- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

Konfigurasi aplikasi menggunakan:

- session berbasis file;
- cache berbasis file;
- queue `sync`.

## 3. Implementasi enum pada MySQL

Nama seperti:

- `peran_akun`
- `status_akun_enum`
- `jenis_kelamin_enum`
- `abo_enum`
- `rhesus_enum`
- `status_jadwal_enum`
- `status_pemesanan_enum`
- `jenis_jawaban_enum`
- `keputusan_seleksi_enum`
- `hasil_penyumbangan_enum`
- `status_unit_enum`

merupakan nama enum pada dokumentasi/DBML.

Pada migration MySQL, implementasikan sebagai kolom ENUM dengan nilai yang tercantum pada dokumen ini.

Jangan membuat tabel master baru hanya untuk menggantikan enum tersebut.

Jangan membuat named enum type terpisah seperti pada PostgreSQL.

## 4. Autentikasi tidak boleh mengubah schema akun

Autentikasi harus menggunakan tabel `akun`.

Gunakan field:

- `email`
- `password_hash`
- `peran`
- `status_akun`

Jangan menambahkan field bawaan Laravel berikut ke tabel `akun`:

- `password`
- `remember_token`

Jika mekanisme autentikasi Laravel membutuhkan penyesuaian, sesuaikan model/provider autentikasi terhadap schema proyek, bukan sebaliknya.

Schema bisnis tetap 15 tabel dan 97 field.

---

# ENUM

## peran_akun
- `PENDONOR`
- `PETUGAS`
- `ADMIN`

Digunakan pada `akun.peran`.

## status_akun_enum
- `AKTIF`
- `NONAKTIF`

Digunakan pada `akun.status_akun`. Default `AKTIF`.

## jenis_kelamin_enum
- `LAKI_LAKI`
- `PEREMPUAN`

Digunakan pada `pendonor.jenis_kelamin`.

## abo_enum
- `A`
- `B`
- `AB`
- `O`

Digunakan pada `golongan_darah.abo`.

## rhesus_enum
- `POSITIF`
- `NEGATIF`

Digunakan pada `golongan_darah.rhesus`.

## status_jadwal_enum
- `DIBUKA`
- `DITUTUP`
- `DIBATALKAN`

Digunakan pada `jadwal_pelayanan.status_jadwal`. Default `DIBUKA`.

## status_pemesanan_enum
- `TERJADWAL`
- `CHECK_IN`
- `SELESAI`
- `DIBATALKAN`
- `TIDAK_HADIR`

Digunakan pada `pemesanan_donor.status_pemesanan`. Default `TERJADWAL`.

## jenis_jawaban_enum
- `YA_TIDAK`
- `TEKS`

Digunakan pada `pertanyaan_kuesioner.jenis_jawaban`.

## keputusan_seleksi_enum
- `LAYAK`
- `DITUNDA`
- `DITOLAK`

Digunakan pada `seleksi_donor.keputusan_seleksi`.

## hasil_penyumbangan_enum
- `BERHASIL`
- `GAGAL`

Digunakan pada `penyumbangan.hasil_penyumbangan`.

## status_unit_enum
- `MENUNGGU_PELULUSAN`
- `TERSEDIA`
- `DITOLAK`
- `DIDISTRIBUSIKAN`

Digunakan pada `unit_komponen_darah.status_unit`. Default `MENUNGGU_PELULUSAN`.

PENTING: `KEDALUWARSA` bukan nilai enum. Kondisi kedaluwarsa ditentukan dengan membandingkan `tanggal_kedaluwarsa` dengan tanggal saat ini.

---

# TABEL

## 1. akun

Primary Key: `id_akun`

| Field | Type | Nullable | Constraint |
|---|---|---:|---|
| id_akun | bigint | Tidak | PK, AUTO INCREMENT |
| email | varchar(255) | Tidak | UNIQUE |
| password_hash | varchar(255) | Tidak | - |
| peran | peran_akun | Tidak | - |
| status_akun | status_akun_enum | Tidak | DEFAULT AKTIF |

Catatan:
- Digunakan untuk autentikasi.
- Admin hanya direpresentasikan melalui `peran = ADMIN`.
- Tidak ada tabel profil admin.

## 2. golongan_darah

Primary Key: `id_golongan_darah`

| Field | Type | Nullable | Constraint |
|---|---|---:|---|
| id_golongan_darah | int | Tidak | PK, AUTO INCREMENT |
| abo | abo_enum | Tidak | - |
| rhesus | rhesus_enum | Tidak | - |

Composite UNIQUE: `UNIQUE (abo, rhesus)`.

## 3. pendonor

Primary Key: `id_pendonor`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_pendonor | bigint | Tidak | PK, AUTO INCREMENT |
| id_akun | bigint | Tidak | FK → akun.id_akun, UNIQUE |
| id_golongan_darah | int | Ya | FK → golongan_darah.id_golongan_darah |
| nik | varchar(20) | Tidak | UNIQUE |
| nomor_donor | varchar(50) | Ya | UNIQUE |
| nama_lengkap | varchar(150) | Tidak | - |
| jenis_kelamin | jenis_kelamin_enum | Tidak | - |
| tanggal_lahir | date | Tidak | - |
| tempat_lahir | varchar(100) | Tidak | - |
| alamat | text | Tidak | - |
| nomor_telepon | varchar(20) | Tidak | - |
| pekerjaan | varchar(100) | Ya | - |
| alamat_kantor | text | Ya | - |

Catatan:
- Pendonor baru dapat belum memiliki golongan darah terkonfirmasi.
- Jangan menyimpan umur, tanggal donor terakhir, jumlah donor, atau tanggal donor berikutnya sebagai field tetap.

## 4. petugas

Primary Key: `id_petugas`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_petugas | bigint | Tidak | PK, AUTO INCREMENT |
| id_akun | bigint | Tidak | FK → akun.id_akun, UNIQUE |
| nomor_petugas | varchar(50) | Tidak | UNIQUE |
| nama_petugas | varchar(150) | Tidak | - |

Status aktif/nonaktif petugas menggunakan `akun.status_akun`. Tidak ada field `status_petugas`.

## 5. jadwal_pelayanan

Primary Key: `id_jadwal`

| Field | Type | Nullable | Constraint |
|---|---|---:|---|
| id_jadwal | bigint | Tidak | PK, AUTO INCREMENT |
| tanggal | date | Tidak | - |
| jam_mulai | time | Tidak | - |
| jam_selesai | time | Tidak | - |
| kapasitas | int | Tidak | - |
| status_jadwal | status_jadwal_enum | Tidak | DEFAULT DIBUKA |

Jumlah pemesanan dan sisa kapasitas tidak disimpan sebagai field tetap.

## 6. pemesanan_donor

Primary Key: `id_pemesanan`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_pemesanan | bigint | Tidak | PK, AUTO INCREMENT |
| id_pendonor | bigint | Tidak | FK → pendonor.id_pendonor |
| id_jadwal | bigint | Tidak | FK → jadwal_pelayanan.id_jadwal |
| waktu_pemesanan | datetime | Tidak | - |
| kode_checkin | varchar(50) | Ya | UNIQUE |
| waktu_checkin | datetime | Ya | - |
| status_pemesanan | status_pemesanan_enum | Tidak | DEFAULT TERJADWAL |

PENTING:
- Tidak terdapat UNIQUE constraint `(id_pendonor, id_jadwal)`.
- Pencegahan pemesanan aktif ganda dilakukan melalui aturan aplikasi.
- Check-in bukan tabel tersendiri.

## 7. kuesioner_pradonasi

Primary Key: `id_kuesioner`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_kuesioner | bigint | Tidak | PK, AUTO INCREMENT |
| id_pemesanan | bigint | Tidak | FK → pemesanan_donor.id_pemesanan, UNIQUE |
| waktu_pengisian | datetime | Tidak | - |

Satu pemesanan maksimal memiliki satu kuesioner.

## 8. pertanyaan_kuesioner

Primary Key: `id_pertanyaan`

| Field | Type | Nullable | Constraint |
|---|---|---:|---|
| id_pertanyaan | bigint | Tidak | PK, AUTO INCREMENT |
| teks_pertanyaan | text | Tidak | - |
| kategori | varchar(100) | Ya | - |
| jenis_jawaban | jenis_jawaban_enum | Tidak | - |
| urutan | int | Tidak | - |
| status_aktif | boolean | Tidak | DEFAULT TRUE |

## 9. jawaban_kuesioner

Primary Key: `id_jawaban`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_jawaban | bigint | Tidak | PK, AUTO INCREMENT |
| id_kuesioner | bigint | Tidak | FK → kuesioner_pradonasi.id_kuesioner |
| id_pertanyaan | bigint | Tidak | FK → pertanyaan_kuesioner.id_pertanyaan |
| jawaban | text | Tidak | - |

Composite UNIQUE: `UNIQUE (id_kuesioner, id_pertanyaan)`.

## 10. seleksi_donor

Primary Key: `id_seleksi`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_seleksi | bigint | Tidak | PK, AUTO INCREMENT |
| id_pemesanan | bigint | Tidak | FK → pemesanan_donor.id_pemesanan, UNIQUE |
| id_petugas | bigint | Tidak | FK → petugas.id_petugas |
| waktu_seleksi | datetime | Tidak | - |
| berat_badan | decimal(5,2) | Tidak | - |
| tekanan_sistolik | smallint | Tidak | - |
| tekanan_diastolik | smallint | Tidak | - |
| denyut_nadi | smallint | Tidak | - |
| suhu_tubuh | decimal(4,1) | Tidak | - |
| kadar_hb | decimal(4,1) | Tidak | - |
| hasil_pemeriksaan_kesehatan | text | Ya | - |
| keputusan_seleksi | keputusan_seleksi_enum | Tidak | - |
| alasan_keputusan | text | Ya | - |

Hanya keputusan `LAYAK` yang dapat dilanjutkan ke penyumbangan. Interval donor dihitung dari riwayat.

## 11. penyumbangan

Primary Key: `id_penyumbangan`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_penyumbangan | bigint | Tidak | PK, AUTO INCREMENT |
| id_seleksi | bigint | Tidak | FK → seleksi_donor.id_seleksi, UNIQUE |
| id_petugas_pencatat | bigint | Tidak | FK → petugas.id_petugas |
| waktu_pengambilan | datetime | Tidak | - |
| volume_ml | smallint | Ya | - |
| hasil_penyumbangan | hasil_penyumbangan_enum | Tidak | - |
| alasan_gagal | text | Ya | - |

Catatan:
- Tidak menyimpan `id_pendonor`.
- Penyumbangan `BERHASIL` menjadi bagian riwayat donor ulang.
- Penyumbangan `GAGAL` tidak diperlakukan sebagai donor berhasil.
- Unit komponen hanya dibuat dari penyumbangan berhasil.

## 12. jenis_komponen_darah

Primary Key: `id_jenis_komponen`

| Field | Type | Nullable | Constraint |
|---|---|---:|---|
| id_jenis_komponen | int | Tidak | PK, AUTO INCREMENT |
| kode_komponen | varchar(10) | Tidak | UNIQUE |
| nama_komponen | varchar(100) | Tidak | - |

Master data awal:
- WB — Whole Blood
- PRC — Packed Red Cell
- TC — Thrombocyte Concentrate
- FFP — Fresh Frozen Plasma

## 13. unit_komponen_darah

Primary Key: `id_unit`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_unit | bigint | Tidak | PK, AUTO INCREMENT |
| nomor_unit | varchar(50) | Tidak | UNIQUE |
| id_penyumbangan | bigint | Tidak | FK → penyumbangan.id_penyumbangan |
| id_jenis_komponen | int | Tidak | FK → jenis_komponen_darah.id_jenis_komponen |
| id_golongan_darah | int | Tidak | FK → golongan_darah.id_golongan_darah |
| id_petugas_pencatat | bigint | Tidak | FK → petugas.id_petugas |
| id_petugas_pelulus | bigint | Ya | FK → petugas.id_petugas |
| tanggal_pembuatan | date | Tidak | - |
| tanggal_kedaluwarsa | date | Tidak | - |
| waktu_pelulusan | datetime | Ya | - |
| status_unit | status_unit_enum | Tidak | DEFAULT MENUNGGU_PELULUSAN |
| catatan_pelulusan | text | Ya | - |
| waktu_distribusi | datetime | Ya | - |

Status unit hanya `MENUNGGU_PELULUSAN`, `TERSEDIA`, `DITOLAK`, `DIDISTRIBUSIKAN`.

Tidak ada status `KEDALUWARSA`.

Unit dihitung sebagai persediaan hanya jika `status_unit = TERSEDIA` dan `tanggal_kedaluwarsa >= tanggal saat ini`.

## 14. ambang_persediaan

Primary Key: `id_ambang`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_ambang | bigint | Tidak | PK, AUTO INCREMENT |
| id_jenis_komponen | int | Tidak | FK → jenis_komponen_darah.id_jenis_komponen |
| id_golongan_darah | int | Tidak | FK → golongan_darah.id_golongan_darah |
| jumlah_minimum | int | Tidak | - |

Composite UNIQUE: `UNIQUE (id_jenis_komponen, id_golongan_darah)`.

## 15. pemberitahuan

Primary Key: `id_pemberitahuan`

| Field | Type | Nullable | Constraint / Reference |
|---|---|---:|---|
| id_pemberitahuan | bigint | Tidak | PK, AUTO INCREMENT |
| id_pendonor | bigint | Tidak | FK → pendonor.id_pendonor |
| id_petugas_pengirim | bigint | Tidak | FK → petugas.id_petugas |
| isi_pesan | text | Tidak | - |
| waktu_dibuat | datetime | Tidak | - |
| waktu_dibaca | datetime | Ya | - |

Pemberitahuan hanya berada di dalam aplikasi.

---

# RELATIONSHIPS

Total relationship: `21`

1. `akun 1 —— 0..1 pendonor`
2. `akun 1 —— 0..1 petugas`
3. `golongan_darah 1 —— 0..N pendonor`, dari sisi pendonor `0..1 golongan_darah`
4. `pendonor 1 —— 0..N pemesanan_donor`
5. `jadwal_pelayanan 1 —— 0..N pemesanan_donor`
6. `pemesanan_donor 1 —— 0..1 kuesioner_pradonasi`
7. Secara bisnis `kuesioner_pradonasi 1 —— 1..N jawaban_kuesioner`
8. `pertanyaan_kuesioner 1 —— 0..N jawaban_kuesioner`
9. `pemesanan_donor 1 —— 0..1 seleksi_donor`
10. `petugas 1 —— 0..N seleksi_donor`
11. `seleksi_donor 1 —— 0..1 penyumbangan`
12. `petugas 1 —— 0..N penyumbangan`
13. `penyumbangan 1 —— 0..N unit_komponen_darah`
14. `jenis_komponen_darah 1 —— 0..N unit_komponen_darah`
15. `golongan_darah 1 —— 0..N unit_komponen_darah`
16. `petugas 1 —— 0..N unit_komponen_darah` sebagai pencatat
17. `petugas 1 —— 0..N unit_komponen_darah`, dari sisi unit `0..1 petugas pelulus`
18. `jenis_komponen_darah 1 —— 0..N ambang_persediaan`
19. `golongan_darah 1 —— 0..N ambang_persediaan`
20. `pendonor 1 —— 0..N pemberitahuan`
21. `petugas 1 —— 0..N pemberitahuan`

---

# Composite UNIQUE Constraints

- `golongan_darah`: `UNIQUE (abo, rhesus)`
- `jawaban_kuesioner`: `UNIQUE (id_kuesioner, id_pertanyaan)`
- `ambang_persediaan`: `UNIQUE (id_jenis_komponen, id_golongan_darah)`

# Single-Column UNIQUE Constraints

- `akun.email`
- `pendonor.id_akun`
- `pendonor.nik`
- `pendonor.nomor_donor`
- `petugas.id_akun`
- `petugas.nomor_petugas`
- `pemesanan_donor.kode_checkin`
- `kuesioner_pradonasi.id_pemesanan`
- `seleksi_donor.id_pemesanan`
- `penyumbangan.id_seleksi`
- `jenis_komponen_darah.kode_komponen`
- `unit_komponen_darah.nomor_unit`

Field UNIQUE yang nullable tetap boleh `NULL`, termasuk `pendonor.nomor_donor` dan `pemesanan_donor.kode_checkin`.

---

# Derived Data - Jangan Disimpan Sebagai Field Baru

- umur pendonor;
- tanggal donor terakhir;
- jumlah donor pendonor;
- tanggal donor berikutnya;
- interval donor;
- jumlah pemesanan pada jadwal;
- sisa kapasitas jadwal;
- jumlah persediaan;
- status persediaan rendah;
- status kedaluwarsa unit.

# Tables That Do Not Exist

Jangan membuat:
- `admin`
- `persediaan`
- `kelayakan_donor`
- `checkin`
- `distribusi`
- `rumah_sakit`
- `pasien`
- `permintaan_darah`
- `crossmatch`
- `transfusi`

---

# Laravel Implementation Notes

Semua model menggunakan nama tabel sesuai schema.

Primary key tidak selalu menggunakan nama `id`, sehingga setiap model harus mengatur `$primaryKey` sesuai tabelnya.

Karena schema tidak memiliki `created_at` dan `updated_at`, model harus menggunakan `public $timestamps = false;`.

Autentikasi harus menggunakan model yang terhubung ke tabel `akun`.

Jangan membuat atau menggunakan tabel `users`.

---

# Final Validation Checklist

Sebelum migration dianggap selesai, pastikan:

- jumlah tabel bisnis = 15;
- jumlah field = 97;
- seluruh nama tabel sesuai;
- seluruh nama field sesuai;
- PK sesuai;
- FK sesuai;
- UNIQUE sesuai;
- nullable sesuai;
- enum sesuai;
- default value sesuai;
- tidak ada `created_at`;
- tidak ada `updated_at`;
- tidak ada tabel `users`;
- tidak ada tabel `persediaan`;
- tidak ada status `KEDALUWARSA`;
- tidak ada field turunan tambahan;
- tidak ada cascade delete yang ditambahkan tanpa instruksi.

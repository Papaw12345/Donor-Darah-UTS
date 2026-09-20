# 05 - Implementation Plan

## Status Dokumen

Dokumen ini merupakan rencana implementasi teknis proyek.

Dokumen ini tidak boleh digunakan untuk mengubah:

- struktur basis data pada `02_DATABASE_SCHEMA.md`;
- pembagian peran dan UI pada `03_ROLE_AND_UI_SCOPE.md`;
- aturan bisnis pada `04_BUSINESS_RULES.md`.

Jika rencana implementasi bertentangan dengan ketiga dokumen tersebut, dokumen spesifikasi yang lebih dahulu menjadi acuan dan implementasi harus disesuaikan.

Tujuan dokumen ini adalah membuat pengerjaan Laravel dilakukan bertahap, dapat diuji, dan tidak melebar dari scope proyek.

---

# A. Prinsip Implementasi

1. Gunakan Laravel 13, PHP 8.3, MySQL 8.4, Blade, HTML/CSS, dan Eloquent ORM.
2. Bootstrap sederhana boleh digunakan jika diperlukan untuk mempercepat pembuatan UI.
3. Jangan menggunakan React, Vue, Livewire, Inertia, atau SPA.
4. Jangan memasang package tambahan tanpa kebutuhan yang jelas dan persetujuan.
5. Jangan mengubah schema hanya untuk mengikuti default Laravel.
6. Jangan membuat fitur di luar scope.
7. Jangan membuat seluruh aplikasi dalam satu task Codex.
8. Selesaikan satu tahap, jalankan pengecekan, laporkan hasil, lalu berhenti.
9. Semua menu dan tombol yang terlihat harus benar-benar berfungsi.
10. Prioritas utama adalah ketepatan basis data, CRUD, relasi, validasi, hak akses, dan alur end-to-end.

---

# B. Kondisi Awal Proyek

Kondisi awal yang sudah disiapkan secara manual:

- Laravel 13 sudah terpasang.
- PHP 8.3 tersedia.
- Composer tersedia.
- MySQL 8.4 tersedia melalui Laragon.
- Basis data `db_donor_darah_udd` sudah dibuat.
- `.env` sudah diarahkan ke MySQL.
- session menggunakan file.
- cache menggunakan file.
- queue menggunakan `sync`.
- default migration Laravel untuk `users`, cache, dan jobs sudah dihapus.
- file SQLite bawaan sudah dihapus.
- folder `database/migrations` siap digunakan untuk migration proyek.
- `AGENTS.md` sudah tersedia.
- folder `docs/` berisi spesifikasi proyek.

Jangan mengulangi instalasi Laravel atau membuat project baru.

Jangan mengaktifkan kembali migration bawaan yang sudah dihapus.

---

# C. Urutan Implementasi

Urutan utama:

1. Migrations dan constraints.
2. Models dan relationships.
3. Seeders master data.
4. Authentication.
5. Role-based access.
6. Fitur Admin.
7. Fitur Pendonor.
8. Fitur Petugas UDD.
9. Aturan bisnis turunan dan integrasi antarmodul.
10. Pengujian end-to-end.
11. Perapihan frontend untuk demonstrasi.

Setiap tahap memiliki gate. Jangan lanjut ke tahap berikutnya sebelum tahap saat ini lolos pemeriksaan.

---

# PHASE 1 - MIGRATIONS DAN CONSTRAINTS

## Tujuan

Membuat struktur MySQL yang sama dengan ERD final.

## Pekerjaan

Buat migration untuk tepat 15 tabel bisnis:

1. `akun`
2. `golongan_darah`
3. `pendonor`
4. `petugas`
5. `jadwal_pelayanan`
6. `pemesanan_donor`
7. `kuesioner_pradonasi`
8. `pertanyaan_kuesioner`
9. `jawaban_kuesioner`
10. `seleksi_donor`
11. `penyumbangan`
12. `jenis_komponen_darah`
13. `unit_komponen_darah`
14. `ambang_persediaan`
15. `pemberitahuan`

Gunakan `02_DATABASE_SCHEMA.md` sebagai satu-satunya acuan nama tabel, field, tipe data, nullable, default, PK, FK, UNIQUE, dan enum.

## Aturan

- Jangan menambahkan `created_at`.
- Jangan menambahkan `updated_at`.
- Jangan menggunakan `$table->timestamps()`.
- Jangan menambahkan soft delete.
- Jangan menambahkan `remember_token`.
- Jangan membuat tabel `users`.
- Jangan membuat tabel `sessions`, `cache`, atau `jobs`.
- Tabel metadata Laravel `migrations` boleh ada.
- Jangan menambahkan cascade delete tanpa instruksi.
- Jangan menambahkan field turunan.

## Gate Phase 1

Sebelum selesai:

- jalankan migration pada `db_donor_darah_udd`;
- pastikan seluruh migration berhasil;
- pastikan ada tepat 15 tabel bisnis;
- pastikan 97 field bisnis sesuai schema;
- periksa seluruh PK;
- periksa seluruh FK;
- periksa seluruh UNIQUE;
- periksa nullable;
- periksa enum dan default;
- pastikan tidak ada tabel bisnis tambahan.

Jika migration gagal, perbaiki migration. Jangan mengubah schema acuan.

---

# PHASE 2 - MODELS DAN RELATIONSHIPS

## Tujuan

Membuat Eloquent model yang merepresentasikan 15 tabel dan relasinya.

## Pekerjaan

Buat model untuk setiap tabel bisnis.

Setiap model harus:

- menggunakan nama tabel yang benar;
- menentukan `$primaryKey` yang benar;
- menggunakan `public $timestamps = false;`;
- mendefinisikan relationship sesuai 21 relationship final;
- menggunakan `$fillable` atau strategi mass-assignment yang aman dan sederhana.

## Aturan Penting

Model `Akun` nantinya digunakan untuk autentikasi dan harus dapat dikembangkan sebagai model authenticatable tanpa mengubah schema tabel `akun`.

Relationship yang memiliki lebih dari satu FK menuju model yang sama harus diberi nama yang jelas.

Contoh pada `unit_komponen_darah`:

- Petugas pencatat;
- Petugas pelulus.

Jangan mengubah kardinalitas untuk mempermudah Eloquent.

## Gate Phase 2

- semua model dapat dimuat;
- relationship dasar dapat dipanggil tanpa error;
- nama FK eksplisit jika tidak mengikuti konvensi Laravel;
- tidak ada model yang mengasumsikan PK bernama `id`;
- tidak ada model yang mengasumsikan timestamps.

---

# PHASE 3 - SEEDERS MASTER DATA

## Tujuan

Menyiapkan data master minimum agar aplikasi dapat digunakan.

## Seeder Wajib

### Golongan Darah

Siapkan 8 kombinasi:

- A POSITIF
- A NEGATIF
- B POSITIF
- B NEGATIF
- AB POSITIF
- AB NEGATIF
- O POSITIF
- O NEGATIF

### Jenis Komponen Darah

Siapkan:

- `WB` - Whole Blood
- `PRC` - Packed Red Cell
- `TC` - Thrombocyte Concentrate
- `FFP` - Fresh Frozen Plasma

## Data Pendukung Demo

Data tambahan untuk demonstrasi boleh dibuat melalui seeder terpisah setelah data master selesai, tetapi harus:

- mengikuti schema;
- mengikuti role;
- mengikuti aturan bisnis;
- mudah dibedakan dari master data.

Akun Admin awal untuk kebutuhan login lokal/demo dapat disiapkan pada tahap implementasi menggunakan seeder terpisah. Akun tersebut tetap hanya berupa record pada tabel `akun` dengan `peran = ADMIN`; tidak boleh membuat tabel profil Admin.

## Gate Phase 3

- seeder dapat dijalankan berulang dengan aman sesuai desain yang dipilih;
- 8 kombinasi golongan darah tersedia;
- 4 jenis komponen tersedia;
- tidak ada menu CRUD untuk `jenis_komponen_darah`;
- tidak ada tabel master baru.

---

# PHASE 4 - AUTHENTICATION

## Tujuan

Membuat login/logout dan registrasi Pendonor menggunakan schema `akun`.

## Pendekatan

Gunakan autentikasi session Laravel secara sederhana dengan Blade.

Jangan memasang starter kit yang menambahkan frontend stack atau schema yang tidak diperlukan.

Model `Akun` harus dapat digunakan oleh sistem autentikasi Laravel.

Password tetap disimpan pada:

`akun.password_hash`

Jangan menambahkan:

- `password`;
- `remember_token`.

Jika Laravel memerlukan penyesuaian untuk membaca hash password, sesuaikan model/authentication layer terhadap `password_hash`.

## Fitur

### Login

- email;
- password;
- cek akun ada;
- cek password;
- cek `status_akun = AKTIF`;
- login session;
- redirect berdasarkan `peran`.

### Logout

Mengakhiri session pengguna.

### Registrasi Pendonor

Registrasi publik hanya untuk Pendonor.

Registrasi membuat:

- satu record `akun` dengan `peran = PENDONOR`;
- satu record `pendonor` yang terhubung ke akun tersebut.

Gunakan transaction agar akun dan profil tidak terbentuk setengah jadi.

Petugas dan Admin tidak boleh self-register melalui halaman publik.

## Gate Phase 4

Uji:

- Pendonor baru dapat registrasi;
- Pendonor dapat login;
- Petugas aktif dapat login jika akun sudah tersedia;
- Admin aktif dapat login jika akun sudah tersedia;
- akun NONAKTIF ditolak;
- password salah ditolak;
- logout bekerja;
- role redirect bekerja;
- tidak ada tabel auth tambahan yang melanggar schema.

---

# PHASE 5 - ROLE-BASED ACCESS

## Tujuan

Menerapkan pembatasan PENDONOR, PETUGAS, dan ADMIN pada route dan controller.

## Pekerjaan

Buat mekanisme middleware/otorisasi yang sederhana untuk:

- autentikasi;
- status akun aktif;
- role.

UI boleh menyembunyikan menu yang tidak relevan, tetapi keamanan tidak boleh hanya bergantung pada menu.

## Aturan

- Pendonor hanya mengakses data miliknya sendiri.
- Petugas mengakses fungsi operasional.
- Admin mengakses fungsi administrasi dan konfigurasi.
- akses role yang salah harus ditolak meskipun URL diketik manual.

## Gate Phase 5

Uji minimal:

- Pendonor tidak dapat membuka route Admin;
- Pendonor tidak dapat membuka route Petugas;
- Petugas tidak dapat membuka route Admin;
- Admin tidak dapat menjalankan route operasional Petugas;
- Pendonor tidak dapat mengakses resource Pendonor lain.

---

# PHASE 6 - FITUR ADMIN

## Tujuan

Menyelesaikan konfigurasi yang diperlukan sebelum alur donor berjalan.

## Menu yang Dikerjakan

1. Dashboard Admin.
2. Kelola Petugas.
3. Jadwal Pelayanan.
4. Pertanyaan Kuesioner.
5. Ambang Persediaan.

## Kelola Petugas

Implementasikan fungsi yang diperlukan untuk:

- membuat akun dan profil Petugas;
- melihat daftar Petugas;
- memperbarui data Petugas;
- mengaktifkan/menonaktifkan akun melalui `akun.status_akun`.

Gunakan transaction ketika perubahan melibatkan `akun` dan `petugas`.

Jangan membuat field `status_petugas`.

## Jadwal Pelayanan

CRUD sesuai field final.

Kapasitas tersisa dihitung dari pemesanan, bukan disimpan sebagai field tambahan.

## Pertanyaan Kuesioner

Kelola:

- teks;
- kategori;
- jenis jawaban;
- urutan;
- status aktif.

Gunakan status aktif/nonaktif untuk menjaga riwayat.

## Ambang Persediaan

Kelola kombinasi:

- jenis komponen;
- golongan darah;
- jumlah minimum.

Hormati UNIQUE kombinasi jenis komponen dan golongan darah.

## Gate Phase 6

- semua menu Admin berfungsi;
- tombol aksi benar-benar bekerja;
- validasi input berjalan;
- constraint database tetap menjadi lapisan pengaman;
- Admin tidak memperoleh menu operasional Petugas.

---

# PHASE 7 - FITUR PENDONOR

## Tujuan

Membuat alur Pendonor sampai siap datang ke UDD.

## Urutan Implementasi

1. Dashboard Pendonor.
2. Profil Saya.
3. Jadwal Donor.
4. Pemesanan Donor.
5. Kuesioner Pradonasi.
6. Kode Check-in.
7. Riwayat Donor.
8. Informasi Donor Berikutnya.
9. Pemberitahuan.

## Pemesanan

Sebelum pemesanan baru, sistem harus memeriksa aturan yang relevan:

- riwayat donor ulang;
- jadwal `DIBUKA`;
- kapasitas masih tersedia;
- pencegahan pemesanan aktif ganda sesuai aturan aplikasi.

Tidak ada UNIQUE `(id_pendonor, id_jadwal)` pada database.

## Kuesioner

Kuesioner dibuat untuk pemesanan milik Pendonor tersebut.

Tampilkan pertanyaan yang aktif.

Simpan jawaban melalui `jawaban_kuesioner`.

Satu pertanyaan hanya boleh memiliki satu jawaban dalam satu kuesioner.

Jangan membuat transaksi review kuesioner terpisah.

## Kode Check-in

Kode check-in:

- unik;
- terkait `pemesanan_donor`;
- dibuat setelah proses pradonasi yang diperlukan selesai;
- ditampilkan sebagai teks biasa.

Jangan membuat QR code atau barcode.

## Riwayat dan Donor Berikutnya

Gunakan penyumbangan `BERHASIL` untuk perhitungan donor ulang.

Jangan menyimpan:

- tanggal donor terakhir;
- jumlah donor;
- tanggal donor berikutnya

sebagai kolom baru.

## Gate Phase 7

Uji minimal:

- Pendonor hanya melihat data sendiri;
- jadwal tertutup/dibatalkan tidak dapat dipilih sebagai pemesanan baru;
- kapasitas tidak dapat dilampaui;
- aturan donor ulang diterapkan;
- kuesioner tersimpan;
- kode check-in unik terbentuk;
- riwayat berasal dari transaksi nyata;
- donor berikutnya dihitung, bukan disimpan;
- pemberitahuan milik Pendonor dapat dibaca.

---

# PHASE 8 - FITUR PETUGAS UDD

## Tujuan

Menyelesaikan proses operasional dari kedatangan Pendonor sampai pengelolaan unit darah.

## Urutan Implementasi

1. Dashboard Petugas.
2. Check-in Pendonor.
3. Kuesioner Pradonasi - lihat.
4. Seleksi Donor.
5. Penyumbangan.
6. Unit Komponen Darah.
7. Pelulusan Unit.
8. Distribusi Unit.
9. Persediaan.
10. Persediaan Rendah.
11. Pemanggilan Pendonor.
12. Pemberitahuan.

## Check-in

Petugas memasukkan `kode_checkin`.

Sistem harus membuka pemesanan yang sesuai dan data terkait.

Catat:

- `waktu_checkin`;
- status pemesanan sesuai proses.

Tidak ada tabel check-in baru.

## Seleksi

Petugas mencatat field pada `seleksi_donor`.

Keputusan hanya:

- `LAYAK`
- `DITUNDA`
- `DITOLAK`

Untuk Pendonor baru, Petugas dapat mencatat golongan darah yang telah terkonfirmasi jika sebelumnya masih NULL.

## Penyumbangan

Hanya seleksi `LAYAK` yang dapat menghasilkan penyumbangan.

Hasil:

- `BERHASIL`
- `GAGAL`

Terapkan aturan volume Whole Blood dan berat badan yang sudah dikunci pada `04_BUSINESS_RULES.md`.

## Unit Komponen

Unit hanya dapat dibuat dari penyumbangan berhasil.

Unit baru memiliki status:

`MENUNGGU_PELULUSAN`

## Pelulusan

Unit menunggu pelulusan dapat diproses menjadi:

- `TERSEDIA`
- `DITOLAK`

Catat:

- Petugas pelulus;
- waktu pelulusan;
- catatan pelulusan jika diperlukan.

## Distribusi

Hanya unit yang sesuai aturan proses yang boleh dicatat keluar dari persediaan.

Saat distribusi:

- status menjadi `DIDISTRIBUSIKAN`;
- `waktu_distribusi` dicatat.

Jangan membuat tabel distribusi baru.

## Persediaan

Persediaan tidak memiliki CRUD angka stok.

Hitung berdasarkan unit:

- `status_unit = TERSEDIA`;
- belum kedaluwarsa.

Kelompokkan berdasarkan:

- jenis komponen;
- golongan darah.

## Persediaan Rendah dan Pemanggilan

Bandingkan jumlah tersedia dengan `ambang_persediaan`.

Kondisi rendah:

`jumlah_persediaan <= jumlah_minimum`

Untuk kondisi rendah, tampilkan Pendonor:

- golongan darah relevan;
- berdasarkan riwayat telah memenuhi aturan donor ulang.

Petugas memilih Pendonor yang akan dikirimi pemberitahuan.

Fitur ini bukan clinical matching.

## Gate Phase 8

Uji alur:

- kode check-in ditemukan;
- seleksi tersimpan;
- DITUNDA/DITOLAK tidak dapat dilanjutkan ke penyumbangan;
- LAYAK dapat dilanjutkan;
- penyumbangan GAGAL tidak menghasilkan unit;
- penyumbangan BERHASIL dapat menghasilkan unit;
- status unit berjalan sesuai alur;
- persediaan berubah karena status unit, bukan edit angka stok;
- Petugas dapat membuat pemberitahuan untuk Pendonor yang dipilih.

---

# PHASE 9 - ATURAN BISNIS TURUNAN DAN INTEGRASI

## Tujuan

Menyatukan perhitungan dan validasi lintas fitur agar tidak tersebar secara tidak konsisten.

## Aturan yang Harus Diverifikasi

### Donor Ulang

- interval minimal Whole Blood = 2 bulan;
- laki-laki maksimal 6 kali per tahun;
- perempuan maksimal 4 kali per tahun;
- hanya penyumbangan `BERHASIL` yang dihitung;
- historical eligibility bukan kelayakan medis akhir.

### Volume

- 350 mL membutuhkan berat minimal 45 kg;
- 450 mL membutuhkan berat minimal 55 kg.

### Jadwal

- kapasitas dihitung dari pemesanan yang berlaku;
- jangan membuat field booked count atau sisa kapasitas.

### Persediaan

- hanya `TERSEDIA`;
- belum kedaluwarsa;
- dihitung per jenis komponen + golongan darah;
- kondisi rendah jika jumlah <= ambang.

### Kedaluwarsa

Kedaluwarsa harus tetap derived dari `tanggal_kedaluwarsa`.

Jangan membuat:

- status `KEDALUWARSA`;
- cron yang mengubah status menjadi `KEDALUWARSA`;
- field flag kedaluwarsa.

### Pemberitahuan

Pemberitahuan hanya in-app.

Jangan menambahkan integrasi SMS, WhatsApp, atau email.

## Struktur Kode

Jika perhitungan yang sama digunakan di beberapa controller, buat logika terpusat secara sederhana, misalnya service/helper/query scope yang jelas.

Jangan membuat architecture berlebihan.

## Gate Phase 9

Bandingkan implementasi dengan seluruh aturan pada `04_BUSINESS_RULES.md`.

Tidak boleh ada aturan bisnis yang diam-diam berbeda antarhalaman.

---

# PHASE 10 - END-TO-END TESTING

## Tujuan

Membuktikan proses utama benar-benar berjalan dari awal sampai akhir.

## Demo Flow Utama

1. Admin login.
2. Admin membuat jadwal pelayanan.
3. Admin memastikan pertanyaan kuesioner tersedia.
4. Admin memastikan ambang persediaan tersedia.
5. Pendonor melakukan registrasi.
6. Pendonor login.
7. Pendonor melihat jadwal.
8. Pendonor membuat pemesanan.
9. Pendonor mengisi kuesioner.
10. Sistem menghasilkan kode check-in.
11. Petugas login.
12. Petugas melakukan check-in menggunakan kode.
13. Petugas melihat kuesioner.
14. Petugas mencatat seleksi `LAYAK`.
15. Petugas mencatat penyumbangan `BERHASIL`.
16. Petugas membuat unit komponen darah.
17. Unit berada pada `MENUNGGU_PELULUSAN`.
18. Petugas meluluskan unit menjadi `TERSEDIA`.
19. Unit muncul dalam perhitungan persediaan.
20. Jika jumlah berada pada/bawah ambang, kondisi persediaan rendah tampil.
21. Petugas dapat memilih Pendonor yang relevan dan membuat pemberitahuan.
22. Pendonor dapat melihat pemberitahuan tersebut.

## Skenario Negatif Minimum

Uji juga:

- email duplikat;
- NIK duplikat;
- Petugas NONAKTIF login;
- role mengakses route yang salah;
- Pendonor mengakses data Pendonor lain;
- jadwal penuh;
- jadwal tidak dibuka;
- pemesanan aktif ganda;
- donor ulang belum memenuhi interval;
- donor ulang melewati batas frekuensi tahunan;
- kuesioner kedua pada pemesanan yang sama;
- seleksi kedua pada pemesanan yang sama;
- penyumbangan dari seleksi bukan LAYAK;
- volume tidak sesuai berat badan;
- unit dari penyumbangan GAGAL;
- distribusi unit yang tidak TERSEDIA;
- unit kedaluwarsa tidak dihitung sebagai persediaan;
- duplicate ambang untuk kombinasi yang sama.

## Pengecekan Basis Data

Setelah flow berjalan, cek langsung database untuk memastikan:

- FK benar;
- status yang tersimpan benar;
- tidak ada field turunan baru;
- tidak ada row yatim yang seharusnya tidak terjadi;
- data dapat ditelusuri dari Pendonor sampai Unit Komponen.

---

# PHASE 11 - PERAPIHAN FRONTEND

## Tujuan

Membuat prototype mudah didemonstrasikan tanpa mengubah fokus proyek menjadi desain UI.

## Prinsip

Gunakan tampilan sederhana dan konsisten:

- navbar/sidebar sesuai peran;
- tabel data;
- form;
- tombol aksi;
- badge status;
- flash message sukses/gagal;
- validasi error yang terbaca;
- konfirmasi untuk aksi yang berdampak besar jika diperlukan.

Tidak perlu:

- animasi kompleks;
- dashboard chart yang tidak diperlukan;
- SPA;
- desain visual berlebihan;
- fitur kosmetik yang tidak membantu demo.

## Gate Phase 11

Pastikan:

- tidak ada link mati;
- tidak ada tombol placeholder;
- tidak ada menu kosong;
- status mudah dibaca;
- alur demo dapat dijalankan tanpa mengetik URL manual.

## Finalisasi Phase 11

Refinement final mencatat state aplikasi berikut tanpa mengubah schema atau menambah subsystem baru:

- Petugas mempunyai halaman Jadwal Pelayanan read-only; CRUD jadwal tetap milik Admin.
- Petugas mempunyai Riwayat Pelayanan Donor read-only yang diturunkan dari seleksi dan penyumbangan existing, tanpa tabel history baru.
- Navigasi top-level Petugas adalah Dashboard, Jadwal, Check-in, Riwayat, Pelulusan, Distribusi, Persediaan, Pemanggilan, dan Logout. Unit Komponen tetap bagian subflow penyumbangan, sedangkan Persediaan Rendah bukan menu top-level tersendiri.
- Dashboard Petugas menampilkan satu row terbaru untuk setiap Pendonor yang masih mempunyai status `CHECK_IN` serta link navigasi `Lanjutkan` tanpa mutation.
- Jadwal Pendonor, pemesanan baru, dan check-in baru menggunakan `jam_selesai` sebagai inclusive cutoff pada jadwal hari ini menurut WIB. `jam_mulai` bukan gate dan cutoff tidak diperluas ke proses berikutnya.
- Source-of-truth docs diselaraskan dengan keputusan final tersebut dan lifecycle `nomor_donor` tanpa mengubah schema.

---

# D. Aturan Pengerjaan dengan Codex

Setiap task untuk Codex harus kecil dan spesifik.

Format task yang disarankan:

1. minta Codex membaca `AGENTS.md`;
2. minta membaca dokumen `docs/` yang relevan;
3. jelaskan hanya phase/subtask yang sedang dikerjakan;
4. larang perubahan schema atau scope;
5. minta Codex memeriksa project sebelum mengubah file;
6. minta menjalankan test/check yang relevan;
7. minta daftar file yang dibuat/diubah;
8. minta laporan command yang dijalankan dan hasilnya;
9. minta berhenti setelah task selesai.

Jangan menggunakan prompt seperti:

`Build the entire application.`

Jangan meminta Codex melanjutkan otomatis ke phase berikutnya.

---

# E. Aturan Perubahan Spesifikasi

Jika saat implementasi ditemukan konflik:

1. hentikan perubahan pada bagian terkait;
2. tunjukkan file dan aturan yang konflik;
3. jangan mengubah `docs/` hanya agar kode menjadi lebih mudah;
4. jangan mengubah schema sendiri;
5. tunggu keputusan pengguna.

Jika memang diputuskan ada perubahan requirement, perbarui dokumen spesifikasi terlebih dahulu, baru kode mengikuti.

---

# F. Definition of Done Prototype

Prototype dianggap selesai jika:

- 15 tabel bisnis terimplementasi sesuai ERD;
- migrations berhasil pada MySQL;
- model dan relationship bekerja;
- master data tersedia;
- login/logout bekerja;
- registrasi Pendonor bekerja;
- tiga peran memiliki hak akses yang benar;
- CRUD Admin yang disepakati bekerja;
- alur Pendonor bekerja;
- alur operasional Petugas bekerja;
- aturan donor ulang bekerja;
- persediaan dihitung secara derived;
- persediaan rendah terdeteksi;
- pemberitahuan in-app bekerja;
- demo flow utama dapat diselesaikan end-to-end;
- tidak ada fitur di luar scope;
- tidak ada menu atau tombol placeholder;
- frontend sederhana tetapi cukup jelas untuk demonstrasi.

---

# G. Prioritas Jika Waktu Terbatas

Jika waktu implementasi terbatas, prioritas tidak boleh mengorbankan integritas schema.

Urutan prioritas:

1. migration dan constraint benar;
2. model/relationship benar;
3. auth dan role benar;
4. Admin dapat menyiapkan jadwal;
5. Pendonor dapat registrasi, pesan jadwal, mengisi kuesioner, dan memperoleh kode check-in;
6. Petugas dapat check-in, seleksi, dan mencatat penyumbangan;
7. unit dapat dibuat dan diluluskan;
8. persediaan dapat dihitung;
9. persediaan rendah dan pemberitahuan;
10. frontend polish.

Lebih baik memiliki alur utama yang benar-benar berfungsi daripada banyak halaman yang belum selesai.

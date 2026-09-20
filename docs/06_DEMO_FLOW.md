# 06 - Demo Flow

## Status Dokumen

Dokumen ini merupakan acuan alur demonstrasi prototype Sistem Informasi Manajemen Donor dan Persediaan Darah pada satu Unit Donor Darah (UDD).

Tujuan demo adalah membuktikan bahwa:

- struktur basis data digunakan dalam proses nyata;
- tiga peran bekerja sesuai hak akses;
- CRUD utama berfungsi;
- data dapat ditelusuri dari Pendonor sampai unit komponen darah;
- aturan bisnis utama diterapkan;
- persediaan dihitung dari unit, bukan diedit manual;
- pemberitahuan dalam aplikasi dapat dibuat dan diterima.

Dokumen ini tidak mengubah:

- `02_DATABASE_SCHEMA.md`;
- `03_ROLE_AND_UI_SCOPE.md`;
- `04_BUSINESS_RULES.md`;
- `05_IMPLEMENTATION_PLAN.md`.

Jika demo membutuhkan perilaku yang belum didukung oleh dokumen tersebut, jangan mengubah spesifikasi hanya untuk keperluan demonstrasi.

---

# A. Prinsip Demo

1. Gunakan satu alur utama yang benar-benar selesai dari awal sampai akhir.
2. Jangan mendemonstrasikan tombol atau halaman yang belum berfungsi.
3. Gunakan data demo yang sederhana dan mudah dijelaskan.
4. Tunjukkan perubahan data melalui UI terlebih dahulu.
5. Query basis data boleh digunakan sebagai bukti tambahan, bukan sebagai pengganti fungsi aplikasi.
6. Hindari fitur di luar scope.
7. Jangan mendemonstrasikan QR code, barcode, SMS, WhatsApp, email notification, pasien, rumah sakit, crossmatch, transfusi, atau proses laboratorium rinci.
8. Jika waktu presentasi terbatas, prioritaskan alur utama sebelum skenario tambahan.

---

# B. Persiapan Sebelum Demo

Sebelum demonstrasi dimulai, pastikan:

- migration sudah berhasil;
- 15 tabel bisnis tersedia;
- master `golongan_darah` tersedia;
- master `jenis_komponen_darah` berisi WB, PRC, TC, dan FFP;
- minimal satu akun Admin aktif tersedia;
- minimal satu akun Petugas aktif tersedia;
- pertanyaan kuesioner aktif tersedia;
- ambang persediaan yang akan digunakan pada demo sudah tersedia atau dapat dibuat oleh Admin saat demo;
- aplikasi dapat dibuka;
- route tiga peran bekerja;
- basis data yang digunakan adalah `db_donor_darah_udd`.

Jika menggunakan data lama dari pengujian, pastikan data tersebut tidak membuat alur demo gagal karena:

- email/NIK sudah dipakai;
- jadwal penuh;
- pemesanan aktif ganda;
- kode check-in bentrok;
- nomor unit sudah digunakan.

---

# C. Alur Utama Demo

Alur utama yang harus dapat diselesaikan:

**Admin → Pendonor → Petugas UDD → Unit Komponen → Persediaan**

---

## 1. Admin Login

### Aksi

Admin login menggunakan akun aktif dengan peran `ADMIN`.

### Hasil yang Diharapkan

- login berhasil;
- Admin masuk ke area Admin;
- menu operasional Petugas tidak tersedia.

### Bukti yang Ditunjukkan

Dashboard Admin atau menu administrasi.

---

## 2. Admin Membuat Jadwal Pelayanan

### Aksi

Buka menu `Jadwal Pelayanan`.

Buat satu jadwal dengan:

- tanggal;
- jam mulai;
- jam selesai;
- kapasitas;
- status `DIBUKA`.

### Hasil yang Diharapkan

Jadwal tersimpan dan dapat dilihat Pendonor.

### Data Utama

`jadwal_pelayanan`

---

## 3. Admin Memastikan Pertanyaan Kuesioner Aktif

### Aksi

Buka menu `Pertanyaan Kuesioner`.

Pastikan tersedia pertanyaan aktif yang akan dijawab Pendonor.

Jika data sudah tersedia dari setup, cukup tampilkan.

### Hasil yang Diharapkan

Pertanyaan aktif tersedia untuk pengisian kuesioner.

### Data Utama

`pertanyaan_kuesioner`

---

## 4. Admin Memastikan Ambang Persediaan

### Aksi

Buka menu `Ambang Persediaan`.

Pastikan terdapat konfigurasi untuk kombinasi jenis komponen dan golongan darah yang akan digunakan pada bagian persediaan demo.

### Hasil yang Diharapkan

Terdapat satu `jumlah_minimum` untuk kombinasi tersebut.

### Data Utama

`ambang_persediaan`

### Catatan

Ambang adalah parameter operasional UDD, bukan angka standar nasional.

---

## 5. Logout Admin

Logout agar perpindahan peran pada demonstrasi jelas.

---

## 6. Pendonor Registrasi

### Aksi

Buka halaman registrasi Pendonor.

Isi data akun dan profil Pendonor.

`nomor_donor` boleh diisi jika Pendonor sudah mempunyai nomor atau kartu donor sebelumnya. Pendonor baru yang belum memilikinya dapat mengosongkan field tersebut.

### Hasil yang Diharapkan

Terbentuk:

- satu record `akun` dengan `peran = PENDONOR`;
- satu record `pendonor` yang merujuk akun tersebut.

### Catatan

Pendonor baru boleh belum memiliki `id_golongan_darah` apabila belum terkonfirmasi.

---

## 7. Pendonor Login

### Aksi

Login menggunakan akun yang baru dibuat.

### Hasil yang Diharapkan

Pendonor masuk ke area Pendonor dan hanya melihat menu yang sesuai hak aksesnya.

---

## 8. Sistem Memeriksa Riwayat Donor Ulang

### Kondisi Demo Utama

Untuk Pendonor baru tanpa riwayat penyumbangan berhasil, pembatasan donor ulang dari riwayat tidak menghalangi kesempatan donor pertama.

### Yang Dijelaskan Saat Demo

Untuk Pendonor ulang, sistem menggunakan riwayat penyumbangan `BERHASIL` untuk memeriksa:

- interval minimal Whole Blood 2 bulan;
- maksimal 6 kali per tahun untuk laki-laki;
- maksimal 4 kali per tahun untuk perempuan.

Hasil pemeriksaan riwayat bukan keputusan kelayakan medis akhir.

### Catatan

Tidak perlu membuat donor lama palsu pada alur utama hanya untuk memaksa perhitungan ini terlihat. Skenario donor ulang dapat ditunjukkan sebagai skenario tambahan apabila data demo tersedia.

---

## 9. Pendonor Melihat Jadwal

### Aksi

Buka menu `Jadwal Donor`.

### Hasil yang Diharapkan

Jadwal yang dibuat Admin tampil karena:

- statusnya `DIBUKA`;
- masih memiliki kapasitas; dan
- untuk jadwal hari ini, waktu demo belum melewati `jam_selesai` menurut WIB.

---

## 10. Pendonor Membuat Pemesanan

### Aksi

Pilih jadwal dan buat pemesanan.

Untuk jadwal hari ini, lakukan aksi paling lambat tepat pada `jam_selesai`. `jam_mulai` tidak membatasi pembuatan pemesanan.

### Hasil yang Diharapkan

Terbentuk record pada:

`pemesanan_donor`

dengan status awal:

`TERJADWAL`

### Validasi yang Harus Berfungsi

- jadwal harus dapat digunakan;
- kapasitas belum penuh;
- tidak ada pemesanan aktif ganda yang dilarang oleh aturan aplikasi.

### Catatan

Sisa kapasitas dihitung dari data, bukan disimpan sebagai field tambahan.

---

## 11. Pendonor Mengisi Kuesioner Pradonasi

### Aksi

Buka `Kuesioner Pradonasi` untuk pemesanan tersebut.

Jawab pertanyaan aktif.

### Hasil yang Diharapkan

Terbentuk:

- satu `kuesioner_pradonasi`;
- beberapa `jawaban_kuesioner`.

Satu pertanyaan hanya memiliki satu jawaban dalam kuesioner tersebut.

### Catatan

Tidak ada transaksi review kuesioner terpisah.

---

## 12. Sistem Menghasilkan Kode Check-in

### Aksi

Setelah proses pradonasi yang diperlukan selesai, buka menu `Kode Check-in`.

### Hasil yang Diharapkan

Pendonor memperoleh `kode_checkin` unik yang terhubung dengan `pemesanan_donor`.

### Yang Ditunjukkan

Kode berupa teks biasa.

### Jangan Ditambahkan

- QR code;
- barcode.

---

## 13. Logout Pendonor

Logout agar perpindahan ke peran Petugas terlihat jelas.

---

## 14. Petugas Login

### Aksi

Login menggunakan akun aktif dengan peran `PETUGAS`.

### Hasil yang Diharapkan

Petugas masuk ke area operasional Petugas.

Admin menu tidak tersedia.

---

## 15. Petugas Melakukan Check-in

### Aksi

Buka `Check-in Pendonor`.

Masukkan `kode_checkin` dari Pendonor.

Pastikan tanggal jadwal adalah hari ini menurut WIB dan waktu check-in belum melewati `jam_selesai`. Tepat pada `jam_selesai` masih diperbolehkan; `jam_mulai` bukan gate.

### Hasil yang Diharapkan

Sistem menampilkan data yang berkaitan dengan kunjungan:

- Pendonor;
- jadwal;
- pemesanan;
- kuesioner.

Petugas melakukan check-in.

### Perubahan Data

- `waktu_checkin` terisi;
- status pemesanan diperbarui sesuai proses check-in.

### Catatan

Tidak ada tabel `checkin` baru.

---

## 16. Petugas Melihat Kuesioner

### Aksi

Tampilkan jawaban kuesioner Pendonor.

### Hasil yang Diharapkan

Petugas dapat membaca jawaban sebagai salah satu informasi proses seleksi.

Petugas tidak mengubah master pertanyaan dari area ini.

---

## 17. Petugas Melakukan Seleksi Donor

### Aksi

Isi data seleksi yang diperlukan:

- berat badan;
- tekanan sistolik;
- tekanan diastolik;
- denyut nadi;
- suhu tubuh;
- kadar Hb;
- hasil pemeriksaan kesehatan bila diperlukan.

Pilih keputusan:

`LAYAK`

### Untuk Pendonor Baru

Jika golongan darah sebelumnya belum tersedia, Petugas dapat mencatat ABO/Rhesus yang telah terkonfirmasi.

### Hasil yang Diharapkan

Satu record `seleksi_donor` terbentuk untuk pemesanan tersebut.

### Yang Dijelaskan

Alternatif keputusan:

- `DITUNDA`: donor tidak dapat dilakukan pada kesempatan tersebut karena kondisi sementara;
- `DITOLAK`: proses donor pada kesempatan tersebut tidak dapat dilanjutkan berdasarkan hasil seleksi dan penilaian Petugas.

Keduanya tidak dilanjutkan ke penyumbangan pada kesempatan tersebut.

---

## 18. Petugas Mencatat Penyumbangan

### Prasyarat

Keputusan seleksi adalah:

`LAYAK`

### Aksi

Catat penyumbangan dengan hasil:

`BERHASIL`

Catat:

- waktu pengambilan;
- volume Whole Blood.

### Validasi

Untuk Whole Blood:

- 350 mL membutuhkan berat badan minimal 45 kg;
- 450 mL membutuhkan berat badan minimal 55 kg.

Gunakan kombinasi data demo yang memenuhi aturan.

### Hasil yang Diharapkan

Satu record `penyumbangan` terbentuk dengan `hasil_penyumbangan = BERHASIL`.

### Yang Dijelaskan

Jika hasil `GAGAL`:

- transaksi penyumbangan tetap dapat tercatat;
- tidak dihitung sebagai donor berhasil;
- tidak dapat menghasilkan unit komponen darah.

---

## 19. Petugas Membuat Unit Komponen Darah

### Prasyarat

Penyumbangan berstatus:

`BERHASIL`

### Aksi

Buat minimal satu unit komponen darah.

Isi:

- nomor unit;
- jenis komponen;
- golongan darah;
- tanggal pembuatan;
- tanggal kedaluwarsa.

### Hasil yang Diharapkan

Unit baru memiliki:

`status_unit = MENUNGGU_PELULUSAN`

### Catatan

Unit ini belum dihitung sebagai persediaan tersedia.

Jenis komponen dalam scope:

- WB;
- PRC;
- TC;
- FFP.

Proses teknis pemisahan komponen tidak didemonstrasikan.

---

## 20. Tunjukkan Persediaan Sebelum Pelulusan

### Aksi

Buka tampilan persediaan sebelum unit diluluskan.

### Hasil yang Diharapkan

Unit `MENUNGGU_PELULUSAN` belum menambah persediaan tersedia.

### Tujuan Demo

Menunjukkan bahwa stok bukan angka manual dan tidak semua unit otomatis menjadi stok tersedia.

---

## 21. Petugas Melakukan Pelulusan Unit

### Aksi

Buka menu `Pelulusan`.

Pilih unit yang masih `MENUNGGU_PELULUSAN`.

Catat hasil pelulusan menjadi:

`TERSEDIA`

### Hasil yang Diharapkan

Data unit mencatat:

- Petugas pelulus;
- waktu pelulusan;
- `status_unit = TERSEDIA`;
- catatan pelulusan jika digunakan.

### Alternatif

Jika unit tidak memenuhi persyaratan, status menjadi:

`DITOLAK`

---

## 22. Tunjukkan Persediaan Setelah Pelulusan

### Aksi

Buka menu `Persediaan`.

### Hasil yang Diharapkan

Unit yang baru diluluskan sekarang dihitung sebagai persediaan jika:

- `status_unit = TERSEDIA`; dan
- belum melewati `tanggal_kedaluwarsa`.

### Yang Ditunjukkan

Persediaan dikelompokkan berdasarkan:

- jenis komponen;
- golongan darah.

### Tujuan Demo

Bandingkan dengan langkah 20 untuk menunjukkan bahwa pelulusan mengubah hasil perhitungan persediaan tanpa mengedit angka stok.

---

# D. Demo Kondisi Persediaan Rendah dan Pemberitahuan

Bagian ini dapat dilakukan setelah alur utama jika waktu memungkinkan.

## 23. Periksa Persediaan Rendah

### Aksi

Buka menu `Persediaan Rendah`.

### Logika

Sistem membandingkan:

`jumlah_persediaan <= jumlah_minimum`

untuk kombinasi jenis komponen dan golongan darah.

### Hasil

Jika kondisi terpenuhi, kombinasi tersebut ditandai sebagai persediaan rendah.

### Catatan

Jika data pada saat demo membuat jumlah persediaan berada di atas ambang, gunakan konfigurasi/data demo yang sudah direncanakan sebelum presentasi. Jangan mengubah rumus bisnis.

---

## 24. Petugas Melihat Pendonor yang Relevan

### Aksi

Buka `Pemanggilan Pendonor` untuk kondisi persediaan rendah.

### Hasil yang Diharapkan

Sistem membantu menampilkan Pendonor yang:

- memiliki golongan darah relevan;
- berdasarkan riwayat telah memenuhi ketentuan donor ulang.

### Catatan

Daftar ini bukan clinical matching dengan pasien.

---

## 25. Petugas Membuat Pemberitahuan

### Aksi

Pilih Pendonor yang relevan.

Buat pesan pemberitahuan.

### Hasil yang Diharapkan

Terbentuk record `pemberitahuan` yang menyimpan:

- Pendonor penerima;
- Petugas pengirim;
- isi pesan;
- waktu dibuat.

Pemberitahuan hanya berada di dalam aplikasi.

---

## 26. Pendonor Membaca Pemberitahuan

### Aksi

Logout Petugas.

Login sebagai Pendonor penerima.

Buka menu `Pemberitahuan`.

### Hasil yang Diharapkan

Pesan yang dibuat Petugas muncul pada akun Pendonor yang benar.

Setelah dibaca, aplikasi dapat mencatat `waktu_dibaca`.

---

# E. Demo Distribusi Unit

Bagian ini dapat dijalankan sebagai skenario tambahan.

## 27. Petugas Mendistribusikan Unit

### Prasyarat

Unit masih:

`TERSEDIA`

dan belum kedaluwarsa.

### Aksi

Petugas mencatat unit keluar dari persediaan.

### Hasil yang Diharapkan

- `status_unit` menjadi `DIDISTRIBUSIKAN`;
- `waktu_distribusi` terisi;
- unit tidak lagi dihitung sebagai persediaan tersedia.

### Catatan

Demo berhenti pada pencatatan status distribusi.

Jangan menambahkan:

- rumah sakit penerima;
- pasien;
- permintaan darah;
- crossmatch;
- transfusi;
- cold chain.

---

# F. Titik Keputusan yang Perlu Dijelaskan Saat Presentasi

Tidak semua cabang harus dibuat sebagai transaksi baru saat demo utama. Namun presenter harus dapat menjelaskan logika berikut.

## 1. Pemeriksaan Donor Ulang

Jika interval/frekuensi belum terpenuhi:

- Pendonor belum dapat melanjutkan pemesanan donor baru sesuai aturan donor ulang.

Jika terpenuhi:

- Pendonor dapat melanjutkan ke pemilihan jadwal.

## 2. Keputusan Seleksi

### LAYAK

Dapat melanjutkan ke penyumbangan.

### DITUNDA

Tidak dapat melakukan donor pada kesempatan tersebut karena kondisi sementara.

### DITOLAK

Proses donor pada kesempatan tersebut tidak dapat dilanjutkan berdasarkan hasil seleksi.

`DITOLAK` tidak otomatis berarti penolakan permanen seumur hidup.

## 3. Hasil Penyumbangan

### BERHASIL

Dapat dilanjutkan ke pencatatan unit komponen darah.

### GAGAL

Tidak menghasilkan unit komponen darah dan tidak dihitung sebagai donor berhasil untuk riwayat donor ulang.

## 4. Hasil Pelulusan

### TERSEDIA

Dapat dihitung sebagai persediaan selama belum kedaluwarsa.

### DITOLAK

Tidak dihitung sebagai persediaan tersedia.

## 5. Kondisi Unit TERSEDIA

Unit dapat:

- tetap menjadi persediaan jika belum kedaluwarsa;
- menjadi `DIDISTRIBUSIKAN` jika keluar dari persediaan;
- tetap memiliki status `TERSEDIA` tetapi tidak dihitung sebagai persediaan jika telah melewati tanggal kedaluwarsa.

`KEDALUWARSA` bukan status unit.

---

# G. Bukti Relasi Data yang Dapat Ditunjukkan

Untuk menunjukkan kualitas desain basis data, demonstrasikan bahwa satu alur dapat ditelusuri:

`akun`
→ `pendonor`
→ `pemesanan_donor`
→ `kuesioner_pradonasi`
→ `seleksi_donor`
→ `penyumbangan`
→ `unit_komponen_darah`

Hubungan tambahan yang dapat ditunjukkan:

- `pemesanan_donor` → `jadwal_pelayanan`;
- `kuesioner_pradonasi` → `jawaban_kuesioner` → `pertanyaan_kuesioner`;
- `unit_komponen_darah` → `jenis_komponen_darah`;
- `unit_komponen_darah` → `golongan_darah`;
- `unit_komponen_darah` → Petugas pencatat;
- `unit_komponen_darah` → Petugas pelulus;
- `pemberitahuan` → Pendonor penerima;
- `pemberitahuan` → Petugas pengirim.

Tidak perlu membuat halaman khusus hanya untuk memperlihatkan relationship. Gunakan data yang memang muncul dalam proses aplikasi.

---

# H. Skenario Negatif untuk Pengujian/Demonstrasi Tambahan

Skenario berikut tidak harus semuanya dipresentasikan, tetapi sebaiknya sudah diuji sebelum demo.

## Hak Akses

- Pendonor mencoba membuka URL Admin.
- Pendonor mencoba membuka URL Petugas.
- Petugas mencoba membuka URL Admin.
- Admin mencoba membuka aksi operasional Petugas.
- Pendonor mencoba membaca resource Pendonor lain.

Expected result: akses ditolak.

## Pemesanan

- jadwal `DITUTUP`;
- jadwal `DIBATALKAN`;
- kapasitas penuh;
- pemesanan aktif ganda pada Pendonor dan jadwal yang sama;
- donor ulang belum memenuhi interval;
- donor ulang sudah mencapai batas frekuensi tahunan.

Expected result: pemesanan baru ditolak sesuai aturan aplikasi.

## Kuesioner dan Seleksi

- mencoba membuat kuesioner kedua untuk pemesanan yang sama;
- mencoba membuat seleksi kedua untuk pemesanan yang sama;
- mencoba memberi dua jawaban pada pertanyaan yang sama dalam satu kuesioner.

Expected result: ditolak oleh validasi/constraint.

## Penyumbangan

- keputusan seleksi `DITUNDA`;
- keputusan seleksi `DITOLAK`;
- volume 350 mL tetapi berat <45 kg;
- volume 450 mL tetapi berat <55 kg.

Expected result: tidak boleh diproses sebagai penyumbangan yang valid sesuai aturan.

## Unit Komponen

- membuat unit dari penyumbangan `GAGAL`;
- memasukkan nomor unit duplikat;
- mencoba mendistribusikan unit yang belum `TERSEDIA`.

Expected result: ditolak.

## Persediaan

- unit `MENUNGGU_PELULUSAN`;
- unit `DITOLAK`;
- unit `DIDISTRIBUSIKAN`;
- unit `TERSEDIA` tetapi kedaluwarsa.

Expected result: tidak dihitung sebagai persediaan tersedia.

---

# I. Query Verifikasi Opsional

Query verifikasi boleh digunakan setelah alur UI selesai untuk menunjukkan bahwa data benar-benar tersimpan pada MySQL.

Query tidak boleh menjadi pengganti fungsi CRUD aplikasi.

Contoh hal yang dapat diverifikasi:

- record Pendonor dan akun saling terhubung;
- pemesanan merujuk Pendonor dan jadwal;
- kuesioner merujuk pemesanan;
- seleksi merujuk pemesanan dan Petugas;
- penyumbangan merujuk seleksi;
- unit merujuk penyumbangan, jenis komponen, golongan darah, dan Petugas;
- pemberitahuan merujuk Pendonor dan Petugas.

Query SQL spesifik dapat disiapkan setelah implementasi selesai sehingga sesuai dengan data demo yang benar-benar tersedia.

---

# J. Urutan Demo Singkat Jika Waktu Terbatas

Jika waktu presentasi sangat terbatas, gunakan alur ringkas berikut:

1. Admin membuat jadwal.
2. Pendonor registrasi dan login.
3. Pendonor memilih jadwal dan membuat pemesanan.
4. Pendonor mengisi kuesioner.
5. Pendonor memperoleh kode check-in.
6. Petugas login dan melakukan check-in.
7. Petugas mencatat seleksi `LAYAK`.
8. Petugas mencatat penyumbangan `BERHASIL`.
9. Petugas membuat unit komponen.
10. Tunjukkan unit belum masuk persediaan karena `MENUNGGU_PELULUSAN`.
11. Petugas meluluskan unit menjadi `TERSEDIA`.
12. Tunjukkan unit sekarang masuk perhitungan persediaan.

Jika masih ada waktu:

13. Tunjukkan kondisi persediaan rendah.
14. Petugas membuat pemberitahuan.
15. Pendonor membaca pemberitahuan.

Alur ringkas tersebut sudah menunjukkan integrasi tiga peran, CRUD utama, relationship, status proses, dan perhitungan persediaan.

---

# K. Checklist Sebelum Presentasi

Pastikan sebelum presentasi:

- aplikasi berjalan tanpa error;
- koneksi MySQL benar;
- akun Admin dapat login;
- akun Petugas dapat login;
- registrasi Pendonor bekerja;
- jadwal demo tersedia;
- pertanyaan kuesioner aktif;
- pemesanan dapat dibuat;
- kuesioner dapat disimpan;
- kode check-in dapat dibuat dan digunakan;
- seleksi dapat disimpan;
- penyumbangan berhasil dapat dicatat;
- unit komponen dapat dibuat;
- unit baru memiliki status `MENUNGGU_PELULUSAN`;
- pelulusan ke `TERSEDIA` bekerja;
- persediaan berubah sesuai unit;
- unit kedaluwarsa tidak dihitung;
- role access bekerja;
- tidak ada menu kosong;
- tidak ada tombol placeholder;
- tidak ada fitur di luar scope;
- data demo sudah disiapkan agar alur dapat selesai tanpa improvisasi.

Jika salah satu langkah utama gagal saat rehearsal, perbaiki fungsi tersebut sebelum menambahkan fitur tambahan.

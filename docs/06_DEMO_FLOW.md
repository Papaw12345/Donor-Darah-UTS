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
- data lama yang mempunyai nomor/kode yang sudah terpakai (UNIQUE tetap berlaku); nomor operasional baru diterbitkan dari PK, sedangkan kode check-in acak.

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

Dashboard `Ringkasan Konfigurasi Sistem` dengan empat ringkasan: Petugas Aktif, Jadwal Dibuka hari ini & mendatang, Pertanyaan Aktif, serta Konfigurasi Ambang `X/Y terisi, Z belum`. Tanggal hari ini menurut `Asia/Jakarta`. Jika membuat Petugas sebagai bagian demo tambahan, `nomor_petugas` diterbitkan server dari PK sebagai `PTG-000001`, tidak diinput Admin.

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

Gunakan NIK tepat 16 digit numerik, tanggal lahir yang tidak di masa depan, dan usia minimal 17 tahun. Jangan isi `nomor_donor`; server menerbitkannya dari PK sebagai `DNR-000001`. Registrasi tidak meminta golongan darah.

### Hasil yang Diharapkan

Terbentuk:

- satu record `akun` dengan `peran = PENDONOR`;
- satu record `pendonor` yang merujuk akun tersebut.
- `nomor_donor` unik dan immutable diterbitkan server.

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

## 10. Pendonor Mendaftar pada Jadwal Donor

### Aksi

Pilih jadwal lalu gunakan aksi `Daftar pada Jadwal Ini`.

Untuk jadwal hari ini, lakukan pendaftaran paling lambat tepat pada `jam_selesai`. `jam_mulai` tidak membatasi pendaftaran pada jadwal donor.

### Hasil yang Diharapkan

Terbentuk record pada:

`pemesanan_donor`

dengan status awal:

`TERJADWAL`

### Validasi yang Harus Berfungsi

- jadwal harus dapat digunakan;
- kapasitas belum penuh;
- usia Pendonor pada tanggal jadwal masih >= 17 tahun;
- tidak ada pemesanan aktif ganda yang dilarang oleh aturan aplikasi.

### Catatan

Sisa kapasitas dihitung dari data, bukan disimpan sebagai field tambahan.

---

## 11. Pendonor Mengisi Kuesioner dan Mendapat Kode Check-in

### Aksi

Buka `Kuesioner Pra-Donor` untuk agenda donor tersebut.

Jawab pertanyaan aktif.

### Hasil yang Diharapkan

Terbentuk:

- satu `kuesioner_pradonasi`;
- seluruh `jawaban_kuesioner`; dan
- satu `kode_checkin` unik pada pemesanan.

Ketiganya berhasil atau gagal bersama dalam satu transaction. Setelah berhasil, Pendonor diarahkan ke halaman kode check-in. Status tetap `TERJADWAL` dan `waktu_checkin` tetap NULL. Kode yang sama dapat dilihat lagi.

Satu pertanyaan hanya memiliki satu jawaban dalam kuesioner tersebut.

### Catatan

Tidak ada transaksi review kuesioner terpisah.

---

## 12. Pendonor Melihat Ulang Kode Check-in

### Aksi

Buka kembali halaman `Kode Check-in` setelah langkah 11. Tidak ada POST generate terpisah.

### Hasil yang Diharapkan

Pendonor melihat kode yang sama, `UDD-` diikuti tepat 12 hex uppercase acak, tidak diturunkan dari PK dan tidak dirotasi.

### Yang Ditunjukkan

Kode berupa teks biasa.

### Jangan Ditambahkan

- QR code;
- barcode.

[SUPERSEDED] Alur lama yang menghasilkan kode setelah Pendonor membuka menu terpisah diganti oleh pembuatan saat submit kuesioner.

---

## 13. Logout Pendonor

Logout agar perpindahan ke peran Petugas terlihat jelas.

---

## 14. Petugas Login

### Aksi

Login menggunakan akun aktif dengan peran `PETUGAS`.

### Hasil yang Diharapkan

Petugas masuk ke area operasional Petugas.

Tunjukkan menu Operasional (Dashboard, Check-in, Seleksi Donor, Penyumbangan, Unit Komponen, Pelulusan, Distribusi) dan Monitoring (Jadwal Pelayanan, Riwayat Pelayanan, Persediaan, Pemanggilan Pendonor). Kuesioner dapat dibuka sebagai konteks Seleksi. Akun Petugas berbeda boleh melanjutkan proses yang dimulai akun pertama; schema tidak dapat mengaudit pelaksana check-in.

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

Setelah check-in, booking muncul di queue Seleksi bila kuesioner/jawaban lengkap dan belum ada seleksi. Pekerjaan yang paling lama menunggu tampil dahulu: Seleksi `waktu_checkin ASC`, `id_pemesanan ASC`; Penyumbangan `waktu_seleksi ASC`, `id_seleksi ASC`; Unit Komponen `waktu_pengambilan ASC`, `id_penyumbangan ASC`; Pelulusan `id_unit ASC`. Sesudah seleksi `LAYAK`, booking muncul di queue Penyumbangan sampai transaksi dicatat. Setelah penyumbangan `BERHASIL`, sumber muncul di queue Unit Komponen; unit baru muncul di queue Pelulusan.

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
- hasil pemeriksaan kesehatan wajib.

Pilih keputusan:

`LAYAK`

### Untuk Pendonor Baru

Jika golongan darah sebelumnya NULL, Petugas wajib mengonfirmasi satu nilai dari master dan menyimpannya. Jika sudah ada, tampilkan read-only dan tolak request yang mencoba menggantinya.

### Hasil yang Diharapkan

Satu record `seleksi_donor` terbentuk untuk pemesanan tersebut.

Gunakan pengukuran yang memenuhi seluruh gate `LAYAK`: usia >= 17, berat >= 45 kg, sistolik 90-160, diastolik 60-100, selisih > 20, nadi 50-100, suhu 36.5-37.5 C, Hb 12.5-17 g/dL. Pengukuran <= 0 ditolak. Nilai di luar gate tetap dapat dicatat untuk `DITUNDA`/`DITOLAK`; Petugas memilih keputusan dan wajib mengisi alasan untuk dua keputusan tersebut. Tidak ada batas maksimum usia 60/65 atau skor medis.

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

Isi jenis komponen, tanggal pembuatan, dan tanggal kedaluwarsa. `tanggal_kedaluwarsa` harus >= `tanggal_pembuatan`. Nomor unit dibuat server dari PK sebagai `UNT-000001`; golongan darah berasal dari Pendonor sumber, bukan pilihan bebas. Jika golongan darah sumber NULL, pencatatan ditolak.

### Hasil yang Diharapkan

Unit baru memiliki:

`status_unit = MENUNGGU_PELULUSAN`

Satu penyumbangan `BERHASIL` dapat menjadi sumber beberapa unit; tidak ada status sintetis selesai membuat komponen. Urutan tetap Unit dahulu, lalu Pelulusan.

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

## Registrasi dan Agenda Donor

- NIK bukan tepat 16 digit, tanggal lahir masa depan, atau usia registrasi/usia pada tanggal jadwal kurang dari 17 tahun;
- jadwal `DITUTUP`;
- jadwal `DIBATALKAN`;
- kapasitas penuh;
- pemesanan aktif ganda pada Pendonor dan jadwal yang sama;
- donor ulang belum memenuhi interval;
- donor ulang sudah mencapai batas frekuensi tahunan.

Expected result: pendaftaran pada jadwal donor ditolak sesuai aturan aplikasi.

## Kuesioner dan Seleksi

- mencoba membuat kuesioner kedua untuk pemesanan yang sama;
- submit kuesioner gagal di tengah pembuatan jawaban/kode: tidak ada data parsial;
- mencoba membuat seleksi kedua untuk pemesanan yang sama;
- mencoba memberi dua jawaban pada pertanyaan yang sama dalam satu kuesioner;
- mencoba keputusan `LAYAK` dengan satu kriteria objektif tidak terpenuhi; mencoba pengukuran <= 0; atau `DITUNDA`/`DITOLAK` tanpa alasan;
- mencoba mengganti golongan darah Pendonor yang sudah terkonfirmasi melalui request seleksi.

Expected result: ditolak oleh validasi/constraint.

## Penyumbangan

- keputusan seleksi `DITUNDA`;
- keputusan seleksi `DITOLAK`;
- volume 350 mL tetapi berat <45 kg;
- volume 450 mL tetapi berat <55 kg.

Expected result: tidak boleh diproses sebagai penyumbangan yang valid sesuai aturan.

## Unit Komponen

- membuat unit dari penyumbangan `GAGAL`;
- request create menyertakan `nomor_unit` atau `id_golongan_darah`, termasuk nilai yang kebetulan sama dengan nilai authoritative: ditolak dengan validation error, bukan diabaikan;
- sumber Pendonor tanpa golongan darah;
- tanggal kedaluwarsa lebih awal dari tanggal pembuatan;
- mencoba mendistribusikan unit yang belum `TERSEDIA`.

Expected result: ditolak.

## Cutoff, No-show, dan Pembatalan Jadwal

- Tepat pada `jam_selesai` hari ini booking, submit kuesioner/kode, pembatalan Pendonor atas `TERJADWAL`, dan check-in masih diperbolehkan jika syarat lain terpenuhi; setelahnya semuanya ditolak. Kuesioner dan kode yang sudah ada tetap dapat dilihat.
- Setelah jam selesai, Petugas secara eksplisit menandai booking `TERJADWAL` tanpa check-in menjadi `TIDAK_HADIR`, meskipun belum ada kuesioner/kode. Booking dan data historical tidak dihapus; tidak ada cron.
- Admin mengubah jadwal menjadi `DIBATALKAN`: hanya booking terkait `TERJADWAL` ikut menjadi `DIBATALKAN` dalam transaction yang sama. `CHECK_IN`, `SELESAI`, `DIBATALKAN`, dan `TIDAK_HADIR` tetap. Membuka jadwal lagi tidak menghidupkan row lama.
- Pertanyaan nonaktif dan kode yang tak lagi bisa dipakai tidak menghapus jawaban/kode historical.

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
3. Pendonor memilih jadwal dan mendaftarkan diri; agenda donor terbentuk.
4. Pendonor mengisi kuesioner; sistem sekaligus menyimpan jawaban dan kode secara atomik.
5. Pendonor melihat kode check-in yang sama pada halaman kode.
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
- kode check-in otomatis terbit bersama kuesioner dan dapat digunakan;
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

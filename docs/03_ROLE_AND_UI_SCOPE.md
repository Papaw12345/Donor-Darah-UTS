# 03 - Role and UI Scope

## Status Dokumen

Dokumen ini merupakan source of truth untuk pembagian akses, menu, fungsi, dan batasan antarpengguna.

Terdapat tiga peran:

- `PENDONOR`
- `PETUGAS`
- `ADMIN`

Codex atau developer tidak boleh:

- memindahkan fungsi dari satu peran ke peran lain tanpa instruksi;
- menambahkan menu baru tanpa kebutuhan yang jelas;
- membuat menu placeholder;
- membuat tombol yang tidak memiliki aksi;
- membuat CRUD untuk setiap tabel hanya karena tabel tersebut ada.

Prinsip utama UI:

**Semua yang terlihat dan dapat diklik harus berfungsi.**

Tidak semua tabel basis data membutuhkan menu tersendiri.

---

# 1. Authentication dan Akses Dasar

## Pendonor

Pendonor dapat melakukan registrasi sendiri melalui aplikasi.

Setelah registrasi, Pendonor dapat login menggunakan akun miliknya.

## Petugas

Petugas tidak melakukan registrasi sendiri.

Akun Petugas dibuat dan dikelola oleh Admin.

Petugas login menggunakan akun yang telah dibuat dan masih berstatus aktif.

## Admin

Admin tidak melakukan registrasi melalui halaman publik.

Admin login menggunakan akun dengan `akun.peran = ADMIN`.

Cara teknis pembuatan akun Admin awal tidak ditentukan pada dokumen ini dan akan ditetapkan pada tahap implementasi.

## Aturan Umum Login

Setelah login, pengguna diarahkan ke area aplikasi sesuai perannya.

Akun dengan:

`akun.status_akun = NONAKTIF`

tidak boleh menggunakan fungsi aplikasi.

Hak akses tidak boleh hanya disembunyikan dari antarmuka. Route, controller, atau mekanisme otorisasi juga harus membatasi akses berdasarkan peran.

---

# 2. PENDONOR

## Peran

Pendonor merupakan pengguna yang menjalani proses pelayanan donor dari sisi pengguna.

Pendonor hanya boleh mengakses data dan aktivitas yang berkaitan dengan dirinya sendiri.

Pendonor tidak menentukan keputusan medis dan tidak menjalankan fungsi operasional UDD.

## Menu / Fitur

### Dashboard

Menampilkan ringkasan:

- informasi pendonor;
- status pemesanan donor yang sedang aktif;
- informasi donor berikutnya;
- pemberitahuan terbaru.

Dashboard hanya berfungsi sebagai ringkasan dan navigasi.

### Profil Saya

Pendonor dapat:

- melihat profil sendiri;
- memperbarui data pribadi yang diperbolehkan.

Golongan darah yang sudah dikonfirmasi oleh UDD tidak boleh diubah secara bebas oleh Pendonor.

### Jadwal Donor

Pendonor dapat:

- melihat jadwal pelayanan donor yang tersedia;
- melihat jadwal yang masih dibuka;
- melihat jadwal yang masih memiliki kapasitas.

Pendonor tidak dapat membuat atau mengubah jadwal pelayanan secara administratif.

### Pemesanan Donor

Pendonor dapat:

- memilih jadwal;
- membuat pemesanan donor;
- melihat pemesanan miliknya sendiri;
- membatalkan pemesanan selama masih diperbolehkan oleh aturan aplikasi.

Pendonor tidak boleh melihat atau mengubah pemesanan milik Pendonor lain.

### Kuesioner Pradonasi

Pendonor dapat mengisi kuesioner kesehatan pradonasi untuk pemesanan miliknya sendiri.

Kuesioner diisi pada setiap kesempatan donor.

Aturan mengenai batas waktu perubahan jawaban tidak ditentukan pada dokumen ini dan tidak boleh diasumsikan tanpa keputusan lebih lanjut.

### Kode Check-in

Pendonor dapat:

- melihat kode check-in unik yang berkaitan dengan pemesanan;
- menunjukkan kode tersebut kepada Petugas ketika datang ke UDD.

Kode check-in bukan QR code atau barcode.

### Riwayat Donor

Pendonor dapat melihat riwayat penyumbangannya sendiri.

Riwayat donor tidak dapat diubah oleh Pendonor.

### Informasi Donor Berikutnya

Pendonor dapat melihat perkiraan waktu ketika dirinya telah memenuhi interval dan frekuensi untuk mencoba melakukan donor kembali berdasarkan riwayat penyumbangan.

Informasi ini bukan keputusan kelayakan medis akhir.

### Pemberitahuan

Pendonor dapat:

- melihat pemberitahuan yang ditujukan kepadanya;
- membaca isi pemberitahuan;
- menandai pemberitahuan sebagai sudah dibaca.

Pemberitahuan hanya berada di dalam aplikasi.

## Batasan Akses Pendonor

Pendonor tidak boleh:

- menentukan hasil seleksi `LAYAK`, `DITUNDA`, atau `DITOLAK`;
- mencatat atau mengubah hasil pemeriksaan seleksi;
- mencatat atau mengubah penyumbangan;
- membuat atau mengubah unit komponen darah;
- melakukan pelulusan unit;
- mencatat distribusi unit;
- mengubah persediaan;
- mengubah ambang persediaan;
- mengelola jadwal pelayanan secara administratif;
- mengelola pertanyaan kuesioner;
- mengelola akun Petugas;
- mengelola hak akses pengguna;
- mengirim pemberitahuan kepada Pendonor lain.

---

# 3. PETUGAS

## Peran

Petugas UDD merupakan pengguna operasional utama.

Petugas menangani proses pelayanan donor setelah Pendonor datang ke UDD serta mengelola unit komponen darah dan pemantauan persediaan.

## Menu / Fitur

### Dashboard

Menampilkan ringkasan operasional seperti:

- kegiatan donor;
- jumlah Pendonor yang sedang diproses;
- kondisi persediaan darah;
- informasi persediaan yang berada pada atau di bawah ambang batas.

### Check-in Pendonor

Petugas dapat:

- memasukkan kode check-in;
- membuka pemesanan yang sesuai;
- melihat data Pendonor yang diperlukan;
- melihat jadwal;
- melihat kuesioner yang terkait;
- mencatat waktu check-in;
- memperbarui status pemesanan sesuai proses.

### Kuesioner Pradonasi

Petugas dapat melihat jawaban kuesioner Pendonor sebagai salah satu informasi dalam proses seleksi.

Petugas tidak mengelola master pertanyaan kuesioner.

### Seleksi Donor

Petugas dapat:

- mencatat berat badan;
- mencatat tekanan sistolik;
- mencatat tekanan diastolik;
- mencatat denyut nadi;
- mencatat suhu tubuh;
- mencatat kadar Hb;
- mencatat hasil pemeriksaan kesehatan;
- menentukan keputusan `LAYAK`, `DITUNDA`, atau `DITOLAK`;
- mencatat alasan keputusan jika diperlukan.

Untuk Pendonor baru yang belum memiliki golongan darah terkonfirmasi, Petugas dapat mencatat golongan darah ABO dan Rhesus yang telah dikonfirmasi.

Kewenangan ini tidak berarti Petugas dapat mengubah profil Pendonor secara bebas.

### Penyumbangan

Petugas dapat mencatat penyumbangan untuk seleksi yang `LAYAK`.

Data yang dicatat mencakup:

- waktu pengambilan;
- volume darah;
- hasil `BERHASIL` atau `GAGAL`;
- alasan gagal jika diperlukan.

### Unit Komponen Darah

Untuk penyumbangan berhasil, Petugas dapat mencatat satu atau lebih unit komponen darah.

Data unit meliputi:

- nomor unit;
- sumber penyumbangan;
- jenis komponen;
- golongan darah;
- tanggal pembuatan;
- tanggal kedaluwarsa;
- Petugas pencatat.

### Pelulusan Unit

Petugas dapat memproses unit yang berstatus `MENUNGGU_PELULUSAN`.

Hasil pelulusan:

- unit yang memenuhi persyaratan menjadi `TERSEDIA`;
- unit yang tidak memenuhi persyaratan menjadi `DITOLAK`.

Petugas pelulus dan waktu pelulusan dicatat ketika proses pelulusan dilakukan.

### Distribusi Unit

Petugas dapat mencatat unit tersedia yang keluar dari persediaan UDD sebagai `DIDISTRIBUSIKAN`.

Waktu distribusi dicatat.

Distribusi tidak dimodelkan secara rinci sampai rumah sakit atau pasien.

### Persediaan Darah

Petugas dapat melihat jumlah persediaan berdasarkan:

- jenis komponen;
- golongan darah.

Persediaan dihitung dari unit yang:

- berstatus `TERSEDIA`; dan
- belum melewati tanggal kedaluwarsa.

Tidak ada CRUD angka stok manual.

### Persediaan Rendah

Petugas dapat melihat kombinasi jenis komponen dan golongan darah yang jumlah persediaannya berada pada atau di bawah ambang minimum.

Petugas dapat melihat nilai ambang untuk keperluan pemantauan, tetapi tidak mengubahnya.

### Pemanggilan Pendonor

Jika persediaan tertentu berada pada atau di bawah ambang batas, Petugas dapat melihat Pendonor yang relevan berdasarkan:

- golongan darah; dan
- riwayat donor ulang.

Petugas kemudian memilih Pendonor yang akan diberi pemberitahuan.

Fitur ini bukan pencocokan darah donor dengan pasien.

### Pemberitahuan

Petugas dapat:

- memilih Pendonor yang relevan;
- membuat pemberitahuan;
- mengirim pemberitahuan di dalam aplikasi.

Pengirim pemberitahuan harus dapat ditelusuri melalui Petugas yang membuatnya.

## Batasan Akses Petugas

Petugas tidak boleh:

- membuat, mengubah, atau menonaktifkan akun Petugas lain;
- mengubah peran atau hak akses pengguna;
- mengelola akun Admin;
- mengubah master pertanyaan kuesioner;
- mengubah jadwal pelayanan secara administratif;
- mengubah nilai ambang persediaan;
- mengubah master jenis komponen darah;
- mengubah data akun atau profil Pendonor secara bebas di luar kebutuhan proses pelayanan;
- mengubah riwayat penyumbangan secara sembarangan;
- menjalankan fungsi pasien, rumah sakit, crossmatch, atau transfusi.

---

# 4. ADMIN

## Peran

Admin menangani fungsi administrasi dan konfigurasi sistem.

Admin tidak menjadi pengganti Petugas UDD dalam proses operasional donor.

Admin direpresentasikan hanya melalui:

`akun.peran = ADMIN`

Tidak ada tabel profil Admin.

## Menu / Fitur

### Dashboard

Menampilkan ringkasan administratif seperti:

- jumlah akun Petugas aktif;
- jadwal pelayanan;
- informasi konfigurasi sistem yang relevan.

Dashboard Admin tidak menyediakan aksi operasional donor.

### Kelola Petugas

Admin dapat:

- membuat akun Petugas;
- melihat data Petugas;
- memperbarui data Petugas;
- menonaktifkan akun Petugas.

### Jadwal Pelayanan

Admin dapat:

- membuat jadwal;
- melihat jadwal;
- memperbarui jadwal;
- mengatur tanggal;
- mengatur jam mulai;
- mengatur jam selesai;
- mengatur kapasitas;
- mengatur status `DIBUKA`, `DITUTUP`, atau `DIBATALKAN`.

### Pertanyaan Kuesioner

Admin dapat:

- menambah pertanyaan;
- melihat pertanyaan;
- memperbarui pertanyaan;
- mengatur kategori;
- mengatur jenis jawaban;
- mengatur urutan;
- mengaktifkan atau menonaktifkan pertanyaan.

Pertanyaan yang sudah tidak digunakan dapat dinonaktifkan agar riwayat jawaban tetap dapat dipertahankan.

### Ambang Persediaan

Admin dapat:

- melihat konfigurasi ambang;
- membuat konfigurasi jika kombinasi belum ada;
- memperbarui `jumlah_minimum`.

Setiap kombinasi:

- jenis komponen; dan
- golongan darah

hanya boleh memiliki satu konfigurasi ambang.

## Batasan Akses Admin

Admin tidak boleh:

- melakukan check-in Pendonor;
- menentukan keputusan seleksi;
- mencatat hasil pemeriksaan kesehatan;
- mencatat penyumbangan;
- mencatat hasil penyumbangan;
- membuat atau mengubah unit komponen sebagai proses operasional;
- melakukan pelulusan unit;
- mencatat distribusi unit;
- mengirim pemberitahuan pemanggilan donor;
- mengubah riwayat donor;
- menangani pasien;
- menangani permintaan darah rumah sakit;
- melakukan crossmatch;
- menangani transfusi.

---

# 5. Master Data dan Tabel Tanpa Menu CRUD Langsung

Tabel berikut tetap dibutuhkan dalam basis data tetapi tidak memerlukan menu CRUD tersendiri untuk pengguna biasa.

## golongan_darah

Master kombinasi:

- ABO;
- Rhesus.

Digunakan oleh:

- Pendonor;
- unit komponen darah;
- ambang persediaan.

## jenis_komponen_darah

Master awal:

- WB;
- PRC;
- TC;
- FFP.

Data ini dapat disiapkan melalui seed data pada tahap implementasi.

Tidak perlu menu CRUD pada prototype kecuali ada keputusan baru.

## jawaban_kuesioner

Tidak memiliki menu tersendiri.

Dikelola sebagai bagian dari proses pengisian kuesioner pradonasi.

## akun

Tidak memiliki menu CRUD generik.

Pengelolaan akun dilakukan melalui alur yang sesuai:

- registrasi Pendonor;
- Kelola Petugas oleh Admin;
- akses Admin menggunakan akun dengan peran `ADMIN`.

Cara teknis pembuatan akun Admin awal ditentukan pada tahap implementasi, bukan pada dokumen role/UI ini.

---

# 6. Matriks Hak Akses Ringkas

| Fitur | Pendonor | Petugas | Admin |
|---|---|---|---|
| Dashboard | Ya | Ya | Ya |
| Profil Pendonor sendiri | Kelola terbatas | Lihat saat diperlukan | Tidak perlu |
| Jadwal pelayanan | Lihat | Lihat | CRUD |
| Pemesanan donor | Kelola milik sendiri | Lihat/proses | Tidak perlu |
| Kuesioner pradonasi | Isi milik sendiri | Lihat | Kelola pertanyaan |
| Kode check-in | Lihat/tunjukkan | Gunakan untuk check-in | Tidak |
| Check-in | Datang dan memberikan kode | Proses | Tidak |
| Seleksi donor | Menjalani | Catat/putuskan | Tidak |
| Golongan darah Pendonor baru | Tidak mengubah bebas | Catat jika telah dikonfirmasi | Tidak |
| Penyumbangan | Menjalani | Catat | Tidak |
| Unit komponen darah | Tidak | Kelola operasional | Tidak |
| Pelulusan unit | Tidak | Proses | Tidak |
| Distribusi unit | Tidak | Catat | Tidak |
| Persediaan | Tidak | Lihat | Tidak perlu operasional |
| Ambang persediaan | Tidak | Lihat | CRUD |
| Pemanggilan Pendonor | Tidak | Pilih calon | Tidak |
| Pemberitahuan | Baca | Buat/kirim | Tidak |
| Kelola akun Petugas | Tidak | Tidak | CRUD |

---

# 7. Aturan UI

1. Menu harus berbeda sesuai peran.
2. Pengguna tidak boleh melihat menu yang tidak menjadi kewenangannya.
3. Hak akses tidak boleh hanya disembunyikan dari UI; route/controller juga harus dilindungi.
4. Jangan membuat menu langsung untuk semua 15 tabel.
5. Jangan membuat tombol aksi yang belum memiliki implementasi.
6. Jangan membuat halaman placeholder.
7. Frontend boleh sederhana.
8. Gunakan form, tabel, badge status, alert sukses/gagal, dan navigasi yang mudah didemonstrasikan.
9. Prioritas utama adalah alur CRUD dan proses bisnis berfungsi dengan benar.
10. Jangan menambahkan fitur di luar scope hanya untuk mempercantik demo.

---

# 8. Prinsip Otorisasi

Setiap request harus diperiksa berdasarkan:

- pengguna sudah login;
- `akun.status_akun = AKTIF`;
- peran pengguna;
- kepemilikan data jika pengguna adalah Pendonor.

Pendonor hanya boleh membaca atau mengubah resource yang menjadi miliknya sendiri.

Petugas hanya menjalankan fungsi operasional yang menjadi kewenangannya.

Admin hanya menjalankan fungsi administrasi dan konfigurasi yang menjadi kewenangannya.

Jika terdapat konflik antara kenyamanan UI dengan batasan akses, batasan akses memiliki prioritas.

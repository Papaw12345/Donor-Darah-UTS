# 04 - Business Rules

## Status Dokumen

Dokumen ini merupakan source of truth untuk aturan bisnis dan validasi proses pada proyek Sistem Informasi Manajemen Donor dan Persediaan Darah pada satu Unit Donor Darah (UDD).

Dokumen ini melengkapi:

- `01_PROJECT_CONTEXT.md`
- `02_DATABASE_SCHEMA.md`
- `03_ROLE_AND_UI_SCOPE.md`

Jika implementasi kode bertentangan dengan aturan pada dokumen ini, kode yang harus diperbaiki.

Jangan mengubah aturan bisnis untuk menyesuaikan implementasi.

Jika suatu kondisi belum diatur secara eksplisit, jangan menebak atau menambahkan aturan baru tanpa keputusan lebih lanjut.

---

# Basis Penguncian Aturan

Aturan pada dokumen ini disusun berdasarkan:

- proses bisnis dan batasan sistem pada `draft basdat.docx`;
- ERD/DBML final proyek;
- `Kamus_Data_ERD_Donor_Darah_Final(2).xlsx`, khususnya sheet `Aturan Bisnis`;
- keputusan yang sudah dikunci selama perancangan proyek;
- Permenkes Nomor 91 Tahun 2015 untuk ketentuan Whole Blood yang digunakan dalam scope proyek.

Aturan klinis di luar yang sudah dipilih untuk prototype tidak boleh ditambahkan secara otomatis.

---

# A. Aturan Inti Final

Bagian ini mempertahankan 25 aturan bisnis final dari kamus data proyek.

## 1. Donor Ulang - Interval

Donor ulang Whole Blood memiliki interval minimal 2 bulan.

Entitas terkait:

- `pendonor`
- `penyumbangan`

Implementasi utama:

Validasi Laravel.

---

## 2. Donor Ulang - Frekuensi Tahunan

Frekuensi donor Whole Blood maksimal:

- 6 kali per tahun untuk laki-laki;
- 4 kali per tahun untuk perempuan.

Entitas terkait:

- `pendonor`
- `penyumbangan`

Implementasi utama:

Validasi Laravel.

---

## 3. Donor Ulang - Data Turunan dari Riwayat

Pemeriksaan interval dan frekuensi donor diperoleh dari riwayat penyumbangan, bukan disimpan sebagai field tetap.

Entitas terkait:

- `pendonor`
- `penyumbangan`

Implementasi utama:

Perhitungan sistem.

---

## 4. Kuesioner - Satu Kuesioner per Pemesanan

Satu pemesanan donor maksimal memiliki satu kuesioner pradonasi.

Entitas terkait:

- `pemesanan_donor`
- `kuesioner_pradonasi`

Implementasi utama:

Constraint basis data.

---

## 5. Seleksi - Satu Seleksi per Pemesanan

Satu pemesanan donor maksimal memiliki satu seleksi donor.

Entitas terkait:

- `pemesanan_donor`
- `seleksi_donor`

Implementasi utama:

Constraint basis data.

---

## 6. Penyumbangan - Hanya dari Seleksi LAYAK

Penyumbangan hanya dapat dilakukan jika keputusan seleksi adalah `LAYAK`.

Entitas terkait:

- `seleksi_donor`
- `penyumbangan`

Implementasi utama:

Validasi Laravel.

---

## 7. Penyumbangan - Volume Whole Blood dan Berat Badan

Untuk penyumbangan Whole Blood:

- volume 350 mL memerlukan berat badan minimal 45 kg;
- volume 450 mL memerlukan berat badan minimal 55 kg.

Entitas terkait:

- `seleksi_donor`
- `penyumbangan`

Implementasi utama:

Validasi Laravel.

---

## 8. Donor Ulang - Penyumbangan Gagal

Penyumbangan yang gagal tidak dihitung sebagai donor berhasil dalam riwayat donor ulang.

Entitas terkait:

- `penyumbangan`

Implementasi utama:

Perhitungan sistem.

---

## 9. Unit Komponen - Sumber dari Penyumbangan Berhasil

Satu penyumbangan dapat menghasilkan satu atau lebih unit komponen darah jika berhasil.

Entitas terkait:

- `penyumbangan`
- `unit_komponen_darah`

Implementasi utama:

Validasi Laravel.

---

## 10. Unit Komponen - Status Awal

Unit komponen baru dimulai dengan status `MENUNGGU_PELULUSAN`.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Default/constraint basis data.

---

## 11. Unit Komponen - Lulus

Unit yang lulus dapat berstatus `TERSEDIA`.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Validasi Laravel.

---

## 12. Unit Komponen - Ditolak

Unit yang tidak memenuhi persyaratan dapat berstatus `DITOLAK`.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Validasi Laravel.

---

## 13. Unit Komponen - Distribusi

Unit yang keluar dari persediaan UDD dicatat sebagai `DIDISTRIBUSIKAN`.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Validasi Laravel.

---

## 14. Unit Komponen - Kedaluwarsa

Kedaluwarsa tidak disimpan sebagai status tersendiri.

Kondisi kedaluwarsa ditentukan dari `tanggal_kedaluwarsa`.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Perhitungan sistem.

---

## 15. Persediaan - Unit yang Dihitung

Persediaan dihitung dari unit dengan:

- `status_unit = TERSEDIA`; dan
- belum melewati `tanggal_kedaluwarsa`.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Perhitungan sistem.

---

## 16. Persediaan - Tidak Ada Stok Manual

Jumlah persediaan tidak disimpan sebagai angka stok manual.

Entitas terkait:

- `unit_komponen_darah`

Implementasi utama:

Perhitungan sistem.

---

## 17. Persediaan - Kondisi Rendah

Kondisi persediaan rendah terjadi jika jumlah unit tersedia berada pada atau di bawah `jumlah_minimum` pada `ambang_persediaan`.

Secara logika:

`jumlah_persediaan <= jumlah_minimum`

Entitas terkait:

- `unit_komponen_darah`
- `ambang_persediaan`

Implementasi utama:

Perhitungan sistem.

---

## 18. Persediaan - Kombinasi Ambang Unik

Kombinasi jenis komponen dan golongan darah pada ambang persediaan harus unik.

Entitas terkait:

- `ambang_persediaan`
- `jenis_komponen_darah`
- `golongan_darah`

Implementasi utama:

Constraint basis data.

---

## 19. Kuesioner - Satu Jawaban per Pertanyaan

Satu pertanyaan hanya boleh memiliki satu jawaban dalam satu kuesioner.

Entitas terkait:

- `kuesioner_pradonasi`
- `jawaban_kuesioner`
- `pertanyaan_kuesioner`

Implementasi utama:

Constraint basis data.

---

## 20. Akun dan Profil - PENDONOR

Akun dengan peran `PENDONOR` memiliki profil Pendonor.

Entitas terkait:

- `akun`
- `pendonor`

Implementasi utama:

Hak akses dan alur registrasi aplikasi.

---

## 21. Akun dan Profil - PETUGAS

Akun dengan peran `PETUGAS` memiliki profil Petugas.

Entitas terkait:

- `akun`
- `petugas`

Implementasi utama:

Hak akses dan pengelolaan akun oleh Admin.

---

## 22. Akun dan Profil - ADMIN

Akun dengan peran `ADMIN` tidak memerlukan tabel profil Admin tersendiri.

Entitas terkait:

- `akun`

Implementasi utama:

Hak akses.

---

## 23. Pemberitahuan - Pengirim dan Penerima

Pemberitahuan dikirim dalam aplikasi oleh Petugas kepada Pendonor.

Entitas terkait:

- `pemberitahuan`
- `petugas`
- `pendonor`

Implementasi utama:

Hak akses aplikasi.

---

## 24. Ruang Lingkup - Tidak Ada Pencocokan Pasien

Sistem tidak melakukan pencocokan darah donor dengan pasien.

Implementasi utama:

Batasan ruang lingkup sistem.

---

## 25. Ruang Lingkup - Satu UDD

Sistem hanya mencakup satu Unit Donor Darah (UDD).

Implementasi utama:

Batasan ruang lingkup sistem.

---

# B. Klarifikasi Operasional yang Sudah Dikunci

Bagian ini menjelaskan keputusan proses yang sudah disepakati agar implementasi tidak menafsirkan 25 aturan inti secara berbeda.

## 26. Kelayakan Berdasarkan Riwayat Bukan Keputusan Medis Akhir

Hasil pemeriksaan interval dan frekuensi donor ulang hanya menentukan apakah Pendonor berdasarkan riwayat sudah dapat mencoba melakukan donor kembali.

Untuk Pendonor ulang, pembuatan pemesanan donor baru harus mengikuti hasil pemeriksaan interval dan frekuensi berdasarkan riwayat.

Pendonor yang belum memiliki riwayat penyumbangan berhasil sebelumnya tidak dikenai pembatasan interval dan frekuensi donor ulang untuk kesempatan donor pertamanya, tetapi tetap wajib menjalani kuesioner dan seleksi di UDD.

Hasil pemeriksaan riwayat bukan keputusan kelayakan medis akhir.

Keputusan medis akhir tetap ditentukan oleh Petugas pada proses seleksi.

---

## 27. Riwayat Donor Ulang Menggunakan Penyumbangan Berhasil

Perhitungan donor ulang menggunakan riwayat penyumbangan yang berhasil.

Penyumbangan dengan `hasil_penyumbangan = GAGAL` tidak digunakan sebagai donor berhasil untuk menentukan interval atau frekuensi donor ulang.

Untuk prototype ini, ketentuan interval 2 bulan dan batas frekuensi 6/4 diterapkan pada donor Whole Blood.

---

## 28. Kuesioner Diisi pada Setiap Kesempatan Donor

Pendonor mengisi kuesioner pradonasi untuk setiap kesempatan donor.

Kuesioner terkait dengan satu `pemesanan_donor`.

Kuesioner digunakan oleh Petugas sebagai salah satu informasi dalam proses seleksi dan bukan sebagai transaksi review medis terpisah.

---

## 29. Kode Check-in Dibuat Setelah Prasyarat Pradonasi

Kode check-in unik dibuat setelah proses penjadwalan/pemesanan dan pengisian kuesioner yang diperlukan telah selesai.

Kode check-in terhubung dengan `pemesanan_donor`.

Petugas menggunakan kode tersebut ketika Pendonor datang untuk membuka data kunjungan yang sesuai.

Tidak ada tabel check-in terpisah.

---

## 30. Keputusan Seleksi

Keputusan seleksi hanya memiliki tiga nilai:

- `LAYAK`
- `DITUNDA`
- `DITOLAK`

Makna operasional:

- `LAYAK`: Pendonor dapat melanjutkan ke proses penyumbangan.
- `DITUNDA`: Pendonor belum dapat melakukan donor pada kesempatan tersebut karena kondisi yang bersifat sementara.
- `DITOLAK`: proses donor pada kesempatan tersebut tidak dapat dilanjutkan berdasarkan hasil seleksi dan penilaian Petugas.

`DITOLAK` pada sistem ini tidak boleh ditafsirkan otomatis sebagai penolakan permanen seumur hidup.

Jika keputusan `DITUNDA` atau `DITOLAK`, penyumbangan pada kesempatan tersebut tidak dilanjutkan.

---

## 31. Tidak Ada Field ditunda_sampai

Sistem tidak menyimpan field `ditunda_sampai`.

Informasi donor berikutnya pada prototype dihitung dari riwayat penyumbangan berhasil dan ketentuan donor ulang yang digunakan dalam scope.

Jangan menambahkan field tanggal penundaan tanpa keputusan perubahan schema.

---

## 32. Golongan Darah Pendonor Baru

`pendonor.id_golongan_darah` boleh `NULL` ketika golongan darah Pendonor belum terkonfirmasi.

Untuk Pendonor baru yang belum mempunyai golongan darah terkonfirmasi, Petugas dapat mencatat golongan darah ABO dan Rhesus setelah dikonfirmasi dalam proses pelayanan.

Pendonor tidak boleh mengubah bebas golongan darah yang telah dikonfirmasi.

---

## 33. Pemesanan Aktif Ganda Dicegah pada Aplikasi

Tidak ada UNIQUE constraint `(id_pendonor, id_jadwal)` pada tabel `pemesanan_donor`.

Alasannya, riwayat pembatalan dan pemesanan ulang pada slot yang sama tetap harus dapat disimpan.

Aplikasi harus mencegah Pendonor memiliki pemesanan aktif ganda untuk kombinasi Pendonor dan jadwal yang sama.

Validasi ini dilakukan di layer aplikasi dengan mempertimbangkan status pemesanan yang masih aktif.

Jangan menambahkan UNIQUE `(id_pendonor, id_jadwal)` ke basis data.

---

## 34. Kapasitas Jadwal adalah Data Turunan

Jumlah pemesanan pada suatu jadwal dan sisa kapasitas tidak disimpan sebagai field tetap.

Sisa kapasitas dihitung dari:

- nilai `jadwal_pelayanan.kapasitas`; dan
- jumlah pemesanan yang masih berlaku untuk jadwal tersebut.

Jangan menambahkan field `jumlah_pemesanan`, `booked_count`, atau `sisa_kapasitas` tanpa perubahan rancangan.

---

## 35. Penyumbangan Berhasil dan Unit Komponen

Hanya penyumbangan dengan `hasil_penyumbangan = BERHASIL` yang dapat dilanjutkan ke pencatatan unit komponen darah.

Unit komponen yang digunakan dalam scope prototype:

- Whole Blood (`WB`)
- Packed Red Cell (`PRC`)
- Thrombocyte Concentrate (`TC`)
- Fresh Frozen Plasma (`FFP`)

Proses teknis laboratorium untuk menghasilkan komponen-komponen tersebut berada di luar rincian sistem.

---

## 36. Status Unit Mengikuti Alur yang Dikunci

Alur status unit yang diperbolehkan dalam scope:

`MENUNGGU_PELULUSAN` → `TERSEDIA`

atau:

`MENUNGGU_PELULUSAN` → `DITOLAK`

Unit `TERSEDIA` yang keluar dari persediaan dapat menjadi:

`TERSEDIA` → `DIDISTRIBUSIKAN`

`KEDALUWARSA` bukan status unit.

Unit yang telah kedaluwarsa tidak dihitung sebagai persediaan tersedia meskipun nilai `status_unit` masih `TERSEDIA`.

---

## 37. Pelulusan Hanya Mencatat Hasil dalam Scope

Sistem tidak memodelkan metode pemeriksaan laboratorium secara rinci.

Setelah proses pemeriksaan yang berada di luar rincian sistem selesai, Petugas hanya mencatat hasil pelulusan unit.

Jangan menambahkan alat, reagen, metode IMLTD, atau workflow laboratorium rinci tanpa perubahan scope.

---

## 38. Persediaan Dipantau per Komponen dan Golongan Darah

Persediaan dipantau berdasarkan kombinasi:

- `jenis_komponen_darah`; dan
- `golongan_darah`.

Ambang minimum disimpan pada `ambang_persediaan` untuk setiap kombinasi tersebut.

Nilai ambang merupakan parameter operasional UDD dan bukan angka standar nasional yang di-hardcode.

---

## 39. Identifikasi Pendonor saat Persediaan Rendah

Jika persediaan berada pada atau di bawah ambang batas, sistem dapat menampilkan Pendonor yang:

- memiliki golongan darah yang relevan; dan
- berdasarkan riwayat sudah memenuhi interval dan frekuensi donor ulang.

Daftar tersebut digunakan untuk membantu pemanggilan Pendonor.

Daftar tersebut bukan hasil pencocokan klinis donor dengan pasien.

---

## 40. Pemberitahuan Hanya di Dalam Aplikasi

Petugas memilih Pendonor yang relevan dan membuat pemberitahuan di dalam aplikasi.

Tidak ada pengiriman melalui:

- SMS;
- WhatsApp;
- email.

Sistem tidak menyimpan status pengiriman eksternal.

---

## 41. Riwayat dan Informasi Donor Berikutnya adalah Data Turunan

Pendonor dapat melihat riwayat penyumbangan dan perkiraan waktu donor berikutnya.

Perkiraan waktu donor berikutnya tidak disimpan sebagai field tetap.

Nilainya dihitung dari riwayat penyumbangan berhasil dan ketentuan donor ulang yang digunakan pada prototype.

---

# C. Aturan Implementasi Berdasarkan Layer

## Constraint Basis Data

Aturan berikut harus terutama dijaga oleh schema/constraint:

- satu pemesanan maksimal satu kuesioner;
- satu pemesanan maksimal satu seleksi;
- satu seleksi maksimal satu penyumbangan;
- satu pertanyaan maksimal satu jawaban dalam satu kuesioner;
- kombinasi ABO dan Rhesus unik;
- kombinasi jenis komponen dan golongan darah pada ambang unik;
- kode check-in unik ketika memiliki nilai;
- nomor unit unik;
- relasi PK/FK dan nullable mengikuti `02_DATABASE_SCHEMA.md`.

## Validasi Laravel

Aturan berikut tidak cukup hanya mengandalkan constraint basis data:

- donor ulang minimal 2 bulan;
- batas frekuensi donor ulang laki-laki/perempuan;
- pengecekan pemesanan aktif ganda;
- kapasitas jadwal;
- penyumbangan hanya dari seleksi `LAYAK`;
- validasi 350 mL/45 kg dan 450 mL/55 kg;
- unit hanya dibuat dari penyumbangan berhasil;
- perubahan status unit sesuai alur yang dikunci;
- pemanggilan Pendonor hanya saat kondisi dan hak akses sesuai.

## Perhitungan Sistem

Data berikut harus dihitung, bukan disimpan sebagai salinan:

- donor terakhir yang berhasil;
- jumlah donor berhasil dalam periode yang relevan;
- kelayakan donor ulang berdasarkan riwayat;
- perkiraan waktu donor berikutnya;
- jumlah pemesanan jadwal;
- sisa kapasitas;
- jumlah persediaan;
- kondisi persediaan rendah;
- kondisi kedaluwarsa unit.

## Hak Akses

- Pendonor hanya mengakses data miliknya sendiri.
- Petugas menangani fungsi operasional UDD.
- Admin menangani administrasi dan konfigurasi.
- Akun `NONAKTIF` tidak boleh menjalankan fungsi aplikasi.

Detail menu dan pembagian akses mengikuti `03_ROLE_AND_UI_SCOPE.md`.

---

# D. Batasan yang Tidak Boleh Diubah Diam-diam

Sistem hanya digunakan pada satu UDD.

Sistem tidak mencakup:

- data pasien;
- permintaan darah rumah sakit;
- pencocokan donor dengan pasien;
- crossmatch;
- proses transfusi;
- koordinasi antar-UDD;
- metode laboratorium secara rinci;
- metode IMLTD secara rinci;
- alat dan reagen laboratorium;
- proses teknis pemisahan komponen;
- cold chain;
- pemantauan suhu penyimpanan;
- distribusi secara rinci sampai rumah sakit/pasien;
- pembayaran;
- QR code;
- barcode;
- SMS;
- WhatsApp;
- email notification;
- apheresis.

Jika implementasi memerlukan salah satu fitur tersebut, berhenti dan minta keputusan pengguna sebelum mengubah scope.

---

# E. Checklist Sebelum Fitur Dianggap Benar

Sebelum menyatakan suatu fitur selesai, periksa:

- tidak ada aturan bisnis yang diubah;
- tidak ada field turunan baru;
- tidak ada tabel baru untuk konsep yang sudah dihitung;
- hak akses sesuai peran;
- penyumbangan tidak dibuat dari seleksi selain `LAYAK`;
- penyumbangan gagal tidak dihitung sebagai donor berhasil;
- unit tidak dibuat dari penyumbangan gagal;
- unit baru tidak langsung dihitung sebagai persediaan;
- unit kedaluwarsa tidak dihitung sebagai persediaan;
- stok tidak dapat diedit manual;
- persediaan rendah menggunakan kondisi `jumlah_persediaan <= jumlah_minimum`;
- pemberitahuan hanya dibuat oleh Petugas untuk Pendonor;
- pemanggilan Pendonor bukan pencocokan klinis;
- tidak ada fitur di luar scope yang ditambahkan tanpa instruksi.

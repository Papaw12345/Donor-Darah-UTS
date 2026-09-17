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

### Keputusan Proyek Phase 7D - Tanggal Acuan Kelayakan

Ketentuan interval 2 bulan, batas frekuensi 6/4, penggunaan penyumbangan berhasil, dan perlakuan bagi Pendonor tanpa riwayat berhasil di atas tetap merupakan aturan yang sudah bersumber dari dokumen awal. Rincian tanggal acuan berikut merupakan keputusan proyek Phase 7D atas hal yang sebelumnya belum ditentukan:

- kelayakan pemesanan dievaluasi terhadap `jadwal_pelayanan.tanggal` yang dipilih, bukan terhadap timestamp pengiriman request pemesanan;
- periode frekuensi tahunan adalah tahun kalender yang memuat tanggal jadwal terpilih;
- perhitungan frekuensi menggunakan penyumbangan `BERHASIL` dalam tahun kalender tersebut sampai dengan tanggal jadwal terpilih;
- interval 2 bulan dihitung antara penyumbangan `BERHASIL` paling akhir sebelum atau pada tanggal jadwal terpilih dan tanggal jadwal terpilih.

Keputusan ini tidak menambahkan aturan medis lain dan tidak mengubah bahwa hasil pemeriksaan riwayat bukan keputusan kelayakan medis akhir.

---

## 28. Kuesioner Diisi pada Setiap Kesempatan Donor

### Aturan yang Sudah Bersumber

Pendonor mengisi kuesioner pradonasi untuk setiap kesempatan donor.

Kuesioner terkait dengan satu `pemesanan_donor`.

Kuesioner digunakan oleh Petugas sebagai salah satu informasi dalam proses seleksi dan bukan sebagai transaksi review medis terpisah.

Aturan yang sudah bersumber juga menetapkan bahwa:

- kuesioner dibuat untuk pemesanan milik Pendonor;
- pertanyaan dengan `status_aktif = true` ditampilkan dan jawabannya disimpan pada `jawaban_kuesioner`;
- satu `pemesanan_donor` maksimal memiliki satu `kuesioner_pradonasi`;
- satu pertanyaan maksimal memiliki satu `jawaban_kuesioner` dalam satu kuesioner;
- pengisian pradonasi yang diperlukan selesai sebelum kode check-in dibuat.

Cardinality tersebut tetap dijaga oleh UNIQUE `kuesioner_pradonasi.id_pemesanan` dan UNIQUE (`jawaban_kuesioner.id_kuesioner`, `jawaban_kuesioner.id_pertanyaan`) yang sudah ada. Sumber awal tidak menentukan batas waktu perubahan jawaban; rincian satu kali kirim di bawah merupakan keputusan proyek, bukan klaim dari sumber awal.

### Keputusan Proyek Phase 7E

Ketentuan berikut memformalkan rincian operasional yang sebelumnya belum ditentukan secara tepat.

#### Kepemilikan dan Kelayakan Pemesanan

- Pendonor hanya dapat membuat dan melihat kuesioner yang berkaitan dengan `pemesanan_donor` miliknya sendiri.
- Kepemilikan harus ditentukan dari Pendonor yang sedang terautentikasi. Identifier Pendonor yang dikirim client tidak boleh memberi akses ke pemesanan atau kuesioner Pendonor lain.
- Kuesioner baru hanya dapat dikirim apabila pemesanan dimiliki Pendonor terautentikasi, memiliki `status_pemesanan = TERJADWAL`, tanggal jadwal terkait belum lewat menurut WIB (`Asia/Jakarta`), dan jadwal tidak berstatus administratif `DIBATALKAN`.
- Jadwal yang kemudian berstatus `DITUTUP` tidak dengan sendirinya menggugurkan pemesanan yang sudah valid untuk penyelesaian kuesioner.

#### Satu Kali Kirim dan Retensi Riwayat

- Prototype menggunakan satu kali pengiriman kuesioner. Setelah pengiriman berhasil, Pendonor dapat melihat kuesioner beserta jawaban yang tersimpan, tetapi tidak mengubah atau mengganti jawaban tersebut.
- Tidak ada draft, penggantian jawaban, atau riwayat revisi jawaban.
- Kuesioner dan jawaban yang sudah berhasil disimpan tidak dihapus otomatis apabila status pemesanan kemudian berubah atau pemesanan dibatalkan.
- Pemesanan baru dengan `id_pemesanan` baru adalah kesempatan donor baru dan mempunyai kuesionernya sendiri.

#### Himpunan Pertanyaan dan Representasi Jawaban

- Pada pengiriman yang berhasil, setiap pertanyaan dalam himpunan pertanyaan berwenang saat ini dengan `status_aktif = true` wajib mempunyai tepat satu jawaban. Kuesioner yang hanya terisi sebagian tidak boleh dibuat.
- Pertanyaan aktif ditampilkan menurut `urutan`, kemudian `id_pertanyaan` sebagai pembeda deterministik. `kategori` hanya digunakan untuk tampilan atau pengelompokan dan tidak mengubah aturan validasi.
- Untuk `jenis_jawaban = YA_TIDAK`, jawaban kanonis yang disimpan harus tepat `YA` atau `TIDAK`.
- Untuk `jenis_jawaban = TEKS`, jawaban wajib tidak kosong setelah trimming.
- Tidak ada jenis jawaban baru dan tipe kolom `jawaban_kuesioner.jawaban` tidak berubah.
- Server wajib menentukan sendiri himpunan pertanyaan aktif yang berwenang pada saat pengiriman dan tidak mempercayai himpunan identifier pertanyaan dari client.
- Key jawaban yang dikirim harus tepat sama dengan himpunan pertanyaan aktif pada saat pengiriman. Jika himpunan pertanyaan berubah sejak form dimuat, pengiriman ditolak secara terkendali dan Pendonor harus memuat ulang form.
- Jawaban untuk pertanyaan yang tidak diharapkan tidak boleh dibuang diam-diam, dan jawaban yang tidak dikirim tidak boleh dibuat diam-diam.
- Apabila tidak ada pertanyaan aktif, sistem tidak membuat `kuesioner_pradonasi` kosong dan memberitahukan bahwa kuesioner sedang tidak tersedia.

#### Pengiriman Atomik

Pengiriman kuesioner yang berhasil merupakan satu transaksi atomik yang:

1. memverifikasi kepemilikan terautentikasi dan kelayakan pemesanan;
2. mencegah kuesioner kedua untuk pemesanan yang sama;
3. menentukan dan memvalidasi himpunan pertanyaan aktif;
4. membuat satu `kuesioner_pradonasi`; dan
5. membuat tepat satu `jawaban_kuesioner` untuk setiap pertanyaan aktif.

Row pemesanan diperlakukan sebagai titik serialisasi untuk pengiriman ganda yang berlangsung bersamaan. Constraint UNIQUE yang sudah ada tetap menjadi lapisan integritas terakhir; Phase 7E tidak menambahkan UNIQUE baru.

`kuesioner_pradonasi.waktu_pengisian` menyatakan waktu pengiriman kuesioner berhasil dengan konvensi timestamp aplikasi yang sudah digunakan.

#### Batas Implementasi Phase 7E

Phase 7E tidak:

- menghasilkan `kode_checkin`;
- mengisi `waktu_checkin`;
- mengubah `status_pemesanan`;
- mengimplementasikan check-in Petugas;
- mengimplementasikan review medis kuesioner;
- mengimplementasikan seleksi atau penyumbangan;
- menambahkan status draft, versioning kuesioner, atau riwayat revisi jawaban;
- menambahkan snapshot teks pertanyaan; atau
- mengubah schema.

Schema yang ada tetap mereferensikan row `pertanyaan_kuesioner` saat ini. Snapshot atau tabel versioning tidak ditambahkan hanya untuk mempertahankan redaksi lama pertanyaan.

---

## 29. Kode Check-in Dibuat Setelah Prasyarat Pradonasi

### Aturan yang Sudah Bersumber

Kode check-in unik dibuat setelah proses penjadwalan/pemesanan dan pengisian kuesioner yang diperlukan telah selesai.

Kode check-in terhubung dengan `pemesanan_donor`.

Petugas menggunakan kode tersebut ketika Pendonor datang untuk membuka data kunjungan yang sesuai.

Tidak ada tabel check-in terpisah.

Kode ditampilkan sebagai teks biasa dan bukan QR code atau barcode. Keunikan kode, relasi kode dengan pemesanan, urutan pembuatannya setelah prasyarat pradonasi, dan penggunaan kode oleh Petugas merupakan aturan yang sudah bersumber, bukan keputusan baru Phase 7F.

### Keputusan Lifecycle Phase 7D yang Tetap Berlaku

Check-in Petugas yang berhasil pada phase berikutnya melakukan transisi:

`TERJADWAL` -> `CHECK_IN`

Pada proses tersebut `waktu_checkin` diisi. Phase 7F hanya mengatur kode milik Pendonor dan tidak menjalankan lifecycle check-in Petugas.

### Keputusan Proyek Phase 7F

Ketentuan berikut memformalkan rincian operasional pembuatan dan penampilan kode yang sebelumnya belum ditentukan secara tepat.

#### Kepemilikan dan Prasyarat Kode Baru

- Pendonor hanya dapat melihat atau menghasilkan kode untuk `pemesanan_donor` miliknya sendiri.
- Kepemilikan harus ditentukan dari Pendonor yang sedang terautentikasi. Identifier Pendonor yang dikirim client tidak boleh memberi akses ke pemesanan atau kode check-in Pendonor lain.
- Kode baru hanya dapat dihasilkan apabila pemesanan dimiliki Pendonor terautentikasi, memiliki `status_pemesanan = TERJADWAL`, sudah mempunyai `kuesioner_pradonasi`, tanggal jadwal terkait belum lewat menurut WIB (`Asia/Jakarta`), dan jadwal tidak berstatus administratif `DIBATALKAN`.
- Jadwal yang berstatus `DITUTUP` tidak dengan sendirinya menggugurkan pemesanan `TERJADWAL` yang sudah valid untuk pembuatan kode.
- Untuk prototype ini, keberadaan `kuesioner_pradonasi` yang berhasil tersimpan bagi pemesanan merupakan bukti pada layer aplikasi bahwa prasyarat kuesioner telah selesai. Tabel atau status penyelesaian pradonasi lain tidak ditambahkan.

#### Satu Kode, Format, dan Tabrakan

- Setelah `kode_checkin` mempunyai nilai, akses atau permintaan pembuatan berulang mempertahankan nilai yang sama. Kode tidak dibuat ulang, diganti, atau dirotasi.
- Riwayat dan versioning kode tidak diperkenalkan.
- Kode yang dihasilkan mempunyai format `UDD-` diikuti tepat 12 karakter heksadesimal huruf besar. `UDD-A84C21EF07B9` hanya merupakan contoh bentuk. Panjang total kode adalah 16 karakter dan tetap berada dalam batas `VARCHAR(50)` yang sudah ada.
- Format khusus tersebut merupakan keputusan proyek Phase 7F, sedangkan keunikan dan tampilan teks biasa tetap merupakan aturan yang sudah bersumber.
- Kandidat kode harus berasal dari sumber acak yang sesuai untuk kode non-sekuensial dan tidak diturunkan langsung dari ID Pendonor atau ID pemesanan.
- Jika kandidat sama dengan kode yang sudah dipakai, aplikasi mencoba kandidat baru dan tidak pernah menimpa kode pemesanan lain.
- UNIQUE `pemesanan_donor.kode_checkin` yang sudah ada tetap menjadi lapisan integritas terakhir. Phase 7F tidak menambahkan UNIQUE baru.

#### Pemisahan GET dan POST

- `GET` menampilkan halaman atau status kode. Jika kode sudah ada, kode tersebut ditampilkan; jika belum ada, halaman menunjukkan apakah pembuatan tersedia.
- `POST` melakukan pembuatan kode untuk pemesanan yang memenuhi syarat.
- `GET` tidak membuat, mengganti, atau merotasi kode. Pemisahan aksi ini merupakan keputusan implementasi Phase 7F, bukan aturan dari sumber awal.

#### Pembuatan Atomik dan Retensi

Pembuatan kode dilakukan dalam satu transaksi atomik dengan row `pemesanan_donor` sebagai titik serialisasi:

1. memverifikasi kepemilikan terautentikasi;
2. mengunci row pemesanan target;
3. memeriksa ulang apakah `kode_checkin` sudah ada;
4. memeriksa ulang keberadaan kuesioner dan seluruh kelayakan pemesanan; dan
5. menghasilkan serta menyimpan satu kode unik.

Permintaan pembuatan serentak untuk pemesanan yang sama tidak boleh mengganti atau merotasi kode yang telah dibuat. Jika pemeriksaan setelah penguncian menemukan kode sudah ada, kode tersebut tetap dipertahankan.

Kode yang sudah dihasilkan tidak dihapus otomatis hanya karena status pemesanan kemudian berubah atau tanggal jadwal berlalu. Tidak ada pembersihan historis otomatis. Retensi kode tidak menyatakan bahwa pemesanan yang dibatalkan atau selesai memenuhi syarat untuk check-in Petugas; aturan kelayakan check-in berada pada Phase 8.

#### Tanpa Efek Samping Check-in

Pembuatan atau penampilan kode tidak:

- mengisi `waktu_checkin`;
- mengubah `status_pemesanan`;
- melakukan transisi `TERJADWAL` menjadi `CHECK_IN`;
- membuat `seleksi_donor`;
- mengubah data kuesioner; atau
- mengubah pemesanan lain.

Segera setelah kode dibuat untuk pemesanan normal, `status_pemesanan` tetap `TERJADWAL`, `waktu_checkin` tetap `NULL`, dan `kode_checkin` berisi kode yang baru dihasilkan.

#### Batas Implementasi Phase 7F

Phase 7F tidak mengimplementasikan:

- check-in Petugas atau pencarian pemesanan berdasarkan kode oleh Petugas;
- pengisian `waktu_checkin` atau transisi status ke `CHECK_IN`;
- tampilan kuesioner bagi Petugas;
- seleksi atau penyumbangan;
- QR code, barcode, atau scanner;
- field kedaluwarsa kode;
- tabel riwayat kode, versioning kode, atau tabel token; maupun
- perubahan schema.

Fungsi operasional tersebut tetap menjadi bagian Phase 8 atau phase berikutnya.

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

### Keputusan Proyek Phase 7H - Informasi Donor Berikutnya

Phase 7H menggunakan aturan donor ulang yang sama dengan Phase 7D, tetapi menjawab pertanyaan yang berbeda. Phase 7D menguji kelayakan terhadap tanggal jadwal yang dipilih, sedangkan Phase 7H menghitung informasi kondisi saat ini dengan tanggal acuan hari ini menurut WIB (`Asia/Jakarta`).

Aturan perhitungannya dikunci sebagai berikut:

1. Kepemilikan data berasal dari Pendonor yang sedang terautentikasi.
2. Riwayat yang digunakan hanya `penyumbangan` dengan `hasil_penyumbangan = BERHASIL` milik Pendonor tersebut dan bertanggal sampai dengan tanggal acuan.
3. Penyumbangan `GAGAL` tidak memengaruhi interval atau frekuensi donor ulang.
4. Jika terdapat penyumbangan berhasil sebelumnya, tanggal pemenuhan interval adalah tanggal donor berhasil paling akhir ditambah 2 bulan kalender menggunakan perilaku tanpa overflow yang sama seperti Phase 7D.
5. Jumlah donor tahunan dihitung dari penyumbangan `BERHASIL` pada tahun kalender tanggal acuan sampai dengan tanggal acuan.
6. Batas tahunan adalah 6 untuk `LAKI_LAKI` dan 4 untuk `PEREMPUAN`.
7. Jika jumlah donor tahun berjalan masih di bawah batas, tidak ada penundaan tambahan dari sisi frekuensi.
8. Jika jumlah donor tahun berjalan sudah mencapai batas, tanggal paling awal dari sisi frekuensi adalah 1 Januari tahun kalender berikutnya.
9. Perkiraan tanggal donor berikutnya adalah tanggal paling awal yang sekaligus tidak lebih awal dari tanggal acuan, memenuhi interval 2 bulan, dan memenuhi batas frekuensi tahunan.
10. Secara operasional, tanggal donor berikutnya adalah nilai maksimum dari tanggal acuan, tanggal pemenuhan interval bila ada, dan tanggal pemenuhan frekuensi bila batas tahunan sudah tercapai.
11. Pendonor yang belum memiliki penyumbangan `BERHASIL` tidak dikenai pembatasan interval atau frekuensi donor ulang untuk kesempatan pertamanya.
12. Keputusan seleksi `DITUNDA` atau `DITOLAK` tidak menghasilkan tanggal `ditunda_sampai`, dan penyumbangan `GAGAL` tidak menggantikan tanggal donor berhasil terakhir.
13. Jika tanggal perkiraan sama dengan tanggal acuan, Pendonor ditampilkan sebagai sudah dapat mencoba donor kembali sekarang berdasarkan riwayat.
14. Hasil perhitungan bukan keputusan kelayakan medis akhir. Kuesioner, pemeriksaan, dan keputusan seleksi Petugas tetap berlaku.
15. Perhitungan bersifat read-only dan tidak boleh membuat atau mengubah record workflow.
16. Donor terakhir berhasil, jumlah donor berhasil, status donor ulang, dan tanggal donor berikutnya tetap dihitung saat diperlukan dan tidak disimpan sebagai field baru.

Phase 7H tidak mengubah aturan Phase 7D, tidak menambahkan aturan medis baru, tidak membuat tabel atau kolom baru, dan tidak menggunakan `seleksi_donor.alasan_keputusan` sebagai sumber tanggal penundaan.

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

### Keputusan Proyek Phase 7D - Status Aktif dan Pemesanan Ulang

Klarifikasi berikut merupakan keputusan proyek Phase 7D atas istilah dan perilaku yang sebelumnya belum ditentukan secara rinci:

- untuk keperluan workflow dan UI prototype, status pemesanan aktif adalah `TERJADWAL` dan `CHECK_IN`;
- `SELESAI`, `DIBATALKAN`, dan `TIDAK_HADIR` tidak didefinisikan sebagai status aktif;
- untuk kombinasi `(id_pendonor, id_jadwal)` yang sama, record berstatus `TERJADWAL`, `CHECK_IN`, `SELESAI`, atau `TIDAK_HADIR` menghalangi pembuatan pemesanan baru;
- hanya record berstatus `DIBATALKAN` yang tidak menghalangi pemesanan ulang untuk kombinasi Pendonor dan jadwal yang sama.

Aturan penghalang pemesanan ulang untuk jadwal yang sama sengaja lebih luas daripada definisi status aktif: `SELESAI` dan `TIDAK_HADIR` bukan status aktif, tetapi tetap menghalangi pemesanan ulang pada jadwal yang sama.

Pemesanan pada nilai `id_jadwal` yang berbeda tidak otomatis dilarang oleh aturan duplikasi ini. Prototype tidak memperkenalkan aturan satu pemesanan per hari, konsep jadwal alternatif, atau pembatalan otomatis atas pemesanan lain.

Pendonor hanya dapat melihat dan mengelola pemesanan miliknya sendiri. Kepemilikan harus berasal dari Pendonor yang sedang terautentikasi; identifier Pendonor yang dikirim oleh client tidak boleh memberi kewenangan atas pemesanan Pendonor lain.

#### Keputusan Proyek Phase 7 Completion - Ringkasan Pemesanan Aktif

Definisi status aktif Phase 7D juga digunakan oleh Dashboard Pendonor.

Aturan ringkasan dashboard dikunci sebagai berikut:

1. Sumber data hanya pemesanan milik Pendonor yang sedang terautentikasi.
2. Pemesanan aktif yang ditampilkan adalah seluruh row dengan `status_pemesanan` tepat `TERJADWAL` atau `CHECK_IN`.
3. `SELESAI`, `DIBATALKAN`, dan `TIDAK_HADIR` tidak termasuk ringkasan aktif.
4. Dashboard tidak menambahkan kondisi tanggal untuk mengubah definisi status aktif. State yang tersimpan tetap ditampilkan sampai workflow mengubah status pemesanan.
5. Lebih dari satu pemesanan aktif dapat tampil apabila berada pada `id_jadwal` yang berbeda. Tidak ada konsep pemesanan utama atau pemilihan satu row secara arbitrer.
6. Urutan data adalah `jadwal_pelayanan.tanggal ASC`, `jadwal_pelayanan.jam_mulai ASC`, `pemesanan_donor.waktu_pemesanan ASC`, kemudian `pemesanan_donor.id_pemesanan ASC`.
7. Ringkasan hanya menampilkan informasi yang diperlukan untuk navigasi dan pemahaman state, minimal tanggal jadwal, jam pelayanan, dan status pemesanan.
8. Aksi pengelolaan tetap dilakukan melalui halaman Pemesanan Donor dan bukan melalui penambahan mutation workflow baru pada dashboard.
9. Akses dashboard bersifat hanya-baca dan tidak mengubah `status_pemesanan`, `waktu_checkin`, `kode_checkin`, atau data transaksi lain.
10. Jika tidak ada pemesanan aktif, aplikasi menampilkan empty state tanpa membuat data sintetis.
11. Ketentuan ini tidak mengubah definisi kapasitas jadwal dan tidak menambahkan field turunan.
12. Phase 7 Completion tidak mengubah schema dan tidak menambahkan aturan medis atau lifecycle baru.

---

## 34. Kapasitas Jadwal adalah Data Turunan

Jumlah pemesanan pada suatu jadwal dan sisa kapasitas tidak disimpan sebagai field tetap.

Sisa kapasitas dihitung dari nilai `jadwal_pelayanan.kapasitas` dikurangi jumlah pemesanan pada jadwal tersebut yang menggunakan kapasitas.

Status pemesanan yang menggunakan kapasitas adalah:

- `TERJADWAL`;
- `CHECK_IN`;
- `SELESAI`;
- `TIDAK_HADIR`.

Pemesanan dengan status `DIBATALKAN` tidak menggunakan kapasitas sehingga tidak mengurangi sisa kapasitas.

Definisi penggunaan kapasitas ini tidak sekaligus menentukan status yang dianggap aktif untuk aturan pencegahan pemesanan aktif ganda. Aturan pemesanan aktif ditentukan terpisah pada alur Pemesanan Donor.

Jangan menambahkan field `jumlah_pemesanan`, `booked_count`, atau `sisa_kapasitas` tanpa perubahan rancangan.

### Keputusan Proyek Phase 7D - Ketersediaan dan Siklus Pemesanan

Pemesanan baru hanya dapat dibuat apabila seluruh kondisi berikut terpenuhi:

- `status_jadwal = DIBUKA`;
- tanggal jadwal sama dengan atau setelah tanggal hari ini menurut WIB (`Asia/Jakarta`);
- sisa kapasitas lebih dari `0` berdasarkan definisi kapasitas di atas;
- kelayakan donor ulang terhadap tanggal jadwal terpilih terpenuhi;
- aturan pemesanan ulang untuk kombinasi Pendonor dan jadwal yang sama terpenuhi.

Saat pemesanan yang valid dibuat:

- `status_pemesanan` diisi `TERJADWAL`;
- `waktu_pemesanan` diisi pada saat pembuatan menggunakan konvensi timestamp aplikasi yang sudah digunakan;
- `kode_checkin` tetap `NULL`;
- `waktu_checkin` tetap `NULL`.

Kode check-in dibuat pada alur pradonasi/check-in berikutnya dan bukan pada pembuatan pemesanan Phase 7D.

Pendonor hanya dapat membatalkan pemesanan miliknya sendiri apabila statusnya `TERJADWAL` dan tanggal jadwal terkait belum lewat menurut WIB (`Asia/Jakarta`). Pembatalan melakukan transisi:

`TERJADWAL` -> `DIBATALKAN`

Pemesanan berstatus `CHECK_IN`, `SELESAI`, `TIDAK_HADIR`, atau `DIBATALKAN` tidak dapat dibatalkan oleh Pendonor. Karena `DIBATALKAN` tidak menggunakan kapasitas, transisi pembatalan melepaskan kontribusi kapasitas dari pemesanan tersebut.

Untuk menghilangkan ambiguitas bagi phase berikutnya, check-in yang berhasil melakukan transisi:

`TERJADWAL` -> `CHECK_IN`

Pada check-in, `waktu_checkin` diisi. Check-in tidak otomatis mengubah pemesanan lain milik Pendonor yang sama, dan prototype tidak memperkenalkan aturan satu check-in per hari. Ketentuan ini hanya memformalkan lifecycle; pembuatan kode check-in dan fungsi check-in Petugas tidak diimplementasikan pada Phase 7D.

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

### Keputusan Proyek Phase 7I - Penerimaan Pemberitahuan Pendonor

Phase 7I mengimplementasikan sisi penerima Pendonor dan tidak mengambil alih fungsi pembuatan atau pengiriman pemberitahuan milik Petugas pada Phase 8.

Aturan operasionalnya dikunci sebagai berikut:

1. Target data selalu Pendonor yang terhubung dengan akun `PENDONOR` yang sedang terautentikasi.
2. Pendonor hanya dapat melihat row `pemberitahuan` dengan `id_pendonor` miliknya sendiri.
3. Identifier Pendonor dari client tidak dapat mengubah ownership atau memperluas akses.
4. Daftar pemberitahuan diurutkan berdasarkan `waktu_dibuat DESC`, kemudian `id_pemberitahuan DESC`.
5. `GET` daftar dan detail tidak mengubah database dan tidak mengisi `waktu_dibaca`.
6. Status belum dibaca diturunkan dari `waktu_dibaca IS NULL`; status sudah dibaca diturunkan dari `waktu_dibaca IS NOT NULL`.
7. Penandaan sebagai sudah dibaca dilakukan melalui aksi `PATCH` yang hanya dapat menargetkan pemberitahuan milik Pendonor terautentikasi.
8. Penandaan pertama mengisi `waktu_dibaca`. Jika field tersebut sudah terisi, request ulang mempertahankan nilai yang ada.
9. Aksi penandaan bersifat idempotent: request berulang tidak membuat row baru, tidak merotasi timestamp yang telah tersimpan, dan tidak mengubah isi pesan, pengirim, penerima, atau `waktu_dibuat`.
10. Untuk request serentak terhadap pemberitahuan yang sama, implementasi harus menjaga satu hasil akhir yang konsisten. Transaction dan row locking sederhana dapat digunakan sebagai titik serialisasi agar request kedua membaca state terbaru.
11. Pemberitahuan milik Pendonor lain harus ditolak melalui pemeriksaan ownership server-side dan tidak boleh membocorkan isi pesan.
12. Dashboard menghitung jumlah belum dibaca dari row milik Pendonor dengan `waktu_dibaca IS NULL`.
13. Dashboard menampilkan maksimal 3 pemberitahuan terbaru milik Pendonor menggunakan urutan yang sama dengan halaman daftar.
14. Dashboard menyediakan link nyata menuju daftar seluruh pemberitahuan.
15. Phase 7I tidak membuat atau mengirim row `pemberitahuan`; data tersebut berasal dari proses Petugas yang diimplementasikan pada Phase 8 atau dari fixture test yang valid.
16. Pendonor tidak memiliki aksi edit pesan, hapus pesan, kirim pesan, tandai belum dibaca, atau tandai semua sudah dibaca.
17. Phase 7I tidak menambahkan tabel, kolom, enum, index, status pengiriman, kanal eksternal, realtime notification infrastructure, maupun perubahan schema.

---

## 41. Riwayat dan Informasi Donor Berikutnya adalah Data Turunan

### Fakta yang Sudah Bersumber

Pendonor dapat melihat riwayat penyumbangannya sendiri, tetapi tidak dapat mengubahnya. Data operasional penyumbangan dicatat oleh Petugas dan riwayat berasal dari row transaksi `penyumbangan`, bukan dibuat atau diedit oleh Pendonor.

Row `penyumbangan` dapat mempunyai `hasil_penyumbangan = BERHASIL` atau `hasil_penyumbangan = GAGAL`. Penyumbangan gagal dapat tetap tersimpan sebagai transaksi, tetapi tidak diperlakukan sebagai donor berhasil untuk perhitungan donor ulang. Fakta tersebut tidak berarti sumber awal secara eksplisit menentukan bahwa halaman riwayat umum harus menampilkan transaksi gagal; semantik tampilan itu ditentukan secara terpisah pada keputusan proyek Phase 7G.

Pendonor hanya dapat mengakses datanya sendiri. Kepemilikan penyumbangan dapat ditelusuri melalui relasi `penyumbangan` -> `seleksi_donor` -> `pemesanan_donor` -> `pendonor`.

Informasi Donor Berikutnya merupakan fitur Pendonor yang terpisah dari Riwayat Donor.

### Aturan Donor Ulang yang Sudah Dikunci

Aturan pada bagian Donor Ulang tetap berlaku tanpa perubahan: perhitungan interval dan frekuensi menggunakan penyumbangan `BERHASIL`, sedangkan penyumbangan `GAGAL` tidak dihitung sebagai donor berhasil. Ketentuan interval Whole Blood minimal 2 bulan, batas tahunan 6 kali untuk laki-laki dan 4 kali untuk perempuan, serta tanggal acuan yang telah dikunci pada Phase 7D tidak dihitung ulang atau ditafsirkan kembali oleh Phase 7G.

Perkiraan waktu donor berikutnya tidak disimpan sebagai field tetap.

Nilainya dihitung dari riwayat penyumbangan berhasil dan ketentuan donor ulang yang digunakan pada prototype.

Perhitungan perkiraan tanggal donor berikutnya, ringkasan kelayakan saat ini, jumlah penyumbangan berhasil tahunan, interval countdown, dan keputusan dapat donor kembali tetap berada pada Phase 7H — Informasi Donor Berikutnya. Phase 7G tidak mengimplementasikan perhitungan tersebut.

### Keputusan Proyek Phase 7G

Ketentuan berikut merupakan keputusan proyek Phase 7G untuk rincian operasional halaman Riwayat Donor yang sebelumnya belum ditentukan secara tepat.

#### Kepemilikan dan Sumber Row Riwayat

- Riwayat milik Pendonor terautentikasi hanya memuat row `penyumbangan` yang rantai kepemilikannya memenuhi `penyumbangan.id_seleksi` -> `seleksi_donor.id_seleksi` -> `seleksi_donor.id_pemesanan` -> `pemesanan_donor.id_pemesanan` -> `pemesanan_donor.id_pendonor` -> `pendonor.id_pendonor` milik Pendonor terautentikasi.
- Identifier `id_pendonor` yang dikirim client tidak boleh menentukan kepemilikan atau membuka riwayat Pendonor lain.
- Row riwayat hanya ada jika row `penyumbangan` yang nyata tersimpan. `pemesanan_donor` tanpa penyumbangan, `CHECK_IN` tanpa penyumbangan, `seleksi_donor` tanpa penyumbangan, keputusan `LAYAK`, `DITUNDA`, atau `DITOLAK`, pemesanan dibatalkan, dan ketidakhadiran bukan row Riwayat Donor dan tidak boleh disintesis menjadi riwayat.

#### Hasil Penyumbangan dan Tampilan

- Halaman Riwayat Donor umum menampilkan seluruh row `penyumbangan` milik Pendonor, baik `BERHASIL` maupun `GAGAL`. Keputusan untuk tidak menyembunyikan transaksi gagal yang nyata merupakan semantik tampilan Phase 7G, bukan klaim bahwa sumber awal secara eksplisit mewajibkan halaman menampilkan penyumbangan gagal.
- Setiap row minimal menampilkan `waktu_pengambilan`, `volume_ml`, `hasil_penyumbangan`, dan `alasan_gagal` ketika berlaku.
- Jika `volume_ml` bernilai `NULL`, tampilan menggunakan representasi netral seperti `-`. Jika `alasan_gagal` bernilai `NULL`, tampilan tidak wajib memberikan isi penjelasan.
- Pengukuran medis rinci dari seleksi, kredensial Petugas, dan data internal lain yang tidak berkaitan tidak ditampilkan hanya karena dapat dijangkau melalui relasi.
- Riwayat diurutkan secara deterministik berdasarkan `waktu_pengambilan DESC`, kemudian `id_penyumbangan DESC` sebagai tie-breaker. Transaksi terbaru tampil lebih dahulu.
- Jika Pendonor tidak memiliki row `penyumbangan`, halaman menampilkan empty state yang terkendali dan tidak menyintesis row riwayat.

#### Hanya-Baca dan Tanpa Efek Samping

Riwayat Donor hanya dapat dilihat oleh Pendonor. Phase 7G tidak menyediakan aksi untuk:

- membuat, mengubah, atau menghapus penyumbangan;
- mengubah `hasil_penyumbangan`, `volume_ml`, `waktu_pengambilan`, atau `alasan_gagal`;
- mengubah seleksi; atau
- mengubah status pemesanan.

Akses halaman menggunakan `GET` dan tidak memperbarui row basis data, mengubah status pemesanan, membuat seleksi, penyumbangan, atau unit komponen, menandai proses apa pun sebagai selesai, maupun mengubah data pemberitahuan.

#### Batas Implementasi Phase 7G

Phase 7G menggunakan rantai transaksi yang sudah ada dan tidak menambahkan tabel, kolom, snapshot riwayat, cache riwayat, total donor tersimpan, tanggal donor terakhir tersimpan, tanggal donor berikutnya tersimpan, atau requirement UNIQUE, foreign key, maupun index baru.

Phase 7G juga tidak menambahkan ekspor PDF/Excel, grafik atau statistik, sertifikat, badge atau reward, kerangka pencarian/filter, editing, penghapusan, arsitektur pagination, riwayat unit komponen, riwayat medis seleksi yang terperinci, fitur Petugas, perhitungan donor berikutnya, atau pemberitahuan. Frontend tetap minimal.

---

## 42. Pembaruan Profil Pendonor

Target pembaruan selalu profil `pendonor` yang terhubung dengan akun `PENDONOR` yang sedang terautentikasi. Nilai `id_pendonor` atau `id_akun` yang dikirim oleh client tidak boleh menentukan target atau kepemilikan profil.

Hanya field berikut yang boleh disimpan melalui pembaruan Profil Saya:

- `nama_lengkap`;
- `tempat_lahir`;
- `alamat`;
- `nomor_telepon`;
- `pekerjaan`;
- `alamat_kantor`.

Field `nik`, `nomor_donor`, `jenis_kelamin`, `tanggal_lahir`, `id_golongan_darah`, dan `akun.email` harus tetap tidak berubah meskipun request yang dibuat secara khusus menyertakan field tersebut.

Nullable pada `pekerjaan` dan `alamat_kantor` tetap mengikuti schema. Aturan ini tidak mengubah perilaku registrasi Pendonor dan tidak menambahkan perilaku perubahan password.

---

## 43. Keputusan Proyek Phase 8A - Dashboard Petugas

Phase 8A mengimplementasikan Dashboard Petugas sebagai ringkasan operasional hanya-baca. Ketentuan pada bagian ini memperjelas istilah dashboard yang sebelumnya belum mempunyai definisi teknis presisi dan tidak mengubah aturan inti donor maupun persediaan.

### Lingkup dan Akses

1. Dashboard Petugas tetap menggunakan route `GET /petugas` dengan nama `petugas.home`.
2. Akses hanya untuk akun terautentikasi dengan `status_akun = AKTIF` dan `peran = PETUGAS`.
3. Akun Petugas yang mengakses dashboard harus dapat ditelusuri ke satu row `petugas`.
4. Data dashboard menggambarkan kondisi satu UDD secara keseluruhan dan tidak dibatasi hanya pada row yang dibuat atau dicatat oleh Petugas yang sedang login.
5. Dashboard bersifat hanya-baca dan tidak menjalankan transisi state atau mutasi transaksi apa pun.

### Tanggal Acuan

Tanggal acuan untuk ringkasan yang berbasis hari adalah tanggal hari ini menurut WIB (`Asia/Jakarta`).

Perbandingan tanggal pada persediaan juga menggunakan tanggal acuan tersebut.

### Kegiatan Donor Hari Ini

Kegiatan donor hari ini bersumber dari `pemesanan_donor` yang terhubung ke `jadwal_pelayanan` dengan:

`jadwal_pelayanan.tanggal = tanggal_acuan`

Dashboard menampilkan hitungan untuk masing-masing status:

- `TERJADWAL`;
- `CHECK_IN`;
- `SELESAI`;
- `TIDAK_HADIR`.

`DIBATALKAN` tidak dihitung sebagai kegiatan donor operasional hari tersebut.

Hitungan ini hanya merupakan agregasi untuk tampilan dashboard. Phase 8A tidak menetapkan kapan status berubah menjadi `SELESAI` atau `TIDAK_HADIR`, tidak mengubah definisi lifecycle, dan tidak memperbarui row `pemesanan_donor`.

### Pendonor yang Sedang Diproses

Jumlah Pendonor yang sedang diproses didefinisikan sebagai:

`COUNT(DISTINCT pemesanan_donor.id_pendonor)`

untuk row dengan:

`status_pemesanan = CHECK_IN`

Hitungan ini tidak diberi filter tanggal jadwal. Tujuannya adalah menampilkan state workflow yang masih tersimpan sebagai `CHECK_IN`, termasuk jika terdapat row yang belum ditutup oleh proses operasional.

Dashboard tidak melakukan auto-complete, auto-no-show, atau koreksi status hanya karena tanggal jadwal sudah berlalu.

### Total Persediaan Tersedia

Jumlah unit yang saat ini dihitung sebagai persediaan adalah unit dengan:

- `status_unit = TERSEDIA`; dan
- `tanggal_kedaluwarsa >= tanggal_acuan`.

Unit yang kedaluwarsa, `MENUNGGU_PELULUSAN`, `DITOLAK`, atau `DIDISTRIBUSIKAN` tidak dihitung sebagai persediaan tersedia.

Total persediaan merupakan nilai turunan dan tidak disimpan sebagai angka stok.

### Ringkasan Persediaan Rendah

Persediaan rendah tetap menggunakan konfigurasi pada `ambang_persediaan`.

Untuk setiap row `ambang_persediaan`, sistem menghitung jumlah unit tersedia dengan kombinasi yang sama berdasarkan:

- `id_jenis_komponen`; dan
- `id_golongan_darah`.

Kombinasi diklasifikasikan rendah apabila:

`jumlah_persediaan <= jumlah_minimum`

Aturan tambahan untuk Dashboard Phase 8A:

1. Stok `0` merupakan nilai persediaan yang valid dan tetap dibandingkan dengan `jumlah_minimum`.
2. Hanya kombinasi yang mempunyai konfigurasi `ambang_persediaan` yang dapat diklasifikasikan low-stock.
3. Sistem tidak menggunakan nilai ambang default atau hardcoded untuk kombinasi yang belum dikonfigurasi.
4. Dashboard menampilkan jumlah kombinasi low-stock.
5. Dashboard menampilkan seluruh kombinasi low-stock tanpa limit arbitrer.
6. Setiap row low-stock minimal menampilkan jenis komponen, golongan darah ABO/Rhesus, jumlah persediaan, dan jumlah minimum.
7. Jika belum ada konfigurasi ambang atau tidak ada kombinasi yang rendah, dashboard menampilkan empty state yang terkendali.
8. Tidak ada CRUD nilai stok atau perubahan nilai `jumlah_minimum` dari Dashboard Petugas.

### Batas Implementasi Phase 8A

Phase 8A hanya menyediakan ringkasan dan navigasi yang sudah mempunyai tujuan route nyata.

Phase 8A tidak mengimplementasikan lebih awal:

- check-in Pendonor;
- tampilan operasional kuesioner bagi Petugas;
- seleksi donor;
- pencatatan penyumbangan;
- pencatatan unit komponen;
- pelulusan unit;
- distribusi unit;
- halaman persediaan lengkap;
- halaman persediaan rendah lengkap;
- pemanggilan Pendonor; atau
- pembuatan pemberitahuan.

Menu atau tombol menuju fitur Phase 8 berikutnya tidak ditampilkan sebelum route dan fungsinya benar-benar tersedia.

Phase 8A juga tidak menambahkan:

- tabel atau kolom baru;
- field statistik atau stok;
- status baru;
- migration baru;
- custom index;
- cache persediaan;
- cron untuk kedaluwarsa;
- chart atau grafik;
- realtime/WebSocket;
- AJAX atau SPA;
- repository/service/DTO architecture khusus dashboard; atau
- perubahan schema.

Query agregasi Dashboard Phase 8A dapat tetap berada secara sederhana pada controller. Apabila perhitungan persediaan yang sama kemudian digunakan oleh beberapa fitur, konsistensinya dievaluasi pada Phase 9 dan dapat dipusatkan secara sederhana tanpa over-engineering.

---
## 44. Keputusan Proyek Phase 8B - Check-in Petugas

Phase 8B memformalkan rincian operasional check-in Petugas yang sebelumnya belum ditentukan secara presisi.

Aturan yang sudah bersumber tetap berlaku:

- Petugas menggunakan `kode_checkin` ketika Pendonor datang untuk menemukan kunjungan terkait;
- kode terhubung dengan `pemesanan_donor`;
- check-in berhasil melakukan transisi `TERJADWAL` -> `CHECK_IN`;
- `waktu_checkin` diisi pada proses tersebut;
- tidak ada tabel check-in baru.

Ketentuan berikut merupakan keputusan proyek Phase 8B untuk implementasi operasional.

### Akses dan Scope

1. Fungsi check-in hanya dapat digunakan oleh akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan relasi profil `petugas` yang valid.
2. Check-in merupakan fungsi satu UDD. Pemesanan target tidak dibatasi oleh Petugas mana yang sedang login.
3. Identifier Petugas atau Pendonor yang dikirim client tidak menentukan target check-in.
4. Schema tidak mempunyai `id_petugas_checkin`; Phase 8B tidak menambahkan field tersebut hanya untuk mencatat pelaksana check-in.

### Route dan Alur UI

Phase 8B menggunakan alur sederhana dua langkah:

1. `GET /petugas/check-in`
   - menampilkan form kode;
   - bila `kode_checkin` diberikan, melakukan lookup hanya-baca;
   - menampilkan data kunjungan dan kelayakan check-in;
   - tidak mengubah database.

2. `POST /petugas/check-in`
   - mengonfirmasi dan menjalankan mutation check-in untuk kode yang dikirim;
   - seluruh syarat diperiksa ulang pada server;
   - setelah hasil berhasil atau idempotent, digunakan pola Post/Redirect/Get.

Lookup tidak boleh menjalankan check-in otomatis.

Dashboard Petugas dapat menampilkan link `Check-in Pendonor` setelah route GET tersebut benar-benar tersedia.

### Normalisasi dan Pencarian Kode

Input `kode_checkin`:

1. dihapus whitespace pada awal dan akhirnya;
2. dinormalisasi menjadi huruf besar;
3. harus mempunyai format `UDD-` diikuti tepat 12 karakter heksadesimal.

Contoh bentuk:

`UDD-A84C21EF07B9`

Kode dengan format tidak valid atau kode yang tidak ditemukan ditolak secara terkendali dan tidak mengubah row bisnis.

Lookup menggunakan `pemesanan_donor.kode_checkin` yang sudah mempunyai constraint UNIQUE.

Phase 8B tidak menambahkan scanner, QR code, barcode, tabel token, versioning kode, atau field kedaluwarsa kode.

### Data yang Ditampilkan Saat Lookup

Untuk kode yang ditemukan, Petugas dapat melihat data kunjungan minimal:

- identitas Pendonor yang diperlukan;
- jadwal pelayanan;
- data/status pemesanan;
- kode check-in terkait; dan
- informasi bahwa `kuesioner_pradonasi` terkait tersedia atau tidak.

Phase 8B belum merupakan halaman review jawaban kuesioner.

Jawaban rinci kuesioner untuk Petugas tetap berada pada subphase berikutnya.

### Kelayakan Check-in Baru

Check-in baru hanya dapat dilakukan apabila seluruh kondisi berikut terpenuhi:

1. `pemesanan_donor.status_pemesanan = TERJADWAL`;
2. `pemesanan_donor.kode_checkin` sesuai dengan kode target;
3. satu `kuesioner_pradonasi` terkait sudah tersimpan;
4. `jadwal_pelayanan.tanggal` tepat sama dengan tanggal hari ini menurut WIB (`Asia/Jakarta`);
5. `jadwal_pelayanan.status_jadwal` bukan `DIBATALKAN`;
6. `waktu_checkin` masih `NULL`.

Pemesanan dengan tanggal jadwal sebelum hari ini atau setelah hari ini ditolak untuk check-in baru.

Phase 8B tidak menambahkan window waktu berdasarkan `jam_mulai` atau `jam_selesai`.

Jadwal `DITUTUP` tidak dengan sendirinya menolak check-in untuk pemesanan `TERJADWAL` yang sudah valid, memiliki kode, dan memenuhi syarat lain.

Pemesanan berstatus:

- `SELESAI`;
- `DIBATALKAN`; atau
- `TIDAK_HADIR`

tidak dapat ditransisikan menjadi `CHECK_IN`.

### Mutation Check-in

Check-in baru yang berhasil hanya melakukan:

`status_pemesanan: TERJADWAL -> CHECK_IN`

dan mengisi:

`waktu_checkin`

dengan waktu keberhasilan check-in menurut konvensi timestamp aplikasi.

Check-in tidak:

- mengubah atau menghapus `kode_checkin`;
- mengubah kuesioner atau jawaban;
- mengubah jadwal;
- mengubah pemesanan lain milik Pendonor yang sama;
- membuat `seleksi_donor`;
- membuat `penyumbangan`;
- membuat unit komponen darah;
- membuat pemberitahuan; atau
- memperbarui profil Pendonor.

Prototype tetap tidak mempunyai aturan satu check-in per Pendonor per hari.

### Idempotency

Apabila kode yang sama dikirim kembali setelah check-in berhasil dan row sudah mempunyai:

- `status_pemesanan = CHECK_IN`; dan
- `waktu_checkin` tidak `NULL`,

request berikutnya diperlakukan sebagai pengiriman ulang idempotent.

Dalam kondisi tersebut:

- tidak ada update kedua;
- `waktu_checkin` pertama tidak diganti;
- kode tidak diganti atau dihapus;
- response memberikan informasi bahwa pemesanan sudah check-in.

Idempotency ini berlaku untuk double-click, resubmit form, refresh melalui alur lama, atau dua tab yang mengirim aksi yang sama.

### State Tidak Konsisten

Aplikasi tidak memperbaiki data workflow yang tidak konsisten secara diam-diam.

Contoh state tidak konsisten:

- `status_pemesanan = CHECK_IN` tetapi `waktu_checkin IS NULL`;
- `status_pemesanan = TERJADWAL` tetapi `waktu_checkin IS NOT NULL`.

State seperti itu ditolak secara terkendali dan tidak dimutasi oleh Phase 8B.

### Transaksi dan Concurrency

Mutation check-in dilakukan secara atomik dalam transaksi basis data.

Row `pemesanan_donor` target menjadi titik serialisasi:

1. server menormalisasi dan memvalidasi kode;
2. transaction dimulai;
3. row dengan `kode_checkin` target diambil dan dikunci menggunakan `lockForUpdate()`;
4. relasi jadwal dan keberadaan kuesioner diperiksa;
5. seluruh syarat check-in diperiksa ulang setelah row lock diperoleh;
6. bila masih `TERJADWAL` dan seluruh syarat terpenuhi, status diubah menjadi `CHECK_IN` dan `waktu_checkin` diisi;
7. transaction di-commit.

Jika dua Petugas melakukan check-in terhadap kode yang sama secara bersamaan:

- request pertama yang memperoleh lock melakukan mutation;
- request lain menunggu lock;
- setelah lock tersedia, request berikutnya membaca state terbaru;
- jika state sudah `CHECK_IN` dengan `waktu_checkin` terisi, request berikutnya menjadi no-op idempotent.

Tidak ada row check-in kedua dan tidak ada timestamp check-in kedua.

### Batas Phase 8B

Phase 8B hanya mengimplementasikan pencarian kunjungan dan check-in.

Phase 8B belum mengimplementasikan:

- review rinci jawaban kuesioner Petugas;
- seleksi donor;
- pembaruan golongan darah dari proses seleksi;
- penyumbangan;
- pencatatan unit;
- pelulusan;
- distribusi;
- halaman persediaan lengkap;
- pemanggilan Pendonor;
- pembuatan pemberitahuan.

Menu atau tombol Phase 8C dan seterusnya tidak ditampilkan sebelum route dan fungsi masing-masing benar-benar tersedia.

Phase 8B juga tidak menambahkan:

- tabel atau field baru;
- migration baru;
- custom index;
- status pemesanan baru;
- tabel log check-in;
- field Petugas check-in;
- QR code;
- barcode;
- scanner;
- AJAX atau SPA;
- service/repository/DTO architecture; atau
- dependency baru.

---
## 45. Keputusan Proyek Phase 8C - Tampilan Kuesioner Petugas

Phase 8C adalah pembacaan data kuesioner yang sudah tersimpan, bukan transaksi review medis baru.

### Akses dan Target

1. Hanya akun aktif dengan `peran = PETUGAS` dan profil `petugas` valid yang dapat mengakses fitur.
2. Route hanya `GET /petugas/pemesanan/{pemesanan}/kuesioner`, bernama `petugas.kuesioner.show`.
3. `pemesanan_donor` pada route adalah target utama. Relasi Pendonor, jadwal, kuesioner, jawaban, dan pertanyaan ditentukan server-side.
4. Identifier Petugas, Pendonor, kuesioner, atau kode check-in tambahan dari client tidak mengubah target.

### State yang Dapat Dilihat

Tampilan diperbolehkan apabila:

- `status_pemesanan = CHECK_IN` dan `waktu_checkin IS NOT NULL`; atau
- `status_pemesanan = SELESAI` dan `waktu_checkin IS NOT NULL`.

`TERJADWAL`, `DIBATALKAN`, dan `TIDAK_HADIR` ditolak sebagai jalur operasional Phase 8C.

Tanggal jadwal dan `status_jadwal` tidak menjadi filter historical setelah check-in berhasil.

### Data Kuesioner

Pemesanan yang dapat dilihat harus mempunyai `kuesioner_pradonasi` dan minimal satu `jawaban_kuesioner`.

Jika kuesioner atau seluruh jawaban tidak tersedia pada state yang seharusnya sudah lengkap, aplikasi memperlakukan kondisi tersebut sebagai data tidak konsisten dan tidak membuat data pengganti.

Daftar tampilan berasal dari row `jawaban_kuesioner` yang tersimpan, bukan dari seluruh pertanyaan aktif saat ini.

Pertanyaan yang sekarang `NONAKTIF` tetap ditampilkan apabila mempunyai jawaban historical.

Urutan jawaban:

1. `pertanyaan_kuesioner.urutan` ascending;
2. `pertanyaan_kuesioner.id_pertanyaan` ascending.

Untuk `YA_TIDAK`, tampilkan nilai tersimpan `YA` atau `TIDAK`.

Untuk `TEKS`, tampilkan teks jawaban yang tersimpan.

Tidak ada skor, klasifikasi risiko, diagnosis, rekomendasi, atau business rule medis tambahan.

Schema tetap tidak menyimpan snapshot redaksi atau versioning pertanyaan. Jawaban historical tetap memakai row `pertanyaan_kuesioner` yang saat ini direferensikan.

### Read-Only dan Navigasi

Request Phase 8C tidak boleh:

- mengubah status pemesanan atau `waktu_checkin`;
- mengubah kuesioner, jawaban, atau master pertanyaan;
- mengubah Pendonor atau jadwal;
- membuat `seleksi_donor`, `penyumbangan`, unit komponen, atau pemberitahuan;
- membuat status/timestamp review.

Karena fitur hanya membaca data, `DB::transaction()` dan `lockForUpdate()` tidak diperlukan.

Setelah pemesanan berhasil `CHECK_IN`, halaman Check-in dapat menampilkan link nyata `Lihat Kuesioner`.

Phase 8C belum menampilkan route atau tombol `Seleksi Donor`. Fitur tersebut tetap Phase 8D.
---

## 46. Keputusan Proyek Phase 8D - Seleksi Donor

Phase 8D mengimplementasikan pencatatan satu `seleksi_donor` untuk kunjungan Pendonor yang sudah berhasil check-in.

### Akses dan Target

1. Fungsi hanya dapat digunakan oleh akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan profil `petugas` yang valid.
2. Target utama ditentukan oleh `pemesanan_donor` pada route.
3. Identifier Petugas, Pendonor, seleksi, kuesioner, atau kode check-in tambahan dari client tidak boleh mengubah target.
4. `seleksi_donor.id_petugas` berasal dari Petugas yang sedang terautentikasi.
5. Sistem mencakup satu UDD sehingga Petugas aktif yang valid tidak dibatasi hanya pada pemesanan tertentu berdasarkan kepemilikan Petugas.

### Route

Phase 8D menggunakan dua route:

1. `GET /petugas/pemesanan/{pemesanan}/seleksi` dengan nama `petugas.seleksi.show`;
2. `POST /petugas/pemesanan/{pemesanan}/seleksi` dengan nama `petugas.seleksi.store`.

Parameter `{pemesanan}` adalah target utama `PemesananDonor`. Identifier tambahan dari client tidak boleh mengganti target tersebut.

### Prasyarat Seleksi Baru

Seleksi baru hanya dapat dibuat apabila:

1. `status_pemesanan = CHECK_IN`;
2. `waktu_checkin` sudah mempunyai nilai;
3. satu `kuesioner_pradonasi` terkait tersedia;
4. kuesioner tersebut mempunyai minimal satu `jawaban_kuesioner`; dan
5. belum terdapat `seleksi_donor` untuk pemesanan tersebut.

`TERJADWAL`, `SELESAI`, `DIBATALKAN`, dan `TIDAK_HADIR` tidak memenuhi syarat untuk membuat seleksi baru.

State check-in yang seharusnya valid tetapi kehilangan kuesioner atau jawaban diperlakukan sebagai data tidak konsisten. Phase 8D tidak membuat atau memperbaiki data prasyarat tersebut secara otomatis.

Setelah check-in valid terjadi, tanggal jadwal, jam pelayanan, dan `status_jadwal` tidak menjadi filter ulang untuk pembuatan seleksi. State operasional `CHECK_IN` dengan `waktu_checkin` valid menjadi titik masuk Phase 8D.

### Data Seleksi

Satu row seleksi mencatat field schema berikut:

- `id_pemesanan`;
- `id_petugas`;
- `waktu_seleksi`;
- `berat_badan`;
- `tekanan_sistolik`;
- `tekanan_diastolik`;
- `denyut_nadi`;
- `suhu_tubuh`;
- `kadar_hb`;
- `hasil_pemeriksaan_kesehatan`;
- `keputusan_seleksi`; dan
- `alasan_keputusan`.

Enam hasil pengukuran utama dan `keputusan_seleksi` wajib mempunyai nilai. `hasil_pemeriksaan_kesehatan` dan `alasan_keputusan` tetap nullable sesuai schema.

`waktu_seleksi` ditentukan server pada saat transaksi seleksi berhasil.

Keputusan hanya:

- `LAYAK`;
- `DITUNDA`; atau
- `DITOLAK`.

Phase 8D tidak menambahkan nilai keputusan lain.

### Tidak Ada Otomatisasi Keputusan Medis

Prototype tidak menambahkan threshold medis baru untuk tekanan darah, denyut nadi, suhu tubuh, kadar Hb, berat badan, atau hasil pemeriksaan lainnya.

Aplikasi tidak:

- menentukan keputusan seleksi secara otomatis dari angka pemeriksaan;
- membuat skor kuesioner;
- membuat risk level;
- mengubah jawaban kuesioner menjadi keputusan otomatis; atau
- menambahkan aturan medis di luar specification yang sudah dikunci.

Petugas tetap menentukan keputusan berdasarkan proses seleksi.

### Golongan Darah Pendonor

Jika `pendonor.id_golongan_darah` masih `NULL`, Petugas dapat mencatat golongan darah yang telah dikonfirmasi dengan memilih row master `golongan_darah`.

Pengisian golongan darah tidak menjadi syarat wajib hanya untuk menyimpan seleksi.

Jika `pendonor.id_golongan_darah` sudah mempunyai nilai, nilai tersebut diperlakukan sebagai golongan darah yang telah dikonfirmasi dan tidak boleh diganti melalui request Phase 8D.

Jika golongan darah Pendonor diisi bersamaan dengan seleksi, perubahan Pendonor dan pembuatan seleksi dilakukan di dalam transaksi yang sama.

### Satu Seleksi dan Concurrency

Pembuatan seleksi merupakan transaksi atomik.

Row `pemesanan_donor` target digunakan sebagai titik serialisasi:

1. row pemesanan dikunci;
2. state check-in diperiksa ulang;
3. keberadaan kuesioner dan jawaban diperiksa ulang;
4. keberadaan seleksi diperiksa ulang;
5. golongan darah Pendonor dapat diisi jika sebelumnya `NULL` dan memang dikirim sebagai hasil konfirmasi;
6. satu `seleksi_donor` dibuat; dan
7. lifecycle pemesanan diterapkan sesuai keputusan seleksi.

Satu pemesanan maksimal mempunyai satu seleksi.

Jika request yang sama dikirim dua kali atau dua Petugas memproses pemesanan yang sama secara bersamaan, hanya satu request yang boleh menghasilkan row `seleksi_donor`. Request berikutnya tidak boleh mengganti atau mengedit row yang sudah tersimpan.

UNIQUE `seleksi_donor.id_pemesanan` yang sudah ada tetap menjadi lapisan integritas terakhir. Tidak ada UNIQUE atau index baru untuk Phase 8D.

Seleksi yang telah tersimpan tidak diedit, dihapus, atau diganti pada prototype.

### Lifecycle Pemesanan Setelah Seleksi

Jika `keputusan_seleksi = LAYAK`:

`CHECK_IN` -> `CHECK_IN`

Pemesanan tetap berada pada state `CHECK_IN` karena kunjungan masih dapat dilanjutkan ke proses Penyumbangan Phase 8E.

Jika `keputusan_seleksi = DITUNDA` atau `DITOLAK`:

`CHECK_IN` -> `SELESAI`

Pembuatan seleksi dan perubahan status menjadi `SELESAI` harus terjadi dalam transaksi yang sama.

`DITUNDA` dan `DITOLAK` tidak menghasilkan penyumbangan untuk kesempatan tersebut.

Phase 8D tidak membuat field `ditunda_sampai`.

### Tampilan dan Riwayat

`GET` Seleksi dapat menampilkan form untuk pemesanan yang belum mempunyai seleksi dan memenuhi prasyarat, atau menampilkan seleksi yang sudah tersimpan secara read-only.

Seleksi yang sudah tersimpan tetap merupakan riwayat dan dapat dilihat setelah tanggal jadwal lewat atau setelah lifecycle pemesanan berubah.

Halaman dapat menampilkan konteks Pendonor, kunjungan, golongan darah terkonfirmasi, dan navigasi kembali ke Kuesioner Petugas.

Setelah Phase 8D tersedia, halaman Kuesioner Petugas boleh menampilkan aksi nyata menuju Seleksi Donor. Jika seleksi sudah ada, UI boleh menggunakan aksi read-only seperti `Lihat Seleksi`.

Phase 8D belum menampilkan aksi Penyumbangan. Navigasi menuju Penyumbangan baru ditambahkan pada Phase 8E setelah route dan prosesnya benar-benar tersedia.

### Batas Implementasi Phase 8D

Phase 8D tidak:

- membuat `penyumbangan`;
- membuat unit komponen darah;
- melakukan pelulusan;
- melakukan distribusi;
- membuat pemberitahuan;
- menambahkan edit atau delete seleksi;
- menambahkan histori revisi seleksi;
- menambahkan tabel review medis;
- menambahkan threshold atau rule engine medis;
- menambahkan tabel, field, constraint, atau index baru;
- mengubah schema; atau
- memperkenalkan service/repository/DTO atau arsitektur tambahan hanya untuk proses ini.

Tidak ada perubahan schema, migration, custom index, tabel review, field review, snapshot/versioning pertanyaan, service/repository/DTO, AJAX, SPA, atau dependency baru.

---
---

## 47. Keputusan Proyek Phase 8E - Penyumbangan

Phase 8E mengimplementasikan pencatatan satu transaksi `penyumbangan` dari satu `seleksi_donor` yang `LAYAK`.

### Akses, Route, dan Target

Route Phase 8E:

1. `GET /petugas/seleksi/{seleksi}/penyumbangan` dengan nama `petugas.penyumbangan.show`;
2. `POST /petugas/seleksi/{seleksi}/penyumbangan` dengan nama `petugas.penyumbangan.store`.

Parameter `{seleksi}` adalah target authoritative `SeleksiDonor`.

Akses hanya bagi akun:

- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

`id_petugas_pencatat` berasal dari Petugas terautentikasi.

Client tidak boleh mengganti authority menggunakan `id_seleksi`, `id_pemesanan`, `id_pendonor`, `id_petugas_pencatat`, `id_penyumbangan`, atau identifier workflow lain.

### Prasyarat Penyumbangan Baru

Penyumbangan baru hanya dapat dibuat apabila:

1. `seleksi_donor.keputusan_seleksi = LAYAK`;
2. seleksi terhubung ke `pemesanan_donor`;
3. `pemesanan_donor.status_pemesanan = CHECK_IN`;
4. `pemesanan_donor.waktu_checkin` mempunyai nilai; dan
5. belum ada `penyumbangan` untuk seleksi tersebut.

Seleksi `DITUNDA` dan `DITOLAK` tidak dapat menghasilkan penyumbangan.

Setelah seleksi `LAYAK`, Phase 8E tidak memeriksa ulang tanggal jadwal, `status_jadwal`, `jam_mulai`, atau `jam_selesai` sebagai filter pencatatan penyumbangan.

### Data Penyumbangan

Phase 8E menggunakan field schema existing:

- `id_penyumbangan`;
- `id_seleksi`;
- `id_petugas_pencatat`;
- `waktu_pengambilan`;
- `volume_ml`;
- `hasil_penyumbangan`; dan
- `alasan_gagal`.

`id_seleksi` berasal dari route-bound `SeleksiDonor` dan `id_petugas_pencatat` berasal dari profil Petugas login.

`waktu_pengambilan` merupakan input operasional Petugas. Nilai tersebut tidak diganti otomatis dengan `now()`.

Phase 8E hanya memvalidasi bahwa `waktu_pengambilan` merupakan datetime yang dapat disimpan dan tidak menambah aturan temporal lain yang tidak ditentukan specification.

`hasil_penyumbangan` hanya `BERHASIL` atau `GAGAL`.

`alasan_gagal` tetap nullable sesuai schema.

### Volume Whole Blood dan Berat Badan

Volume Whole Blood prototype hanya:

- `350` mL; atau
- `450` mL.

Untuk `hasil_penyumbangan = BERHASIL`:

- `volume_ml` wajib;
- nilai harus `350` atau `450`.

Untuk `hasil_penyumbangan = GAGAL`:

- `volume_ml` boleh `NULL`;
- jika diisi, nilai harus `350` atau `450`.

Aturan berat menggunakan `seleksi_donor.berat_badan` yang sudah tersimpan:

- `350` mL memerlukan berat minimal `45` kg;
- `450` mL memerlukan berat minimal `55` kg.

Berat badan tidak diterima ulang dari request Penyumbangan.

Aturan berat tersebut berlaku setiap kali volume terkait dicatat. Phase 8E tidak menambahkan threshold medis lain.

### Satu Penyumbangan dan Concurrency

Satu `seleksi_donor` maksimal mempunyai satu `penyumbangan`.

Pembuatan penyumbangan harus atomik. Di dalam transaction:

1. `seleksi_donor` target di-query ulang berdasarkan route dan dikunci;
2. keputusan `LAYAK` diperiksa ulang;
3. `pemesanan_donor` terkait dikunci;
4. state `CHECK_IN` dan `waktu_checkin` diperiksa ulang;
5. keberadaan `penyumbangan` diperiksa ulang;
6. volume divalidasi terhadap berat badan seleksi;
7. tepat satu row `penyumbangan` dibuat; dan
8. lifecycle pemesanan diselesaikan.

Request ganda atau serentak tidak boleh menghasilkan transaksi kedua atau menimpa transaksi pertama.

UNIQUE `penyumbangan.id_seleksi` existing tetap menjadi lapisan integritas terakhir.

Phase 8E tidak menambah UNIQUE, index, constraint, atau migration baru.

Penyumbangan existing tidak diedit, dihapus, direvisi, atau diganti.

### Lifecycle Pemesanan Setelah Penyumbangan

Sebelum pencatatan penyumbangan baru, state valid adalah:

`CHECK_IN`

Setelah penyumbangan `BERHASIL` maupun `GAGAL` berhasil dicatat:

`CHECK_IN` -> `SELESAI`

Create `penyumbangan` dan perubahan `status_pemesanan` menjadi `SELESAI` harus terjadi dalam transaction yang sama.

Transisi ini merupakan keputusan workflow prototype untuk menutup satu kesempatan donor setelah proses Penyumbangan selesai dan bukan aturan medis.

Penyumbangan `GAGAL`:

- tetap merupakan transaksi nyata;
- tidak dihitung sebagai donor berhasil;
- tidak menggantikan donor berhasil terakhir; dan
- tidak dapat menghasilkan unit komponen darah.

### Tampilan dan Riwayat

`GET` Penyumbangan mempunyai dua mode:

1. form pencatatan untuk seleksi yang masih eligible dan belum mempunyai penyumbangan; atau
2. tampilan read-only untuk transaksi yang sudah tersimpan.

Penyumbangan existing tetap dapat dilihat setelah pemesanan `SELESAI`, tanggal jadwal berlalu, atau status administratif jadwal berubah.

GET existing tidak memperbaiki atau mengubah state workflow.

Setelah Phase 8E tersedia, halaman Seleksi Donor menampilkan:

- `Catat Penyumbangan` untuk seleksi `LAYAK` tanpa penyumbangan;
- `Lihat Penyumbangan` jika penyumbangan sudah ada.

Seleksi `DITUNDA` atau `DITOLAK` tidak menampilkan aksi Penyumbangan.

### Batas Implementasi Phase 8E

Phase 8E tidak:

- membuat unit komponen darah;
- melakukan pelulusan unit;
- melakukan distribusi unit;
- mengubah persediaan secara manual;
- membuat pemberitahuan;
- menyediakan edit atau delete penyumbangan;
- menambah riwayat revisi;
- menambah rule medis di luar specification;
- menambah tabel, field, constraint, index, atau migration;
- mengubah schema;
- menambah service, repository, atau DTO hanya untuk workflow ini; atau
- mengimplementasikan Phase 8F dan phase setelahnya.

Penyumbangan `BERHASIL` baru menjadi sumber untuk Phase 8F Unit Komponen Darah. Phase 8E sendiri belum membuat unit tersebut.

---

## 48. Keputusan Proyek Phase 8F - Unit Komponen Darah

Phase 8F mengimplementasikan pencatatan satu atau lebih row `unit_komponen_darah` dari satu `penyumbangan` yang berhasil.

### Akses, Route, dan Target

Phase 8F menggunakan dua route:

1. `GET /petugas/penyumbangan/{penyumbangan}/unit-komponen` dengan nama `petugas.unit-komponen.show`;
2. `POST /petugas/penyumbangan/{penyumbangan}/unit-komponen` dengan nama `petugas.unit-komponen.store`.

Parameter `{penyumbangan}` adalah target authoritative `Penyumbangan`.

Fungsi hanya dapat digunakan oleh akun:

- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

`unit_komponen_darah.id_penyumbangan` berasal dari route-bound `Penyumbangan`.

`unit_komponen_darah.id_petugas_pencatat` berasal dari profil Petugas yang sedang terautentikasi.

Identifier client seperti `id_penyumbangan`, `id_petugas_pencatat`, `id_pendonor`, `id_pemesanan`, atau identifier workflow lain tidak boleh mengganti target atau authority server.

### Prasyarat Sumber Unit

Unit baru hanya dapat dibuat apabila:

1. row `penyumbangan` target benar-benar ada; dan
2. `penyumbangan.hasil_penyumbangan = BERHASIL`.

Penyumbangan `GAGAL` tidak dapat menghasilkan unit komponen darah.

Setelah penyumbangan `BERHASIL` tersimpan, Phase 8F tidak memeriksa ulang:

- `pemesanan_donor.status_pemesanan`;
- tanggal jadwal;
- `status_jadwal`;
- `jam_mulai`; atau
- `jam_selesai`

sebagai filter pencatatan unit.

### Kardinalitas dan Satu POST

Satu `penyumbangan` yang berhasil dapat menghasilkan satu atau lebih row `unit_komponen_darah`.

Satu request `POST` Phase 8F membuat tepat satu unit.

Untuk membuat unit lain dari penyumbangan yang sama, Petugas melakukan pencatatan berikutnya melalui form yang sama.

Tidak ada batas jumlah unit per penyumbangan yang ditambahkan oleh Phase 8F.

Tidak ada UNIQUE `(id_penyumbangan, id_jenis_komponen)`.

Karena itu, dua unit berbeda dari penyumbangan yang sama boleh menggunakan jenis komponen yang sama selama `nomor_unit` berbeda.

### Field yang Dicatat

Form Phase 8F hanya menerima:

- `nomor_unit`;
- `id_jenis_komponen`;
- `id_golongan_darah`;
- `tanggal_pembuatan`; dan
- `tanggal_kedaluwarsa`.

Field authority berikut tidak berasal dari client:

- `id_penyumbangan`; dan
- `id_petugas_pencatat`.

Field lifecycle phase berikutnya juga tidak dapat ditentukan client pada Phase 8F:

- `id_petugas_pelulus`;
- `waktu_pelulusan`;
- `status_unit` selain status awal;
- `catatan_pelulusan`; dan
- `waktu_distribusi`.

### Nomor Unit

`nomor_unit`:

- wajib diisi;
- di-trim sebelum disimpan;
- maksimal 50 karakter sesuai schema;
- harus unik secara global sesuai UNIQUE existing `unit_komponen_darah.nomor_unit`.

Specification tidak menentukan format khusus `nomor_unit`.

Phase 8F tidak:

- membuat prefix tertentu;
- membuat nomor otomatis;
- menggunakan random token;
- menggunakan barcode atau QR code;
- menurunkan nomor dari ID Pendonor, ID penyumbangan, atau ID unit.

### Jenis Komponen

`id_jenis_komponen` harus mereferensikan master `jenis_komponen_darah` existing.

Jenis komponen yang berada dalam scope prototype:

- `WB` - Whole Blood;
- `PRC` - Packed Red Cell;
- `TC` - Thrombocyte Concentrate;
- `FFP` - Fresh Frozen Plasma.

Server harus memastikan pilihan jenis komponen berasal dari master dan mempunyai salah satu kode tersebut.

Phase 8F tidak menambahkan:

- prosedur pemisahan komponen;
- rule laboratorium rinci;
- batas jumlah hasil komponen;
- kombinasi jenis komponen otomatis; atau
- rule medis lain.

### Golongan Darah Unit

`id_golongan_darah` dipilih dari master `golongan_darah` existing dan wajib mereferensikan row master yang valid.

Golongan darah unit merupakan field transaksi `unit_komponen_darah` sendiri.

Phase 8F tidak otomatis mengubah `pendonor.id_golongan_darah` ketika unit dibuat.

Client tidak dapat menggunakan pilihan golongan darah unit untuk mengubah profil Pendonor.

### Tanggal Pembuatan dan Kedaluwarsa

`tanggal_pembuatan` dan `tanggal_kedaluwarsa`:

- wajib diisi;
- berasal dari input Petugas;
- harus merupakan nilai tanggal yang dapat disimpan pada kolom `date` existing.

Phase 8F tidak menghitung `tanggal_kedaluwarsa` otomatis.

Master `jenis_komponen_darah` tidak mempunyai field `masa_simpan_hari`, sehingga Phase 8F tidak menambahkan atau mengasumsikan masa simpan komponen dari pengetahuan umum.

Specification saat ini tidak mengunci relasi urutan antara `tanggal_pembuatan` dan `tanggal_kedaluwarsa`. Karena itu Phase 8F tidak menambahkan rule seperti `tanggal_kedaluwarsa >= tanggal_pembuatan` tanpa keputusan requirement baru.

### Status Awal dan Field Phase Berikutnya

Setiap unit baru harus disimpan dengan:

`status_unit = MENUNGGU_PELULUSAN`

Nilai status awal ditentukan server dan tidak dipercaya dari request.

Ketika unit pertama kali dibuat pada Phase 8F:

- `id_petugas_pelulus = NULL`;
- `waktu_pelulusan = NULL`;
- `catatan_pelulusan = NULL`;
- `waktu_distribusi = NULL`.

Unit `MENUNGGU_PELULUSAN` belum dihitung sebagai persediaan tersedia.

Phase 8F tidak mengubah unit langsung menjadi `TERSEDIA`, `DITOLAK`, atau `DIDISTRIBUSIKAN`.

### Transaction, Duplicate, dan Concurrency

Pembuatan unit dilakukan dalam transaction dengan row `penyumbangan` target sebagai titik serialisasi untuk request pada sumber penyumbangan yang sama.

Di dalam transaction:

1. row `penyumbangan` target di-query ulang berdasarkan identifier route dan dikunci;
2. hasil penyumbangan `BERHASIL` diperiksa ulang;
3. `nomor_unit` diperiksa ulang terhadap unit yang sudah ada;
4. master jenis komponen dan golongan darah yang dipilih harus valid;
5. tepat satu row `unit_komponen_darah` dibuat dengan authority dan status awal dari server.

Jika form yang sama terkirim dua kali untuk penyumbangan yang sama dengan `nomor_unit` yang sama:

- hanya satu row unit boleh tersimpan;
- unit pertama tidak boleh ditimpa;
- request berikutnya harus ditolak secara terkendali setelah state terbaru dibaca.

UNIQUE `unit_komponen_darah.nomor_unit` existing tetap menjadi lapisan integritas terakhir.

Phase 8F tidak menambah lock table, distributed lock, UNIQUE, index, atau constraint baru.

Nomor unit berbeda tetap merupakan pencatatan unit yang berbeda dan diperbolehkan untuk sumber penyumbangan yang sama.

### Tampilan dan Navigasi

`GET` Unit Komponen bersifat read-only terhadap unit yang sudah tersimpan.

Untuk penyumbangan `BERHASIL`, halaman menampilkan:

- konteks penyumbangan yang diperlukan;
- daftar unit yang sudah dibuat dari penyumbangan tersebut; dan
- form pencatatan satu unit baru.

Unit existing tidak diedit, dihapus, diganti, atau direvisi pada Phase 8F.

Setelah route Phase 8F tersedia, halaman Penyumbangan `BERHASIL` boleh menampilkan link nyata `Unit Komponen Darah`.

Penyumbangan `GAGAL` tidak menampilkan aksi pencatatan Unit Komponen.

### Batas Implementasi Phase 8F

Phase 8F tidak:

- melakukan pelulusan unit;
- mengisi Petugas pelulus;
- mengisi waktu pelulusan;
- mengisi catatan pelulusan;
- mengubah unit menjadi `TERSEDIA` atau `DITOLAK`;
- melakukan distribusi;
- mengubah unit menjadi `DIDISTRIBUSIKAN`;
- mengisi waktu distribusi;
- membuat persediaan manual;
- menghitung atau mengubah low-stock sebagai mutation;
- membuat pemberitahuan;
- menambahkan proses laboratorium rinci;
- menambahkan edit/delete/revisi unit;
- menambah tabel, field, enum, UNIQUE, FK, index, atau migration;
- mengubah schema; atau
- mengimplementasikan Phase 8G dan phase setelahnya.

Pelulusan unit tetap menjadi Phase 8G. Phase 8F berhenti setelah unit berhasil dicatat dengan status `MENUNGGU_PELULUSAN`.

---

## 49. Keputusan Proyek Phase 8G - Pelulusan Unit

Phase 8G mengimplementasikan pencatatan hasil pelulusan unit komponen darah yang sudah berada pada `MENUNGGU_PELULUSAN`.

### Scope Pelulusan

Sistem tidak menentukan atau menjalankan metode pemeriksaan laboratorium secara rinci.

Setelah proses pemeriksaan yang berada di luar rincian sistem selesai, Petugas hanya mencatat hasil pelulusan unit.

Phase 8G tidak menambahkan:

- alat;
- reagen;
- metode IMLTD;
- metode quality control;
- hasil laboratorium baru;
- workflow laboratorium rinci;
- tabel laboratorium;
- field laboratorium.

### Akses dan Route

Phase 8G menggunakan tiga route:

1. `GET /petugas/pelulusan` dengan nama `petugas.pelulusan.index`;
2. `GET /petugas/pelulusan/{unit}` dengan nama `petugas.pelulusan.show`;
3. `POST /petugas/pelulusan/{unit}` dengan nama `petugas.pelulusan.store`.

Fungsi hanya dapat digunakan oleh akun:

- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

Parameter `{unit}` adalah target authoritative `UnitKomponenDarah` untuk halaman detail dan mutation.

Identifier client tidak boleh mengganti:

- `id_unit` target;
- `id_petugas_pelulus`;
- sumber penyumbangan;
- Petugas pencatat;
- field lifecycle lain.

### Halaman Index

Menu `Pelulusan` menampilkan unit yang masih mempunyai:

`status_unit = MENUNGGU_PELULUSAN`

sebagai daftar kandidat yang dapat diproses.

Index bersifat read-only.

Unit `TERSEDIA`, `DITOLAK`, dan `DIDISTRIBUSIKAN` tidak ditampilkan sebagai kandidat baru pada index.

### Detail Unit

Halaman detail menampilkan data unit yang diperlukan untuk keterlacakan, sekurang-kurangnya:

- nomor unit;
- sumber penyumbangan yang relevan;
- jenis komponen;
- golongan darah;
- tanggal pembuatan;
- tanggal kedaluwarsa;
- Petugas pencatat;
- status unit saat ini.

Jika status masih `MENUNGGU_PELULUSAN`, halaman dapat menampilkan form pelulusan.

Jika unit sudah `TERSEDIA`, `DITOLAK`, atau nanti `DIDISTRIBUSIKAN`, detail tetap dapat dilihat sebagai riwayat read-only dan tidak menampilkan form mutation.

### Hasil Pelulusan

Mutation hanya menerima hasil:

- `TERSEDIA`; atau
- `DITOLAK`.

Transisi yang diizinkan oleh Phase 8G hanya:

`MENUNGGU_PELULUSAN -> TERSEDIA`

atau:

`MENUNGGU_PELULUSAN -> DITOLAK`.

Tidak ada:

- `TERSEDIA -> DITOLAK`;
- `DITOLAK -> TERSEDIA`;
- kembali ke `MENUNGGU_PELULUSAN`;
- edit hasil;
- revisi;
- undo;
- relulus.

### Authority Petugas dan Waktu

`id_petugas_pelulus` berasal dari profil Petugas yang sedang terautentikasi.

Client tidak dapat memilih atau mengganti Petugas pelulus.

`waktu_pelulusan` ditentukan server ketika transaction pelulusan berhasil.

`waktu_pelulusan` bukan input form.

Phase 8G tidak menambahkan rule temporal medis atau laboratorium lain terhadap timestamp tersebut.

### Catatan Pelulusan

`catatan_pelulusan` bersifat nullable sesuai schema.

Catatan:

- boleh diisi untuk `TERSEDIA`;
- boleh diisi untuk `DITOLAK`;
- tidak diwajibkan untuk salah satu hasil;
- di-trim sebelum disimpan;
- string kosong setelah trimming disimpan sebagai `NULL`.

Form Phase 8G hanya menerima:

- `hasil_pelulusan`; dan
- `catatan_pelulusan`.

### Transaction dan Concurrency

Pencatatan pelulusan dilakukan di dalam database transaction.

Urutan minimum mutation:

1. resolve Petugas terautentikasi;
2. query ulang row `unit_komponen_darah` berdasarkan route-bound unit;
3. lock row unit menggunakan row-level lock;
4. periksa ulang `status_unit = MENUNGGU_PELULUSAN`;
5. validasi hasil `TERSEDIA` atau `DITOLAK`;
6. simpan `status_unit`, `id_petugas_pelulus`, `waktu_pelulusan`, dan `catatan_pelulusan` secara atomik.

Jika request yang sama dikirim dua kali atau dua tab memproses unit yang sama secara bersamaan:

- request pertama yang memperoleh state valid dapat menyimpan hasil;
- request berikutnya harus membaca state terbaru setelah lock;
- request berikutnya tidak boleh mengganti hasil pertama;
- request berikutnya ditolak secara terkendali jika unit tidak lagi `MENUNGGU_PELULUSAN`.

Phase 8G tidak menambahkan distributed lock, lock table, UNIQUE, index, atau constraint baru.

### Kedaluwarsa

`KEDALUWARSA` tetap bukan nilai `status_unit`.

Kondisi kedaluwarsa tetap derived dari `tanggal_kedaluwarsa`.

Phase 8G tidak menjadikan tanggal kedaluwarsa sebagai syarat tambahan untuk mencatat hasil pelulusan unit yang masih `MENUNGGU_PELULUSAN` karena source tidak menetapkan gate pelulusan tersebut.

Apabila unit dicatat `TERSEDIA` tetapi tanggal kedaluwarsanya sudah lewat, status tetap `TERSEDIA`, tetapi unit tersebut tidak dihitung sebagai persediaan tersedia.

Phase 8G tidak membuat:

- status `KEDALUWARSA`;
- cron perubahan status;
- flag kedaluwarsa;
- mutation expiry otomatis.

### Dampak terhadap Persediaan

Phase 8G tidak membuat row persediaan dan tidak mengedit angka stok.

Persediaan tetap dihitung secara derived berdasarkan unit yang:

- `status_unit = TERSEDIA`; dan
- belum melewati `tanggal_kedaluwarsa`.

Karena itu perubahan unit dari `MENUNGGU_PELULUSAN` menjadi `TERSEDIA` dapat mengubah hasil query persediaan tanpa mutation angka stok.

Unit `DITOLAK` tidak dihitung sebagai persediaan tersedia.

### Field yang Tidak Diubah

Phase 8G tidak mengubah:

- `nomor_unit`;
- `id_penyumbangan`;
- `id_jenis_komponen`;
- `id_golongan_darah`;
- `id_petugas_pencatat`;
- `tanggal_pembuatan`;
- `tanggal_kedaluwarsa`;
- `waktu_distribusi`.

Phase 8G hanya memutasi field lifecycle pelulusan yang memang sudah tersedia pada schema:

- `id_petugas_pelulus`;
- `waktu_pelulusan`;
- `status_unit`;
- `catatan_pelulusan`.

### Navigasi

Setelah route Phase 8G benar-benar tersedia, Dashboard Petugas boleh menampilkan link nyata `Pelulusan`.

Menu tersebut harus menuju index Pelulusan yang berfungsi.

Phase 8G tidak menampilkan link atau aksi Distribusi sebelum Phase 8H benar-benar tersedia.

### Batas Implementasi Phase 8G

Phase 8G tidak:

- melakukan distribusi;
- mengubah unit menjadi `DIDISTRIBUSIKAN`;
- mengisi `waktu_distribusi`;
- membuat CRUD persediaan;
- membuat angka stok manual;
- membuat atau mengubah ambang persediaan;
- membuat low-stock mutation;
- melakukan pemanggilan Pendonor;
- membuat pemberitahuan;
- menambahkan rule medis atau laboratorium baru;
- menambah tabel, field, enum, UNIQUE, FK, index, atau migration;
- mengubah schema;
- mengimplementasikan Phase 8H atau phase setelahnya.

Distribusi Unit tetap menjadi Phase 8H.

---

## 50. Keputusan Proyek Phase 8H - Distribusi Unit

Phase 8H mengimplementasikan pencatatan satu unit komponen darah yang keluar dari persediaan UDD tanpa memodelkan penerima atau proses distribusi secara rinci.

### Scope Distribusi

Distribusi hanya mencatat bahwa unit existing telah keluar dari persediaan UDD.

Phase 8H tidak memodelkan rumah sakit penerima, pasien, permintaan darah, pencocokan donor-pasien, crossmatch, transfusi, cold chain, tujuan, kendaraan, pengiriman, atau logistik rinci.

Schema tidak mempunyai tabel distribusi. Row `unit_komponen_darah` existing menjadi riwayat distribusi prototype melalui `status_unit` dan `waktu_distribusi`.

### Route dan Authority

Phase 8H menggunakan tiga route:

1. `GET /petugas/distribusi` dengan nama `petugas.distribusi.index`;
2. `GET /petugas/distribusi/{unit}` dengan nama `petugas.distribusi.show`;
3. `POST /petugas/distribusi/{unit}` dengan nama `petugas.distribusi.store`.

Fungsi hanya dapat digunakan oleh akun:

- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

Parameter `{unit}` adalah target authoritative `UnitKomponenDarah` untuk halaman detail dan mutation.

Identifier atau field lifecycle dari client tidak boleh mengganti:

- `id_unit` target;
- `status_unit` tujuan;
- `waktu_distribusi`;
- sumber penyumbangan;
- jenis komponen;
- golongan darah;
- Petugas pencatat; atau
- Petugas pelulus.

Schema tidak mempunyai `id_petugas_distributor`. Walaupun profil Petugas tetap wajib untuk authorization yang konsisten, Phase 8H tidak menambah field distributor, tabel audit distributor, atau schema lain untuk mencatat siapa yang melakukan distribusi.

### Kandidat Distribusi

Tanggal acuan distribusi adalah tanggal kalender hari ini menurut WIB (`Asia/Jakarta`).

Unit menjadi kandidat distribusi hanya apabila kedua kondisi berikut terpenuhi:

1. `status_unit = TERSEDIA`; dan
2. `tanggal_kedaluwarsa >= tanggal_acuan`.

Batas tanggal bersifat inklusif. Unit dengan `tanggal_kedaluwarsa = tanggal_acuan` masih valid untuk distribusi.

Unit berikut bukan kandidat distribusi:

- `MENUNGGU_PELULUSAN`;
- `DITOLAK`;
- `DIDISTRIBUSIKAN`; dan
- `TERSEDIA` dengan `tanggal_kedaluwarsa < tanggal_acuan`.

### Index Distribusi

Index Distribusi hanya menampilkan kandidat distribusi yang valid dan bersifat read-only.

Daftar diurutkan secara deterministik berdasarkan `id_unit` menaik.

Setiap row menampilkan sekurang-kurangnya:

- nomor unit;
- jenis komponen;
- golongan darah;
- tanggal pembuatan;
- tanggal kedaluwarsa;
- status; dan
- link nyata menuju detail Distribusi.

GET index tidak membuat atau mengubah row, status, timestamp, maupun data persediaan.

### Detail dan Historical View

Unit `TERSEDIA` yang belum kedaluwarsa dapat menampilkan aksi distribusi.

Unit `DIDISTRIBUSIKAN` tetap dapat dibuka sebagai riwayat read-only dan menampilkan `waktu_distribusi` apabila tersedia.

Unit `TERSEDIA` yang sudah kedaluwarsa dapat dibuka sebagai detail read-only, tetapi tidak menampilkan aksi distribusi.

Unit yang tidak eligible tidak menjadi distributable hanya karena URL detail atau mutation diakses langsung.

Detail tidak menambahkan data rumah sakit, pasien, tujuan, permintaan darah, atau logistik.

### Input Distribusi

Phase 8H tidak memerlukan field input bisnis baru.

`POST` hanya menyatakan permintaan untuk mendistribusikan unit authoritative pada route apabila unit tersebut masih eligible.

Client tidak menentukan:

- `id_unit` target;
- `status_unit` tujuan;
- `waktu_distribusi`;
- rumah sakit;
- pasien;
- tujuan;
- permintaan darah; atau
- identifier distributor.

### Waktu Distribusi

`waktu_distribusi` ditentukan server ketika transaction distribusi berhasil.

`waktu_distribusi` bukan input form.

Phase 8H menggunakan konvensi timestamp aplikasi existing dan tidak mengubah konfigurasi timezone.

### Mutation Lifecycle

Satu-satunya transisi yang diizinkan pada Phase 8H adalah:

`TERSEDIA -> DIDISTRIBUSIKAN`

Pada mutation yang berhasil, hanya field berikut yang berubah:

- `status_unit = DIDISTRIBUSIKAN`; dan
- `waktu_distribusi` diisi timestamp server.

Phase 8H tidak mengubah:

- `nomor_unit`;
- `id_penyumbangan`;
- `id_jenis_komponen`;
- `id_golongan_darah`;
- `id_petugas_pencatat`;
- `id_petugas_pelulus`;
- `tanggal_pembuatan`;
- `tanggal_kedaluwarsa`;
- `waktu_pelulusan`; atau
- `catatan_pelulusan`.

Tidak ada row baru yang dibuat oleh mutation distribusi.

### Transaction dan Concurrency

Pencatatan distribusi dilakukan di dalam database transaction.

Urutan minimum mutation:

1. resolve profil Petugas terautentikasi;
2. query ulang row `unit_komponen_darah` berdasarkan route-bound unit;
3. lock row unit menggunakan row-level lock;
4. tentukan tanggal acuan hari ini menurut WIB;
5. periksa ulang `status_unit = TERSEDIA`;
6. periksa ulang `tanggal_kedaluwarsa >= tanggal_acuan`;
7. simpan `status_unit = DIDISTRIBUSIKAN` dan `waktu_distribusi` secara atomik.

Jika status atau batas kedaluwarsa tidak lagi memenuhi syarat setelah lock, request ditolak secara terkendali tanpa memperbaiki atau mengganti state.

Jika request yang sama dikirim dua kali atau dua tab memproses unit yang sama secara bersamaan:

- request pertama yang memperoleh state valid dapat menyimpan distribusi;
- request berikutnya membaca state terbaru setelah lock;
- request berikutnya ditolak karena unit tidak lagi `TERSEDIA`; dan
- request berikutnya tidak boleh menimpa `waktu_distribusi` pertama.

Phase 8H tidak menambahkan distributed lock, lock table, UNIQUE, index, atau constraint baru.

### One-Shot dan Riwayat

Distribusi bersifat one-shot.

Phase 8H tidak menyediakan:

- redistribusi;
- edit `waktu_distribusi`;
- revisi;
- undo; atau
- transisi `DIDISTRIBUSIKAN -> TERSEDIA`.

Row `unit_komponen_darah` existing dengan status dan waktu distribusinya merupakan riwayat distribusi dalam prototype.

### Kedaluwarsa

`KEDALUWARSA` tetap bukan nilai `status_unit`.

Kondisi kedaluwarsa tetap derived dari `tanggal_kedaluwarsa`.

Berbeda dari Phase 8G, kedaluwarsa menjadi gate pada Phase 8H karena hanya unit persediaan `TERSEDIA` yang belum kedaluwarsa yang boleh didistribusikan.

Phase 8H tidak membuat:

- status `KEDALUWARSA`;
- flag kedaluwarsa;
- cron perubahan status; atau
- mutation expiry otomatis.

### Dampak terhadap Persediaan

Phase 8H tidak membuat row persediaan dan tidak mengedit angka stok.

Persediaan tetap dihitung secara derived berdasarkan unit yang:

- `status_unit = TERSEDIA`; dan
- `tanggal_kedaluwarsa >= tanggal acuan`.

Setelah distribusi mengubah status menjadi `DIDISTRIBUSIKAN`, unit secara alami tidak lagi termasuk query persediaan tersebut. Tidak diperlukan mutation stok kedua.

### Navigasi

Setelah route Phase 8H benar-benar tersedia, Dashboard Petugas boleh menampilkan link nyata `Distribusi` menuju index Distribusi yang berfungsi.

Phase 8H tidak menampilkan link atau aksi placeholder untuk Persediaan Phase 8I, Persediaan Rendah Phase 8J, atau phase setelahnya.

### Batas Implementasi Phase 8H

Phase 8H tidak:

- menambah tabel distribusi atau persediaan;
- menambah field atau audit distributor;
- menambah rumah sakit penerima, pasien, permintaan darah, tujuan, donor-patient matching, crossmatch, atau transfusi;
- menambah cold chain, kendaraan, pengiriman, atau logistik rinci;
- membuat inventory CRUD atau mengedit angka stok manual;
- mengubah ambang persediaan atau membuat low-stock mutation;
- menambahkan rule medis;
- menambah tabel, field, enum, FK, UNIQUE, index, migration, atau package;
- menambah service, repository, event/listener, job, atau queue; atau
- mengimplementasikan Phase 8I dan phase setelahnya.

Phase 8H berhenti setelah `status_unit = DIDISTRIBUSIKAN` dan `waktu_distribusi` tersimpan.

---

## 51. Keputusan Proyek Phase 8I - Persediaan Darah

Phase 8I ditetapkan sebagai halaman ringkasan read-only untuk persediaan darah aktual yang tersedia pada satu UDD.

### Scope Persediaan

Persediaan tetap merupakan data turunan dari row `unit_komponen_darah` dan bukan entitas transaksi atau angka stok yang disimpan terpisah.

Phase 8I menampilkan jumlah persediaan agregat berdasarkan jenis komponen dan golongan darah. Phase ini tidak menyediakan halaman CRUD unit individual dan tidak menyediakan edit angka stok manual.

Phase 8I tidak membuat tabel `persediaan` dan tidak menyimpan `jumlah_persediaan`.

### Akses dan Route

Fungsi hanya dapat digunakan oleh akun:

- terautentikasi;
- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

Phase 8I menggunakan satu route:

`GET /petugas/persediaan` dengan nama `petugas.persediaan.index`.

Tidak ada route `POST`, `PATCH`, `PUT`, atau `DELETE` untuk persediaan.

### Tanggal Acuan dan Unit yang Dihitung

Tanggal acuan persediaan adalah tanggal kalender hari ini menurut WIB (`Asia/Jakarta`).

Satu unit dihitung sebagai persediaan hanya apabila kedua kondisi berikut terpenuhi:

1. `status_unit = TERSEDIA`; dan
2. `tanggal_kedaluwarsa >= tanggal_acuan`.

Batas kedaluwarsa bersifat inklusif. Unit dengan `tanggal_kedaluwarsa = tanggal_acuan` tetap dihitung sebagai persediaan.

Unit berikut tidak dihitung:

- `MENUNGGU_PELULUSAN`;
- `DITOLAK`;
- `DIDISTRIBUSIKAN`; dan
- `TERSEDIA` dengan `tanggal_kedaluwarsa < tanggal_acuan`.

`KEDALUWARSA` tetap bukan nilai `status_unit`. Phase 8I tidak membuat status, flag, cron, atau mutation expiry otomatis.

### Agregasi dan Data yang Ditampilkan

Jumlah persediaan dihitung dan dikelompokkan berdasarkan pasangan tepat:

- `id_jenis_komponen`; dan
- `id_golongan_darah`.

Halaman merepresentasikan actual available inventory. Karena itu, Phase 8I hanya menampilkan kelompok dengan `jumlah_persediaan > 0`.

Phase 8I tidak membentuk seluruh kemungkinan kombinasi master `jenis_komponen_darah` dan `golongan_darah`. Jika tidak ada unit yang memenuhi syarat, halaman menampilkan empty state yang terkendali.

Setiap kelompok menampilkan sekurang-kurangnya:

- `jenis_komponen_darah.kode_komponen`;
- `jenis_komponen_darah.nama_komponen`;
- `golongan_darah.abo`;
- `golongan_darah.rhesus`; dan
- `jumlah_persediaan` hasil perhitungan.

Daftar diurutkan secara deterministik berdasarkan:

1. `jenis_komponen_darah.kode_komponen` menaik;
2. `golongan_darah.abo` menaik; dan
3. `golongan_darah.rhesus` menaik.

ID existing boleh digunakan hanya sebagai tie-breaker deterministik apabila secara teknis diperlukan.

### Pemisahan dari Phase 8J

Phase 8I tidak menampilkan atau menghitung output bisnis Persediaan Rendah Phase 8J, termasuk:

- `jumlah_minimum`;
- klasifikasi persediaan rendah;
- badge atau aksi persediaan rendah;
- pemanggilan Pendonor; atau
- pengiriman pemberitahuan.

Aturan ambang dan workflow persediaan rendah tetap menjadi tanggung jawab Phase 8J. Ketentuan ini tidak mengubah ringkasan Dashboard Petugas Phase 8A yang sudah dikunci sebelumnya.

### Read-Only dan Tanpa Efek Samping

Membuka halaman Persediaan tidak boleh:

- membuat row;
- memperbarui row;
- menghapus row;
- mengubah `status_unit`;
- mengubah `tanggal_kedaluwarsa`;
- mengubah `ambang_persediaan`; atau
- membuat `pemberitahuan`.

Karena hanya melakukan pembacaan dan agregasi, Phase 8I tidak memerlukan database transaction atau row lock.

Nilai `jumlah_persediaan` tidak dapat diedit dan harus dihitung saat halaman diminta.

### Navigasi

Setelah route Phase 8I benar-benar tersedia, Dashboard Petugas boleh menampilkan link nyata `Persediaan` menuju halaman tersebut.

Dashboard tidak boleh menampilkan dead link untuk `Persediaan Rendah`, `Pemanggilan Pendonor`, atau `Pemberitahuan Petugas` sampai phase terkait benar-benar diimplementasikan.

### Batas Implementasi Phase 8I

Phase 8I tidak menambahkan:

- tabel, field, enum, migration, FK, atau UNIQUE;
- custom index, View, Stored Procedure, atau Trigger;
- tabel persediaan atau cache stok;
- status `KEDALUWARSA`, flag kedaluwarsa, atau cron expiry updater;
- penyimpanan `jumlah_persediaan`;
- route mutation persediaan;
- service, repository, atau DTO architecture;
- package atau frontend framework; atau
- aturan medis baru.

Phase 8I tidak merefaktor logika persediaan existing pada Dashboard Petugas Phase 8A hanya untuk sentralisasi. Konsolidasi lintas fitur dapat dipertimbangkan kembali pada integrasi/Phase 9.

Phase 8I berhenti pada ringkasan jumlah persediaan aktual per jenis komponen dan golongan darah. Persediaan Rendah tetap menjadi Phase 8J.

---

## 52. Keputusan Proyek Phase 8J - Persediaan Rendah

Phase 8J ditetapkan sebagai halaman monitoring read-only untuk mengidentifikasi dan menampilkan kombinasi persediaan yang berada pada atau di bawah ambang minimum pada satu UDD.

### Scope dan Akses

Fungsi hanya dapat digunakan oleh akun:

- terautentikasi;
- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

Authorization tetap diperiksa server-side. Identifier Petugas dari client tidak menentukan ownership atau akses.

Monitoring Persediaan Rendah berlaku untuk satu UDD secara keseluruhan dan tidak dibatasi oleh Petugas yang mencatat atau meluluskan unit.

### Route

Phase 8J menggunakan satu route:

`GET /petugas/persediaan-rendah` dengan nama `petugas.persediaan-rendah.index`.

Halaman bersifat read-only. Phase 8J tidak menambahkan route `POST`, `PATCH`, `PUT`, atau `DELETE`.

### Tanggal Acuan dan Eligibility Unit

Tanggal acuan adalah tanggal kalender hari ini menurut WIB (`Asia/Jakarta`). Tanggal tersebut ditentukan saat request dan tidak disimpan.

`jumlah_persediaan` untuk evaluasi persediaan rendah hanya menghitung unit yang memenuhi kedua kondisi berikut:

1. `status_unit = TERSEDIA`; dan
2. `tanggal_kedaluwarsa >= tanggal_acuan`.

Batas kedaluwarsa bersifat inklusif. Unit dengan `tanggal_kedaluwarsa = tanggal_acuan` tetap dihitung.

Unit berikut tidak dihitung:

- `MENUNGGU_PELULUSAN`;
- `DITOLAK`;
- `DIDISTRIBUSIKAN`; dan
- `TERSEDIA` dengan `tanggal_kedaluwarsa < tanggal_acuan`.

Unit yang sudah kedaluwarsa tidak dimutasi. `KEDALUWARSA` tetap bukan nilai `status_unit` dan tidak dibuat sebagai status atau flag.

### Basis Evaluasi Ambang

Evaluasi Phase 8J dimulai dari setiap row existing pada `ambang_persediaan`.

Setiap row ambang mewakili satu kombinasi yang dikonfigurasi berdasarkan pasangan tepat:

- `id_jenis_komponen`; dan
- `id_golongan_darah`.

Untuk setiap kombinasi tersebut, sistem menghitung `jumlah_persediaan` terkini dari `unit_komponen_darah` yang memenuhi eligibility.

Hanya kombinasi yang mempunyai row `ambang_persediaan` dapat diklasifikasikan sebagai persediaan rendah. Kombinasi tanpa konfigurasi ambang tidak mempunyai nilai default, tidak diberi angka hardcoded, dan tidak diklasifikasikan sebagai low-stock.

### Stok Nol

Jika satu kombinasi yang dikonfigurasi tidak mempunyai unit eligible, nilai derived-nya adalah:

`jumlah_persediaan = 0`

Nilai nol tetap dibandingkan dengan `jumlah_minimum`. Kombinasi yang dikonfigurasi tidak boleh dihilangkan hanya karena tidak mempunyai unit yang cocok.

Ketentuan ini sengaja berbeda dari halaman Persediaan Phase 8I, yang hanya menampilkan kelompok actual available inventory dengan `jumlah_persediaan > 0`.

### Kondisi Persediaan Rendah

Satu kombinasi yang dikonfigurasi diklasifikasikan sebagai persediaan rendah tepat ketika:

`jumlah_persediaan <= jumlah_minimum`

Operator yang digunakan adalah `<=`, bukan `<`.

Phase 8J tidak menambahkan warning tier, severity level, persentase, atau klasifikasi lain.

### Data yang Ditampilkan

Halaman hanya menampilkan kombinasi yang memenuhi kondisi persediaan rendah.

Setiap row menampilkan sekurang-kurangnya:

- `jenis_komponen_darah.kode_komponen`;
- `jenis_komponen_darah.nama_komponen`;
- `golongan_darah.abo`;
- `golongan_darah.rhesus`;
- `jumlah_persediaan`; dan
- `jumlah_minimum`.

`jumlah_persediaan` merupakan nilai derived dan tidak disimpan. `jumlah_minimum` berasal dari row `ambang_persediaan`.

Petugas boleh melihat `jumlah_minimum`, tetapi Phase 8J tidak menyediakan kontrol untuk mengubah stok atau ambang.

### Ordering

Daftar diurutkan secara deterministik berdasarkan:

1. `jenis_komponen_darah.kode_komponen` menaik;
2. `golongan_darah.abo` menaik; dan
3. `golongan_darah.rhesus` menaik.

ID existing boleh digunakan hanya sebagai tie-breaker deterministik apabila secara teknis diperlukan.

Phase 8J tidak menggunakan urutan ABO medis khusus. Ordering mengikuti pengurutan ascending biasa pada basis data.

### Empty State

Phase 8J mempunyai dua empty state yang berbeda.

Jika tidak ada konfigurasi `ambang_persediaan` sama sekali, tampilkan:

`Belum ada konfigurasi ambang persediaan.`

Jika konfigurasi ambang tersedia tetapi tidak ada kombinasi yang memenuhi `jumlah_persediaan <= jumlah_minimum`, tampilkan:

`Tidak ada persediaan yang berada pada atau di bawah ambang.`

Sistem tidak membuat row buatan hanya untuk menghindari empty state.

### Read-Only dan Tanpa Efek Samping

Membuka halaman Persediaan Rendah tidak boleh:

- membuat row;
- memperbarui row;
- menghapus row;
- mengubah `status_unit`;
- mengubah `tanggal_kedaluwarsa`;
- mengubah `ambang_persediaan`;
- mengubah `jumlah_minimum`;
- membuat `pemberitahuan`;
- memilih Pendonor; atau
- memicu pemanggilan Pendonor.

Karena hanya melakukan pembacaan dan perhitungan, Phase 8J tidak memerlukan database transaction atau row lock.

### Pemisahan dari Phase 8K dan Phase 8L

Phase 8J berhenti pada identifikasi dan tampilan kombinasi persediaan rendah.

Phase 8J tidak mencakup:

- Pemanggilan Pendonor;
- daftar kandidat Pendonor;
- checkbox atau form pemilihan Pendonor;
- filter kelayakan donor ulang untuk pemanggilan;
- form pemberitahuan;
- pembuatan pemberitahuan;
- pengiriman pemberitahuan;
- SMS;
- WhatsApp;
- email; atau
- clinical donor-patient matching.

Pemanggilan Pendonor tetap menjadi Phase 8K. Pembuatan dan pengiriman pemberitahuan Petugas tetap menjadi Phase 8L.

### Navigasi

Setelah route Phase 8J benar-benar tersedia, Dashboard Petugas boleh menampilkan link nyata `Persediaan Rendah` menuju `petugas.persediaan-rendah.index`.

Dashboard tidak boleh menampilkan dead link untuk `Pemanggilan Pendonor` atau `Pemberitahuan Petugas` sebelum route terkait benar-benar tersedia.

### Konsistensi dengan Dashboard Phase 8A

Semantik Persediaan Rendah harus konsisten antara Dashboard Petugas Phase 8A dan halaman dedicated Phase 8J:

- evaluasi dimulai dari kombinasi yang mempunyai row `ambang_persediaan`;
- kombinasi terkonfigurasi dengan stok nol tetap dievaluasi;
- kombinasi tanpa ambang tidak diklasifikasikan; dan
- kondisi rendah menggunakan `jumlah_persediaan <= jumlah_minimum`.

Phase 8J tidak mewajibkan refactor `PetugasDashboardController` hanya untuk DRY atau sentralisasi. Konsolidasi lintas fitur dapat dipertimbangkan kembali pada integrasi/Phase 9 sebagaimana sudah diizinkan oleh implementation plan.

### Query dan Batas Course/UTS

Phase 8J tetap merupakan perhitungan derived dan harus dapat dijelaskan dengan konsep basis data serta Laravel yang sederhana:

- `ambang_persediaan` sebagai driving rows yang dikonfigurasi;
- `LEFT JOIN` atau ekuivalen agar kombinasi tanpa unit eligible tetap tersedia;
- `COUNT` aggregate;
- `GROUP BY` atau subquery;
- `COALESCE` untuk merepresentasikan tidak adanya unit eligible sebagai nol;
- perbandingan `jumlah_persediaan <= jumlah_minimum`;
- `JOIN` ke `jenis_komponen_darah` dan `golongan_darah`;
- `ORDER BY`; dan
- Laravel Controller, Query Builder, serta Blade.

Ketentuan ini mengunci perilaku dan batas kompleksitas, bukan kode implementasi pada task dokumentasi ini.

### Batas Implementasi Phase 8J

Phase 8J tidak menambahkan:

- tabel `persediaan`, tabel low-stock, atau tabel riwayat stok;
- field derived untuk stok atau status low-stock;
- expiry flag atau status `KEDALUWARSA`;
- cache stok atau cron expiry updater;
- Trigger, Stored Procedure, View, atau custom index;
- migration, tabel, field, FK, atau UNIQUE;
- penyimpanan `jumlah_persediaan`;
- package;
- service, repository, atau DTO;
- event/listener atau queue;
- stock editing atau threshold editing; atau
- arsitektur besar lain.

Phase 8J tidak mengubah schema yang tetap terdiri dari 15 tabel bisnis dan 97 field.

Phase 8J berhenti pada halaman monitoring read-only Persediaan Rendah. Pemanggilan Pendonor dan pemberitahuan tetap menjadi phase berikutnya.

---

## 53. Keputusan Proyek Phase 8K - Pemanggilan Pendonor

Phase 8K ditetapkan sebagai halaman read-only yang membantu Petugas mengidentifikasi Pendonor yang relevan ketika satu kombinasi persediaan darah yang dikonfigurasi sedang berada pada atau di bawah ambang. Candidate dan eligibility tetap merupakan informasi derived dari data existing; Phase 8K tidak membuat entitas bisnis baru.

### Scope dan Akses

Fungsi hanya dapat digunakan oleh akun:

- terautentikasi;
- `status_akun = AKTIF`;
- `peran = PETUGAS`; dan
- mempunyai profil `petugas` yang valid.

Authorization diperiksa server-side. Identifier Petugas yang dikirim client tidak menentukan authority, ownership, atau cakupan data. Pemanggilan berlaku untuk satu UDD secara keseluruhan.

Phase 8K bukan patient matching, transfusion compatibility matching, crossmatch, hospital request matching, clinical decision support, keputusan kelayakan medis akhir, ataupun pembuatan/pengiriman pemberitahuan.

### Route dan Sifat Read-Only

Phase 8K menggunakan satu route:

`GET /petugas/pemanggilan` dengan nama `petugas.pemanggilan.index`.

Phase 8K tidak menambahkan route `POST`, `PATCH`, `PUT`, atau `DELETE`. Halaman dan seluruh perubahan konteks melalui query parameter bersifat read-only terhadap business data.

### Konteks Persediaan Rendah dan Input id_ambang

Konteks berwenang untuk satu kondisi persediaan rendah adalah row existing `ambang_persediaan` yang dipilih melalui query parameter GET `id_ambang`.

Nilai `id_ambang` dari client hanya merupakan permintaan untuk memilih konteks. Nilai tersebut tidak membuktikan bahwa row ada atau bahwa kondisi masih rendah. Server wajib:

1. memuat row `ambang_persediaan` existing;
2. menghitung ulang `jumlah_persediaan` saat request;
3. membandingkan hasil tersebut dengan `jumlah_minimum`; dan
4. hanya melanjutkan ke kandidat jika kondisi saat ini memenuhi aturan persediaan rendah.

Server tidak boleh menggunakan halaman Phase 8J yang pernah dirender sebagai bukti bahwa state masih rendah.

Jika tidak ada `id_ambang` yang dipilih, halaman boleh menampilkan daftar kondisi yang saat ini rendah dan meminta Petugas memilih satu kondisi. Sistem tidak otomatis memilih row pertama dan tidak menggabungkan kandidat beberapa golongan darah. Tampilkan:

`Pilih kondisi persediaan rendah untuk melihat kandidat Pendonor.`

### Rekalkulasi Persediaan Rendah

Daftar konteks dan validasi ambang terpilih menggunakan semantik Phase 8J tanpa perubahan:

- evaluasi hanya dimulai dari row `ambang_persediaan` existing;
- tanggal acuan adalah tanggal kalender hari ini menurut WIB (`Asia/Jakarta`);
- hanya unit dengan `status_unit = TERSEDIA` dan `tanggal_kedaluwarsa >= tanggal_acuan` yang dihitung;
- batas kedaluwarsa bersifat inklusif;
- unit `MENUNGGU_PELULUSAN`, `DITOLAK`, `DIDISTRIBUSIKAN`, dan unit `TERSEDIA` yang sudah kedaluwarsa tidak dihitung;
- kombinasi ambang tanpa unit eligible mempunyai `jumlah_persediaan = 0`;
- tidak ada ambang default atau hardcoded bagi kombinasi yang tidak dikonfigurasi; dan
- kondisi rendah berlaku tepat ketika `jumlah_persediaan <= jumlah_minimum`.

Daftar konteks boleh menampilkan sekurang-kurangnya kode/nama komponen, ABO, Rhesus, `jumlah_persediaan`, dan `jumlah_minimum`. Ini merupakan konteks untuk pemanggilan, bukan aturan stok baru.

### Golongan Darah Relevan

Untuk prototype ini, `golongan darah relevan` berarti kecocokan tepat:

`pendonor.id_golongan_darah = ambang_persediaan.id_golongan_darah`

Master `golongan_darah` yang sama menentukan ABO dan Rhesus sekaligus. Phase 8K tidak menerapkan:

- matriks kompatibilitas donor-penerima;
- universal donor atau universal recipient;
- substitusi antargolongan darah;
- kompatibilitas transfusi spesifik komponen;
- crossmatch;
- patient matching; atau
- clinical matching.

Keputusan ini adalah penyederhanaan operasional prototype dan bukan klaim kompatibilitas klinis.

### Komponen sebagai Konteks

Row ambang terpilih tetap mempunyai `id_jenis_komponen` karena persediaan dipantau per pasangan komponen dan golongan darah. Komponen hanya menjadi alasan atau konteks persediaan rendah.

Schema tidak mempunyai field yang menyatakan komponen apa yang dapat dihasilkan oleh seorang Pendonor. Karena itu, candidate filtering menggunakan kecocokan tepat golongan darah confirmed dan eligibility historis donor ulang, tanpa menambahkan aturan kemampuan donor untuk WB, PRC, TC, atau FFP.

### Persyaratan Akun dan Profil Pendonor

Kandidat harus merupakan row `pendonor` existing yang terhubung dengan akun:

- `peran = PENDONOR`; dan
- `status_akun = AKTIF`.

Pendonor yang terhubung dengan akun `NONAKTIF` bukan kandidat. Pendonor dengan `id_golongan_darah = NULL` juga bukan kandidat karena kecocokan tepat ABO/Rhesus belum terkonfirmasi.

Phase 8K tidak membuat Pendonor, memperbaiki relasi akun/profil, mengaktifkan akun, atau mengubah golongan darah Pendonor.

### Eligibility Historis Berdasarkan Phase 7H

Phase 8K memakai ulang semantik donor-repeat Phase 7H untuk kondisi saat ini. Tanggal acuan adalah tanggal kalender hari ini menurut WIB (`Asia/Jakarta`).

Riwayat yang berwenang hanya penyumbangan yang:

- mempunyai `hasil_penyumbangan = BERHASIL`; dan
- bertanggal sampai dengan tanggal acuan.

Penyumbangan berhasil di masa depan setelah tanggal acuan tidak memengaruhi eligibility historis saat ini. Penyumbangan `GAGAL` tidak dihitung dalam frekuensi, tidak menggantikan penyumbangan berhasil terbaru, dan tidak menambahkan masa tunggu.

Keputusan seleksi `DITUNDA` atau `DITOLAK` tidak membuat tanggal defer-until dan tidak secara mandiri mengecualikan Pendonor. `seleksi_donor.alasan_keputusan` tidak digunakan sebagai tanggal eligibility. Field `ditunda_sampai` atau field baru lain tidak ditambahkan.

### Interval Dua Bulan Kalender

Jika Pendonor mempunyai penyumbangan berhasil sebelumnya:

`tanggal_pemenuhan_interval = tanggal penyumbangan berhasil terbaru + 2 bulan kalender`

Penambahan menggunakan perilaku tanpa overflow yang sama seperti Phase 7D dan Phase 7H. Interval tidak ditafsirkan sebagai 60 hari tetap, jumlah jam tetap, atau aturan medis baru.

### Frekuensi Tahun Kalender

Jumlah donor tahun berjalan menghitung penyumbangan `BERHASIL` dalam tahun kalender tanggal acuan sampai dengan tanggal acuan.

Batas tetap:

- `LAKI_LAKI`: maksimum 6 penyumbangan berhasil dalam tahun kalender;
- `PEREMPUAN`: maksimum 4 penyumbangan berhasil dalam tahun kalender.

Jika hitungan tahun berjalan sudah mencapai batas, tanggal paling awal dari sisi frekuensi adalah 1 Januari tahun kalender berikutnya. Phase 8K tidak menambah kategori jenis kelamin atau limit baru.

### Pendonor Pertama Kali

Pendonor tanpa riwayat `hasil_penyumbangan = BERHASIL` boleh menjadi kandidat Phase 8K apabila:

- akunnya aktif dengan `peran = PENDONOR`; dan
- golongan darah confirmed-nya cocok tepat dengan golongan darah pada ambang terpilih.

Pendonor pertama kali tersebut tidak mempunyai pembatasan interval atau frekuensi historis untuk kesempatan donor pertamanya. Hal ini memakai aturan Phase 7H dan tidak menyatakan kelayakan medis akhir.

### Eligibility Historis Saat Ini

Secara konseptual:

`tanggal_donor_berikutnya = maksimum dari tanggal_acuan, tanggal_pemenuhan_interval bila ada, dan 1 Januari tahun berikutnya bila batas frekuensi tercapai`

Pendonor menjadi kandidat hanya jika aturan interval dan frekuensi sama-sama sudah terpenuhi pada tanggal acuan WIB. Nilai `tanggal_donor_berikutnya` tetap derived, dihitung ketika diperlukan, dan tidak disimpan.

Hasil hanya menyatakan eligibility historis untuk pemanggilan atau kesempatan mencoba donor kembali. Hasil tidak boleh diberi label yang menyiratkan `kelayakan medis akhir`. Pendonor tetap menjalani booking, kuesioner, check-in, dan seleksi sebagaimana berlaku.

### Data Kandidat dan Ordering

Setiap kandidat menampilkan data minimal:

- `pendonor.nomor_donor`;
- `pendonor.nama_lengkap`;
- ABO;
- Rhesus;
- tanggal penyumbangan berhasil terbaru, atau `-` jika belum ada; dan
- jumlah penyumbangan `BERHASIL` dalam tahun kalender berjalan sampai tanggal acuan.

Phase 8K tidak menampilkan NIK, alamat lengkap, tempat lahir, tanggal lahir, pekerjaan, alamat kantor, password/auth data, atau data profil lain hanya karena tersedia. Nomor telepon tidak diperlukan karena Phase 8K tidak mengimplementasikan telepon, SMS, WhatsApp, email, atau kanal eksternal.

Kandidat diurutkan secara deterministik berdasarkan:

1. `pendonor.nama_lengkap ASC`; dan
2. `pendonor.id_pendonor ASC`.

Tidak ada ranking medis berdasarkan umur, frekuensi donor, recency donor terakhir, jenis kelamin, ketersediaan nomor telepon, skor, atau prioritas klinis.

### Empty State dan Kondisi Tidak Valid

Phase 8K mengunci state berikut:

1. Jika tidak ada kondisi persediaan rendah saat ini, tampilkan `Tidak ada kondisi persediaan rendah yang memerlukan pemanggilan Pendonor.`
2. Jika kondisi rendah ada tetapi belum dipilih, tampilkan `Pilih kondisi persediaan rendah untuk melihat kandidat Pendonor.`
3. Jika kondisi terpilih saat ini rendah tetapi tidak mempunyai kandidat, tampilkan `Tidak ada Pendonor yang memenuhi kriteria pemanggilan untuk kondisi persediaan ini.`
4. Jika row ambang existing yang dipilih tidak lagi rendah saat dihitung ulang, tampilkan `Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.`

Pada state keempat, kandidat tidak ditampilkan sebagai valid dan state rendah lama tidak digunakan. `id_ambang` yang tidak ada harus ditangani secara terkendali serta tidak boleh membuka data Pendonor yang tidak berkaitan.

### Read-Only dan Tanpa Efek Samping

Membuka halaman atau mengganti konteks GET tidak boleh:

- membuat, memperbarui, atau menghapus row;
- mengubah `unit_komponen_darah` atau `ambang_persediaan`;
- mengubah `pendonor` atau `akun`;
- mengubah `penyumbangan` atau `seleksi_donor`;
- membuat atau memperbarui `pemberitahuan`;
- menyimpan kandidat terpilih; atau
- menyimpan hasil eligibility, tanggal donor berikutnya, hitungan donor, maupun state low-stock.

Tidak diperlukan transaction atau row lock hanya untuk pembacaan dan kalkulasi Phase 8K.

### Pemisahan dari Phase 8L

Phase 8K menentukan dan menampilkan kandidat valid sebagai konteks dari mana Petugas kelak dapat memilih target pemberitahuan. Phase 8K tidak menyimpan state perantara `selected donor`.

Phase 8L kelak menyediakan aksi/form nyata untuk memilih kandidat sebagai target, membuat row `pemberitahuan`, mencatat Petugas terautentikasi sebagai pengirim, serta melakukan pembuatan/pengiriman pemberitahuan in-app.

Sebelum Phase 8L tersedia, Phase 8K tidak menambahkan:

- tombol `Kirim` yang mati;
- form pemberitahuan yang mati;
- route POST pemilihan kandidat;
- checkbox submission tanpa tujuan yang bekerja;
- penyimpanan kandidat sementara pada tabel atau session; atau
- mutation `pemberitahuan` apa pun.

Phase 8K juga tidak mengisi `waktu_dibuat` atau `waktu_dibaca`, memilih `id_petugas_pengirim` untuk persistence, mengirim SMS/WhatsApp/email, menggunakan Laravel Notification infrastructure, menambah status delivery eksternal, atau membuat realtime push.

### Navigasi

Setelah route Phase 8K benar-benar tersedia, Dashboard Petugas boleh menampilkan link nyata `Pemanggilan Pendonor` menuju `petugas.pemanggilan.index`.

Phase 8J Persediaan Rendah juga boleh menyediakan link nyata dari row yang rendah saat ini menuju:

`petugas.pemanggilan.index?id_ambang=<existing id_ambang>`

Server tetap wajib menghitung ulang kondisi low-stock. Dashboard tidak menampilkan dead link `Pemberitahuan Petugas` sebelum route Phase 8L tersedia.

### Batas Implementasi dan Course/UTS

Phase 8K harus tetap dapat dijelaskan menggunakan konsep biasa yang sesuai scope mata kuliah:

- tabel relasional dan relasi FK;
- `JOIN`;
- `COUNT`;
- `GROUP BY` bila berguna;
- aggregate query;
- subquery atau `EXISTS` bila berguna;
- `ORDER BY`;
- derived value yang dihitung ketika diperlukan;
- Laravel Controller;
- Query Builder dan/atau Eloquent sederhana; dan
- Blade.

Phase 8K tidak memerlukan search/filter framework, pagination architecture, AJAX, SPA, realtime, background job, queue, View, Stored Procedure, Trigger, custom index, service, repository, DTO, event/listener, cache, package, atau frontend framework. Teknik tersebut tidak ditambahkan hanya untuk menunjukkan kompleksitas.

Phase 8K tidak menambahkan atau mengubah tabel, field, PK, FK, UNIQUE, enum, index, migration, timestamp, soft delete, atau `remember_token`. Tidak dibuat tabel `pemanggilan`, `kandidat_pendonor`, candidate selection, atau penyimpanan sementara; tidak dibuat field eligibility, tanggal donor terakhir, jumlah donor, tanggal donor berikutnya, status low-stock, maupun selected donor.

Schema tetap terdiri dari 15 tabel bisnis dan 97 field.

### Konsistensi Antar-Phase

Semantik historical eligibility Phase 8K harus tetap sama dengan Phase 7H. Phase 8K tidak menciptakan aturan donor-repeat kedua.

Semantik low-stock Phase 8K harus tetap sama dengan Phase 8J. Phase 8K tidak menciptakan aturan stok/ambang kedua.

Task dokumentasi Phase 8K tidak merefaktor implementasi Phase 7H atau Phase 8J untuk DRY. Jika kalkulasi berulang perlu dikonsolidasikan, Phase 9 menjadi tempat untuk mengevaluasi konsolidasi sederhana tanpa arsitektur berlebihan.

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

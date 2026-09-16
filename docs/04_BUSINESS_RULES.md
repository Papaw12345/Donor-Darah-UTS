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

Tidak ada perubahan schema, migration, custom index, tabel review, field review, snapshot/versioning pertanyaan, service/repository/DTO, AJAX, SPA, atau dependency baru.

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

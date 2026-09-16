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

#### Keputusan Proyek Phase 7 Completion - Ringkasan Pemesanan Aktif

Untuk menutup Gate Phase 7, ringkasan pemesanan aktif pada Dashboard Pendonor menggunakan ketentuan berikut:

- sumber data selalu Pendonor yang terhubung dengan akun `PENDONOR` yang sedang terautentikasi;
- dashboard menampilkan seluruh `pemesanan_donor` milik Pendonor tersebut yang mempunyai `status_pemesanan = TERJADWAL` atau `status_pemesanan = CHECK_IN`;
- `SELESAI`, `DIBATALKAN`, dan `TIDAK_HADIR` tidak ditampilkan sebagai pemesanan aktif;
- ringkasan tidak menambahkan filter berdasarkan tanggal jadwal. Apabila row masih berstatus aktif pada basis data, dashboard menampilkan state tersebut sebagaimana tersimpan;
- karena pemesanan pada `id_jadwal` berbeda dapat sama-sama valid, dashboard tidak memilih satu pemesanan sebagai pemesanan utama dan tidak menyembunyikan pemesanan aktif lain;
- urutan ringkasan adalah `jadwal_pelayanan.tanggal ASC`, `jadwal_pelayanan.jam_mulai ASC`, `pemesanan_donor.waktu_pemesanan ASC`, kemudian `pemesanan_donor.id_pemesanan ASC` sebagai tie-breaker deterministik;
- setiap item ringkasan minimal menampilkan tanggal jadwal, jam pelayanan, dan `status_pemesanan`;
- dashboard menyediakan link nyata menuju halaman `Pemesanan Donor Saya` untuk tindakan atau informasi lebih rinci;
- dashboard tidak menambahkan tombol buat pemesanan, pembatalan, pengisian kuesioner, pembuatan kode check-in, atau aksi mutasi lain di dalam ringkasan ini;
- apabila tidak ada pemesanan aktif, dashboard menampilkan empty state yang terkendali;
- Dashboard Pendonor tetap menggunakan `GET` dan bersifat hanya-baca. Membuka dashboard tidak mengubah status atau data pemesanan;
- Phase 7 Completion ini tidak menambahkan tabel, kolom, enum, index, status, atau perubahan schema.

### Profil Saya

Pendonor dapat:

- melihat profil sendiri;
- memperbarui data pribadi yang diperbolehkan.

Golongan darah yang sudah dikonfirmasi oleh UDD tidak boleh diubah secara bebas oleh Pendonor.

#### Batas Field Profil

Field yang dapat diperbarui oleh Pendonor:

- `nama_lengkap`;
- `tempat_lahir`;
- `alamat`;
- `nomor_telepon`;
- `pekerjaan`;
- `alamat_kantor`.

Field yang hanya dapat dilihat pada area Profil Saya dan tidak dapat diperbarui oleh Pendonor:

- NIK (`nik`);
- nomor donor (`nomor_donor`);
- jenis kelamin (`jenis_kelamin`);
- tanggal lahir (`tanggal_lahir`);
- golongan darah (`id_golongan_darah`);
- email akun (`akun.email`).

Golongan darah tetap dikendalikan melalui proses konfirmasi UDD. Pengelolaan password tidak menjadi bagian dari menu Profil Saya. Pendonor hanya dapat melihat dan memperbarui profilnya sendiri.

Pembatasan ini menjaga NIK sebagai identitas unik, nomor donor sebagai identitas operasional, serta jenis kelamin dan tanggal lahir sebagai data yang berkaitan dengan identitas atau aturan donor ulang. Golongan darah dikonfirmasi oleh UDD/Petugas, sedangkan email merupakan identitas akun autentikasi. Field yang dapat diperbarui dibatasi pada data profil pribadi, kontak, dan pekerjaan yang tidak mengubah kepemilikan transaksi atau aturan kelayakan donor.

### Jadwal Donor

Pendonor dapat:

- melihat jadwal pelayanan donor yang tersedia;
- melihat jadwal yang masih dibuka;
- melihat jadwal yang masih memiliki kapasitas.

Pada menu Jadwal Donor, jadwal dianggap tersedia untuk ditampilkan apabila:

- `status_jadwal = DIBUKA`;
- `tanggal` sama dengan atau setelah tanggal hari ini menurut waktu operasional WIB (`Asia/Jakarta`); dan
- sisa kapasitas lebih dari `0`.

Jadwal dengan tanggal sebelum hari ini, jadwal `DITUTUP`, jadwal `DIBATALKAN`, atau jadwal yang sudah penuh tidak ditampilkan sebagai jadwal tersedia.

Untuk jadwal pada tanggal hari ini, `status_jadwal` tetap menjadi kontrol administratif ketersediaan. Phase ini tidak menambahkan perubahan status otomatis berdasarkan jam berjalan.

Pendonor tidak dapat membuat atau mengubah jadwal pelayanan secara administratif.

### Pemesanan Donor

Pendonor dapat:

- memilih jadwal;
- membuat pemesanan donor;
- melihat pemesanan miliknya sendiri;
- membatalkan pemesanan selama masih diperbolehkan oleh aturan aplikasi.

Pendonor tidak boleh melihat atau mengubah pemesanan milik Pendonor lain.

#### Keputusan Proyek Phase 7D

Klarifikasi berikut merupakan keputusan proyek Phase 7D untuk bagian alur Pemesanan Donor yang sebelumnya belum ditentukan secara rinci.

- Kepemilikan pemesanan harus ditentukan dari Pendonor yang sedang terautentikasi. Identifier Pendonor yang dikirim oleh client tidak boleh digunakan untuk memberi akses ke pemesanan milik Pendonor lain.
- Pemesanan baru hanya dapat dibuat untuk jadwal `DIBUKA` dengan tanggal hari ini atau setelahnya menurut WIB (`Asia/Jakarta`), sisa kapasitas lebih dari `0`, kelayakan donor ulang terpenuhi terhadap tanggal jadwal yang dipilih, dan aturan pemesanan ulang untuk jadwal yang sama terpenuhi.
- Untuk keperluan workflow dan UI, status pemesanan aktif adalah `TERJADWAL` dan `CHECK_IN`.
- Untuk kombinasi Pendonor dan jadwal yang sama, pemesanan berstatus `TERJADWAL`, `CHECK_IN`, `SELESAI`, atau `TIDAK_HADIR` menghalangi pemesanan baru. Hanya pemesanan berstatus `DIBATALKAN` yang tidak menghalangi pemesanan ulang pada jadwal yang sama.
- Pemesanan pada `id_jadwal` yang berbeda tidak otomatis dilarang oleh aturan duplikasi. Prototype ini tidak menambahkan aturan satu pemesanan per hari, konsep jadwal alternatif, atau pembatalan otomatis atas pemesanan lain.
- Pemesanan yang valid dibuat dengan status `TERJADWAL`; `waktu_pemesanan` diisi pada saat pembuatan menggunakan konvensi timestamp aplikasi; sedangkan `kode_checkin` dan `waktu_checkin` tetap `NULL`.
- Pendonor hanya dapat membatalkan pemesanan miliknya yang berstatus `TERJADWAL` dan tanggal jadwalnya belum lewat menurut WIB (`Asia/Jakarta`). Pembatalan mengubah status `TERJADWAL` menjadi `DIBATALKAN`.
- Pemesanan berstatus `CHECK_IN`, `SELESAI`, `TIDAK_HADIR`, atau `DIBATALKAN` tidak dapat dibatalkan oleh Pendonor.

Pembuatan kode check-in dan proses check-in bukan bagian implementasi Phase 7D.

### Kuesioner Pradonasi

Pendonor dapat mengisi kuesioner kesehatan pradonasi untuk pemesanan miliknya sendiri.

Kuesioner diisi pada setiap kesempatan donor.

Aturan mengenai batas waktu perubahan jawaban tidak ditentukan pada dokumen ini dan tidak boleh diasumsikan tanpa keputusan lebih lanjut.

Ketentuan bahwa kuesioner dibuat untuk pemesanan milik Pendonor pada setiap kesempatan donor, hanya pertanyaan aktif yang ditampilkan, jawaban disimpan pada `jawaban_kuesioner`, satu pemesanan maksimal memiliki satu kuesioner, dan satu pertanyaan maksimal memiliki satu jawaban dalam satu kuesioner merupakan aturan yang sudah bersumber dari dokumen awal dan schema. Demikian pula, kuesioner bukan transaksi review medis tersendiri, jawabannya kemudian dapat dilihat Petugas sebagai informasi untuk seleksi, dan pengisian pradonasi yang diperlukan mendahului pembuatan kode check-in. Ketentuan tersebut bukan keputusan baru Phase 7E.

#### Keputusan Proyek Phase 7E

Rincian berikut merupakan keputusan proyek Phase 7E untuk perilaku operasional yang sebelumnya belum ditentukan secara tepat:

- Pendonor hanya dapat membuat dan melihat kuesioner yang terkait dengan `pemesanan_donor` miliknya sendiri. Kepemilikan berasal dari Pendonor yang sedang terautentikasi; identifier Pendonor dari client tidak dapat memberi akses ke pemesanan atau kuesioner Pendonor lain.
- Kuesioner baru hanya dapat dikirim untuk pemesanan milik Pendonor terautentikasi yang berstatus `TERJADWAL`, memiliki tanggal jadwal yang belum lewat menurut WIB (`Asia/Jakarta`), dan tidak terkait dengan jadwal berstatus administratif `DIBATALKAN`. Perubahan jadwal menjadi `DITUTUP` tidak dengan sendirinya membatalkan hak mengisi kuesioner untuk pemesanan yang sudah valid.
- Untuk prototype ini, pengisian kuesioner bersifat satu kali kirim. Setelah berhasil dikirim, Pendonor dapat melihat kuesioner dan jawaban yang tersimpan, tetapi tidak dapat mengubah atau mengganti jawabannya. Prototype tidak menyediakan draft atau riwayat revisi jawaban.
- Form menampilkan seluruh pertanyaan dengan `status_aktif = true`, diurutkan berdasarkan `urutan` lalu `id_pertanyaan` sebagai pembeda deterministik. `kategori` hanya boleh digunakan untuk tampilan atau pengelompokan dan tidak mengubah kewajiban menjawab.
- Setiap pertanyaan aktif wajib memiliki tepat satu jawaban saat pengiriman berhasil. Jawaban `YA_TIDAK` disimpan secara kanonis sebagai tepat `YA` atau `TIDAK`, sedangkan jawaban `TEKS` wajib tidak kosong setelah whitespace awal dan akhir dihapus. Tidak ada jenis jawaban tambahan.
- Server menentukan sendiri himpunan pertanyaan aktif yang berwenang pada saat pengiriman. Key jawaban yang dikirim harus tepat sama dengan himpunan pertanyaan aktif tersebut. Jika himpunan pertanyaan berubah sejak form dimuat, pengiriman ditolak secara terkendali dan Pendonor diminta memuat ulang form; jawaban tak terduga atau jawaban yang hilang tidak boleh diabaikan secara diam-diam.
- Jika tidak ada pertanyaan aktif, kuesioner dinyatakan tidak tersedia bagi Pendonor dan sistem tidak membuat `kuesioner_pradonasi` kosong.
- Pengiriman yang berhasil disimpan secara atomik sebagai satu `kuesioner_pradonasi` dan tepat satu `jawaban_kuesioner` untuk setiap pertanyaan aktif. `waktu_pengisian` menyatakan waktu keberhasilan pengiriman menurut konvensi timestamp aplikasi.
- Kuesioner dan jawabannya tetap disimpan sebagai riwayat apabila status pemesanan kemudian berubah atau pemesanan dibatalkan. Pemesanan baru dengan `id_pemesanan` baru merupakan kesempatan donor baru dan mempunyai kuesionernya sendiri.

Phase 7E tidak menghasilkan `kode_checkin`, tidak mengisi `waktu_checkin`, tidak mengubah `status_pemesanan`, serta tidak mengimplementasikan check-in Petugas, review medis kuesioner, seleksi, atau penyumbangan. Phase ini juga tidak menambahkan draft, versioning kuesioner, snapshot teks pertanyaan, atau perubahan schema. Riwayat jawaban tetap mereferensikan row `pertanyaan_kuesioner` yang ada sesuai schema saat ini.

### Kode Check-in

Pendonor dapat:

- melihat kode check-in unik yang berkaitan dengan pemesanan;
- menunjukkan kode tersebut kepada Petugas ketika datang ke UDD.

Kode check-in bukan QR code atau barcode.

Ketentuan bahwa kode check-in bersifat unik, berupa teks biasa, terkait dengan `pemesanan_donor`, dibuat setelah proses pradonasi yang diperlukan termasuk kuesioner selesai, ditunjukkan Pendonor kepada Petugas, dan kemudian digunakan Petugas untuk menemukan kunjungan terkait merupakan aturan yang sudah bersumber. Ketentuan tersebut bukan keputusan baru Phase 7F.

Keputusan lifecycle Phase 7D juga tetap berlaku: proses check-in Petugas yang berhasil pada phase berikutnya melakukan transisi `TERJADWAL` menjadi `CHECK_IN` dan mengisi `waktu_checkin`. Pembuatan atau penampilan kode pada Phase 7F bukan proses check-in tersebut.

#### Keputusan Proyek Phase 7F

Rincian berikut merupakan keputusan proyek Phase 7F untuk perilaku operasional yang sebelumnya belum ditentukan secara tepat:

- Pendonor hanya dapat melihat atau menghasilkan kode check-in untuk `pemesanan_donor` miliknya sendiri. Kepemilikan berasal dari Pendonor yang sedang terautentikasi; identifier Pendonor dari client tidak dapat memberi akses ke pemesanan atau kode Pendonor lain.
- Kode baru hanya dapat dihasilkan untuk pemesanan milik Pendonor terautentikasi yang berstatus `TERJADWAL`, sudah mempunyai `kuesioner_pradonasi`, memiliki tanggal jadwal yang belum lewat menurut WIB (`Asia/Jakarta`), dan tidak terkait dengan jadwal berstatus administratif `DIBATALKAN`. Jadwal berstatus `DITUTUP` tidak dengan sendirinya menggugurkan pemesanan `TERJADWAL` yang sudah valid untuk pembuatan kode.
- Untuk prototype ini, keberadaan `kuesioner_pradonasi` yang tersimpan bagi pemesanan menjadi bukti pada layer aplikasi bahwa prasyarat kuesioner telah selesai. Tidak ada tabel atau status penyelesaian pradonasi tambahan.
- Satu pemesanan mempertahankan satu nilai kode. Setelah `kode_checkin` terisi, akses atau permintaan pembuatan berulang menampilkan atau mempertahankan kode yang sama dan tidak menghasilkan, mengganti, atau merotasinya. Tidak ada riwayat atau versioning kode.
- Format kode yang dihasilkan adalah `UDD-` diikuti tepat 12 karakter heksadesimal huruf besar, dengan bentuk contoh `UDD-A84C21EF07B9`. Panjang totalnya 16 karakter dan tetap berada dalam batas `VARCHAR(50)` yang sudah ada. Format khusus ini merupakan keputusan proyek Phase 7F. Kandidat harus dibuat menggunakan sumber acak yang sesuai untuk kode non-sekuensial dan tidak boleh diturunkan langsung dari ID Pendonor atau ID pemesanan.
- Jika kandidat bertabrakan dengan kode yang sudah ada, aplikasi mencoba kandidat baru. UNIQUE `pemesanan_donor.kode_checkin` yang sudah ada tetap menjadi lapisan integritas terakhir; kode milik pemesanan lain tidak boleh ditimpa dan tidak ada UNIQUE baru.
- Akses `GET` hanya menampilkan halaman dan status kode: kode yang sudah ada ditampilkan, sedangkan pemesanan yang belum mempunyai kode menunjukkan apakah pembuatan tersedia. Akses `POST` melakukan pembuatan kode untuk pemesanan yang memenuhi syarat. `GET` tidak membuat atau merotasi kode.
- Pembuatan dilakukan secara atomik dengan row `pemesanan_donor` sebagai titik serialisasi: kepemilikan diverifikasi, row pemesanan dikunci, keberadaan kode dan seluruh prasyarat diperiksa ulang, lalu satu kode unik dibuat dan disimpan. Permintaan serentak untuk pemesanan yang sama tidak boleh mengganti atau merotasi kode yang telah dibuat.
- Kode yang sudah dibuat tidak dihapus otomatis hanya karena status pemesanan kemudian berubah atau tanggal jadwal berlalu. Retensi ini bukan izin check-in untuk pemesanan yang dibatalkan atau selesai; kelayakan check-in oleh Petugas ditentukan pada Phase 8.

Melihat atau menghasilkan kode tidak mengisi `waktu_checkin`, tidak mengubah `status_pemesanan`, tidak melakukan transisi `TERJADWAL` menjadi `CHECK_IN`, tidak membuat `seleksi_donor`, tidak mengubah data kuesioner, dan tidak mengubah pemesanan lain. Segera setelah kode dibuat untuk pemesanan normal, status tetap `TERJADWAL`, `waktu_checkin` tetap `NULL`, dan `kode_checkin` berisi kode yang baru dihasilkan.

Phase 7F tidak mengimplementasikan check-in Petugas, pencarian kode oleh Petugas, pengisian `waktu_checkin`, transisi ke `CHECK_IN`, tampilan kuesioner bagi Petugas, seleksi, penyumbangan, QR code, barcode, scanner, field kedaluwarsa kode, riwayat kode, tabel token, atau perubahan schema. Fungsi operasional tersebut tetap menjadi Phase 8 atau phase berikutnya.

### Riwayat Donor

Pendonor dapat melihat riwayat penyumbangannya sendiri.

Riwayat donor tidak dapat diubah oleh Pendonor.

Riwayat donor bersumber dari transaksi `penyumbangan` yang dicatat melalui proses operasional oleh Petugas. Transaksi tersebut dapat mempunyai `hasil_penyumbangan = BERHASIL` atau `hasil_penyumbangan = GAGAL`; penyumbangan gagal dapat tetap tercatat, tetapi tidak diperlakukan sebagai donor berhasil dalam perhitungan interval atau frekuensi donor ulang. Pendonor tidak membuat atau mengubah transaksi penyumbangan.

Ketentuan bahwa Pendonor hanya dapat mengakses datanya sendiri, dapat melihat tetapi tidak mengubah riwayat penyumbangan, riwayat berasal dari transaksi `penyumbangan`, penyumbangan dapat berakhir `BERHASIL` atau `GAGAL`, dan hanya penyumbangan berhasil yang digunakan untuk perhitungan donor ulang merupakan aturan yang sudah bersumber. Informasi Donor Berikutnya tetap merupakan fitur Pendonor yang terpisah.

#### Keputusan Proyek Phase 7G

Ketentuan berikut memformalkan rincian operasional halaman Riwayat Donor yang sebelumnya belum ditentukan secara tepat.

- Riwayat milik Pendonor terautentikasi hanya memuat row `penyumbangan` yang kepemilikannya dapat ditelusuri melalui `penyumbangan.id_seleksi` -> `seleksi_donor.id_seleksi` -> `seleksi_donor.id_pemesanan` -> `pemesanan_donor.id_pemesanan` -> `pemesanan_donor.id_pendonor` -> `pendonor.id_pendonor` milik Pendonor terautentikasi. Identifier Pendonor yang dikirim client tidak boleh menentukan kepemilikan.
- Satu row Riwayat Donor hanya ada apabila row `penyumbangan` yang nyata memang tersimpan. `pemesanan_donor` tanpa penyumbangan, status `CHECK_IN` tanpa penyumbangan, `seleksi_donor` tanpa penyumbangan, keputusan `LAYAK`, `DITUNDA`, atau `DITOLAK`, pemesanan dibatalkan, dan ketidakhadiran tidak boleh disintesis menjadi row riwayat.
- Halaman riwayat umum menampilkan seluruh transaksi penyumbangan milik Pendonor, termasuk hasil `BERHASIL` dan `GAGAL`. Penampilan transaksi gagal ini merupakan keputusan proyek Phase 7G; sumber awal hanya menetapkan bahwa transaksi gagal dapat tetap tercatat dan tidak dihitung sebagai donor berhasil untuk aturan donor ulang.
- Riwayat bersifat hanya-baca. Phase 7G tidak memberi Pendonor aksi untuk membuat, mengubah, atau menghapus penyumbangan; mengubah hasil, volume, waktu pengambilan, atau alasan gagal; maupun mengubah seleksi atau status pemesanan.
- Setiap row minimal menampilkan `waktu_pengambilan`, `volume_ml`, `hasil_penyumbangan`, dan `alasan_gagal` ketika berlaku. `volume_ml` yang `NULL` ditampilkan secara netral, misalnya `-`; `alasan_gagal` yang `NULL` tidak memerlukan isi penjelasan.
- Tampilan tidak membuka pengukuran medis rinci dari seleksi, kredensial Petugas, atau data internal lain yang tidak berkaitan hanya karena data tersebut dapat dijangkau melalui relasi.
- Riwayat diurutkan secara deterministik berdasarkan `waktu_pengambilan` menurun, kemudian `id_penyumbangan` menurun sebagai tie-breaker, sehingga transaksi terbaru tampil lebih dahulu.
- Apabila tidak ada row `penyumbangan` milik Pendonor, halaman menampilkan pesan empty state yang terkendali dan tidak membuat riwayat palsu.
- Akses riwayat menggunakan `GET` dan bersifat hanya-baca: tidak memperbarui row basis data, mengubah status pemesanan, membuat seleksi, penyumbangan, atau unit komponen, menandai proses selesai, maupun mengubah data pemberitahuan.
- Phase 7G tidak menambahkan tabel, kolom, snapshot riwayat, cache riwayat, total donor tersimpan, tanggal donor terakhir tersimpan, tanggal donor berikutnya tersimpan, maupun requirement UNIQUE, foreign key, atau index baru. Relasi transaksi yang sudah ada tetap digunakan.

Phase 7G tidak mengimplementasikan perkiraan tanggal donor berikutnya, ringkasan kelayakan donor ulang saat ini, hitungan penyumbangan berhasil tahunan, interval countdown, atau keputusan dapat donor kembali. Phase ini juga tidak menambahkan ekspor PDF/Excel, grafik atau statistik, sertifikat, badge atau reward, kerangka pencarian/filter, editing, penghapusan, arsitektur pagination, riwayat unit komponen, riwayat medis seleksi yang terperinci, fitur Petugas, atau pemberitahuan. Frontend tetap minimal. Perhitungan Informasi Donor Berikutnya tetap berada pada Phase 7H dan tidak digabungkan ke Phase 7G.

### Informasi Donor Berikutnya

Pendonor dapat melihat perkiraan waktu ketika dirinya telah memenuhi interval dan frekuensi untuk mencoba melakukan donor kembali berdasarkan riwayat penyumbangan.

Informasi ini bukan keputusan kelayakan medis akhir.

#### Keputusan Proyek Phase 7H

Rincian berikut mengunci perilaku Informasi Donor Berikutnya tanpa mengubah aturan donor ulang yang sudah digunakan pada Phase 7D:

- fitur hanya menggunakan data milik Pendonor yang sedang terautentikasi;
- sumber perhitungan adalah penyumbangan dengan `hasil_penyumbangan = BERHASIL` milik Pendonor tersebut;
- penyumbangan `GAGAL` tidak digunakan untuk interval maupun frekuensi donor ulang;
- tanggal acuan informasi adalah tanggal hari ini menurut WIB (`Asia/Jakarta`); tanggal acuan ini khusus untuk informasi kondisi saat ini dan tidak mengubah Phase 7D yang mengevaluasi pemesanan terhadap tanggal jadwal terpilih;
- hanya penyumbangan berhasil dengan tanggal pengambilan sampai dengan tanggal acuan yang diperlakukan sebagai riwayat pada perhitungan saat ini;
- jika terdapat riwayat berhasil, batas interval dihitung dari tanggal penyumbangan berhasil paling akhir ditambah 2 bulan kalender dengan perilaku tanpa overflow yang sama seperti Phase 7D;
- frekuensi tahun berjalan dihitung dari penyumbangan `BERHASIL` dalam tahun kalender tanggal acuan sampai dengan tanggal acuan;
- batas frekuensi tetap 6 kali per tahun untuk `LAKI_LAKI` dan 4 kali per tahun untuk `PEREMPUAN`;
- apabila batas frekuensi tahun berjalan sudah tercapai, batas dari sisi frekuensi baru terbuka pada 1 Januari tahun kalender berikutnya;
- perkiraan tanggal donor berikutnya adalah tanggal paling awal yang tidak lebih awal dari tanggal acuan, memenuhi interval 2 bulan, dan berada pada periode ketika batas frekuensi tahunan belum terlampaui;
- secara operasional, tanggal tersebut merupakan nilai maksimum dari tanggal acuan, tanggal hasil interval 2 bulan bila ada, dan 1 Januari tahun berikutnya bila batas frekuensi tahun berjalan sudah tercapai;
- Pendonor tanpa riwayat penyumbangan `BERHASIL` tidak dikenai pembatasan donor ulang untuk kesempatan pertama dan ditampilkan sebagai dapat mencoba donor pertama sekarang berdasarkan riwayat;
- keputusan seleksi DITUNDA atau DITOLAK dan penyumbangan `GAGAL` tidak menghasilkan tanggal penundaan baru pada fitur ini karena prototype tidak memiliki field `ditunda_sampai`;
- apabila seluruh aturan riwayat sudah terpenuhi pada tanggal acuan, UI menyatakan bahwa Pendonor sudah dapat mencoba donor kembali sekarang;
- apabila belum terpenuhi, UI menampilkan perkiraan tanggal paling awal berdasarkan aturan interval/frekuensi di atas;
- hasil selalu disertai penjelasan bahwa informasi ini bukan keputusan kelayakan medis akhir dan Pendonor tetap harus menjalani proses pradonasi serta seleksi Petugas;
- fitur bersifat read-only dan tidak mengubah riwayat penyumbangan, pemesanan, seleksi, maupun data lain;
- tanggal donor terakhir, jumlah donor, status donor ulang, dan tanggal donor berikutnya tetap merupakan data turunan dan tidak disimpan sebagai field baru.

Dashboard Pendonor menampilkan ringkasan hasil perhitungan donor berikutnya dan menyediakan navigasi menuju informasi yang lebih rinci. Ringkasan dan halaman rinci harus menggunakan aturan perhitungan yang sama.

Phase 7H tidak menambahkan keputusan medis, countdown wajib, field penundaan, tabel baru, kolom baru, atau perubahan schema.

### Pemberitahuan

Pendonor dapat:

- melihat pemberitahuan yang ditujukan kepadanya;
- membaca isi pemberitahuan;
- menandai pemberitahuan sebagai sudah dibaca.

Pemberitahuan hanya berada di dalam aplikasi.

#### Keputusan Proyek Phase 7I

Rincian berikut mengunci perilaku sisi Pendonor untuk fitur Pemberitahuan:

- Pendonor hanya dapat melihat dan menandai pemberitahuan yang ditujukan kepada dirinya sendiri. Kepemilikan berasal dari Pendonor yang terhubung dengan akun yang sedang terautentikasi; identifier Pendonor dari client tidak menentukan kepemilikan.
- Daftar pemberitahuan hanya memuat row `pemberitahuan` milik Pendonor terautentikasi.
- Daftar diurutkan secara deterministik berdasarkan `waktu_dibuat DESC`, kemudian `id_pemberitahuan DESC` sebagai tie-breaker.
- Pendonor dapat membuka halaman detail untuk membaca isi lengkap pemberitahuan miliknya.
- Akses daftar dan detail menggunakan `GET` dan bersifat hanya-baca. Membuka halaman tidak otomatis mengubah `waktu_dibaca`.
- Status tampilan `Belum dibaca` diturunkan dari kondisi `waktu_dibaca IS NULL`, sedangkan `Sudah dibaca` diturunkan dari `waktu_dibaca IS NOT NULL`. Tidak ada field status pemberitahuan tambahan.
- Aksi `Tandai Sudah Dibaca` dilakukan secara eksplisit melalui request mutasi non-GET, yaitu `PATCH`.
- Pada penandaan pertama yang berhasil, `waktu_dibaca` diisi dengan waktu aksi menurut konvensi timestamp aplikasi.
- Penandaan ulang bersifat idempotent. Jika `waktu_dibaca` sudah terisi, request berikutnya mempertahankan timestamp yang sudah ada, tidak membuat row baru, dan tidak menghasilkan error hanya karena pemberitahuan sudah dibaca.
- Request serentak untuk menandai pemberitahuan yang sama harus menghasilkan satu state akhir yang konsisten. Implementasi boleh menggunakan transaction dan row locking sederhana agar timestamp pertama yang berhasil tidak diganti oleh request paralel berikutnya.
- Pemberitahuan milik Pendonor lain tidak boleh dapat dibaca atau diubah melalui manipulasi URL, route parameter, query string, atau request body. Resource harus ditolak secara terkendali melalui ownership server-side.
- Daftar minimal menampilkan waktu dibuat, Petugas pengirim, isi atau ringkasan pesan, dan status baca. Halaman detail menampilkan isi pesan lengkap serta informasi pengirim dan waktu yang relevan.
- Pendonor tidak dapat membuat, mengirim, mengedit, atau menghapus pemberitahuan.
- Pembuatan dan pengiriman pemberitahuan oleh Petugas tetap berada pada Phase 8. Phase 7I hanya mengimplementasikan sisi penerima Pendonor.
- Dashboard Pendonor menampilkan jumlah pemberitahuan yang belum dibaca, maksimal 3 pemberitahuan terbaru milik Pendonor, dan link nyata menuju daftar seluruh pemberitahuan.
- Tiga pemberitahuan terbaru pada dashboard menggunakan urutan yang sama dengan halaman daftar.
- Jika tidak ada pemberitahuan, daftar dan ringkasan dashboard menampilkan empty state yang terkendali.
- Phase 7I tidak menambahkan fitur tandai semua sudah dibaca, tandai belum dibaca, filter, search, arsitektur pagination, delete, edit, email, SMS, WhatsApp, WebSocket, realtime push, Laravel Notification framework, tabel baru, kolom baru, atau perubahan schema.
- Semua tombol dan link yang ditampilkan pada fitur Phase 7I harus mempunyai route dan aksi yang benar-benar berfungsi.

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

#### Keputusan Proyek Phase 8A - Dashboard Petugas

Dashboard Petugas merupakan ringkasan operasional hanya-baca untuk satu UDD.

Ketentuan Phase 8A dikunci sebagai berikut:

- route dashboard tetap `GET /petugas` dengan nama `petugas.home` dan wajib dilindungi autentikasi, status akun aktif, serta role `PETUGAS`;
- akun Petugas yang sedang terautentikasi tetap harus mempunyai profil `petugas` yang valid, tetapi data ringkasan operasional mencakup satu UDD secara keseluruhan dan bukan hanya transaksi yang dicatat oleh Petugas tersebut;
- tanggal acuan yang digunakan untuk ringkasan berbasis hari adalah tanggal hari ini menurut WIB (`Asia/Jakarta`);
- bagian kegiatan donor hari ini dihitung dari `pemesanan_donor` yang terkait dengan `jadwal_pelayanan.tanggal` pada tanggal acuan;
- kegiatan donor hari ini menampilkan jumlah pemesanan untuk masing-masing status `TERJADWAL`, `CHECK_IN`, `SELESAI`, dan `TIDAK_HADIR`;
- pemesanan `DIBATALKAN` tidak dihitung dalam ringkasan kegiatan donor hari ini;
- definisi kegiatan donor hari ini hanya merupakan ringkasan dashboard dan tidak mengubah lifecycle atau arti `status_pemesanan`;
- jumlah Pendonor yang sedang diproses adalah jumlah Pendonor berbeda yang mempunyai `pemesanan_donor.status_pemesanan = CHECK_IN`;
- hitungan Pendonor yang sedang diproses tidak dibatasi oleh tanggal jadwal. Row yang masih berstatus `CHECK_IN` tetap terlihat sebagai state workflow sampai proses operasional yang berwenang mengubah status tersebut;
- Dashboard Petugas tidak memperbaiki, menutup, atau mengubah status workflow secara otomatis;
- kondisi persediaan menggunakan jumlah unit yang memiliki `status_unit = TERSEDIA` dan `tanggal_kedaluwarsa` sama dengan atau setelah tanggal acuan;
- jumlah persediaan selalu dihitung saat diperlukan dan tidak disimpan sebagai field atau angka stok manual;
- ringkasan persediaan minimal menampilkan total unit yang saat ini tersedia;
- kondisi persediaan rendah dievaluasi untuk setiap kombinasi yang mempunyai konfigurasi `ambang_persediaan`;
- jumlah persediaan untuk ambang dihitung berdasarkan kombinasi `id_jenis_komponen` dan `id_golongan_darah`;
- kombinasi berada pada kondisi rendah apabila `jumlah_persediaan <= jumlah_minimum`;
- kombinasi dengan stok `0` tetap dievaluasi terhadap ambang dan dapat menjadi kondisi persediaan rendah;
- kombinasi yang tidak mempunyai row `ambang_persediaan` tidak diberi ambang default dan tidak diklasifikasikan sebagai low-stock secara otomatis;
- dashboard menampilkan jumlah kombinasi yang berada pada kondisi rendah serta seluruh kombinasi low-stock dengan informasi minimal jenis komponen, golongan darah ABO/Rhesus, jumlah persediaan, dan jumlah minimum;
- dashboard tidak membatasi daftar low-stock menggunakan jumlah arbitrer seperti top 3;
- apabila belum ada konfigurasi ambang, daftar low-stock menampilkan empty state yang terkendali tanpa menghalangi perhitungan total unit tersedia;
- Phase 8A tidak menyediakan aksi check-in, seleksi, penyumbangan, pembuatan unit, pelulusan, distribusi, pemanggilan Pendonor, atau pembuatan pemberitahuan;
- dashboard hanya boleh menampilkan navigasi ke route yang benar-benar sudah tersedia. Menu atau tombol untuk subphase Phase 8 yang belum diimplementasikan tidak boleh menjadi placeholder atau dead link;
- akses dashboard menggunakan `GET` dan tidak membuat, memperbarui, atau menghapus row bisnis;
- Phase 8A tidak menambahkan chart, grafik, realtime update, AJAX, SPA, cache stok, tabel ringkasan, service architecture, tabel baru, field baru, enum baru, index baru, atau perubahan schema.

### Check-in Pendonor

Petugas dapat:

- memasukkan kode check-in;
- membuka pemesanan yang sesuai;
- melihat data Pendonor yang diperlukan;
- melihat jadwal;
- melihat kuesioner yang terkait;
- mencatat waktu check-in;
- memperbarui status pemesanan sesuai proses.

#### Keputusan Proyek Phase 8B - Check-in Petugas

Phase 8B mengimplementasikan check-in operasional Petugas tanpa menambahkan tabel atau field baru.

Ketentuan Phase 8B dikunci sebagai berikut:

- hanya akun aktif dengan peran `PETUGAS` dan profil `petugas` yang valid yang dapat menggunakan fungsi check-in;
- fungsi check-in berlaku untuk kunjungan pada satu UDD secara keseluruhan dan tidak dibatasi oleh kepemilikan transaksi terhadap Petugas tertentu;
- halaman utama menggunakan `GET /petugas/check-in`;
- pencarian kode pada halaman tersebut bersifat hanya-baca dan tidak mengubah row bisnis;
- Petugas memasukkan satu `kode_checkin`; input dihapus whitespace awal/akhirnya dan dinormalisasi menjadi huruf besar sebelum validasi dan pencarian;
- format kode yang diterima tetap format Phase 7F, yaitu `UDD-` diikuti tepat 12 karakter heksadesimal;
- kode yang valid digunakan untuk menemukan satu `pemesanan_donor` berdasarkan UNIQUE `kode_checkin`;
- hasil lookup menampilkan informasi kunjungan minimal berupa Pendonor, jadwal, pemesanan, status pemesanan, dan keberadaan kuesioner pradonasi;
- Phase 8B belum menampilkan atau mengubah jawaban rinci kuesioner sebagai proses review; tampilan jawaban Petugas tetap berada pada phase berikutnya;
- lookup kode tidak melakukan check-in otomatis. Check-in baru dilakukan melalui aksi konfirmasi `POST`;
- check-in baru hanya dapat dilakukan untuk pemesanan berstatus tepat `TERJADWAL`;
- pemesanan `SELESAI`, `DIBATALKAN`, atau `TIDAK_HADIR` tidak dapat ditransisikan menjadi `CHECK_IN`;
- pemesanan harus memiliki `kode_checkin` yang sesuai dan satu `kuesioner_pradonasi` yang sudah tersimpan;
- tanggal `jadwal_pelayanan.tanggal` untuk check-in baru harus tepat sama dengan tanggal hari ini menurut WIB (`Asia/Jakarta`);
- pemesanan dengan tanggal jadwal masa depan atau yang sudah lewat tidak dapat menjalani check-in baru;
- `status_jadwal = DIBATALKAN` menolak check-in baru;
- `status_jadwal = DITUTUP` tidak dengan sendirinya menggugurkan pemesanan `TERJADWAL` yang sudah valid dan mempunyai kode check-in;
- Phase 8B tidak menambahkan pembatasan berdasarkan `jam_mulai` atau `jam_selesai`;
- check-in berhasil hanya mengubah `status_pemesanan` dari `TERJADWAL` menjadi `CHECK_IN` dan mengisi `waktu_checkin` menggunakan konvensi timestamp aplikasi;
- check-in tidak mengubah `kode_checkin`, kuesioner, jadwal, pemesanan lain, profil Pendonor, atau transaksi operasional lain;
- pengiriman ulang untuk kode yang sudah berhasil check-in bersifat idempotent apabila status sudah `CHECK_IN` dan `waktu_checkin` sudah terisi: timestamp pertama dipertahankan dan tidak ada mutation kedua;
- kombinasi state yang tidak konsisten, misalnya `CHECK_IN` tetapi `waktu_checkin = NULL` atau `TERJADWAL` tetapi `waktu_checkin` sudah terisi, ditolak secara terkendali dan tidak diperbaiki otomatis;
- mutation check-in dilakukan dalam transaksi basis data dengan row `pemesanan_donor` target sebagai titik serialisasi dan dikunci menggunakan row lock sebelum syarat diperiksa ulang;
- apabila dua Petugas mengirim check-in untuk kode yang sama secara bersamaan, hanya request pertama yang melakukan transisi; request berikutnya setelah memperoleh lock melihat state `CHECK_IN` dan diperlakukan sebagai pengiriman ulang idempotent;
- setelah check-in berhasil digunakan pola Post/Redirect/Get sehingga refresh halaman hasil tidak mengulang mutation;
- setelah route Check-in nyata tersedia, Dashboard Petugas boleh menampilkan navigasi nyata menuju `Check-in Pendonor`;
- Phase 8B tidak menampilkan link atau aksi operasional Phase 8C dan seterusnya sebelum route tersebut benar-benar tersedia;
- Phase 8B tidak membuat `seleksi_donor`, `penyumbangan`, unit komponen, pemberitahuan, atau mutation lain di luar check-in;
- Phase 8B tidak menambahkan tabel check-in, tabel log, field Petugas check-in, status baru, migration, custom index, QR code, barcode, scanner, service/repository architecture, AJAX, SPA, atau dependency baru.

### Kuesioner Pradonasi

Petugas dapat melihat jawaban kuesioner Pendonor sebagai salah satu informasi dalam proses seleksi.

Petugas tidak mengelola master pertanyaan kuesioner.

#### Keputusan Proyek Phase 8C - Tampilan Kuesioner Petugas

Phase 8C hanya menambahkan tampilan read-only jawaban kuesioner bagi Petugas setelah check-in dan sebelum seleksi.

- Akses hanya untuk akun `PETUGAS` aktif dengan profil `petugas` yang valid dan berlaku untuk satu UDD.
- Route menggunakan `GET /petugas/pemesanan/{pemesanan}/kuesioner` dengan nama `petugas.kuesioner.show`.
- Target ditentukan dari `pemesanan_donor` pada route. `id_petugas`, `id_pendonor`, `id_kuesioner`, dan `kode_checkin` dari client tidak menentukan target.
- Tampilan diperbolehkan untuk `CHECK_IN` atau `SELESAI` apabila `waktu_checkin` sudah terisi.
- `TERJADWAL`, `DIBATALKAN`, dan `TIDAK_HADIR` bukan jalur tampilan operasional Phase 8C.
- Setelah check-in berhasil, tanggal dan status administratif jadwal tidak membatasi tampilan historical kuesioner.
- Kuesioner dan jawaban harus sudah tersimpan. Data yang hilang atau tidak konsisten tidak dibuat atau diperbaiki otomatis.
- Daftar jawaban berasal dari row `jawaban_kuesioner` yang benar-benar tersimpan dan tetap menampilkan jawaban untuk pertanyaan yang kemudian `NONAKTIF`.
- Jawaban diurutkan menurut `pertanyaan_kuesioner.urutan`, kemudian `id_pertanyaan`.
- Nilai `YA_TIDAK` ditampilkan sebagai `YA` atau `TIDAK`; nilai `TEKS` ditampilkan sebagaimana tersimpan. Tidak ada interpretasi atau aturan medis baru.
- Halaman bersifat sepenuhnya read-only: tidak mengubah pemesanan, kuesioner, jawaban, pertanyaan, jadwal, Pendonor, atau transaksi lain dan tidak membuat `seleksi_donor`.
- Karena tidak ada mutation, Phase 8C tidak memakai `DB::transaction()` atau `lockForUpdate()`.
- Halaman Check-in boleh menampilkan link nyata `Lihat Kuesioner` untuk pemesanan yang sudah `CHECK_IN`.
- Tombol atau route `Seleksi Donor` belum ditampilkan sampai Phase 8D benar-benar diimplementasikan.
- Tidak ada tabel review, field review, snapshot/versioning pertanyaan, migration, custom index, service/repository/DTO, AJAX, SPA, atau dependency baru.
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

#### Keputusan Proyek Phase 8D - Seleksi Donor

Rincian berikut mengunci perilaku operasional Seleksi Donor pada Phase 8D.

- Seleksi diakses berdasarkan `pemesanan_donor` sebagai target utama. Target tidak ditentukan oleh `id_petugas`, `id_pendonor`, `id_seleksi`, `kode_checkin`, atau identifier tambahan dari client.
- Route Phase 8D menggunakan `GET /petugas/pemesanan/{pemesanan}/seleksi` bernama `petugas.seleksi.show` dan `POST /petugas/pemesanan/{pemesanan}/seleksi` bernama `petugas.seleksi.store`.
- Fungsi hanya tersedia bagi akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan relasi profil `petugas` yang valid.
- Sistem mencakup satu UDD. Petugas aktif yang valid dapat menangani pemesanan operasional yang memenuhi syarat; pemesanan tidak dimiliki oleh Petugas tertentu.
- Seleksi baru hanya dapat dibuat untuk pemesanan dengan `status_pemesanan = CHECK_IN` dan `waktu_checkin` yang sudah terisi.
- Pemesanan `TERJADWAL`, `SELESAI`, `DIBATALKAN`, atau `TIDAK_HADIR` tidak dapat menghasilkan seleksi baru.
- Pemesanan harus mempunyai `kuesioner_pradonasi` dan minimal satu `jawaban_kuesioner` yang tersimpan. Data yang hilang atau tidak konsisten ditolak secara terkendali dan tidak dibuat atau diperbaiki otomatis.
- Setelah check-in valid terjadi, tanggal, jam, dan status administratif jadwal tidak diperiksa ulang sebagai syarat pembuatan seleksi.
- Satu pemesanan maksimal mempunyai satu `seleksi_donor`. Seleksi bersifat satu kali pencatatan: tidak ada edit, delete, revisi, approval tambahan, atau riwayat perubahan seleksi pada prototype.
- Seleksi yang sudah tersimpan tetap dapat dilihat sebagai riwayat meskipun tanggal jadwal telah lewat atau status pemesanan kemudian berubah.
- `id_petugas` berasal dari profil Petugas yang sedang terautentikasi dan `waktu_seleksi` ditentukan oleh server ketika transaksi berhasil. Nilai tersebut tidak dipercaya dari request client.
- Field pemeriksaan mengikuti schema `seleksi_donor`: `berat_badan`, `tekanan_sistolik`, `tekanan_diastolik`, `denyut_nadi`, `suhu_tubuh`, `kadar_hb`, `hasil_pemeriksaan_kesehatan`, `keputusan_seleksi`, dan `alasan_keputusan`.
- Enam nilai pengukuran utama dan `keputusan_seleksi` wajib diisi. `hasil_pemeriksaan_kesehatan` dan `alasan_keputusan` tetap nullable sesuai schema.
- Keputusan hanya `LAYAK`, `DITUNDA`, atau `DITOLAK`.
- Phase 8D tidak menambahkan threshold medis atau rule engine untuk menentukan keputusan dari hasil pengukuran maupun jawaban kuesioner. Keputusan dicatat oleh Petugas.
- Jawaban kuesioner merupakan informasi pendukung seleksi dan tidak menghasilkan skor risiko atau keputusan otomatis.
- Jika `pendonor.id_golongan_darah` masih `NULL`, Petugas dapat mencatat golongan darah yang sudah dikonfirmasi menggunakan master `golongan_darah`. Pengisian ini tidak diwajibkan hanya untuk membuat seleksi.
- Jika Pendonor sudah memiliki `id_golongan_darah`, Phase 8D menampilkannya sebagai data terkonfirmasi dan tidak mengizinkan perubahan melalui request seleksi.
- Pembuatan seleksi dilakukan secara atomik dengan row `pemesanan_donor` sebagai titik serialisasi. Setelah row dikunci, aplikasi memeriksa ulang state check-in, prasyarat kuesioner, dan keberadaan seleksi sebelum menyimpan.
- Permintaan ganda atau serentak untuk pemesanan yang sama tidak boleh menghasilkan seleksi kedua atau menimpa seleksi pertama. Constraint UNIQUE `seleksi_donor.id_pemesanan` yang sudah ada tetap menjadi lapisan integritas terakhir.
- Jika keputusan `LAYAK`, `status_pemesanan` tetap `CHECK_IN` agar proses dapat dilanjutkan ke Penyumbangan pada Phase 8E.
- Jika keputusan `DITUNDA` atau `DITOLAK`, seleksi dan transisi `status_pemesanan` dari `CHECK_IN` menjadi `SELESAI` disimpan dalam transaksi yang sama karena proses donor pada kesempatan tersebut tidak dilanjutkan.
- Halaman Kuesioner Petugas boleh menyediakan aksi nyata menuju Seleksi Donor setelah Phase 8D tersedia. Untuk pemesanan yang belum mempunyai seleksi dan masih memenuhi syarat, aksi dapat berupa `Seleksi Donor`; seleksi yang sudah ada dapat dibuka sebagai tampilan read-only.
- Phase 8D belum menyediakan aksi Penyumbangan. Aksi tersebut baru boleh muncul setelah Phase 8E benar-benar diimplementasikan.
- Phase 8D tidak membuat `penyumbangan`, unit komponen darah, pelulusan, distribusi, pemberitahuan, tabel baru, kolom baru, index baru, atau perubahan schema.

### Penyumbangan

Petugas dapat mencatat penyumbangan untuk seleksi yang `LAYAK`.

Data yang dicatat mencakup:

- waktu pengambilan;
- volume darah;
- hasil `BERHASIL` atau `GAGAL`;
- alasan gagal jika diperlukan.

#### Keputusan Proyek Phase 8E - Penyumbangan

Rincian berikut mengunci perilaku operasional Penyumbangan pada Phase 8E.

- Target utama Phase 8E adalah `seleksi_donor`. Route menggunakan `GET /petugas/seleksi/{seleksi}/penyumbangan` bernama `petugas.penyumbangan.show` dan `POST /petugas/seleksi/{seleksi}/penyumbangan` bernama `petugas.penyumbangan.store`.
- Fungsi hanya tersedia bagi akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan relasi profil `petugas` yang valid.
- `id_petugas_pencatat` berasal dari Petugas terautentikasi. Identifier Petugas, pemesanan, Pendonor, penyumbangan, atau workflow lain dari client tidak boleh mengganti target route maupun Petugas pencatat.
- Penyumbangan baru hanya dapat dibuat dari seleksi dengan `keputusan_seleksi = LAYAK`.
- Pemesanan terkait harus masih `status_pemesanan = CHECK_IN` dan mempunyai `waktu_checkin`.
- Seleksi `DITUNDA` atau `DITOLAK` tidak dapat menghasilkan penyumbangan.
- Setelah workflow mencapai seleksi `LAYAK`, tanggal jadwal, jam pelayanan, dan `status_jadwal` tidak diperiksa ulang sebagai filter pencatatan penyumbangan.
- Satu `seleksi_donor` maksimal mempunyai satu `penyumbangan`. Prototype tidak menyediakan edit, delete, revisi, atau penggantian transaksi penyumbangan.
- Penyumbangan existing tetap dapat dilihat secara read-only setelah pemesanan menjadi `SELESAI`, tanggal jadwal berlalu, atau status administratif jadwal berubah.
- `waktu_pengambilan` merupakan data operasional yang dicatat Petugas melalui form. Phase 8E tidak otomatis menggantinya dengan waktu request server.
- `hasil_penyumbangan` hanya `BERHASIL` atau `GAGAL`.
- Untuk `BERHASIL`, `volume_ml` wajib dan hanya menggunakan Whole Blood `350` atau `450` mL.
- Untuk `GAGAL`, `volume_ml` boleh `NULL`; jika dicatat, nilainya tetap hanya `350` atau `450` mL.
- Volume `350` mL memerlukan `seleksi_donor.berat_badan >= 45` kg dan volume `450` mL memerlukan `seleksi_donor.berat_badan >= 55` kg.
- Berat badan authoritative berasal dari seleksi yang sudah tersimpan, bukan dari request Penyumbangan.
- `alasan_gagal` tetap nullable sesuai schema dan tidak diwajibkan oleh Phase 8E.
- Pembuatan penyumbangan menggunakan transaction dan row locking untuk menjaga satu seleksi hanya menghasilkan satu transaksi.
- UNIQUE `penyumbangan.id_seleksi` yang sudah ada tetap menjadi lapisan integritas terakhir.
- Setelah penyumbangan `BERHASIL` maupun `GAGAL` dicatat, `status_pemesanan` berubah dari `CHECK_IN` menjadi `SELESAI` dalam transaksi yang sama.
- Halaman Seleksi Donor menampilkan `Catat Penyumbangan` untuk seleksi `LAYAK` yang belum mempunyai penyumbangan dan `Lihat Penyumbangan` jika transaksi sudah tersimpan.
- Seleksi `DITUNDA` atau `DITOLAK` tidak menampilkan aksi Penyumbangan.
- Phase 8E tidak membuat unit komponen darah. Penyumbangan `BERHASIL` baru dapat menjadi sumber Phase 8F, sedangkan `GAGAL` tidak dapat menghasilkan unit.
- Phase 8E tidak menambah tabel, kolom, migration, index, notification, pelulusan, distribusi, atau perubahan schema.

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

#### Keputusan Proyek Phase 8F - Unit Komponen Darah

Rincian berikut mengunci perilaku operasional pencatatan Unit Komponen Darah pada Phase 8F.

- Target utama Phase 8F adalah `penyumbangan`. Route menggunakan `GET /petugas/penyumbangan/{penyumbangan}/unit-komponen` bernama `petugas.unit-komponen.show` dan `POST /petugas/penyumbangan/{penyumbangan}/unit-komponen` bernama `petugas.unit-komponen.store`.
- Fungsi hanya tersedia bagi akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan relasi profil `petugas` yang valid.
- `id_penyumbangan` berasal dari target route dan `id_petugas_pencatat` berasal dari profil Petugas yang sedang terautentikasi. Identifier authority yang dikirim client tidak boleh mengganti keduanya.
- Unit baru hanya dapat dibuat dari `penyumbangan.hasil_penyumbangan = BERHASIL`. Penyumbangan `GAGAL` tidak dapat menghasilkan unit.
- Setelah penyumbangan berhasil tersimpan, Phase 8F tidak memeriksa ulang status pemesanan, tanggal jadwal, status jadwal, atau jam pelayanan sebagai syarat pencatatan unit.
- Satu penyumbangan berhasil dapat menghasilkan satu atau lebih unit komponen darah.
- Satu pengiriman form `POST` membuat tepat satu row `unit_komponen_darah`. Petugas dapat mengulangi pencatatan untuk menambah unit lain dari penyumbangan yang sama.
- Form pencatatan unit hanya menerima `nomor_unit`, `id_jenis_komponen`, `id_golongan_darah`, `tanggal_pembuatan`, dan `tanggal_kedaluwarsa`.
- `nomor_unit` diinput Petugas, wajib diisi setelah trimming, maksimal 50 karakter, dan harus unik sesuai UNIQUE existing `unit_komponen_darah.nomor_unit`.
- Phase 8F tidak membuat format nomor unit baru dan tidak menghasilkan `nomor_unit` secara otomatis karena specification tidak menentukan format tersebut.
- Jenis komponen dipilih dari master `jenis_komponen_darah` existing dan dibatasi pada kode prototype `WB`, `PRC`, `TC`, dan `FFP`.
- Phase 8F tidak menambahkan aturan pemisahan darah, jumlah maksimum produk per penyumbangan, atau rule medis baru berdasarkan jenis komponen.
- Golongan darah unit dipilih dari master `golongan_darah` existing. Pencatatan unit tidak mengubah `pendonor.id_golongan_darah`.
- `tanggal_pembuatan` dan `tanggal_kedaluwarsa` merupakan input Petugas dan wajib berupa tanggal yang dapat disimpan.
- Phase 8F tidak menghitung tanggal kedaluwarsa otomatis, tidak menggunakan masa simpan komponen dari pengetahuan umum, dan tidak menambah field `masa_simpan_hari`.
- Phase 8F tidak menambahkan validasi urutan antara `tanggal_pembuatan` dan `tanggal_kedaluwarsa` karena specification tidak menetapkan rule tersebut.
- Saat unit dibuat, server menetapkan `status_unit = MENUNGGU_PELULUSAN`.
- Pada Phase 8F, `id_petugas_pelulus`, `waktu_pelulusan`, `catatan_pelulusan`, dan `waktu_distribusi` tetap `NULL`.
- Unit `MENUNGGU_PELULUSAN` belum dihitung sebagai persediaan tersedia.
- Unit yang sudah tersimpan ditampilkan sebagai daftar read-only pada halaman sumber penyumbangan. Phase 8F tidak menyediakan edit, delete, revisi, pelulusan, atau distribusi.
- Pembuatan unit dilakukan dalam transaction dengan row `penyumbangan` target sebagai titik serialisasi. Setelah row dikunci, hasil `BERHASIL` dan keunikan `nomor_unit` diperiksa ulang sebelum create.
- Pengiriman ganda untuk penyumbangan yang sama dengan `nomor_unit` yang sama tidak boleh menghasilkan row kedua atau menimpa unit pertama. UNIQUE `unit_komponen_darah.nomor_unit` existing tetap menjadi lapisan integritas terakhir.
- `nomor_unit` yang berbeda tetap boleh menghasilkan unit tambahan dari penyumbangan yang sama, termasuk jenis komponen yang sama, karena schema tidak mempunyai UNIQUE `(id_penyumbangan, id_jenis_komponen)`.
- Setelah route Phase 8F tersedia, halaman Penyumbangan `BERHASIL` boleh menampilkan navigasi nyata menuju `Unit Komponen Darah`. Penyumbangan `GAGAL` tidak menampilkan aksi tersebut.
- Phase 8F berhenti pada pencatatan dan penampilan unit berstatus `MENUNGGU_PELULUSAN`. Pelulusan tetap menjadi Phase 8G dan distribusi tetap phase berikutnya.
- Phase 8F tidak menambah tabel, field, enum, UNIQUE, foreign key, index, migration, notification, stok manual, atau perubahan schema.

### Pelulusan Unit

Petugas dapat memproses unit yang berstatus `MENUNGGU_PELULUSAN`.

Hasil pelulusan:

- unit yang memenuhi persyaratan menjadi `TERSEDIA`;
- unit yang tidak memenuhi persyaratan menjadi `DITOLAK`.

Petugas pelulus dan waktu pelulusan dicatat ketika proses pelulusan dilakukan.

#### Keputusan Proyek Phase 8G - Pelulusan Unit

Rincian berikut mengunci perilaku operasional Pelulusan Unit pada Phase 8G tanpa menambah proses laboratorium rinci.

- Phase 8G menyediakan menu global `Pelulusan` bagi Petugas untuk memilih unit yang masih berstatus `MENUNGGU_PELULUSAN`.
- Route menggunakan `GET /petugas/pelulusan` bernama `petugas.pelulusan.index`, `GET /petugas/pelulusan/{unit}` bernama `petugas.pelulusan.show`, dan `POST /petugas/pelulusan/{unit}` bernama `petugas.pelulusan.store`.
- Target detail dan mutasi berasal dari route-bound `UnitKomponenDarah`. Identifier unit, Petugas pelulus, sumber penyumbangan, atau workflow lain dari client tidak boleh mengganti target dan authority server.
- Fungsi hanya tersedia bagi akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan relasi profil `petugas` yang valid.
- `id_petugas_pelulus` selalu berasal dari profil Petugas yang sedang terautentikasi.
- Halaman index hanya menampilkan unit dengan `status_unit = MENUNGGU_PELULUSAN` sebagai kandidat yang dapat diproses.
- Hasil pelulusan yang dapat dicatat hanya `TERSEDIA` atau `DITOLAK`.
- Form mutasi hanya menerima `hasil_pelulusan` dan `catatan_pelulusan`.
- `catatan_pelulusan` tetap nullable. Nilai kosong setelah trimming disimpan sebagai `NULL` dan tidak diwajibkan khusus untuk hasil `DITOLAK`.
- `waktu_pelulusan` ditentukan server pada saat transaksi pelulusan berhasil dan bukan input form Petugas.
- Pelulusan bersifat satu kali terhadap lifecycle unit: hanya `MENUNGGU_PELULUSAN` yang dapat dimutasi menjadi `TERSEDIA` atau `DITOLAK`.
- Phase 8G tidak menyediakan edit, revisi, undo, relulus, transisi `TERSEDIA` menjadi `DITOLAK`, atau transisi `DITOLAK` menjadi `TERSEDIA`.
- Unit yang sudah `TERSEDIA`, `DITOLAK`, atau pada phase berikutnya `DIDISTRIBUSIKAN` dapat dibuka melalui halaman detail sebagai riwayat read-only, tetapi tidak menampilkan form pelulusan.
- Pencatatan pelulusan dilakukan dalam transaction dengan row `unit_komponen_darah` target dikunci menggunakan row-level lock. Setelah lock, status unit diperiksa ulang sebelum mutation.
- Jika request pelulusan dikirim dua kali atau dua tab memproses unit yang sama, hanya request pertama yang boleh mengubah unit. Request berikutnya membaca state terbaru dan ditolak secara terkendali tanpa menimpa hasil pertama.
- Kondisi kedaluwarsa tidak mengubah enum `status_unit` dan Phase 8G tidak menambahkan status `KEDALUWARSA`.
- Phase 8G tidak menjadikan `tanggal_kedaluwarsa` sebagai syarat tambahan untuk menentukan apakah unit `MENUNGGU_PELULUSAN` boleh dicatat hasil pelulusannya. Kondisi kedaluwarsa tetap digunakan pada perhitungan persediaan.
- Unit yang berakhir `TERSEDIA` hanya dihitung sebagai persediaan apabila belum melewati `tanggal_kedaluwarsa`, sesuai aturan persediaan existing.
- Phase 8G tidak mengubah `nomor_unit`, sumber penyumbangan, jenis komponen, golongan darah, Petugas pencatat, tanggal pembuatan, tanggal kedaluwarsa, atau `waktu_distribusi`.
- Phase 8G tidak membuat atau mengedit angka stok. Perubahan persediaan terjadi secara derived dari `status_unit` dan `tanggal_kedaluwarsa`.
- Sistem tidak menentukan metode pemeriksaan laboratorium. Phase 8G hanya mencatat hasil setelah proses pemeriksaan di luar rincian sistem selesai.
- Phase 8G tidak menambahkan alat, reagen, IMLTD, metode QC, workflow laboratorium, tabel, field, enum, UNIQUE, foreign key, index, migration, atau package baru.
- Setelah route nyata tersedia, Dashboard Petugas boleh menampilkan navigasi `Pelulusan`. Tidak boleh ada link Distribusi Phase 8H sebelum route dan fungsinya benar-benar tersedia.
- Phase 8G berhenti setelah pencatatan hasil pelulusan. Distribusi tetap menjadi Phase 8H.

### Distribusi Unit

Petugas dapat mencatat unit tersedia yang keluar dari persediaan UDD sebagai `DIDISTRIBUSIKAN`.

Waktu distribusi dicatat.

Distribusi tidak dimodelkan secara rinci sampai rumah sakit atau pasien.

#### Keputusan Proyek Phase 8H - Distribusi Unit

Rincian berikut mengunci perilaku operasional Distribusi Unit pada Phase 8H tanpa menambah tujuan penerima atau proses logistik rinci.

- Phase 8H menyediakan menu global `Distribusi` bagi Petugas untuk memilih unit yang masih `TERSEDIA` dan belum kedaluwarsa.
- Route menggunakan `GET /petugas/distribusi` bernama `petugas.distribusi.index`, `GET /petugas/distribusi/{unit}` bernama `petugas.distribusi.show`, dan `POST /petugas/distribusi/{unit}` bernama `petugas.distribusi.store`.
- Target detail dan mutasi berasal dari route-bound `UnitKomponenDarah`. Identifier unit, status, waktu distribusi, atau workflow lain dari client tidak boleh mengganti target dan authority server.
- Fungsi hanya tersedia bagi akun terautentikasi dengan `status_akun = AKTIF`, `peran = PETUGAS`, dan relasi profil `petugas` yang valid.
- Schema tidak mempunyai `id_petugas_distributor`. Phase 8H tidak menambah field atau tabel audit distributor hanya untuk mencatat Petugas yang melakukan distribusi.
- Tanggal acuan distribusi adalah tanggal kalender hari ini menurut WIB (`Asia/Jakarta`). Kandidat distribusi harus mempunyai `status_unit = TERSEDIA` dan `tanggal_kedaluwarsa >= tanggal_acuan`; tanggal kedaluwarsa yang sama dengan tanggal acuan masih valid.
- Index hanya menampilkan kandidat distribusi yang valid, diurutkan berdasarkan `id_unit` menaik, dan bersifat read-only.
- Unit `DIDISTRIBUSIKAN` tetap dapat dibuka sebagai riwayat read-only dan menampilkan `waktu_distribusi`. Unit `TERSEDIA` yang sudah kedaluwarsa juga hanya dapat dilihat tanpa aksi distribusi.
- Phase 8H tidak menerima field bisnis baru. `POST` hanya menyatakan permintaan untuk mendistribusikan unit authoritative pada route apabila masih eligible.
- `waktu_distribusi` ditentukan server ketika transaction distribusi berhasil dan bukan input Petugas.
- Transisi yang diizinkan hanya `TERSEDIA -> DIDISTRIBUSIKAN`. Mutation hanya mengubah `status_unit` dan `waktu_distribusi`.
- Distribusi dilakukan dalam database transaction dengan row unit dikunci menggunakan row-level lock. Setelah lock, status `TERSEDIA` dan batas kedaluwarsa terhadap tanggal WIB diperiksa ulang.
- Jika request dikirim dua kali atau dua tab memproses unit yang sama, hanya request pertama yang boleh berhasil. Request berikutnya membaca state terbaru, ditolak secara terkendali, dan tidak menimpa `waktu_distribusi` pertama.
- Distribusi bersifat one-shot. Phase 8H tidak menyediakan redistribusi, edit waktu distribusi, revisi, undo, atau transisi `DIDISTRIBUSIKAN -> TERSEDIA`.
- `KEDALUWARSA` tetap bukan nilai `status_unit`. Kedaluwarsa merupakan kondisi derived dan menjadi gate distribusi tanpa membuat status, flag, cron, atau mutation expiry otomatis.
- Phase 8H tidak membuat row atau angka persediaan manual. Setelah status menjadi `DIDISTRIBUSIKAN`, unit tidak lagi termasuk query persediaan derived yang hanya menghitung unit `TERSEDIA` dan belum kedaluwarsa.
- Setelah route nyata tersedia, Dashboard Petugas boleh menampilkan navigasi `Distribusi`. Phase 8H tidak menampilkan navigasi mati untuk Persediaan Phase 8I atau phase setelahnya.
- Phase 8H tidak menambahkan tabel distribusi, distributor, rumah sakit, pasien, permintaan darah, tujuan, crossmatch, transfusi, cold chain, logistik rinci, inventory CRUD, field, enum, FK, UNIQUE, index, migration, package, atau arsitektur baru.
- Phase 8H berhenti setelah `status_unit = DIDISTRIBUSIKAN` dan `waktu_distribusi` tersimpan. Persediaan tetap menjadi Phase 8I.

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

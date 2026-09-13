\# 01 - Project Context



\## Nama Proyek



Sistem Informasi Manajemen Donor dan Persediaan Darah pada satu Unit Donor Darah (UDD)



\## Konteks Proyek



Proyek ini dikembangkan untuk mata kuliah Basis Data.



Aplikasi dibuat sebagai sistem informasi berbasis web menggunakan Laravel dan MySQL untuk mendukung pengelolaan proses donor darah dan persediaan darah pada satu Unit Donor Darah (UDD).



Fokus utama proyek adalah penerapan:

\- basis data relasional;

\- CRUD;

\- hubungan antarentitas;

\- constraint dan integritas data;

\- hak akses berdasarkan peran;

\- aturan bisnis;

\- alur transaksi dari pelayanan donor sampai pengelolaan persediaan.



Frontend hanya perlu cukup jelas dan fungsional untuk demonstrasi.



\---



\## Permasalahan



Pelayanan donor darah menghasilkan data yang saling berkaitan dan berlangsung secara berkelanjutan.



Seorang pendonor dapat melakukan penyumbangan lebih dari satu kali sehingga identitas pendonor perlu terhubung dengan riwayat penyumbangan sebelumnya.



Selain itu, darah hasil penyumbangan tidak langsung dianggap sebagai persediaan yang tersedia. Penyumbangan dapat menghasilkan unit komponen darah yang harus melalui proses pelulusan dan memiliki tanggal kedaluwarsa.



Sistem dibutuhkan untuk mengelola data tersebut secara terintegrasi dan menjaga keterlacakan proses donor sampai unit komponen darah.



\---



\## Mission Statement



Mengelola data pelayanan donor darah pada satu Unit Donor Darah (UDD) secara terintegrasi, mulai dari data dan riwayat pendonor, proses penyumbangan, hingga pengelolaan komponen dan persediaan darah, sehingga informasi dapat ditelusuri dengan baik serta mendukung kegiatan operasional dan pengambilan keputusan UDD.



\---



\## Tujuan Sistem



Sistem dirancang untuk mendukung:



1\. pengelolaan identitas dan riwayat pendonor;

2\. akun dan hak akses pengguna;

3\. jadwal pelayanan donor;

4\. pemesanan jadwal donor;

5\. kuesioner pradonasi;

6\. pemeriksaan riwayat donor ulang;

7\. check-in pendonor;

8\. seleksi donor;

9\. pencatatan penyumbangan;

10\. pengelolaan unit komponen darah;

11\. pelulusan unit komponen darah;

12\. pencatatan distribusi unit secara sederhana;

13\. perhitungan persediaan darah;

14\. pemantauan kondisi persediaan rendah;

15\. identifikasi pendonor yang relevan untuk penambahan persediaan;

16\. pemberitahuan dalam aplikasi kepada pendonor;

17\. informasi riwayat dan perkiraan waktu donor berikutnya.



\---



\## Pengguna Sistem



Sistem memiliki tiga peran utama:



\### 1. Pendonor



Pendonor menggunakan sistem untuk:

\- registrasi dan login;

\- mengelola profil;

\- melihat jadwal donor;

\- membuat atau membatalkan pemesanan;

\- mengisi kuesioner pradonasi;

\- memperoleh kode check-in;

\- melihat riwayat penyumbangan;

\- melihat informasi donor berikutnya;

\- menerima pemberitahuan dalam aplikasi.



Pendonor tidak menentukan keputusan medis dan tidak melakukan proses operasional UDD.



\### 2. Petugas UDD



Petugas UDD menangani proses operasional:

\- check-in pendonor;

\- melihat kuesioner;

\- melakukan dan mencatat seleksi donor;

\- mencatat penyumbangan;

\- mencatat unit komponen darah;

\- melakukan pencatatan pelulusan;

\- mencatat distribusi unit;

\- memantau persediaan;

\- melihat kondisi persediaan rendah;

\- melihat pendonor yang relevan;

\- mengirim pemberitahuan dalam aplikasi.



\### 3. Admin



Admin menangani fungsi administrasi dan konfigurasi:

\- mengelola akun petugas;

\- mengelola jadwal pelayanan;

\- mengelola pertanyaan kuesioner;

\- mengelola ambang batas persediaan.



Admin tidak digunakan sebagai pengganti petugas UDD dalam proses operasional donor.



\---



\## Gambaran Alur Utama



Alur utama sistem adalah:



Admin menyiapkan konfigurasi

→ Pendonor melakukan registrasi

→ Sistem memeriksa riwayat donor ulang

→ Pendonor memilih jadwal

→ Pendonor membuat pemesanan

→ Pendonor mengisi kuesioner

→ Sistem menghasilkan kode check-in

→ Petugas melakukan check-in

→ Petugas melakukan seleksi

→ Jika LAYAK, proses penyumbangan dilakukan

→ Petugas mencatat hasil penyumbangan

→ Penyumbangan berhasil dapat menghasilkan unit komponen darah

→ Unit menunggu pelulusan

→ Unit yang lulus menjadi TERSEDIA

→ Persediaan dihitung dari unit tersedia yang belum kedaluwarsa

→ Sistem membandingkan persediaan dengan ambang batas

→ Jika persediaan rendah, sistem membantu menemukan pendonor yang relevan

→ Petugas dapat mengirim pemberitahuan kepada pendonor.



\---



\## Keputusan Penting dalam Alur



\### Pemeriksaan Donor Ulang



Pemeriksaan riwayat donor ulang hanya menentukan apakah pendonor sudah memenuhi interval dan frekuensi berdasarkan riwayat penyumbangan.



Pemeriksaan ini bukan keputusan kelayakan medis akhir.



\### Seleksi Donor



Petugas menetapkan keputusan:



\- LAYAK

\- DITUNDA

\- DITOLAK



Hanya keputusan LAYAK yang dapat dilanjutkan ke proses penyumbangan.



\### Penyumbangan



Hasil penyumbangan:



\- BERHASIL

\- GAGAL



Penyumbangan yang gagal tidak menghasilkan unit komponen darah.



\### Unit Komponen Darah



Unit baru dimulai dalam status:



MENUNGGU\_PELULUSAN



Setelah pelulusan, unit dapat menjadi:



\- TERSEDIA

\- DITOLAK



Unit yang keluar dari persediaan menjadi:



DIDISTRIBUSIKAN



Kedaluwarsa bukan status unit tersendiri. Kondisi kedaluwarsa ditentukan menggunakan tanggal kedaluwarsa.



\---



\## Persediaan



Tidak terdapat tabel persediaan tersendiri.



Jumlah persediaan dihitung dari unit komponen darah yang:



\- berstatus TERSEDIA; dan

\- belum melewati tanggal kedaluwarsa.



Persediaan dipantau berdasarkan kombinasi:



\- jenis komponen darah; dan

\- golongan darah.



Jika jumlah persediaan berada pada atau di bawah ambang batas, kondisi tersebut dianggap sebagai persediaan rendah.



\---



\## Pemberitahuan Pendonor



Ketika persediaan tertentu rendah, sistem dapat membantu menampilkan pendonor yang:



\- memiliki golongan darah yang relevan; dan

\- berdasarkan riwayat sudah memenuhi ketentuan donor ulang.



Petugas memilih pendonor yang akan dihubungi.



Pemberitahuan hanya dilakukan di dalam aplikasi.



Fitur ini bukan pencocokan klinis antara donor dan pasien.



\---



\## Ruang Lingkup



Sistem hanya mencakup proses internal satu UDD.



Sistem tidak mencakup:



\- data pasien;

\- permintaan darah dari rumah sakit;

\- pencocokan darah donor dengan pasien;

\- crossmatch;

\- proses transfusi;

\- koordinasi antar-UDD;

\- proses laboratorium secara rinci;

\- metode pemeriksaan IMLTD secara rinci;

\- alat dan reagen laboratorium;

\- proses teknis pemisahan komponen darah;

\- pemantauan suhu penyimpanan;

\- cold chain;

\- distribusi darah secara rinci;

\- pembayaran;

\- QR code;

\- barcode;

\- SMS;

\- WhatsApp;

\- email notification;

\- apheresis.



\---



\## Prinsip Implementasi



Prototype harus mengutamakan fungsi.



Setiap menu, tombol, atau aksi yang ditampilkan kepada pengguna harus benar-benar dapat digunakan.



Tidak semua tabel basis data harus mempunyai halaman CRUD tersendiri.



UI dibangun berdasarkan kebutuhan pengguna dan proses bisnis, bukan sekadar mencerminkan struktur tabel.


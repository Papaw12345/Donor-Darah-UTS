# 07 - Course Scope and References

## Status Dokumen

Dokumen ini menetapkan batas akademik dan tingkat kompleksitas implementasi untuk proyek **Sistem Informasi Manajemen Donor dan Persediaan Darah pada satu Unit Donor Darah (UDD)**.

Dokumen ini disusun dari bahan yang benar-benar tersedia dalam percakapan: materi Pengantar Basis Data, materi Basis Data sebelum UTS, modul praktikum, kontrak perkuliahan, serta buku referensi yang tersedia. Dokumen ini tidak dimaksudkan untuk mengklaim bahwa semua hal yang mungkin pernah dipelajari mahasiswa sudah tercakup.

Prinsip penting:

> Jika suatu topik tidak dapat diverifikasi dari bahan yang tersedia, topik tersebut tidak boleh diklaim sebagai materi yang sudah dipelajari hanya berdasarkan asumsi.

Dokumen ini tidak mengubah requirement proyek. Urutan otoritas proyek tetap:

1. `01_PROJECT_CONTEXT.md`
2. `02_DATABASE_SCHEMA.md`
3. `03_ROLE_AND_UI_SCOPE.md`
4. `04_BUSINESS_RULES.md`
5. `05_IMPLEMENTATION_PLAN.md`
6. `06_DEMO_FLOW.md`

`07_COURSE_SCOPE_AND_REFERENCES.md` hanya menjadi pagar untuk **cara implementasi, tingkat kompleksitas, dan cara menjelaskan keputusan teknis**.

---

# A. Tiga Jenis Informasi dalam Dokumen Ini

Agar tidak mencampurkan fakta kuliah dengan keputusan proyek, setiap keputusan harus dipahami sebagai salah satu dari tiga kategori berikut.

## A1. Terkonfirmasi dari materi rinci

Topik dianggap benar-benar terkonfirmasi jika slide, modul, atau bahan ajarnya tersedia dan isinya dapat diperiksa.

## A2. Terkonfirmasi dari kontrak perkuliahan

Topik dapat dicatat sebagai bagian jadwal/cakupan mata kuliah jika tercantum di kontrak perkuliahan, tetapi jangan mengarang detail yang tidak terdapat dalam bahan rinci.

## A3. Keputusan teknis proyek

Contohnya versi PHP, Laravel, MySQL, pemakaian Laragon, schema donor darah, role aplikasi, dan batas fitur.

Hal tersebut merupakan keputusan proyek yang sudah dikunci pada dokumen `01`-`06`, bukan klaim bahwa dosen mengajarkan versi teknologi yang sama.

---

# B. Prinsip Modernisasi

## B1. Konsep kuliah tetap diikuti, teknologi lama tidak dipaksakan

Materi dan buku digunakan sebagai acuan konsep, cara berpikir, dan tingkat kompleksitas.

Jika contoh lama memakai sintaks, API, struktur framework, atau tooling yang sudah berubah, implementasi menggunakan cara yang **supported dan ekuivalen** pada stack proyek saat ini.

Dengan demikian:

- konsep CRUD tetap digunakan, tetapi fungsi PHP `mysql_*` lama tidak dipakai;
- konsep PHP-MySQL tetap relevan, tetapi data access aplikasi menggunakan mekanisme Laravel yang sesuai versi proyek;
- konsep MVC tetap digunakan, tetapi struktur file mengikuti Laravel yang terpasang sekarang;
- contoh SQL dari Oracle/MariaDB/DBMS lain tidak disalin literal jika berbeda dengan MySQL 8.4;
- contoh Laravel versi lama tidak memaksa proyek Laravel sekarang menggunakan struktur Laravel versi lama;
- Laragon tidak perlu diganti dengan XAMPP hanya karena sebagian contoh menggunakan XAMPP.

## B2. Modernisasi bukan alasan memperluas scope

Modernisasi hanya untuk:

- compatibility;
- security;
- API yang masih didukung;
- sintaks yang benar untuk DBMS/framework proyek;
- pengganti resmi untuk mekanisme yang sudah deprecated.

Modernisasi tidak boleh dipakai sebagai alasan untuk menambahkan arsitektur atau teknologi baru yang tidak diperlukan.

## B3. Jika perubahan modern mengubah konsep atau scope, berhenti

Jika pengganti modern ternyata:

- mengubah schema;
- mengubah business rule;
- menambah service eksternal;
- menambah framework utama;
- menambah pola arsitektur yang jauh lebih kompleks;

maka perubahan tidak boleh dilakukan otomatis. Laporkan terlebih dahulu.

---

# C. Stack Proyek Saat Ini

Bagian ini adalah **keputusan proyek**, bukan klaim materi kuliah.

Stack yang sudah dikunci:

- PHP 8.3;
- Laravel 13.x;
- MySQL 8.4;
- Blade;
- Eloquent ORM;
- Composer;
- Laragon untuk environment lokal.

Implementasi harus mengikuti versi yang benar-benar terpasang. Jangan downgrade hanya agar sama dengan screenshot atau buku lama.

---

# D. Pengantar Basis Data - Cakupan yang Terkonfirmasi

Kontrak Pengantar Basis Data menempatkan pemodelan relasional, SQL, ERD, normal form, dan implementasi basis data relasional sebagai inti mata kuliah. Jadwal kuliahnya juga mencakup Security/Privacy/Ethics, Model Data Relasional, ERD, DDL, DML, DCL, SQL Join, Aggregate & Nested Query, Stored Procedures/Trigger/View, Normalisasi, dan Web Technology & DBMS.

Bagian berikut hanya merangkum topik yang dapat diverifikasi dari materi yang tersedia.

## D1. Model data relasional

Terkonfirmasi:

- relation / tabel;
- tuple / record;
- attribute / kolom;
- domain;
- primary key;
- foreign key;
- integrity constraints;
- referential integrity;
- entity integrity;
- domain constraint.

Implikasi proyek:

Schema relasional, PK, FK, NULL/NOT NULL, UNIQUE, dan constraint dapat dijelaskan menggunakan fondasi yang sudah dipelajari.

Sumber utama:

- `4. Model Data Relasional (5).pdf`
- Kontrak Pengantar Basis Data

---

## D2. Perancangan konseptual dan ERD

Terkonfirmasi:

- requirement/kebutuhan data sebagai dasar desain;
- entity;
- attribute;
- relationship;
- key;
- cardinality;
- participation constraint;
- weak entity;
- conceptual schema;
- ERD.

Materi ERD juga mengajarkan tahapan identifikasi entity, attribute, primary key, relationship, cardinality, dan participation constraint.

Implikasi proyek:

ERD donor darah harus berasal dari kebutuhan dan proses bisnis UDD, bukan dari default Laravel.

Sumber utama:

- `01. Perancangan Skema Konseptual (4).pdf`
- `5. ERD dan Perancangan Basis Data (4).pdf`

---

## D3. Perancangan fisik

Terkonfirmasi:

- physical schema bergantung pada DBMS yang digunakan;
- table;
- column;
- data type;
- primary key;
- foreign key;
- constraint;
- index sebagai elemen physical model.

Implikasi proyek:

Physical schema tetap harus mengikuti `02_DATABASE_SCHEMA.md`. Materi physical design tidak memberi izin untuk mengubah schema secara sepihak saat coding.

Sumber utama:

- `02. Perancangan Skema Fisik (4).pdf`
- `5. ERD dan Perancangan Basis Data (4).pdf`

---

## D4. DDL

Terkonfirmasi:

- `CREATE`;
- `ALTER`;
- `DROP`;
- `TRUNCATE`;
- `RENAME`;
- data type;
- `NOT NULL`;
- `UNIQUE`;
- `PRIMARY KEY`;
- `FOREIGN KEY`;
- `REFERENCES`;
- referential actions seperti `CASCADE` dan `SET NULL` sebagai konsep.

Implikasi proyek:

Laravel migration adalah mekanisme framework untuk mewujudkan physical schema. Migration harus menghasilkan struktur yang sama dengan schema final, bukan menambah field default tanpa alasan.

Sumber utama:

- `6. Data Definition Language (DDL) (9).pdf`
- `6. Data Definition Language (DDL) (10).pdf`
- `4. Model Data Relasional (5).pdf`

---

## D5. DML dan CRUD

Terkonfirmasi:

- `SELECT`;
- projection;
- selection;
- arithmetic expression;
- alias;
- `INSERT`;
- `UPDATE`;
- `DELETE`;
- `WHERE`.

Materi juga menghubungkan operasi database dengan konsep CRUD.

Implikasi proyek:

Operasi Create, Read, Update, Delete pada aplikasi harus dapat ditelusuri ke perubahan atau pembacaan data yang sesuai.

Sumber utama:

- `7. Data Manipulation Language (DML) (3).pdf`
- `04. Data Manipulation Language (DML) (5).pdf`

---

## D6. DCL dan database access control

Terkonfirmasi:

- DCL untuk mengendalikan akses ke data;
- privilege;
- role;
- `GRANT`;
- `REVOKE`;
- account management pada contoh DBMS seperti `CREATE USER`, `ALTER USER`, `DROP USER`, `CREATE ROLE`, dan `SHOW GRANTS`.

Pembedaan untuk proyek:

**DBMS access control** dan **application authorization** bukan hal yang identik.

Pada proyek donor darah:

- role aplikasi disimpan pada `akun.peran`;
- aplikasi tidak perlu membuat satu MySQL user untuk setiap Pendonor/Petugas/Admin.

Sumber utama:

- `8. Data Control Language (DCL) (4).pdf`
- modul praktikum DCL yang tersedia

---

## D7. Security, privacy, ethics

Terkonfirmasi:

- database security;
- unauthorized access;
- confidentiality;
- privacy;
- integrity;
- availability;
- authentication;
- authorization;
- access control;
- granting/revoking privileges;
- security and integrity controls;
- aspek etika pengelolaan data.

Implikasi proyek:

Pembatasan data berdasarkan role dan kepemilikan data dapat dijelaskan dari konsep authentication, authorization, dan access control.

Aturan bahwa password aplikasi disimpan melalui `password_hash` berasal dari schema proyek, bukan dari klaim bahwa struktur autentikasi Laravel tertentu diajarkan pada materi ini.

Sumber utama:

- `3. Data Security, Data Privacy, Ethics in Database (4).pdf`

---

## D8. SQL JOIN

Terkonfirmasi:

- EQUI JOIN;
- INNER JOIN;
- LEFT OUTER JOIN;
- RIGHT OUTER JOIN;
- FULL OUTER JOIN sebagai konsep dalam materi;
- CROSS/CARTESIAN JOIN pada materi;
- penggabungan data lintas tabel berdasarkan relationship.

Implikasi proyek:

JOIN boleh digunakan untuk mengambil data lintas relasi. Bentuk sintaks aktual harus mengikuti kemampuan MySQL 8.4.

Sumber utama:

- `6. SQL Join (3).pdf`
- materi SQL Join lain yang tersedia

---

## D9. Aggregate dan nested query

Terkonfirmasi:

- `COUNT`;
- `SUM`;
- `AVG`;
- `MIN`;
- `MAX`;
- `GROUP BY`;
- `HAVING`;
- `ORDER BY`;
- nested query / subquery;
- single-row subquery;
- operator `IN`;
- `ANY` / `SOME`;
- `ALL`;
- `EXISTS`;
- `NOT EXISTS`.

Implikasi proyek:

Teknik tersebut sah digunakan jika membuat query lebih tepat dan tetap mudah dijelaskan, misalnya:

- menghitung jumlah persediaan;
- menghitung frekuensi donor berhasil;
- mengambil nilai terakhir;
- mengecek apakah riwayat tertentu ada.

Sumber utama:

- `10. Fungsi Aggregat dan Nested Query (3).pdf`
- modul praktikum aggregate/nested query yang tersedia

---

## D10. View

Terkonfirmasi:

- View sebagai tabel virtual/query definition;
- Simple View;
- Complex View;
- `CREATE VIEW`;
- `DROP VIEW`;
- DML melalui View memiliki batasan tertentu;
- View dapat menyederhanakan query dan membatasi data yang terlihat.

Implikasi proyek:

View **boleh digunakan jika ada manfaat nyata**, tetapi tidak wajib. Jangan membuat View hanya agar terlihat lebih kompleks.

Sumber utama:

- `Week 2-MODUL View dan Index.pdf`
- `11. Stored Procedures, Trigger, View (3).pdf`
- `8. Stored Procedures, Views, Triggers (3).pdf`

---

## D11. Stored Procedure

Terkonfirmasi:

- `CREATE PROCEDURE`;
- pemanggilan procedure;
- parameter `IN`, `OUT`, `INOUT`;
- `DECLARE`;
- variable;
- `SET`;
- `SELECT ... INTO`;
- `IF/ELSE`;
- `CASE`;
- loop;
- cursor.

Implikasi proyek:

Stored Procedure bukan materi asing, tetapi tidak wajib. Jika controller + query biasa sudah cukup jelas, jangan menambah Stored Procedure hanya untuk menunjukkan penggunaan fitur.

Sumber utama:

- `11. Stored Procedures, Trigger, View (3).pdf`
- `8. Stored Procedures, Views, Triggers (3).pdf`

---

## D12. Trigger

Terkonfirmasi:

- event `INSERT`, `UPDATE`, `DELETE`;
- timing `BEFORE`, `AFTER`;
- `OLD`, `NEW`;
- `FOR EACH ROW`;
- validasi menggunakan trigger;
- logging perubahan;
- `SIGNAL SQLSTATE` pada contoh validasi.

Implikasi proyek:

Trigger boleh digunakan hanya jika ada alasan jelas bahwa aturan lebih tepat ditegakkan di level database. Jangan memindahkan seluruh business logic Laravel ke Trigger.

Sumber utama:

- `11. Stored Procedures, Trigger, View (3).pdf`
- `8. Stored Procedures, Views, Triggers (3).pdf`

---

## D13. Normalisasi

Terkonfirmasi secara rinci:

- unnormalized relation;
- redundancy;
- insertion/update/deletion anomaly;
- functional dependency;
- determinant;
- partial dependency;
- transitive dependency;
- 1NF;
- 2NF;
- 3NF;
- BCNF;
- decomposition;
- lossless decomposition;
- dependency preservation;
- denormalization sebagai trade-off performa.

Materi juga menyebut level lebih tinggi seperti 4NF/5NF, tetapi fokus praktik yang jelas tersedia berada pada 1NF, 2NF, 3NF, dan BCNF.

Implikasi proyek:

Gunakan konsep dependency dan anomaly untuk menjelaskan mengapa data dipisahkan atau tidak diduplikasi. Jangan melakukan normalisasi ulang terhadap schema final tanpa keputusan eksplisit.

Sumber utama:

- `12. Normalisasi Part 1 (2).pdf`
- `12. Normalisasi pt1.pptx`
- `13. Normalisasi pt2 (1).pptx`
- `9. Normalisasi (2).pdf`

---

## D14. Web Technology and DBMS

Terkonfirmasi:

- HTML;
- PHP;
- server-side scripting;
- form;
- GET dan POST;
- koneksi PHP ke MySQL;
- query dari aplikasi;
- CRUD pada aplikasi web;
- risiko SQL injection;
- parameterization sebagai pendekatan lebih aman;
- MVC disebut sebagai pendekatan yang lebih aman/terstruktur dibanding satu script langsung.

Bahan lama masih menunjukkan fungsi `mysql_*`, tetapi materi juga menunjukkan bahwa pendekatan lama tersebut bukan target implementasi modern.

Implikasi proyek:

Konsep PHP-MySQL-CRUD tetap menjadi fondasi, tetapi implementasi proyek menggunakan Laravel yang sesuai versi sekarang.

Sumber utama:

- `14. Web Technology and DBMS (1).pdf`
- `10. Web Tech DBMS (1).pdf`

---

# E. Basis Data Semester V - Materi Sebelum UTS yang Terkonfirmasi

Kontrak Basis Data 2026-2027 menyatakan bahwa mata kuliah memperluas pemodelan SQL dari mata kuliah pengantar dan menggunakan database yang dirancang dalam sistem informasi CRUD sederhana.

Kontrak juga menunjukkan:

- Pertemuan 2: Database Performance dan Indexes;
- Pertemuan 3: Database Lifecycle;
- Pertemuan 4-6: sistem informasi sederhana CRUD, MVC, Laravel Framework, Sistem Informasi, dan project UTS CRUD;
- Pertemuan 7: diskusi project/progres UTS CRUD;
- Pertemuan 8: UTS berupa membuat sistem informasi.

---

## E1. TM2 - Database Performance dan Indexes

Materi rinci yang tersedia mengonfirmasi:

- database performance;
- performance tuning;
- full table scan;
- query/SQL tuning;
- poor database design;
- penggunaan tipe data;
- normalisasi terlalu rendah/berlebihan sebagai isu desain;
- denormalisasi terukur;
- connection pooling sebagai konsep tuning;
- index;
- search key;
- index pada satu atau beberapa kolom;
- index pada kolom yang sering digunakan pada `WHERE` atau `JOIN`;
- overhead index terhadap operasi `INSERT`, `UPDATE`, dan `DELETE`;
- tidak semua index berguna.

Praktikum TM2 juga mengonfirmasi:

- View;
- Simple View;
- Complex View;
- Index;
- composite/multi-column index.

Implikasi proyek:

- jangan membuat index pada semua kolom;
- custom index harus punya alasan dari pola query nyata;
- karena schema proyek sudah dikunci, Codex tidak boleh menambah custom index tanpa keputusan eksplisit.

Sumber utama:

- `02. Database Performance and Indexes (3).pdf`
- `Week 2-MODUL View dan Index.pdf`
- *Concise Guide to Databases* untuk penguatan teori performa/index

---

## E2. TM3 - Database Lifecycle

Terkonfirmasi:

- Database Life Cycle / Database Development Lifecycle;
- Database Planning;
- Mission Statement;
- Mission Objectives;
- System Definition;
- scope/boundary;
- Requirement Collection and Analysis;
- multiple user views;
- Database Design;
- DBMS Selection;
- Application Design;
- transaction design;
- interface design;
- prototyping;
- implementation;
- testing;
- operational maintenance.

Tiga tahap desain yang terkonfirmasi:

1. conceptual database design;
2. logical database design;
3. physical database design.

### Conceptual design

- berdasarkan user requirement specification;
- independen dari DBMS, bahasa pemrograman, dan hardware.

### Logical design

- memetakan desain ke model database;
- menggunakan normalisasi untuk menghindari redundancy/anomaly;
- belum berfokus pada aspek fisik seperti index.

### Physical design

- table;
- constraint;
- PK/FK;
- storage/access method;
- security.

Praktikum TM3 meminta database planning, deskripsi bidang aplikasi dan proses bisnis, Mission Statement, Mission Objectives, rencana desain, dan rencana implementasi.

Sumber utama:

- `03. Database Lifecycle (3).pdf`
- `Week 3 Modul Minggu 3 DB Lifecycle.pdf`

---

## E3. TM4 - Web Programming, MVC, Laravel

Terkonfirmasi:

- web programming;
- PHP/HTML/MySQL sebagai review;
- HTTP request;
- CRUD;
- MVC;
- Laravel Framework;
- Model;
- View;
- Controller;
- routing;
- GET;
- POST;
- PUT;
- DELETE;
- Blade;
- Query Builder;
- migrations;
- database seeding;
- pagination;
- CSRF/form security;
- Composer;
- Artisan.

Praktikum 2026 juga menunjukkan struktur proyek Laravel seperti:

- `app/Http`;
- `app/Models`;
- `database/migrations`;
- `database/seeders`;
- `resources/views`;
- `routes`;
- `storage`;
- `vendor`.

Praktikum secara eksplisit menerima XAMPP/Laragon sebagai environment lokal dan menggunakan Composer serta `php artisan serve`.

Implikasi proyek:

Struktur implementasi sederhana yang paling dekat dengan materi adalah:

`Route -> Controller -> Model/Eloquent -> MySQL`

dan:

`Controller -> Blade View`

Sumber utama:

- `4. Web programming MVC (3).pptx`
- `4. MVC Laravel (1).docx`
- `PPT PRAKTIKUM BASIS DATA 2026_20260901_094444_0000 (2).pdf`
- *Learning Laravel: The Easiest Way* sebagai referensi konseptual lama

---

## E4. TM5 - Database Design Case Studies

Terkonfirmasi:

- review ERD;
- review physical database design;
- case studies untuk domain society, e-commerce, education, dan healthcare;
- contoh Healthcare DB Design: Emergency Room.

Implikasi proyek:

Metode yang diambil adalah menerjemahkan requirement domain menjadi entity/attribute/relationship/constraint. Struktur database Emergency Room **tidak** dijadikan template untuk donor darah.

Sumber utama:

- `5. Case Study Database Design (2).pptx`

---

## E5. TM6 dan TM7

Tidak ada bahan teori terpisah TM6/TM7 yang tersedia dalam kumpulan file saat audit ini.

Yang boleh dinyatakan hanya berdasarkan kontrak kuliah:

- pertemuan 4-6 berada dalam blok sistem informasi sederhana CRUD, MVC, Laravel Framework, Sistem Informasi, dan project UTS CRUD;
- pertemuan 7 adalah diskusi project dan progres UTS CRUD.

Jangan mengarang topik tambahan untuk TM6 atau TM7.

---

# F. Materi Pasca-UTS Bukan Baseline UTS

Kontrak Basis Data menunjukkan materi setelah UTS mencakup antara lain:

- Data Warehouse;
- multidimensional data model;
- OLAP;
- SQL vs NoSQL;
- XML DB;
- MongoDB;
- Graph DB.

Topik tersebut merupakan bagian mata kuliah, tetapi **tidak digunakan sebagai baseline implementasi project UTS ini** kecuali dosen kemudian memberi requirement baru.

---

# G. Referensi Resmi dan Status Verifikasinya

## Pengantar Basis Data

Kontrak perkuliahan mencantumkan:

1. Connolly, T. M., & Begg, C. E. (2015). *Database Systems: A Practical Approach to Design, Implementation, and Management*. Sixth Edition. Pearson Education.
2. Gupta, S. B., & Mittal, A. (2009). *Introduction to Database Management System*. Laxmi Publications.

Status pada audit ini:

- keberadaan keduanya sebagai **bahan bacaan resmi** terkonfirmasi dari kontrak;
- isi penuh kedua buku tidak dijadikan dasar klaim rinci dalam dokumen ini kecuali juga muncul pada materi kuliah yang tersedia.

## Basis Data

Kontrak kuliah mencantumkan:

1. Lake, P., & Crowther, P. (2013). *Concise Guide to Databases*. Springer.
2. McCreary, D., & Kelly, A. (2014). *Making Sense of NoSQL*. Manning.
3. Nagabhushana, S. (2006). *Data Warehousing, OLAP and Data Mining*. New Age International Publishers.
4. Vo, J. (2014). *Learning Laravel: The Easiest Way*.

Status pada audit ini:

- *Concise Guide to Databases* tersedia dan telah dipakai untuk cross-check relational database, normalisation, CRUD, security, dan performance/index;
- *Learning Laravel: The Easiest Way* tersedia, tetapi merupakan buku Laravel 4; digunakan untuk konsep dasar Laravel/MVC, bukan untuk menyalin API lama;
- McCreary & Kelly serta Nagabhushana terkonfirmasi sebagai referensi resmi dari kontrak, tetapi bukan dasar utama project UTS relational CRUD ini.

---

# H. Aturan Pemilihan Teknik untuk Project

Gunakan solusi paling sederhana yang memenuhi requirement dan masih dapat dijelaskan dari materi.

| Kebutuhan | Pilihan awal |
| --- | --- |
| CRUD aplikasi | Route -> Controller -> Model/Eloquent -> Blade |
| Validasi input | Laravel validation + constraint database |
| Data lintas tabel | Eloquent relationship atau JOIN yang jelas |
| Nilai ringkasan | Aggregate query |
| Cek keberadaan | Query sederhana / `EXISTS` / subquery jika relevan |
| Query berulang | Query biasa lebih dulu; View jika jelas membantu |
| Routine database | Application code lebih dulu; Stored Procedure jika jelas lebih tepat |
| Otomasi event DB | Application flow/constraint lebih dulu; Trigger jika jelas lebih tepat |
| Performa | Correctness -> query/design -> baru pertimbangkan index |
| Derived data | Hitung dari source-of-truth sesuai business rule |
| UI | Blade sederhana dan fungsional |

Tabel ini adalah pedoman tingkat kompleksitas, bukan izin untuk mengubah dokumen `01`-`06`.

---

# I. Teknik yang Tidak Menjadi Default

Teknik berikut tidak ditemukan sebagai kebutuhan project UTS pada bahan yang diaudit dan tidak boleh diperkenalkan otomatis:

- microservices;
- event sourcing;
- CQRS;
- message broker;
- Redis sebagai dependency baru;
- queue/background architecture kompleks;
- Docker orchestration;
- GraphQL;
- SPA framework;
- React/Vue/Inertia/Livewire sebagai perubahan stack;
- repository/service/DTO layer berlapis yang tidak diperlukan.

Pernyataan ini **bukan klaim bahwa mahasiswa pasti belum pernah mendengar semua teknik tersebut**. Maksudnya: teknik tersebut tidak menjadi baseline dari bahan pre-UTS yang diaudit dan tidak diperlukan oleh spesifikasi proyek saat ini.

Jika benar-benar diperlukan, berhenti dan jelaskan alasannya sebelum mengubah arsitektur.

---

# J. Guardrail Query dan Database

1. Utamakan query yang dapat dibaca dan dijelaskan.
2. JOIN boleh digunakan jika data berasal dari beberapa relasi.
3. Aggregate digunakan untuk ringkasan yang memang derived.
4. Subquery/`EXISTS` digunakan jika relevan, bukan untuk membuat query terlihat kompleks.
5. Jangan membuat field baru untuk derived data yang sudah ditetapkan oleh `04_BUSINESS_RULES.md`.
6. Jangan menambahkan custom index tanpa alasan query nyata dan persetujuan.
7. View/Stored Procedure/Trigger bukan kewajiban.
8. Jangan mengubah schema untuk mempermudah query.
9. Jangan menggabungkan input user secara tidak aman ke raw SQL.
10. Jika raw SQL memang dibutuhkan, gunakan parameter binding yang supported.
11. Constraint database tetap menjadi lapisan integritas walaupun aplikasi memiliki validation.
12. Authorization tidak boleh hanya berupa menu yang disembunyikan; server-side access tetap harus diperiksa.

---

# K. Guardrail Laravel

1. Ikuti MVC yang sederhana.
2. Gunakan struktur Laravel versi yang benar-benar terpasang.
3. Jangan menyalin path/API Laravel 4/5 secara literal.
4. Jangan membuat tabel `users` atau field auth default jika bertentangan dengan `02_DATABASE_SCHEMA.md`.
5. Model `Akun` harus disesuaikan dengan schema proyek, bukan schema diubah untuk mengikuti default framework.
6. Gunakan Blade untuk UI proyek sesuai stack yang sudah dikunci.
7. Gunakan migration untuk implementasi schema yang sudah disetujui.
8. Gunakan seeder untuk master/demo data yang memang telah direncanakan.
9. Jangan menambah package jika kemampuan bawaan Laravel/PHP/MySQL sudah cukup.
10. Business rule harus tetap mudah ditelusuri ke `04_BUSINESS_RULES.md`.

---

# L. Instruksi untuk Codex

Sebelum coding, Codex harus membaca:

- `AGENTS.md`;
- dokumen `01`-`06` yang relevan;
- `07_COURSE_SCOPE_AND_REFERENCES.md`.

Codex harus:

1. membedakan requirement proyek dari contoh kuliah;
2. tidak mengubah schema agar mengikuti default framework;
3. memilih implementasi yang paling sederhana dan dapat dipertanggungjawabkan;
4. memakai API/sintaks yang supported pada PHP 8.3, Laravel 13.x, dan MySQL 8.4;
5. tidak menyalin API deprecated dari bahan lama;
6. tidak menambahkan package, index, View, Stored Procedure, Trigger, tabel, atau field tanpa kebutuhan yang jelas;
7. berhenti jika solusi yang dianggap perlu akan memperluas scope atau membutuhkan teknik jauh di luar baseline;
8. mengerjakan satu task kecil per tahap;
9. melaporkan file yang diubah dan command/test yang dijalankan;
10. tidak menganggap suatu topik "sudah dipelajari" jika tidak didukung dokumen ini atau sumber baru yang diberikan pengguna.

---

# M. Definition of Course-Aligned Implementation

Implementasi dianggap sesuai jika:

- requirement tetap berasal dari dokumen proyek;
- desain dapat dijelaskan dari requirement, ERD, relational model, key, constraint, dan normalisasi;
- query dapat dijelaskan dengan SQL yang sudah terkonfirmasi dalam materi;
- CRUD tetap jelas;
- role/access dapat dijelaskan melalui security, authentication, authorization, dan access-control concepts;
- Laravel digunakan dengan pola MVC yang sederhana;
- implementasi menggunakan API modern yang supported;
- index/View/Stored Procedure/Trigger hanya digunakan jika ada alasan;
- tidak ada feature creep;
- tidak ada arsitektur yang jauh lebih rumit daripada kebutuhan;
- mahasiswa dapat menjelaskan alasan desain, query, dan flow tanpa bergantung pada jawaban "karena AI membuatnya demikian".

Tujuan dokumen ini bukan membuat implementasi sengaja kuno. Tujuannya adalah menjaga agar prototype **benar secara basis data, berfungsi sebagai sistem informasi CRUD, tetap dekat dengan materi yang benar-benar dapat diverifikasi, dan menggunakan teknologi proyek dengan cara yang masih didukung saat ini**.

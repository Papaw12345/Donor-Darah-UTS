<?php

namespace Database\Seeders;

use App\Models\PertanyaanKuesioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PertanyaanKuesionerSeeder extends Seeder
{
    /**
     * Mengisi master 41 pertanyaan kuesioner pradonasi.
     *
     * Redaksi mengikuti screenshot referensi yang disepakati.
     * Normalisasi hanya pada spasi, kapitalisasi, dan tanda baca ringan.
     * Tidak ada penambahan aturan medis atau jenis jawaban baru.
     */
    public function run(): void
    {
        $pertanyaan = [
            ['urutan' => 1, 'kategori' => 'Apakah Anda', 'teks_pertanyaan' => 'Merasa sehat pada hari ini?'],
            ['urutan' => 2, 'kategori' => 'Apakah Anda', 'teks_pertanyaan' => 'Sedang minum antibiotik?'],
            ['urutan' => 3, 'kategori' => 'Apakah Anda', 'teks_pertanyaan' => 'Sedang minum obat lain untuk infeksi?'],

            ['urutan' => 4, 'kategori' => 'Dalam waktu 48 jam terakhir', 'teks_pertanyaan' => 'Apakah Anda sedang minum Aspirin atau obat yang mengandung Aspirin?'],

            ['urutan' => 5, 'kategori' => 'Dalam waktu 1 minggu terakhir', 'teks_pertanyaan' => 'Apakah Anda mengalami sakit kepala dan demam bersamaan?'],

            ['urutan' => 6, 'kategori' => 'Dalam waktu 6 minggu terakhir', 'teks_pertanyaan' => 'Untuk donor wanita apakah Anda saat ini sedang hamil? Jika ya, kehamilan berapa?'],

            ['urutan' => 7, 'kategori' => 'Dalam waktu 8 minggu terakhir', 'teks_pertanyaan' => 'Apakah Anda mendonorkan darah, trombosit atau plasma?'],
            ['urutan' => 8, 'kategori' => 'Dalam waktu 8 minggu terakhir', 'teks_pertanyaan' => 'Apakah Anda menerima vaksinasi atau suntikan lainnya?'],
            ['urutan' => 9, 'kategori' => 'Dalam waktu 8 minggu terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah kontak dengan orang yang menerima vaksinasi smallpox?'],

            ['urutan' => 10, 'kategori' => 'Dalam waktu 16 minggu terakhir', 'teks_pertanyaan' => 'Apakah Anda mendonorkan 2 kantong sel darah melalui proses aferesis?'],

            ['urutan' => 11, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah menerima transfusi darah?'],
            ['urutan' => 12, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah mendapat transplantasi, organ, jaringan atau sumsum tulang?'],
            ['urutan' => 13, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah cangkok tulang atau kulit?'],
            ['urutan' => 14, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah tertusuk jarum medis?'],
            ['urutan' => 15, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah berhubungan sexual dengan ODHA?'],
            ['urutan' => 16, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah berhubungan sexual dengan WPS?'],
            ['urutan' => 17, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah berhubungan sexual dengan dengan pengguna narkoba jarum suntik?'],
            ['urutan' => 18, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah berhubungan sexual dengan pengguna konsentrat faktor pembekuan?'],
            ['urutan' => 19, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Donor wanita; apakah Anda pernah berhubungan sexual dengan laki laki yang bisexual?'],
            ['urutan' => 20, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah berhubungan sexual dengan penderita hepatitis?'],
            ['urutan' => 21, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda tinggal bersama penderita hepatitis?'],
            ['urutan' => 22, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda memiliki tato?'],
            ['urutan' => 23, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda memiliki tindik telinga atau bagian tubuh yang lainnya?'],
            ['urutan' => 24, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda sedang atau pernah mendapat pengobatan sifilis atau GO (kencing nanah)'],
            ['urutan' => 25, 'kategori' => 'Dalam waktu 12 bulan terakhir', 'teks_pertanyaan' => 'Apakah Anda pernah di tahan di penjara untuk waktu lebih dari 72 jam?'],

            ['urutan' => 26, 'kategori' => 'Dalam waktu 3 tahun', 'teks_pertanyaan' => 'Apakah Anda pernah berada di luar wilayah Indonesia?'],

            ['urutan' => 27, 'kategori' => 'Tahun 1980 hingga 1996', 'teks_pertanyaan' => 'Apakah Anda tinggal selama 3 bulan atau lebih di Inggris?'],

            ['urutan' => 28, 'kategori' => 'Tahun 1980 hingga sekarang', 'teks_pertanyaan' => 'Apakah Anda tinggal selama 5 tahun atau lebih di Eropa?'],
            ['urutan' => 29, 'kategori' => 'Tahun 1980 hingga sekarang', 'teks_pertanyaan' => 'Apakah Anda menerima transfusi darah di Inggris?'],

            ['urutan' => 30, 'kategori' => 'Tahun 1997 hingga sekarang', 'teks_pertanyaan' => 'Apakah Anda menerima uang, obat, atau pembayaran lainnya untuk seks?'],
            ['urutan' => 31, 'kategori' => 'Tahun 1997 hingga sekarang', 'teks_pertanyaan' => 'Laki laki; Apakah Anda pernah berhubungan sexual dengan laki laki, walaupun sekali?'],

            ['urutan' => 32, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Mendapatkan hasil positif untuk tes HIV/AIDS?'],
            ['urutan' => 33, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Menggunakan jarum suntik untuk obat obatan, steroid yang tidak di resepkan dokter?'],
            ['urutan' => 34, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Menggunakan konsentrat, faktor pembekuan?'],
            ['urutan' => 35, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Menderita Hepatitis?'],
            ['urutan' => 36, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Menderita Malaria?'],
            ['urutan' => 37, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Menderita kanker termasuk leukemia?'],
            ['urutan' => 38, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Bermasalah dengan jantung dan paru paru?'],
            ['urutan' => 39, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Menderita perdarahan atau penyakit berhubungan dengan darah?'],
            ['urutan' => 40, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Apakah Anda pernah berhubungan sexual dengan orang yang tinggal di Afrika?'],
            ['urutan' => 41, 'kategori' => 'Apakah Anda PERNAH', 'teks_pertanyaan' => 'Tinggal di Afrika?'],
        ];

        DB::transaction(function () use ($pertanyaan): void {
            PertanyaanKuesioner::query()
                ->where('status_aktif', true)
                ->update(['status_aktif' => false]);

            foreach ($pertanyaan as $item) {
                PertanyaanKuesioner::query()->updateOrCreate(
                    ['teks_pertanyaan' => $item['teks_pertanyaan']],
                    [
                        'kategori' => $item['kategori'],
                        'jenis_jawaban' => 'YA_TIDAK',
                        'urutan' => $item['urutan'],
                        'status_aktif' => true,
                    ]
                );
            }
        });
    }
}
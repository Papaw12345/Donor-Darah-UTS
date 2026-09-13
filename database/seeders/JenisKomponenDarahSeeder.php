<?php

namespace Database\Seeders;

use App\Models\JenisKomponenDarah;
use Illuminate\Database\Seeder;

class JenisKomponenDarahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisKomponenDarah = [
            ['kode_komponen' => 'WB', 'nama_komponen' => 'Whole Blood'],
            ['kode_komponen' => 'PRC', 'nama_komponen' => 'Packed Red Cell'],
            ['kode_komponen' => 'TC', 'nama_komponen' => 'Thrombocyte Concentrate'],
            ['kode_komponen' => 'FFP', 'nama_komponen' => 'Fresh Frozen Plasma'],
        ];

        foreach ($jenisKomponenDarah as $data) {
            JenisKomponenDarah::updateOrCreate(
                ['kode_komponen' => $data['kode_komponen']],
                ['nama_komponen' => $data['nama_komponen']],
            );
        }
    }
}

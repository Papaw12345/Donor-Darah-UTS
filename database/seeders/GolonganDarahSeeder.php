<?php

namespace Database\Seeders;

use App\Models\GolonganDarah;
use Illuminate\Database\Seeder;

class GolonganDarahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $golonganDarah = [
            ['abo' => 'A', 'rhesus' => 'POSITIF'],
            ['abo' => 'A', 'rhesus' => 'NEGATIF'],
            ['abo' => 'B', 'rhesus' => 'POSITIF'],
            ['abo' => 'B', 'rhesus' => 'NEGATIF'],
            ['abo' => 'AB', 'rhesus' => 'POSITIF'],
            ['abo' => 'AB', 'rhesus' => 'NEGATIF'],
            ['abo' => 'O', 'rhesus' => 'POSITIF'],
            ['abo' => 'O', 'rhesus' => 'NEGATIF'],
        ];

        foreach ($golonganDarah as $data) {
            GolonganDarah::firstOrCreate($data);
        }
    }
}

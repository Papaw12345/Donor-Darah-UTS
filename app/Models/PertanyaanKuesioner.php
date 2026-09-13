<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PertanyaanKuesioner extends Model
{
    protected $table = 'pertanyaan_kuesioner';

    protected $primaryKey = 'id_pertanyaan';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'teks_pertanyaan',
        'kategori',
        'jenis_jawaban',
        'urutan',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function jawabanKuesioner(): HasMany
    {
        return $this->hasMany(JawabanKuesioner::class, 'id_pertanyaan', 'id_pertanyaan');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penyumbangan extends Model
{
    protected $table = 'penyumbangan';

    protected $primaryKey = 'id_penyumbangan';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_seleksi',
        'id_petugas_pencatat',
        'waktu_pengambilan',
        'volume_ml',
        'hasil_penyumbangan',
        'alasan_gagal',
    ];

    protected function casts(): array
    {
        return [
            'waktu_pengambilan' => 'datetime',
        ];
    }

    public function seleksiDonor(): BelongsTo
    {
        return $this->belongsTo(SeleksiDonor::class, 'id_seleksi', 'id_seleksi');
    }

    public function petugasPencatat(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas_pencatat', 'id_petugas');
    }
}

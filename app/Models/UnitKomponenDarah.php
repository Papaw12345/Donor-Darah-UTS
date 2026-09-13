<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitKomponenDarah extends Model
{
    protected $table = 'unit_komponen_darah';

    protected $primaryKey = 'id_unit';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nomor_unit',
        'id_penyumbangan',
        'id_jenis_komponen',
        'id_golongan_darah',
        'id_petugas_pencatat',
        'id_petugas_pelulus',
        'tanggal_pembuatan',
        'tanggal_kedaluwarsa',
        'waktu_pelulusan',
        'status_unit',
        'catatan_pelulusan',
        'waktu_distribusi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pembuatan' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'waktu_pelulusan' => 'datetime',
            'waktu_distribusi' => 'datetime',
        ];
    }

    public function penyumbangan(): BelongsTo
    {
        return $this->belongsTo(Penyumbangan::class, 'id_penyumbangan', 'id_penyumbangan');
    }

    public function jenisKomponenDarah(): BelongsTo
    {
        return $this->belongsTo(JenisKomponenDarah::class, 'id_jenis_komponen', 'id_jenis_komponen');
    }

    public function golonganDarah(): BelongsTo
    {
        return $this->belongsTo(GolonganDarah::class, 'id_golongan_darah', 'id_golongan_darah');
    }

    public function petugasPencatat(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas_pencatat', 'id_petugas');
    }

    public function petugasPelulus(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas_pelulus', 'id_petugas');
    }
}

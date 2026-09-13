<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeleksiDonor extends Model
{
    protected $table = 'seleksi_donor';

    protected $primaryKey = 'id_seleksi';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_pemesanan',
        'id_petugas',
        'waktu_seleksi',
        'berat_badan',
        'tekanan_sistolik',
        'tekanan_diastolik',
        'denyut_nadi',
        'suhu_tubuh',
        'kadar_hb',
        'hasil_pemeriksaan_kesehatan',
        'keputusan_seleksi',
        'alasan_keputusan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_seleksi' => 'datetime',
        ];
    }

    public function pemesananDonor(): BelongsTo
    {
        return $this->belongsTo(PemesananDonor::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas', 'id_petugas');
    }

    public function penyumbangan(): HasOne
    {
        return $this->hasOne(Penyumbangan::class, 'id_seleksi', 'id_seleksi');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KuesionerPradonasi extends Model
{
    protected $table = 'kuesioner_pradonasi';

    protected $primaryKey = 'id_kuesioner';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_pemesanan',
        'waktu_pengisian',
    ];

    protected function casts(): array
    {
        return [
            'waktu_pengisian' => 'datetime',
        ];
    }

    public function pemesananDonor(): BelongsTo
    {
        return $this->belongsTo(PemesananDonor::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function jawabanKuesioner(): HasMany
    {
        return $this->hasMany(JawabanKuesioner::class, 'id_kuesioner', 'id_kuesioner');
    }
}

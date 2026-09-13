<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbangPersediaan extends Model
{
    protected $table = 'ambang_persediaan';

    protected $primaryKey = 'id_ambang';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_jenis_komponen',
        'id_golongan_darah',
        'jumlah_minimum',
    ];

    public function jenisKomponenDarah(): BelongsTo
    {
        return $this->belongsTo(JenisKomponenDarah::class, 'id_jenis_komponen', 'id_jenis_komponen');
    }

    public function golonganDarah(): BelongsTo
    {
        return $this->belongsTo(GolonganDarah::class, 'id_golongan_darah', 'id_golongan_darah');
    }
}

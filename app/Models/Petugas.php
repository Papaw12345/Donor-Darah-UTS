<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petugas extends Model
{
    protected $table = 'petugas';

    protected $primaryKey = 'id_petugas';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_akun',
        'nomor_petugas',
        'nama_petugas',
    ];

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }

    public function seleksiDonor(): HasMany
    {
        return $this->hasMany(SeleksiDonor::class, 'id_petugas', 'id_petugas');
    }

    public function penyumbanganDicatat(): HasMany
    {
        return $this->hasMany(Penyumbangan::class, 'id_petugas_pencatat', 'id_petugas');
    }

    public function unitKomponenDarahDicatat(): HasMany
    {
        return $this->hasMany(UnitKomponenDarah::class, 'id_petugas_pencatat', 'id_petugas');
    }

    public function unitKomponenDarahDiluluskan(): HasMany
    {
        return $this->hasMany(UnitKomponenDarah::class, 'id_petugas_pelulus', 'id_petugas');
    }

    public function pemberitahuanDikirim(): HasMany
    {
        return $this->hasMany(Pemberitahuan::class, 'id_petugas_pengirim', 'id_petugas');
    }
}

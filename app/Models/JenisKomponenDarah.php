<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisKomponenDarah extends Model
{
    protected $table = 'jenis_komponen_darah';

    protected $primaryKey = 'id_jenis_komponen';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'kode_komponen',
        'nama_komponen',
    ];

    public function unitKomponenDarah(): HasMany
    {
        return $this->hasMany(UnitKomponenDarah::class, 'id_jenis_komponen', 'id_jenis_komponen');
    }

    public function ambangPersediaan(): HasMany
    {
        return $this->hasMany(AmbangPersediaan::class, 'id_jenis_komponen', 'id_jenis_komponen');
    }
}

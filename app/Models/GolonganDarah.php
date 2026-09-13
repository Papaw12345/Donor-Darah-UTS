<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GolonganDarah extends Model
{
    protected $table = 'golongan_darah';

    protected $primaryKey = 'id_golongan_darah';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'abo',
        'rhesus',
    ];

    public function pendonor(): HasMany
    {
        return $this->hasMany(Pendonor::class, 'id_golongan_darah', 'id_golongan_darah');
    }

    public function unitKomponenDarah(): HasMany
    {
        return $this->hasMany(UnitKomponenDarah::class, 'id_golongan_darah', 'id_golongan_darah');
    }

    public function ambangPersediaan(): HasMany
    {
        return $this->hasMany(AmbangPersediaan::class, 'id_golongan_darah', 'id_golongan_darah');
    }
}

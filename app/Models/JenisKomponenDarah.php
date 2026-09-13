<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}

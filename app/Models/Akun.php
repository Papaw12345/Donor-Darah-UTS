<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Akun extends Model
{
    protected $table = 'akun';

    protected $primaryKey = 'id_akun';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'password_hash',
        'peran',
        'status_akun',
    ];

    public function pendonor(): HasOne
    {
        return $this->hasOne(Pendonor::class, 'id_akun', 'id_akun');
    }

    public function petugas(): HasOne
    {
        return $this->hasOne(Petugas::class, 'id_akun', 'id_akun');
    }
}

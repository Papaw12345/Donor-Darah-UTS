<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Akun extends Authenticatable
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

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function pendonor(): HasOne
    {
        return $this->hasOne(Pendonor::class, 'id_akun', 'id_akun');
    }

    public function petugas(): HasOne
    {
        return $this->hasOne(Petugas::class, 'id_akun', 'id_akun');
    }
}

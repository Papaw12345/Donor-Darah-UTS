<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pendonor extends Model
{
    protected $table = 'pendonor';

    protected $primaryKey = 'id_pendonor';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_akun',
        'id_golongan_darah',
        'nik',
        'nomor_donor',
        'nama_lengkap',
        'jenis_kelamin',
        'tanggal_lahir',
        'tempat_lahir',
        'alamat',
        'nomor_telepon',
        'pekerjaan',
        'alamat_kantor',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }

    public function golonganDarah(): BelongsTo
    {
        return $this->belongsTo(GolonganDarah::class, 'id_golongan_darah', 'id_golongan_darah');
    }
}

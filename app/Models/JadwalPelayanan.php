<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalPelayanan extends Model
{
    protected $table = 'jadwal_pelayanan';

    protected $primaryKey = 'id_jadwal';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'kapasitas',
        'status_jadwal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function pemesananDonor(): HasMany
    {
        return $this->hasMany(PemesananDonor::class, 'id_jadwal', 'id_jadwal');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pemberitahuan extends Model
{
    protected $table = 'pemberitahuan';

    protected $primaryKey = 'id_pemberitahuan';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_pendonor',
        'id_petugas_pengirim',
        'isi_pesan',
        'waktu_dibuat',
        'waktu_dibaca',
    ];

    protected function casts(): array
    {
        return [
            'waktu_dibuat' => 'datetime',
            'waktu_dibaca' => 'datetime',
        ];
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class, 'id_pendonor', 'id_pendonor');
    }

    public function petugasPengirim(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas_pengirim', 'id_petugas');
    }
}

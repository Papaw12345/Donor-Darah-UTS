<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PemesananDonor extends Model
{
    public const CAPACITY_CONSUMING_STATUSES = [
        'TERJADWAL',
        'CHECK_IN',
        'SELESAI',
        'TIDAK_HADIR',
    ];

    protected $table = 'pemesanan_donor';

    protected $primaryKey = 'id_pemesanan';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_pendonor',
        'id_jadwal',
        'waktu_pemesanan',
        'kode_checkin',
        'waktu_checkin',
        'status_pemesanan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_pemesanan' => 'datetime',
            'waktu_checkin' => 'datetime',
        ];
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class, 'id_pendonor', 'id_pendonor');
    }

    public function jadwalPelayanan(): BelongsTo
    {
        return $this->belongsTo(JadwalPelayanan::class, 'id_jadwal', 'id_jadwal');
    }

    public function kuesionerPradonasi(): HasOne
    {
        return $this->hasOne(KuesionerPradonasi::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function seleksiDonor(): HasOne
    {
        return $this->hasOne(SeleksiDonor::class, 'id_pemesanan', 'id_pemesanan');
    }
}

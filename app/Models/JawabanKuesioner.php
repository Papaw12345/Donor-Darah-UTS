<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JawabanKuesioner extends Model
{
    protected $table = 'jawaban_kuesioner';

    protected $primaryKey = 'id_jawaban';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_kuesioner',
        'id_pertanyaan',
        'jawaban',
    ];

    public function kuesionerPradonasi(): BelongsTo
    {
        return $this->belongsTo(KuesionerPradonasi::class, 'id_kuesioner', 'id_kuesioner');
    }

    public function pertanyaanKuesioner(): BelongsTo
    {
        return $this->belongsTo(PertanyaanKuesioner::class, 'id_pertanyaan', 'id_pertanyaan');
    }
}

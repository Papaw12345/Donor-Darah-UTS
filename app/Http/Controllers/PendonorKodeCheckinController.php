<?php

namespace App\Http\Controllers;

use App\Models\PemesananDonor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendonorKodeCheckinController extends Controller
{
    public function show(Request $request, string $pemesanan): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $pemesananMilikPendonor = PemesananDonor::query()
            ->whereKey($pemesanan)
            ->where('id_pendonor', $pendonor->id_pendonor)
            ->with(['jadwalPelayanan', 'kuesionerPradonasi'])
            ->firstOrFail();

        $pesanTidakTersedia = $pemesananMilikPendonor->kode_checkin === null
            ? 'Kode Check-in belum tersedia untuk agenda donor ini.'
            : null;

        return view('pendonor.kode-checkin', [
            'pemesanan' => $pemesananMilikPendonor,
            'pesanTidakTersedia' => $pesanTidakTersedia,
        ]);
    }

}

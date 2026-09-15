<?php

namespace App\Http\Controllers;

use App\Models\Penyumbangan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendonorRiwayatController extends Controller
{
    public function index(Request $request): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $riwayat = Penyumbangan::query()
            ->whereHas(
                'seleksiDonor.pemesananDonor',
                fn (Builder $query) => $query->where('id_pendonor', $pendonor->id_pendonor)
            )
            ->orderByDesc('waktu_pengambilan')
            ->orderByDesc('id_penyumbangan')
            ->get();

        return view('pendonor.riwayat', [
            'riwayat' => $riwayat,
        ]);
    }
}

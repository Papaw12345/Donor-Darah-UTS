<?php

namespace App\Http\Controllers;

use App\Models\Pemberitahuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PendonorPemberitahuanController extends Controller
{
    public function index(Request $request): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $pemberitahuan = $pendonor->pemberitahuan()
            ->with('petugasPengirim')
            ->orderByDesc('waktu_dibuat')
            ->orderByDesc('id_pemberitahuan')
            ->get();

        return view('pendonor.pemberitahuan.index', [
            'pemberitahuan' => $pemberitahuan,
        ]);
    }

    public function show(Request $request, string $pemberitahuan): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $pemberitahuan = $pendonor->pemberitahuan()
            ->with('petugasPengirim')
            ->whereKey($pemberitahuan)
            ->firstOrFail();

        return view('pendonor.pemberitahuan.show', [
            'pemberitahuan' => $pemberitahuan,
        ]);
    }

    public function read(Request $request, string $pemberitahuan): RedirectResponse
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $sudahDibaca = DB::transaction(function () use ($pendonor, $pemberitahuan): bool {
            $pemberitahuanMilikPendonor = Pemberitahuan::query()
                ->whereKey($pemberitahuan)
                ->where('id_pendonor', $pendonor->id_pendonor)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pemberitahuanMilikPendonor->waktu_dibaca !== null) {
                return true;
            }

            $pemberitahuanMilikPendonor->update([
                'waktu_dibaca' => now(),
            ]);

            return false;
        });

        return redirect()
            ->route('pendonor.pemberitahuan.show', $pemberitahuan)
            ->with(
                'success',
                $sudahDibaca
                    ? 'Pemberitahuan sudah ditandai sebagai dibaca.'
                    : 'Pemberitahuan berhasil ditandai sebagai dibaca.'
            );
    }
}

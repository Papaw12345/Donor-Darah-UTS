<?php

namespace App\Http\Controllers;

use App\Support\PendonorDonorBerikutnyaCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendonorDashboardController extends Controller
{
    public function index(
        Request $request,
        PendonorDonorBerikutnyaCalculator $calculator
    ): View {
        $akun = $request->user();
        $pendonor = $akun->pendonor()
            ->with('golonganDarah')
            ->firstOrFail();

        $jumlahPemberitahuanBelumDibaca = $pendonor->pemberitahuan()
            ->whereNull('waktu_dibaca')
            ->count();

        $pemberitahuanTerbaru = $pendonor->pemberitahuan()
            ->with('petugasPengirim')
            ->orderByDesc('waktu_dibuat')
            ->orderByDesc('id_pemberitahuan')
            ->limit(3)
            ->get();

        return view('pendonor.dashboard', [
            'akun' => $akun,
            'pendonor' => $pendonor,
            'informasiDonorBerikutnya' => $calculator->calculate($pendonor),
            'jumlahPemberitahuanBelumDibaca' => $jumlahPemberitahuanBelumDibaca,
            'pemberitahuanTerbaru' => $pemberitahuanTerbaru,
        ]);
    }
}

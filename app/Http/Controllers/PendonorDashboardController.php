<?php

namespace App\Http\Controllers;

use App\Models\PemesananDonor;
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

        $pemesananAktif = PemesananDonor::query()
            ->where('pemesanan_donor.id_pendonor', $pendonor->id_pendonor)
            ->whereIn('pemesanan_donor.status_pemesanan', [
                'TERJADWAL',
                'CHECK_IN',
            ])
            ->join(
                'jadwal_pelayanan',
                'jadwal_pelayanan.id_jadwal',
                '=',
                'pemesanan_donor.id_jadwal'
            )
            ->select('pemesanan_donor.*')
            ->with('jadwalPelayanan')
            ->orderBy('jadwal_pelayanan.tanggal')
            ->orderBy('jadwal_pelayanan.jam_mulai')
            ->orderBy('pemesanan_donor.waktu_pemesanan')
            ->orderBy('pemesanan_donor.id_pemesanan')
            ->get();

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
            'pemesananAktif' => $pemesananAktif,
            'informasiDonorBerikutnya' => $calculator->calculate($pendonor),
            'jumlahPemberitahuanBelumDibaca' => $jumlahPemberitahuanBelumDibaca,
            'pemberitahuanTerbaru' => $pemberitahuanTerbaru,
        ]);
    }
}

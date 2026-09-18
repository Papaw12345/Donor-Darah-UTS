<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class PendonorJadwalController extends Controller
{
    public function index(): View
    {
        $sekarangWib = CarbonImmutable::now('Asia/Jakarta');

        $jadwal = JadwalPelayanan::query()
            ->where('status_jadwal', 'DIBUKA')
            ->whereDate('tanggal', '>=', $sekarangWib->toDateString())
            ->withCount([
                'pemesananDonor as jumlah_pemesanan_berlaku' => function ($query) {
                    $query->whereIn(
                        'status_pemesanan',
                        PemesananDonor::CAPACITY_CONSUMING_STATUSES
                    );
                },
            ])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get()
            ->filter(function (JadwalPelayanan $item) use ($sekarangWib) {
                return $item->jumlah_pemesanan_berlaku < $item->kapasitas
                    && ! $this->pelayananSudahBerakhir($item, $sekarangWib);
            })
            ->values();

        return view('pendonor.jadwal', [
            'jadwal' => $jadwal,
        ]);
    }

    private function pelayananSudahBerakhir(
        JadwalPelayanan $jadwal,
        CarbonImmutable $sekarangWib
    ): bool {
        $tanggalJadwal = $jadwal->tanggal->toDateString();

        if ($tanggalJadwal !== $sekarangWib->toDateString()) {
            return false;
        }

        $waktuSelesai = CarbonImmutable::parse(
            $tanggalJadwal.' '.$jadwal->jam_selesai,
            'Asia/Jakarta'
        );

        return $sekarangWib->gt($waktuSelesai);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelayanan;
use Illuminate\View\View;

class PendonorJadwalController extends Controller
{
    public function index(): View
    {
        $jadwal = JadwalPelayanan::query()
            ->where('status_jadwal', 'DIBUKA')
            ->whereDate('tanggal', '>=', today('Asia/Jakarta'))
            ->withCount([
                'pemesananDonor as jumlah_pemesanan_berlaku' => function ($query) {
                    $query->whereIn('status_pemesanan', [
                        'TERJADWAL',
                        'CHECK_IN',
                        'SELESAI',
                        'TIDAK_HADIR',
                    ]);
                },
            ])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get()
            ->filter(function (JadwalPelayanan $item) {
                return $item->jumlah_pemesanan_berlaku < $item->kapasitas;
            })
            ->values();

        return view('pendonor.jadwal', [
            'jadwal' => $jadwal,
        ]);
    }
}

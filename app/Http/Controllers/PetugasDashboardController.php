<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $akun = $request->user();
        $petugas = $akun->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        $tanggalAcuan = CarbonImmutable::now('Asia/Jakarta')->toDateString();
        $statusKegiatan = [
            'TERJADWAL',
            'CHECK_IN',
            'SELESAI',
            'TIDAK_HADIR',
        ];

        $kegiatanHariIni = array_fill_keys($statusKegiatan, 0);
        $hasilKegiatan = DB::table('pemesanan_donor')
            ->join(
                'jadwal_pelayanan',
                'jadwal_pelayanan.id_jadwal',
                '=',
                'pemesanan_donor.id_jadwal'
            )
            ->whereDate('jadwal_pelayanan.tanggal', $tanggalAcuan)
            ->whereIn('pemesanan_donor.status_pemesanan', $statusKegiatan)
            ->select('pemesanan_donor.status_pemesanan')
            ->selectRaw('COUNT(*) AS jumlah')
            ->groupBy('pemesanan_donor.status_pemesanan')
            ->pluck('jumlah', 'status_pemesanan');

        foreach ($hasilKegiatan as $status => $jumlah) {
            $kegiatanHariIni[$status] = (int) $jumlah;
        }

        $jumlahPendonorDiproses = DB::table('pemesanan_donor')
            ->where('status_pemesanan', 'CHECK_IN')
            ->distinct()
            ->count('id_pendonor');

        $totalPersediaanTersedia = DB::table('unit_komponen_darah')
            ->where('status_unit', 'TERSEDIA')
            ->whereDate('tanggal_kedaluwarsa', '>=', $tanggalAcuan)
            ->count();

        $ambangPersediaan = DB::table('ambang_persediaan')
            ->join(
                'jenis_komponen_darah',
                'jenis_komponen_darah.id_jenis_komponen',
                '=',
                'ambang_persediaan.id_jenis_komponen'
            )
            ->join(
                'golongan_darah',
                'golongan_darah.id_golongan_darah',
                '=',
                'ambang_persediaan.id_golongan_darah'
            )
            ->select([
                'ambang_persediaan.id_ambang',
                'ambang_persediaan.id_jenis_komponen',
                'ambang_persediaan.id_golongan_darah',
                'ambang_persediaan.jumlah_minimum',
                'jenis_komponen_darah.kode_komponen',
                'jenis_komponen_darah.nama_komponen',
                'golongan_darah.abo',
                'golongan_darah.rhesus',
            ])
            ->orderBy('jenis_komponen_darah.kode_komponen')
            ->orderBy('jenis_komponen_darah.nama_komponen')
            ->orderBy('golongan_darah.abo')
            ->orderBy('golongan_darah.rhesus')
            ->orderBy('ambang_persediaan.id_ambang')
            ->get();

        $persediaanRendah = $ambangPersediaan
            ->map(function (object $ambang) use ($tanggalAcuan): object {
                $ambang->jumlah_persediaan = DB::table('unit_komponen_darah')
                    ->where('status_unit', 'TERSEDIA')
                    ->whereDate('tanggal_kedaluwarsa', '>=', $tanggalAcuan)
                    ->where('id_jenis_komponen', $ambang->id_jenis_komponen)
                    ->where('id_golongan_darah', $ambang->id_golongan_darah)
                    ->count();

                return $ambang;
            })
            ->filter(
                fn (object $ambang): bool => $ambang->jumlah_persediaan
                    <= $ambang->jumlah_minimum
            )
            ->values();

        return view('petugas.dashboard', [
            'akun' => $akun,
            'petugas' => $petugas,
            'tanggalAcuan' => $tanggalAcuan,
            'kegiatanHariIni' => $kegiatanHariIni,
            'jumlahPendonorDiproses' => $jumlahPendonorDiproses,
            'totalPersediaanTersedia' => $totalPersediaanTersedia,
            'jumlahAmbangPersediaan' => $ambangPersediaan->count(),
            'persediaanRendah' => $persediaanRendah,
        ]);
    }
}

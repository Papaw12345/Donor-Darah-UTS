<?php

namespace App\Http\Controllers;

use App\Support\PersediaanDarahQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasDashboardController extends Controller
{
    public function index(
        Request $request,
        PersediaanDarahQuery $persediaanQuery
    ): View
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

        $totalPersediaanTersedia = $persediaanQuery
            ->eligibleUnitsQuery($tanggalAcuan)
            ->count();

        $jumlahAmbangPersediaan = DB::table('ambang_persediaan')->count();

        $persediaanRendah = $persediaanQuery
            ->lowStockBaseQuery($tanggalAcuan)
            ->orderBy('jenis_komponen_darah.kode_komponen')
            ->orderBy('jenis_komponen_darah.nama_komponen')
            ->orderBy('golongan_darah.abo')
            ->orderBy('golongan_darah.rhesus')
            ->orderBy('ambang_persediaan.id_ambang')
            ->get();

        return view('petugas.dashboard', [
            'akun' => $akun,
            'petugas' => $petugas,
            'tanggalAcuan' => $tanggalAcuan,
            'kegiatanHariIni' => $kegiatanHariIni,
            'jumlahPendonorDiproses' => $jumlahPendonorDiproses,
            'totalPersediaanTersedia' => $totalPersediaanTersedia,
            'jumlahAmbangPersediaan' => $jumlahAmbangPersediaan,
            'persediaanRendah' => $persediaanRendah,
        ]);
    }
}

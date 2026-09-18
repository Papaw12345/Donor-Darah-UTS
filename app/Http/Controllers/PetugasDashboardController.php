<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelayanan;
use App\Support\PersediaanDarahQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasDashboardController extends Controller
{
    public function jadwal(Request $request): View
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        $jadwal = JadwalPelayanan::query()
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_mulai')
            ->get();

        return view('petugas.jadwal', [
            'jadwal' => $jadwal,
        ]);
    }

    public function riwayatPelayanan(Request $request): View
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        $ringkasanUnit = DB::table('unit_komponen_darah')
            ->select('id_penyumbangan')
            ->selectRaw('COUNT(*) AS jumlah_unit')
            ->groupBy('id_penyumbangan');

        $riwayatPelayanan = DB::table('seleksi_donor')
            ->join(
                'pemesanan_donor',
                'pemesanan_donor.id_pemesanan',
                '=',
                'seleksi_donor.id_pemesanan'
            )
            ->join(
                'pendonor',
                'pendonor.id_pendonor',
                '=',
                'pemesanan_donor.id_pendonor'
            )
            ->leftJoin(
                'penyumbangan',
                'penyumbangan.id_seleksi',
                '=',
                'seleksi_donor.id_seleksi'
            )
            ->leftJoinSub(
                $ringkasanUnit,
                'ringkasan_unit',
                'ringkasan_unit.id_penyumbangan',
                '=',
                'penyumbangan.id_penyumbangan'
            )
            ->where(function ($query): void {
                $query
                    ->whereIn(
                        'seleksi_donor.keputusan_seleksi',
                        ['DITUNDA', 'DITOLAK']
                    )
                    ->orWhereNotNull('penyumbangan.id_penyumbangan');
            })
            ->select([
                'seleksi_donor.id_seleksi',
                'seleksi_donor.id_pemesanan',
                'seleksi_donor.waktu_seleksi',
                'seleksi_donor.keputusan_seleksi',
                'pemesanan_donor.id_pendonor',
                'pendonor.nama_lengkap',
                'pendonor.nomor_donor',
                'penyumbangan.id_penyumbangan',
                'penyumbangan.waktu_pengambilan',
                'penyumbangan.volume_ml',
                'penyumbangan.hasil_penyumbangan',
            ])
            ->selectRaw(
                'CASE
                    WHEN penyumbangan.id_penyumbangan IS NOT NULL
                        THEN penyumbangan.hasil_penyumbangan
                    ELSE seleksi_donor.keputusan_seleksi
                END AS hasil_pelayanan'
            )
            ->selectRaw(
                'COALESCE(
                    penyumbangan.waktu_pengambilan,
                    seleksi_donor.waktu_seleksi
                ) AS waktu_pelayanan'
            )
            ->selectRaw(
                'COALESCE(ringkasan_unit.jumlah_unit, 0) AS jumlah_unit'
            )
            ->orderByDesc('waktu_pelayanan')
            ->orderByDesc('seleksi_donor.id_seleksi')
            ->get();

        return view('petugas.riwayat-pelayanan', [
            'riwayatPelayanan' => $riwayatPelayanan,
        ]);
    }
    public function index(
        Request $request,
        PersediaanDarahQuery $persediaanQuery
    ): View {
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

        $pendonorSedangDiproses = DB::table('pemesanan_donor')
            ->join(
                'pendonor',
                'pendonor.id_pendonor',
                '=',
                'pemesanan_donor.id_pendonor'
            )
            ->join(
                'jadwal_pelayanan',
                'jadwal_pelayanan.id_jadwal',
                '=',
                'pemesanan_donor.id_jadwal'
            )
            ->where('pemesanan_donor.status_pemesanan', 'CHECK_IN')
            ->select([
                'pemesanan_donor.id_pemesanan',
                'pemesanan_donor.id_pendonor',
                'pendonor.nama_lengkap',
                'pendonor.nomor_donor',
                'jadwal_pelayanan.tanggal as tanggal_jadwal',
                'pemesanan_donor.waktu_checkin',
            ])
            ->orderByDesc('pemesanan_donor.waktu_checkin')
            ->orderByDesc('pemesanan_donor.id_pemesanan')
            ->get()
            ->unique('id_pendonor')
            ->values();

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
            'pendonorSedangDiproses' => $pendonorSedangDiproses,
            'totalPersediaanTersedia' => $totalPersediaanTersedia,
            'jumlahAmbangPersediaan' => $jumlahAmbangPersediaan,
            'persediaanRendah' => $persediaanRendah,
        ]);
    }
}
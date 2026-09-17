<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasPersediaanRendahController extends Controller
{
    public function index(Request $request): View
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        $tanggalAcuan = now('Asia/Jakarta')->toDateString();

        $jumlahPerKombinasi = DB::table('unit_komponen_darah')
            ->where('status_unit', 'TERSEDIA')
            ->whereDate('tanggal_kedaluwarsa', '>=', $tanggalAcuan)
            ->select([
                'id_jenis_komponen',
                'id_golongan_darah',
            ])
            ->selectRaw('COUNT(*) AS jumlah_persediaan')
            ->groupBy('id_jenis_komponen', 'id_golongan_darah');

        $jumlahAmbangPersediaan = DB::table('ambang_persediaan')->count();

        $persediaanRendah = DB::table('ambang_persediaan')
            ->leftJoinSub(
                $jumlahPerKombinasi,
                'persediaan',
                function (JoinClause $join): void {
                    $join->on(
                        'persediaan.id_jenis_komponen',
                        '=',
                        'ambang_persediaan.id_jenis_komponen'
                    )->on(
                        'persediaan.id_golongan_darah',
                        '=',
                        'ambang_persediaan.id_golongan_darah'
                    );
                }
            )
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
            ->whereRaw(
                'COALESCE(persediaan.jumlah_persediaan, 0) '
                .'<= ambang_persediaan.jumlah_minimum'
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
            ->selectRaw(
                'COALESCE(persediaan.jumlah_persediaan, 0) '
                .'AS jumlah_persediaan'
            )
            ->orderBy('jenis_komponen_darah.kode_komponen')
            ->orderBy('golongan_darah.abo')
            ->orderBy('golongan_darah.rhesus')
            ->orderBy('ambang_persediaan.id_ambang')
            ->get();

        return view('petugas.persediaan-rendah-index', compact(
            'petugas',
            'tanggalAcuan',
            'jumlahAmbangPersediaan',
            'persediaanRendah'
        ));
    }
}

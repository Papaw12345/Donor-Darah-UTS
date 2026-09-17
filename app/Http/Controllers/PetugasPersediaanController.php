<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasPersediaanController extends Controller
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

        $persediaan = DB::query()
            ->fromSub($jumlahPerKombinasi, 'persediaan')
            ->join(
                'jenis_komponen_darah',
                'jenis_komponen_darah.id_jenis_komponen',
                '=',
                'persediaan.id_jenis_komponen'
            )
            ->join(
                'golongan_darah',
                'golongan_darah.id_golongan_darah',
                '=',
                'persediaan.id_golongan_darah'
            )
            ->select([
                'persediaan.id_jenis_komponen',
                'persediaan.id_golongan_darah',
                'jenis_komponen_darah.kode_komponen',
                'jenis_komponen_darah.nama_komponen',
                'golongan_darah.abo',
                'golongan_darah.rhesus',
                'persediaan.jumlah_persediaan',
            ])
            ->orderBy('jenis_komponen_darah.kode_komponen')
            ->orderBy('golongan_darah.abo')
            ->orderBy('golongan_darah.rhesus')
            ->orderBy('persediaan.id_jenis_komponen')
            ->orderBy('persediaan.id_golongan_darah')
            ->get();

        return view('petugas.persediaan-index', compact(
            'petugas',
            'tanggalAcuan',
            'persediaan'
        ));
    }
}

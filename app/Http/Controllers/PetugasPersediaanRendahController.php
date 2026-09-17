<?php

namespace App\Http\Controllers;

use App\Support\PersediaanDarahQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasPersediaanRendahController extends Controller
{
    public function index(
        Request $request,
        PersediaanDarahQuery $persediaanQuery
    ): View
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        $tanggalAcuan = now('Asia/Jakarta')->toDateString();

        $jumlahAmbangPersediaan = DB::table('ambang_persediaan')->count();

        $persediaanRendah = $persediaanQuery
            ->lowStockBaseQuery($tanggalAcuan)
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

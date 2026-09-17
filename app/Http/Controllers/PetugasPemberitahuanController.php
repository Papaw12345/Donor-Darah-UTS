<?php

namespace App\Http\Controllers;

use App\Models\AmbangPersediaan;
use App\Models\Pemberitahuan;
use App\Models\Pendonor;
use App\Models\Petugas;
use App\Support\PendonorDonorBerikutnyaCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasPemberitahuanController extends Controller
{
    public function create(
        Request $request,
        AmbangPersediaan $ambang,
        Pendonor $pendonor,
        PendonorDonorBerikutnyaCalculator $calculator
    ): View|Response {
        $petugas = $this->authenticatedPetugas($request);

        if ($petugas === null) {
            return response(
                'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.',
                409
            );
        }

        $jumlahPersediaan = $this->currentInventoryCount($ambang);

        if ($jumlahPersediaan > $ambang->jumlah_minimum) {
            return response(
                'Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.',
                409
            );
        }

        if (! $this->isValidCandidate($ambang, $pendonor, $calculator)) {
            return response(
                'Pendonor tidak lagi memenuhi kriteria pemanggilan untuk kondisi persediaan ini.',
                409
            );
        }

        $ambang->loadMissing(['jenisKomponenDarah', 'golonganDarah']);
        $pendonor->loadMissing('golonganDarah');

        return view('petugas.pemberitahuan-create', compact(
            'petugas',
            'ambang',
            'pendonor',
            'jumlahPersediaan'
        ));
    }

    public function store(
        Request $request,
        AmbangPersediaan $ambang,
        Pendonor $pendonor,
        PendonorDonorBerikutnyaCalculator $calculator
    ): RedirectResponse|Response {
        $petugas = $this->authenticatedPetugas($request);

        if ($petugas === null) {
            return response(
                'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.',
                409
            );
        }

        $jumlahPersediaan = $this->currentInventoryCount($ambang);

        if ($jumlahPersediaan > $ambang->jumlah_minimum) {
            return response(
                'Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.',
                409
            );
        }

        if (! $this->isValidCandidate($ambang, $pendonor, $calculator)) {
            return response(
                'Pendonor tidak lagi memenuhi kriteria pemanggilan untuk kondisi persediaan ini.',
                409
            );
        }

        $isiPesan = $request->input('isi_pesan');

        if (is_string($isiPesan)) {
            $request->merge(['isi_pesan' => trim($isiPesan)]);
        }

        $validated = $request->validate([
            'isi_pesan' => ['required', 'string'],
        ]);

        Pemberitahuan::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_petugas_pengirim' => $petugas->id_petugas,
            'isi_pesan' => $validated['isi_pesan'],
            'waktu_dibuat' => now(),
            'waktu_dibaca' => null,
        ]);

        return redirect()
            ->route('petugas.pemanggilan.index', [
                'id_ambang' => $ambang->id_ambang,
            ])
            ->with('success', 'Pemberitahuan berhasil dikirim kepada Pendonor.');
    }

    private function authenticatedPetugas(Request $request): ?Petugas
    {
        return $request->user()->petugas()->first();
    }

    private function currentInventoryCount(AmbangPersediaan $ambang): int
    {
        return DB::table('unit_komponen_darah')
            ->where('id_jenis_komponen', $ambang->id_jenis_komponen)
            ->where('id_golongan_darah', $ambang->id_golongan_darah)
            ->where('status_unit', 'TERSEDIA')
            ->whereDate(
                'tanggal_kedaluwarsa',
                '>=',
                now('Asia/Jakarta')->toDateString()
            )
            ->count();
    }

    private function isValidCandidate(
        AmbangPersediaan $ambang,
        Pendonor $pendonor,
        PendonorDonorBerikutnyaCalculator $calculator
    ): bool {
        if ((int) $pendonor->id_golongan_darah !== (int) $ambang->id_golongan_darah) {
            return false;
        }

        $pendonor->loadMissing('akun');

        if ($pendonor->akun === null
            || $pendonor->akun->peran !== 'PENDONOR'
            || $pendonor->akun->status_akun !== 'AKTIF') {
            return false;
        }

        return $calculator->calculate($pendonor)['dapat_mencoba_sekarang'] === true;
    }
}

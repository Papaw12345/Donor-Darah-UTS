<?php

namespace App\Http\Controllers;

use App\Models\PemesananDonor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PetugasKuesionerController extends Controller
{
    public function show(Request $request, PemesananDonor $pemesanan): View
    {
        $petugas = $this->authenticatedPetugas($request);

        $pemesanan->load([
            'pendonor',
            'jadwalPelayanan',
            'kuesionerPradonasi.jawabanKuesioner.pertanyaanKuesioner',
        ]);

        abort_unless(
            in_array($pemesanan->status_pemesanan, ['CHECK_IN', 'SELESAI'], true),
            409,
            'Status pemesanan tidak sesuai untuk tampilan kuesioner Petugas.'
        );

        abort_if(
            $pemesanan->waktu_checkin === null,
            409,
            'Data check-in tidak konsisten: waktu check-in belum tersedia.'
        );

        $kuesioner = $pemesanan->kuesionerPradonasi;

        abort_if(
            $kuesioner === null,
            409,
            'Data kuesioner pradonasi belum tersedia untuk pemesanan ini.'
        );

        $jawaban = $kuesioner->jawabanKuesioner;

        abort_if(
            $jawaban->isEmpty(),
            409,
            'Data kuesioner tidak konsisten: belum ada jawaban yang tersimpan.'
        );

        abort_if(
            $jawaban->contains(
                fn ($item): bool => $item->pertanyaanKuesioner === null
            ),
            409,
            'Data kuesioner tidak konsisten: pertanyaan untuk jawaban tidak ditemukan.'
        );

        $jawaban = $jawaban
            ->sort(function ($left, $right): int {
                return [
                    $left->pertanyaanKuesioner->urutan,
                    $left->pertanyaanKuesioner->id_pertanyaan,
                ] <=> [
                    $right->pertanyaanKuesioner->urutan,
                    $right->pertanyaanKuesioner->id_pertanyaan,
                ];
            })
            ->values();

        return view('petugas.kuesioner', [
            'petugas' => $petugas,
            'pemesanan' => $pemesanan,
            'kuesioner' => $kuesioner,
            'jawaban' => $jawaban,
        ]);
    }

    private function authenticatedPetugas(Request $request): object
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        return $petugas;
    }
}

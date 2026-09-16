<?php

namespace App\Http\Controllers;

use App\Models\GolonganDarah;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\SeleksiDonor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PetugasSeleksiController extends Controller
{
    public function show(Request $request, PemesananDonor $pemesanan): View
    {
        $petugas = $this->authenticatedPetugas($request);

        $pemesanan->load([
            'pendonor.golonganDarah',
            'jadwalPelayanan',
            'kuesionerPradonasi.jawabanKuesioner',
            'seleksiDonor.petugas',
            'seleksiDonor.penyumbangan',
        ]);

        $seleksi = $pemesanan->seleksiDonor;

        if ($seleksi === null) {
            $this->assertEligibleForNewSelection($pemesanan);
        }

        return view('petugas.seleksi', [
            'petugas' => $petugas,
            'pemesanan' => $pemesanan,
            'seleksi' => $seleksi,
            'golonganDarah' => $seleksi === null
                && $pemesanan->pendonor->id_golongan_darah === null
                    ? GolonganDarah::query()
                        ->orderBy('abo')
                        ->orderBy('rhesus')
                        ->get()
                    : collect(),
        ]);
    }

    public function store(Request $request, PemesananDonor $pemesanan): RedirectResponse
    {
        $petugas = $this->authenticatedPetugas($request);
        $validated = $request->validate([
            'berat_badan' => ['required', 'numeric', 'decimal:0,2', 'between:-999.99,999.99'],
            'tekanan_sistolik' => ['required', 'integer', 'between:-32768,32767'],
            'tekanan_diastolik' => ['required', 'integer', 'between:-32768,32767'],
            'denyut_nadi' => ['required', 'integer', 'between:-32768,32767'],
            'suhu_tubuh' => ['required', 'numeric', 'decimal:0,1', 'between:-999.9,999.9'],
            'kadar_hb' => ['required', 'numeric', 'decimal:0,1', 'between:-999.9,999.9'],
            'hasil_pemeriksaan_kesehatan' => ['nullable', 'string', 'max:65535'],
            'keputusan_seleksi' => ['required', Rule::in(['LAYAK', 'DITUNDA', 'DITOLAK'])],
            'alasan_keputusan' => ['nullable', 'string', 'max:65535'],
            'id_golongan_darah' => [
                'nullable',
                'integer',
                Rule::exists('golongan_darah', 'id_golongan_darah'),
            ],
        ]);

        DB::transaction(function () use ($pemesanan, $petugas, $validated): void {
            $lockedPemesanan = PemesananDonor::query()
                ->whereKey($pemesanan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertEligibleForNewSelection($lockedPemesanan);

            $pendonor = Pendonor::query()
                ->whereKey($lockedPemesanan->id_pendonor)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $pendonor->id_golongan_darah === null
                && isset($validated['id_golongan_darah'])
            ) {
                $pendonor->update([
                    'id_golongan_darah' => $validated['id_golongan_darah'],
                ]);
            }

            SeleksiDonor::create([
                'id_pemesanan' => $lockedPemesanan->id_pemesanan,
                'id_petugas' => $petugas->id_petugas,
                'waktu_seleksi' => now(),
                'berat_badan' => $validated['berat_badan'],
                'tekanan_sistolik' => $validated['tekanan_sistolik'],
                'tekanan_diastolik' => $validated['tekanan_diastolik'],
                'denyut_nadi' => $validated['denyut_nadi'],
                'suhu_tubuh' => $validated['suhu_tubuh'],
                'kadar_hb' => $validated['kadar_hb'],
                'hasil_pemeriksaan_kesehatan' => $validated['hasil_pemeriksaan_kesehatan'] ?? null,
                'keputusan_seleksi' => $validated['keputusan_seleksi'],
                'alasan_keputusan' => $validated['alasan_keputusan'] ?? null,
            ]);

            if (in_array($validated['keputusan_seleksi'], ['DITUNDA', 'DITOLAK'], true)) {
                $lockedPemesanan->update(['status_pemesanan' => 'SELESAI']);
            }
        });

        return redirect()
            ->route('petugas.seleksi.show', $pemesanan)
            ->with('success', 'Seleksi donor berhasil disimpan.');
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

    private function assertEligibleForNewSelection(PemesananDonor $pemesanan): void
    {
        abort_unless(
            $pemesanan->status_pemesanan === 'CHECK_IN',
            409,
            'Status pemesanan tidak sesuai untuk membuat seleksi donor.'
        );

        abort_if(
            $pemesanan->waktu_checkin === null,
            409,
            'Data check-in tidak konsisten: waktu check-in belum tersedia.'
        );

        $kuesioner = $pemesanan->kuesionerPradonasi()->first();

        abort_if(
            $kuesioner === null,
            409,
            'Data kuesioner pradonasi belum tersedia untuk pemesanan ini.'
        );

        abort_unless(
            $kuesioner->jawabanKuesioner()->exists(),
            409,
            'Data kuesioner tidak konsisten: belum ada jawaban yang tersimpan.'
        );

        abort_if(
            $pemesanan->seleksiDonor()->exists(),
            409,
            'Seleksi donor untuk pemesanan ini sudah tersimpan dan tidak dapat diubah.'
        );
    }
}

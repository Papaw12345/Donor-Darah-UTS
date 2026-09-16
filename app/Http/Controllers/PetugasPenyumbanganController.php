<?php

namespace App\Http\Controllers;

use App\Models\PemesananDonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PetugasPenyumbanganController extends Controller
{
    public function show(Request $request, SeleksiDonor $seleksi): View
    {
        $petugas = $this->authenticatedPetugas($request);

        $seleksi->load([
            'pemesananDonor.pendonor',
            'pemesananDonor.jadwalPelayanan',
            'penyumbangan.petugasPencatat',
        ]);

        if ($seleksi->penyumbangan === null) {
            $this->assertEligibleForNewDonation($seleksi);
        }

        return view('petugas.penyumbangan', [
            'petugas' => $petugas,
            'seleksi' => $seleksi,
            'pemesanan' => $seleksi->pemesananDonor,
            'penyumbangan' => $seleksi->penyumbangan,
        ]);
    }

    public function store(Request $request, SeleksiDonor $seleksi): RedirectResponse
    {
        $petugas = $this->authenticatedPetugas($request);
        $validated = $request->validate([
            'waktu_pengambilan' => ['required', 'date_format:Y-m-d\\TH:i'],
            'volume_ml' => [
                'nullable',
                'integer',
                Rule::in([350, 450]),
                Rule::requiredIf($request->input('hasil_penyumbangan') === 'BERHASIL'),
            ],
            'hasil_penyumbangan' => ['required', Rule::in(['BERHASIL', 'GAGAL'])],
            'alasan_gagal' => ['nullable', 'string', 'max:65535'],
        ]);

        DB::transaction(function () use ($seleksi, $petugas, $validated): void {
            $lockedSeleksi = SeleksiDonor::query()
                ->whereKey($seleksi->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedSeleksi->keputusan_seleksi === 'LAYAK',
                409,
                'Keputusan seleksi tidak sesuai untuk mencatat penyumbangan.'
            );

            $lockedPemesanan = PemesananDonor::query()
                ->whereKey($lockedSeleksi->id_pemesanan)
                ->lockForUpdate()
                ->first();

            abort_if(
                $lockedPemesanan === null,
                409,
                'Pemesanan donor terkait tidak tersedia.'
            );

            abort_unless(
                $lockedPemesanan->status_pemesanan === 'CHECK_IN',
                409,
                'Status pemesanan tidak sesuai untuk mencatat penyumbangan.'
            );

            abort_if(
                $lockedPemesanan->waktu_checkin === null,
                409,
                'Data check-in tidak konsisten: waktu check-in belum tersedia.'
            );

            abort_if(
                $lockedSeleksi->penyumbangan()->exists(),
                409,
                'Penyumbangan untuk seleksi ini sudah tersimpan dan tidak dapat diubah.'
            );

            $this->assertVolumeMatchesSelectionWeight(
                $validated['volume_ml'] ?? null,
                $lockedSeleksi->berat_badan
            );

            Penyumbangan::create([
                'id_seleksi' => $lockedSeleksi->id_seleksi,
                'id_petugas_pencatat' => $petugas->id_petugas,
                'waktu_pengambilan' => CarbonImmutable::createFromFormat(
                    'Y-m-d\\TH:i',
                    $validated['waktu_pengambilan']
                )->format('Y-m-d H:i:s'),
                'volume_ml' => $validated['volume_ml'] ?? null,
                'hasil_penyumbangan' => $validated['hasil_penyumbangan'],
                'alasan_gagal' => $validated['alasan_gagal'] ?? null,
            ]);

            $lockedPemesanan->update(['status_pemesanan' => 'SELESAI']);
        });

        return redirect()
            ->route('petugas.penyumbangan.show', $seleksi)
            ->with('success', 'Penyumbangan berhasil disimpan.');
    }

    private function authenticatedPetugas(Request $request): Petugas
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        return $petugas;
    }

    private function assertEligibleForNewDonation(SeleksiDonor $seleksi): void
    {
        abort_unless(
            $seleksi->keputusan_seleksi === 'LAYAK',
            409,
            'Keputusan seleksi tidak sesuai untuk mencatat penyumbangan.'
        );

        $pemesanan = $seleksi->pemesananDonor;

        abort_if(
            $pemesanan === null,
            409,
            'Pemesanan donor terkait tidak tersedia.'
        );

        abort_unless(
            $pemesanan->status_pemesanan === 'CHECK_IN',
            409,
            'Status pemesanan tidak sesuai untuk mencatat penyumbangan.'
        );

        abort_if(
            $pemesanan->waktu_checkin === null,
            409,
            'Data check-in tidak konsisten: waktu check-in belum tersedia.'
        );
    }

    private function assertVolumeMatchesSelectionWeight(?int $volume, mixed $weight): void
    {
        if ($volume === 350 && (float) $weight < 45) {
            throw ValidationException::withMessages([
                'volume_ml' => 'Volume 350 mL memerlukan berat badan minimal 45 kg.',
            ]);
        }

        if ($volume === 450 && (float) $weight < 55) {
            throw ValidationException::withMessages([
                'volume_ml' => 'Volume 450 mL memerlukan berat badan minimal 55 kg.',
            ]);
        }
    }
}

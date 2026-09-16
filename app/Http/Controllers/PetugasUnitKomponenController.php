<?php

namespace App\Http\Controllers;

use App\Models\GolonganDarah;
use App\Models\JenisKomponenDarah;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\UnitKomponenDarah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PetugasUnitKomponenController extends Controller
{
    private const COMPONENT_CODES = ['WB', 'PRC', 'TC', 'FFP'];

    public function show(Request $request, Penyumbangan $penyumbangan): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $this->assertSuccessfulDonation($penyumbangan);

        $penyumbangan->load([
            'seleksiDonor.pemesananDonor.pendonor',
            'unitKomponenDarah' => fn ($query) => $query->with([
                'jenisKomponenDarah', 'golonganDarah', 'petugasPencatat',
            ])->orderBy('id_unit'),
        ]);

        return view('petugas.unit-komponen', [
            'petugas' => $petugas,
            'penyumbangan' => $penyumbangan,
            'jenisKomponen' => JenisKomponenDarah::query()
                ->whereIn('kode_komponen', self::COMPONENT_CODES)
                ->orderBy('kode_komponen')
                ->get(),
            'golonganDarah' => GolonganDarah::query()->orderBy('abo')->orderBy('rhesus')->get(),
        ]);
    }

    public function store(Request $request, Penyumbangan $penyumbangan): RedirectResponse
    {
        $petugas = $this->authenticatedPetugas($request);
        $request->merge(['nomor_unit' => trim((string) $request->input('nomor_unit'))]);
        $validated = $request->validate([
            'nomor_unit' => ['required', 'string', 'max:50'],
            'id_jenis_komponen' => ['required', 'integer'],
            'id_golongan_darah' => ['required', 'integer'],
            'tanggal_pembuatan' => ['required', 'date_format:Y-m-d'],
            'tanggal_kedaluwarsa' => ['required', 'date_format:Y-m-d'],
        ]);

        DB::transaction(function () use ($penyumbangan, $petugas, $validated): void {
            $lockedDonation = Penyumbangan::query()->whereKey($penyumbangan->getKey())
                ->lockForUpdate()->firstOrFail();
            $this->assertSuccessfulDonation($lockedDonation);

            if (UnitKomponenDarah::query()->where('nomor_unit', $validated['nomor_unit'])->exists()) {
                throw ValidationException::withMessages(['nomor_unit' => 'Nomor unit sudah digunakan.']);
            }

            $jenis = JenisKomponenDarah::query()->whereKey($validated['id_jenis_komponen'])->first();
            if ($jenis === null || ! in_array($jenis->kode_komponen, self::COMPONENT_CODES, true)) {
                throw ValidationException::withMessages(['id_jenis_komponen' => 'Jenis komponen tidak valid.']);
            }
            if (! GolonganDarah::query()->whereKey($validated['id_golongan_darah'])->exists()) {
                throw ValidationException::withMessages(['id_golongan_darah' => 'Golongan darah tidak valid.']);
            }

            UnitKomponenDarah::create([
                'nomor_unit' => $validated['nomor_unit'],
                'id_penyumbangan' => $lockedDonation->id_penyumbangan,
                'id_jenis_komponen' => $jenis->id_jenis_komponen,
                'id_golongan_darah' => $validated['id_golongan_darah'],
                'id_petugas_pencatat' => $petugas->id_petugas,
                'id_petugas_pelulus' => null,
                'tanggal_pembuatan' => $validated['tanggal_pembuatan'],
                'tanggal_kedaluwarsa' => $validated['tanggal_kedaluwarsa'],
                'waktu_pelulusan' => null,
                'status_unit' => 'MENUNGGU_PELULUSAN',
                'catatan_pelulusan' => null,
                'waktu_distribusi' => null,
            ]);
        });

        return redirect()->route('petugas.unit-komponen.show', $penyumbangan)
            ->with('success', 'Unit komponen berhasil disimpan.');
    }

    private function authenticatedPetugas(Request $request): Petugas
    {
        $petugas = $request->user()->petugas()->first();
        abort_if($petugas === null, 409, 'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');
        return $petugas;
    }

    private function assertSuccessfulDonation(Penyumbangan $penyumbangan): void
    {
        abort_unless($penyumbangan->hasil_penyumbangan === 'BERHASIL', 409, 'Penyumbangan tidak berhasil tidak dapat menghasilkan unit komponen darah.');
    }
}

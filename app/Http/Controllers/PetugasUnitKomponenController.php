<?php

namespace App\Http\Controllers;

use App\Models\GolonganDarah;
use App\Models\JenisKomponenDarah;
use App\Models\Pendonor;
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

    public function index(Request $request): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $penyumbanganBerhasil = Penyumbangan::query()
            ->with(['seleksiDonor.pemesananDonor.pendonor'])
            ->withCount('unitKomponenDarah')
            ->where('hasil_penyumbangan', 'BERHASIL')
            ->orderBy('waktu_pengambilan')
            ->orderBy('id_penyumbangan')
            ->get();

        return view('petugas.unit-komponen-index', compact('petugas', 'penyumbanganBerhasil'));
    }

    public function show(Request $request, Penyumbangan $penyumbangan): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $this->assertSuccessfulDonation($penyumbangan);

        $penyumbangan->load([
            'seleksiDonor.pemesananDonor.pendonor.golonganDarah',
            'unitKomponenDarah' => fn ($query) => $query->with([
                'jenisKomponenDarah', 'golonganDarah', 'petugasPencatat',
            ])->orderBy('id_unit'),
        ]);

        return view('petugas.unit-komponen', [
            'petugas' => $petugas,
            'penyumbangan' => $penyumbangan,
            'tanggalAcuan' => now('Asia/Jakarta')->toDateString(),
            'jenisKomponen' => JenisKomponenDarah::query()
                ->whereIn('kode_komponen', self::COMPONENT_CODES)
                ->orderBy('kode_komponen')
                ->get(),
        ]);
    }

    public function store(Request $request, Penyumbangan $penyumbangan): RedirectResponse
    {
        $petugas = $this->authenticatedPetugas($request);
        foreach (['nomor_unit', 'id_golongan_darah'] as $serverOwnedField) {
            if ($request->exists($serverOwnedField)) {
                throw ValidationException::withMessages([
                    $serverOwnedField => 'Field ini ditentukan oleh server dan tidak boleh dikirim.',
                ]);
            }
        }

        $validated = $request->validate([
            'id_jenis_komponen' => ['required', 'integer'],
            'tanggal_pembuatan' => ['required', 'date_format:Y-m-d'],
            'tanggal_kedaluwarsa' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_pembuatan'],
        ]);

        DB::transaction(function () use ($penyumbangan, $petugas, $validated): void {
            $lockedDonation = Penyumbangan::query()->whereKey($penyumbangan->getKey())
                ->lockForUpdate()->firstOrFail();
            $this->assertSuccessfulDonation($lockedDonation);

            $jenis = JenisKomponenDarah::query()->whereKey($validated['id_jenis_komponen'])->first();
            if ($jenis === null || ! in_array($jenis->kode_komponen, self::COMPONENT_CODES, true)) {
                throw ValidationException::withMessages(['id_jenis_komponen' => 'Jenis komponen tidak valid.']);
            }

            if ($validated['tanggal_kedaluwarsa'] < $validated['tanggal_pembuatan']) {
                throw ValidationException::withMessages([
                    'tanggal_kedaluwarsa' => 'Tanggal kedaluwarsa tidak boleh sebelum tanggal pembuatan.',
                ]);
            }

            $sourceBooking = $lockedDonation->seleksiDonor?->pemesananDonor;
            abort_if($sourceBooking === null, 409, 'Rantai Pendonor sumber penyumbangan tidak tersedia.');

            $sourceDonor = Pendonor::query()->whereKey($sourceBooking->id_pendonor)
                ->lockForUpdate()->first();
            abort_if(
                $sourceDonor === null || $sourceDonor->id_golongan_darah === null,
                409,
                'Golongan darah Pendonor sumber belum terkonfirmasi.'
            );
            abort_unless(
                GolonganDarah::query()->whereKey($sourceDonor->id_golongan_darah)->exists(),
                409,
                'Master golongan darah Pendonor sumber tidak tersedia.'
            );

            // Nomor UNT memakai PK hasil INSERT; nilai unik sementara memenuhi kolom wajib/UNIQUE hingga diganti dalam transaksi yang sama.
            $unit = UnitKomponenDarah::create([
                'nomor_unit' => 'TMP-'.strtoupper(bin2hex(random_bytes(16))),
                'id_penyumbangan' => $lockedDonation->id_penyumbangan,
                'id_jenis_komponen' => $jenis->id_jenis_komponen,
                'id_golongan_darah' => $sourceDonor->id_golongan_darah,
                'id_petugas_pencatat' => $petugas->id_petugas,
                'id_petugas_pelulus' => null,
                'tanggal_pembuatan' => $validated['tanggal_pembuatan'],
                'tanggal_kedaluwarsa' => $validated['tanggal_kedaluwarsa'],
                'waktu_pelulusan' => null,
                'status_unit' => 'MENUNGGU_PELULUSAN',
                'catatan_pelulusan' => null,
                'waktu_distribusi' => null,
            ]);

            $unit->update([
                'nomor_unit' => 'UNT-'.str_pad((string) $unit->id_unit, 6, '0', STR_PAD_LEFT),
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

<?php

namespace App\Http\Controllers;

use App\Models\GolonganDarah;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\SeleksiDonor;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PetugasSeleksiController extends Controller
{
    public function index(Request $request): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $pemesananMenunggu = PemesananDonor::query()
            ->with(['pendonor', 'jadwalPelayanan'])
            ->where('status_pemesanan', 'CHECK_IN')
            ->whereNotNull('waktu_checkin')
            ->whereHas('kuesionerPradonasi', fn ($query) => $query->whereHas('jawabanKuesioner'))
            ->whereDoesntHave('seleksiDonor')
            ->orderBy('waktu_checkin')
            ->orderBy('id_pemesanan')
            ->get();

        return view('petugas.seleksi-index', compact('petugas', 'pemesananMenunggu'));
    }

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
            'berat_badan' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'between:-999.99,999.99'],
            'tekanan_sistolik' => ['required', 'integer', 'gt:0', 'between:-32768,32767'],
            'tekanan_diastolik' => ['required', 'integer', 'gt:0', 'between:-32768,32767'],
            'denyut_nadi' => ['required', 'integer', 'gt:0', 'between:-32768,32767'],
            'suhu_tubuh' => ['required', 'numeric', 'decimal:0,1', 'gt:0', 'between:-999.9,999.9'],
            'kadar_hb' => ['required', 'numeric', 'decimal:0,1', 'gt:0', 'between:-999.9,999.9'],
            'hasil_pemeriksaan_kesehatan' => ['required', 'string', 'max:65535'],
            'keputusan_seleksi' => ['required', Rule::in(['LAYAK', 'DITUNDA', 'DITOLAK'])],
            'alasan_keputusan' => ['required_if:keputusan_seleksi,DITUNDA,DITOLAK', 'nullable', 'string', 'max:65535'],
            'id_golongan_darah' => [
                'nullable',
                'integer',
                Rule::exists('golongan_darah', 'id_golongan_darah'),
            ],
        ], [
            'required' => ':attribute wajib diisi.',
            'numeric' => ':attribute harus berupa angka.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'decimal' => ':attribute memiliki jumlah angka desimal yang tidak sesuai.',
            'berat_badan.decimal' => 'Berat badan harus memiliki paling banyak 2 angka di belakang koma.',
            'suhu_tubuh.decimal' => 'Suhu tubuh harus memiliki paling banyak 1 angka di belakang koma.',
            'kadar_hb.decimal' => 'Kadar Hb harus memiliki paling banyak 1 angka di belakang koma.',
            'gt' => ':attribute harus lebih besar dari :value.',
            'between' => ':attribute harus berada di antara :min dan :max.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'required_if' => 'Alasan keputusan wajib diisi untuk keputusan Ditunda atau Ditolak.',
            'in' => 'Keputusan seleksi harus berupa Layak, Ditunda, atau Ditolak.',
            'exists' => 'Golongan darah harus dipilih dari daftar yang tersedia.',
        ], [
            'berat_badan' => 'Berat badan',
            'tekanan_sistolik' => 'Tekanan sistolik',
            'tekanan_diastolik' => 'Tekanan diastolik',
            'denyut_nadi' => 'Denyut nadi',
            'suhu_tubuh' => 'Suhu tubuh',
            'kadar_hb' => 'Kadar Hb',
            'hasil_pemeriksaan_kesehatan' => 'Hasil pemeriksaan kesehatan',
            'keputusan_seleksi' => 'Keputusan seleksi',
            'alasan_keputusan' => 'Alasan keputusan',
            'id_golongan_darah' => 'Golongan darah',
        ]);

        DB::transaction(function () use ($pemesanan, $petugas, $validated, $request): void {
            $lockedPemesanan = PemesananDonor::query()
                ->whereKey($pemesanan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertEligibleForNewSelection($lockedPemesanan);

            $pendonor = Pendonor::query()
                ->whereKey($lockedPemesanan->id_pendonor)
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['keputusan_seleksi'] === 'LAYAK') {
                $turnsSeventeen = CarbonImmutable::parse(
                    $pendonor->tanggal_lahir->toDateString(),
                    'Asia/Jakarta'
                )->addYearsNoOverflow(17);
                $meetsLayakCriteria = $turnsSeventeen->lte(CarbonImmutable::today('Asia/Jakarta'))
                    && (float) $validated['berat_badan'] >= 45
                    && (int) $validated['tekanan_sistolik'] >= 90
                    && (int) $validated['tekanan_sistolik'] <= 160
                    && (int) $validated['tekanan_diastolik'] >= 60
                    && (int) $validated['tekanan_diastolik'] <= 100
                    && (int) $validated['tekanan_sistolik'] - (int) $validated['tekanan_diastolik'] > 20
                    && (int) $validated['denyut_nadi'] >= 50
                    && (int) $validated['denyut_nadi'] <= 100
                    && (float) $validated['suhu_tubuh'] >= 36.5
                    && (float) $validated['suhu_tubuh'] <= 37.5
                    && (float) $validated['kadar_hb'] >= 12.5
                    && (float) $validated['kadar_hb'] <= 17;

                if (! $meetsLayakCriteria) {
                    throw ValidationException::withMessages([
                        'keputusan_seleksi' => 'Keputusan LAYAK memerlukan seluruh kriteria objektif terpenuhi.',
                    ]);
                }
            }

            if ($pendonor->id_golongan_darah === null) {
                if (! isset($validated['id_golongan_darah'])
                    || ! GolonganDarah::query()->whereKey($validated['id_golongan_darah'])->exists()) {
                    throw ValidationException::withMessages([
                        'id_golongan_darah' => 'Golongan darah Pendonor wajib dikonfirmasi dari master.',
                    ]);
                }

                $pendonor->update([
                    'id_golongan_darah' => $validated['id_golongan_darah'],
                ]);
            } elseif ($request->exists('id_golongan_darah')
                && (string) ($validated['id_golongan_darah'] ?? '') !== (string) $pendonor->id_golongan_darah) {
                throw ValidationException::withMessages([
                    'id_golongan_darah' => 'Golongan darah Pendonor yang sudah terkonfirmasi tidak dapat diganti.',
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

<?php

namespace App\Http\Controllers;

use App\Models\PemesananDonor;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PetugasCheckinController extends Controller
{
    private const CODE_PATTERN = '/\AUDD-[0-9A-F]{12}\z/';

    public function index(Request $request): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $submittedCode = $request->query('kode_checkin');
        $normalizedCode = null;
        $pemesanan = null;
        $lookupError = null;
        $eligibilityMessage = null;
        $canCheckIn = false;

        if ($submittedCode !== null) {
            $normalizedCode = $this->normalizeCode($submittedCode);

            if (! preg_match(self::CODE_PATTERN, $normalizedCode)) {
                $lookupError = 'Format kode check-in tidak valid. Gunakan UDD- diikuti 12 karakter heksadesimal.';
            } else {
                $pemesanan = PemesananDonor::query()
                    ->where('kode_checkin', $normalizedCode)
                    ->with(['pendonor', 'jadwalPelayanan', 'kuesionerPradonasi'])
                    ->first();

                if ($pemesanan === null) {
                    $lookupError = 'Kode check-in tidak ditemukan.';
                } else {
                    $eligibilityMessage = $this->newCheckinUnavailableReason(
                        $pemesanan,
                        $normalizedCode
                    );
                    $canCheckIn = $eligibilityMessage === null;
                }
            }
        }

        return view('petugas.check-in', [
            'petugas' => $petugas,
            'normalizedCode' => $normalizedCode,
            'pemesanan' => $pemesanan,
            'lookupError' => $lookupError,
            'eligibilityMessage' => $eligibilityMessage,
            'canCheckIn' => $canCheckIn,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authenticatedPetugas($request);

        $normalizedCode = $this->normalizeCode($request->input('kode_checkin'));
        $request->merge(['kode_checkin' => $normalizedCode]);
        $request->validate([
            'kode_checkin' => ['required', 'regex:'.self::CODE_PATTERN],
        ], [
            'kode_checkin.required' => 'Kode check-in wajib diisi.',
            'kode_checkin.regex' => 'Format kode check-in tidak valid. Gunakan UDD- diikuti 12 karakter heksadesimal.',
        ]);

        $idempotent = DB::transaction(function () use ($normalizedCode): bool {
            $pemesanan = PemesananDonor::query()
                ->where('kode_checkin', $normalizedCode)
                ->lockForUpdate()
                ->first();

            if ($pemesanan === null) {
                $this->reject('Kode check-in tidak ditemukan.');
            }

            $pemesanan->load(['jadwalPelayanan', 'kuesionerPradonasi']);

            if (
                $pemesanan->status_pemesanan === 'CHECK_IN'
                && $pemesanan->waktu_checkin !== null
            ) {
                return true;
            }

            $unavailableReason = $this->newCheckinUnavailableReason(
                $pemesanan,
                $normalizedCode
            );

            if ($unavailableReason !== null) {
                $this->reject($unavailableReason);
            }

            $pemesanan->update([
                'status_pemesanan' => 'CHECK_IN',
                'waktu_checkin' => now(),
            ]);

            return false;
        });

        return redirect()
            ->route('petugas.check-in.index', ['kode_checkin' => $normalizedCode])
            ->with(
                'success',
                $idempotent
                    ? 'Pemesanan ini sudah berhasil check-in.'
                    : 'Check-in Pendonor berhasil.'
            );
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

    private function normalizeCode(mixed $code): string
    {
        return strtoupper(trim(is_string($code) ? $code : ''));
    }

    private function newCheckinUnavailableReason(
        PemesananDonor $pemesanan,
        string $normalizedCode
    ): ?string {
        if (
            $pemesanan->status_pemesanan === 'CHECK_IN'
            && $pemesanan->waktu_checkin !== null
        ) {
            return 'Pemesanan ini sudah berhasil check-in.';
        }

        if (
            $pemesanan->status_pemesanan === 'CHECK_IN'
            && $pemesanan->waktu_checkin === null
        ) {
            return 'Data check-in tidak konsisten: status CHECK_IN belum memiliki waktu check-in.';
        }

        if (
            $pemesanan->status_pemesanan === 'TERJADWAL'
            && $pemesanan->waktu_checkin !== null
        ) {
            return 'Data check-in tidak konsisten: pemesanan TERJADWAL sudah memiliki waktu check-in.';
        }

        if ($pemesanan->status_pemesanan !== 'TERJADWAL') {
            return 'Pemesanan berstatus '.$pemesanan->status_pemesanan.' tidak dapat menjalani check-in baru.';
        }

        if ($pemesanan->kode_checkin !== $normalizedCode) {
            return 'Kode check-in tidak sesuai dengan pemesanan.';
        }

        if ($pemesanan->kuesionerPradonasi === null) {
            return 'Check-in belum dapat dilakukan karena kuesioner pradonasi belum tersedia.';
        }

        $todayWib = CarbonImmutable::now('Asia/Jakarta')->toDateString();

        if ($pemesanan->jadwalPelayanan->tanggal->toDateString() !== $todayWib) {
            return 'Check-in baru hanya dapat dilakukan pada tanggal jadwal menurut WIB.';
        }

        if ($pemesanan->jadwalPelayanan->status_jadwal === 'DIBATALKAN') {
            return 'Check-in tidak dapat dilakukan karena jadwal dibatalkan.';
        }

        if ($pemesanan->waktu_checkin !== null) {
            return 'Check-in tidak dapat dilakukan karena waktu check-in sudah terisi.';
        }

        return null;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'kode_checkin' => $message,
        ]);
    }
}

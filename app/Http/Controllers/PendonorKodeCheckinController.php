<?php

namespace App\Http\Controllers;

use App\Models\PemesananDonor;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PendonorKodeCheckinController extends Controller
{
    private const MAX_GENERATION_ATTEMPTS = 10;

    public function show(Request $request, string $pemesanan): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $pemesananMilikPendonor = PemesananDonor::query()
            ->whereKey($pemesanan)
            ->where('id_pendonor', $pendonor->id_pendonor)
            ->with(['jadwalPelayanan', 'kuesionerPradonasi'])
            ->firstOrFail();

        $pesanTidakTersedia = $pemesananMilikPendonor->kode_checkin === null
            ? $this->newCodeUnavailableReason($pemesananMilikPendonor)
            : null;

        return view('pendonor.kode-checkin', [
            'pemesanan' => $pemesananMilikPendonor,
            'pesanTidakTersedia' => $pesanTidakTersedia,
        ]);
    }

    public function generate(Request $request, string $pemesanan): RedirectResponse
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $existingCodeRetained = DB::transaction(function () use ($pemesanan, $pendonor): bool {
            $pemesananTerkunci = PemesananDonor::query()
                ->whereKey($pemesanan)
                ->where('id_pendonor', $pendonor->id_pendonor)
                ->lockForUpdate()
                ->firstOrFail();

            $pemesananTerkunci->load(['jadwalPelayanan', 'kuesionerPradonasi']);

            if ($pemesananTerkunci->kode_checkin !== null) {
                return true;
            }

            $pesanTidakTersedia = $this->newCodeUnavailableReason($pemesananTerkunci);

            if ($pesanTidakTersedia !== null) {
                $this->reject($pesanTidakTersedia);
            }

            for ($attempt = 1; $attempt <= self::MAX_GENERATION_ATTEMPTS; $attempt++) {
                $candidate = 'UDD-'.strtoupper(bin2hex(random_bytes(6)));

                $candidateAlreadyUsed = PemesananDonor::query()
                    ->where('kode_checkin', $candidate)
                    ->exists();

                if ($candidateAlreadyUsed) {
                    continue;
                }

                try {
                    $pemesananTerkunci->update([
                        'kode_checkin' => $candidate,
                    ]);

                    return false;
                } catch (QueryException $exception) {
                    if (! $this->isCodeCollision($exception)) {
                        throw $exception;
                    }
                }
            }

            $this->reject('Kode check-in belum dapat dibuat. Silakan coba lagi.');
        });

        $message = $existingCodeRetained
            ? 'Kode check-in yang sudah ada tetap digunakan.'
            : 'Kode check-in berhasil dibuat.';

        return redirect()
            ->route('pendonor.kode-checkin.show', $pemesanan)
            ->with('success', $message);
    }

    private function newCodeUnavailableReason(PemesananDonor $pemesanan): ?string
    {
        if ($pemesanan->status_pemesanan !== 'TERJADWAL') {
            return 'Kode check-in baru hanya dapat dibuat untuk pemesanan berstatus TERJADWAL.';
        }

        if ($pemesanan->kuesionerPradonasi === null) {
            return 'Kode check-in belum dapat dibuat karena kuesioner pradonasi belum diisi.';
        }

        if ($pemesanan->jadwalPelayanan->tanggal->lt(today('Asia/Jakarta'))) {
            return 'Kode check-in tidak dapat dibuat karena tanggal jadwal sudah lewat.';
        }

        if ($pemesanan->jadwalPelayanan->status_jadwal === 'DIBATALKAN') {
            return 'Kode check-in tidak dapat dibuat karena jadwal telah dibatalkan.';
        }

        return null;
    }

    private function isCodeCollision(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());
        $isUniqueViolation = $sqlState === '23000'
            || $driverCode === 1062
            || $driverCode === 19;

        return $isUniqueViolation && str_contains($message, 'kode_checkin');
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'kode_checkin' => $message,
        ]);
    }
}

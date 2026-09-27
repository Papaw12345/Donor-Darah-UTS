<?php

namespace App\Http\Controllers;

use App\Models\JawabanKuesioner;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\PertanyaanKuesioner;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PendonorKuesionerController extends Controller
{
    private const MAX_CODE_ATTEMPTS = 10;

    public function show(Request $request, string $pemesanan): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $pemesananMilikPendonor = PemesananDonor::query()
            ->whereKey($pemesanan)
            ->where('id_pendonor', $pendonor->id_pendonor)
            ->with([
                'jadwalPelayanan',
                'kuesionerPradonasi.jawabanKuesioner.pertanyaanKuesioner',
            ])
            ->firstOrFail();

        $kuesioner = $pemesananMilikPendonor->kuesionerPradonasi;
        $pertanyaanAktif = collect();
        $pesanTidakTersedia = null;

        if ($kuesioner !== null) {
            $kuesioner->setRelation(
                'jawabanKuesioner',
                $kuesioner->jawabanKuesioner
                    ->sort(fn ($left, $right) => [
                        $left->pertanyaanKuesioner->urutan,
                        $left->pertanyaanKuesioner->id_pertanyaan,
                    ] <=> [
                        $right->pertanyaanKuesioner->urutan,
                        $right->pertanyaanKuesioner->id_pertanyaan,
                    ])
                    ->values()
            );
        } else {
            $pesanTidakTersedia = $this->newQuestionnaireUnavailableReason($pemesananMilikPendonor);

            if ($pesanTidakTersedia === null) {
                $pertanyaanAktif = PertanyaanKuesioner::query()
                    ->where('status_aktif', true)
                    ->orderBy('urutan')
                    ->orderBy('id_pertanyaan')
                    ->get();

                if ($pertanyaanAktif->isEmpty()) {
                    $pesanTidakTersedia = 'Kuesioner saat ini belum tersedia karena tidak ada pertanyaan aktif.';
                }
            }
        }

        return view('pendonor.kuesioner', [
            'pemesanan' => $pemesananMilikPendonor,
            'kuesioner' => $kuesioner,
            'pertanyaanAktif' => $pertanyaanAktif,
            'pesanTidakTersedia' => $pesanTidakTersedia,
        ]);
    }

    public function store(Request $request, string $pemesanan): RedirectResponse
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        DB::transaction(function () use ($request, $pemesanan, $pendonor): void {
            $pemesananTerkunci = PemesananDonor::query()
                ->whereKey($pemesanan)
                ->where('id_pendonor', $pendonor->id_pendonor)
                ->lockForUpdate()
                ->firstOrFail();

            if (KuesionerPradonasi::query()
                ->where('id_pemesanan', $pemesananTerkunci->id_pemesanan)
                ->exists()) {
                $this->reject('answers', 'Kuesioner untuk agenda donor ini sudah pernah dikirim.');
            }

            if ($pemesananTerkunci->kode_checkin !== null) {
                $this->reject('answers', 'Agenda donor ini sudah memiliki kode check-in.');
            }

            $pemesananTerkunci->load('jadwalPelayanan');
            $pesanTidakTersedia = $this->newQuestionnaireUnavailableReason($pemesananTerkunci);

            if ($pesanTidakTersedia !== null) {
                $this->reject('answers', $pesanTidakTersedia);
            }

            $pertanyaanAktif = PertanyaanKuesioner::query()
                ->where('status_aktif', true)
                ->orderBy('urutan')
                ->orderBy('id_pertanyaan')
                ->get();

            if ($pertanyaanAktif->isEmpty()) {
                $this->reject('answers', 'Kuesioner saat ini belum tersedia karena tidak ada pertanyaan aktif.');
            }

            $jawabanDikirim = $request->input('answers');

            if (! is_array($jawabanDikirim)) {
                $this->reject('answers', 'Jawaban kuesioner harus dikirim sebagai daftar jawaban.');
            }

            $jawabanMenurutPertanyaan = $this->normalizeSubmittedAnswers($jawabanDikirim);
            $idPertanyaanAktif = $pertanyaanAktif
                ->pluck('id_pertanyaan')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
            $idPertanyaanDikirim = array_keys($jawabanMenurutPertanyaan);
            sort($idPertanyaanDikirim, SORT_NUMERIC);

            if ($idPertanyaanDikirim !== $idPertanyaanAktif) {
                $this->reject(
                    'answers',
                    'Daftar pertanyaan telah berubah atau tidak lengkap. Muat ulang formulir kuesioner.'
                );
            }

            $jawabanTervalidasi = [];

            foreach ($pertanyaanAktif as $pertanyaan) {
                $idPertanyaan = (int) $pertanyaan->id_pertanyaan;
                $jawaban = $jawabanMenurutPertanyaan[$idPertanyaan];

                if (! is_scalar($jawaban)) {
                    $this->reject(
                        "answers.{$idPertanyaan}",
                        'Jawaban harus berupa nilai teks.'
                    );
                }

                $nilaiJawaban = (string) $jawaban;

                if ($pertanyaan->jenis_jawaban === 'YA_TIDAK') {
                    if (! in_array($nilaiJawaban, ['YA', 'TIDAK'], true)) {
                        $this->reject(
                            "answers.{$idPertanyaan}",
                            'Jawaban harus tepat YA atau TIDAK.'
                        );
                    }

                    $jawabanTervalidasi[$idPertanyaan] = $nilaiJawaban;
                } elseif ($pertanyaan->jenis_jawaban === 'TEKS') {
                    $nilaiJawaban = trim($nilaiJawaban);

                    if ($nilaiJawaban === '') {
                        $this->reject(
                            "answers.{$idPertanyaan}",
                            'Jawaban teks wajib diisi.'
                        );
                    }

                    $jawabanTervalidasi[$idPertanyaan] = $nilaiJawaban;
                } else {
                    $this->reject(
                        "answers.{$idPertanyaan}",
                        'Jenis jawaban pertanyaan tidak didukung.'
                    );
                }
            }

            $kuesioner = KuesionerPradonasi::create([
                'id_pemesanan' => $pemesananTerkunci->id_pemesanan,
                'waktu_pengisian' => now(),
            ]);

            foreach ($pertanyaanAktif as $pertanyaan) {
                $idPertanyaan = (int) $pertanyaan->id_pertanyaan;

                JawabanKuesioner::create([
                    'id_kuesioner' => $kuesioner->id_kuesioner,
                    'id_pertanyaan' => $idPertanyaan,
                    'jawaban' => $jawabanTervalidasi[$idPertanyaan],
                ]);
            }

            for ($attempt = 1; $attempt <= self::MAX_CODE_ATTEMPTS; $attempt++) {
                $candidate = 'UDD-'.strtoupper(bin2hex(random_bytes(6)));

                if (PemesananDonor::query()->where('kode_checkin', $candidate)->exists()) {
                    continue;
                }

                try {
                    $pemesananTerkunci->update(['kode_checkin' => $candidate]);

                    return;
                } catch (QueryException $exception) {
                    if (! $this->isCodeCollision($exception)) {
                        throw $exception;
                    }
                }
            }

            $this->reject('answers', 'Kode check-in belum dapat dibuat. Silakan coba lagi.');
        });

        return redirect()
            ->route('pendonor.kode-checkin.show', $pemesanan)
            ->with('success', 'Kuesioner dan kode check-in berhasil disimpan.');
    }

    private function newQuestionnaireUnavailableReason(PemesananDonor $pemesanan): ?string
    {
        if ($pemesanan->status_pemesanan !== 'TERJADWAL') {
            return 'Kuesioner baru hanya dapat diisi untuk agenda donor berstatus Terjadwal.';
        }

        if ($pemesanan->jadwalPelayanan->tanggal->lt(today('Asia/Jakarta'))) {
            return 'Kuesioner tidak dapat diisi karena tanggal jadwal sudah lewat.';
        }

        $sekarangWib = CarbonImmutable::now('Asia/Jakarta');
        $tanggalJadwal = $pemesanan->jadwalPelayanan->tanggal->toDateString();
        $waktuSelesai = CarbonImmutable::parse(
            $tanggalJadwal.' '.$pemesanan->jadwalPelayanan->jam_selesai,
            'Asia/Jakarta'
        );

        if ($tanggalJadwal === $sekarangWib->toDateString() && $sekarangWib->gt($waktuSelesai)) {
            return 'Kuesioner tidak dapat diisi karena waktu pelayanan sudah berakhir.';
        }

        if ($pemesanan->jadwalPelayanan->status_jadwal === 'DIBATALKAN') {
            return 'Kuesioner tidak dapat diisi karena jadwal telah dibatalkan.';
        }

        return null;
    }

    private function isCodeCollision(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        return ($sqlState === '23000' || $driverCode === 1062 || $driverCode === 19)
            && str_contains($message, 'kode_checkin');
    }

    private function normalizeSubmittedAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $key => $answer) {
            $stringKey = (string) $key;

            if (! ctype_digit($stringKey)) {
                $this->reject(
                    'answers',
                    'Daftar pertanyaan telah berubah atau tidak lengkap. Muat ulang formulir kuesioner.'
                );
            }

            $normalizedKey = (int) $stringKey;

            if ($normalizedKey < 1 || array_key_exists($normalizedKey, $normalized)) {
                $this->reject(
                    'answers',
                    'Daftar pertanyaan telah berubah atau tidak lengkap. Muat ulang formulir kuesioner.'
                );
            }

            $normalized[$normalizedKey] = $answer;
        }

        return $normalized;
    }

    private function reject(string $key, string $message): never
    {
        throw ValidationException::withMessages([
            $key => $message,
        ]);
    }
}

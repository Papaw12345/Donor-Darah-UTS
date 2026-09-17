<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Support\PendonorDonorBerikutnyaCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PendonorPemesananController extends Controller
{
    private const SAME_SCHEDULE_BLOCKING_STATUSES = [
        'TERJADWAL',
        'CHECK_IN',
        'SELESAI',
        'TIDAK_HADIR',
    ];

    public function index(Request $request): View
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $pemesanan = PemesananDonor::query()
            ->where('pemesanan_donor.id_pendonor', $pendonor->id_pendonor)
            ->join(
                'jadwal_pelayanan',
                'jadwal_pelayanan.id_jadwal',
                '=',
                'pemesanan_donor.id_jadwal'
            )
            ->select('pemesanan_donor.*')
            ->with('jadwalPelayanan')
            ->orderBy('jadwal_pelayanan.tanggal')
            ->orderBy('jadwal_pelayanan.jam_mulai')
            ->orderBy('pemesanan_donor.waktu_pemesanan')
            ->get();

        return view('pendonor.pemesanan', [
            'pemesanan' => $pemesanan,
        ]);
    }

    public function store(
        Request $request,
        JadwalPelayanan $jadwal,
        PendonorDonorBerikutnyaCalculator $calculator
    ): RedirectResponse
    {
        $akun = $request->user();
        $pendonorTerautentikasi = $akun->pendonor()->firstOrFail();

        DB::transaction(function () use (
            $akun,
            $jadwal,
            $pendonorTerautentikasi,
            $calculator
        ): void {
            $pendonor = Pendonor::query()
                ->whereKey($pendonorTerautentikasi->id_pendonor)
                ->where('id_akun', $akun->id_akun)
                ->lockForUpdate()
                ->firstOrFail();

            $jadwalTerkunci = JadwalPelayanan::query()
                ->whereKey($jadwal->id_jadwal)
                ->lockForUpdate()
                ->firstOrFail();

            $tanggalJadwal = CarbonImmutable::parse(
                $jadwalTerkunci->tanggal->format('Y-m-d'),
                'Asia/Jakarta'
            )->startOfDay();

            if ($jadwalTerkunci->status_jadwal !== 'DIBUKA') {
                $this->reject('Jadwal tidak dibuka untuk pemesanan.');
            }

            if ($tanggalJadwal->lt(today('Asia/Jakarta'))) {
                $this->reject('Jadwal yang sudah lewat tidak dapat dipesan.');
            }

            $jumlahPemesanan = PemesananDonor::query()
                ->where('id_jadwal', $jadwalTerkunci->id_jadwal)
                ->whereIn(
                    'status_pemesanan',
                    PemesananDonor::CAPACITY_CONSUMING_STATUSES
                )
                ->count();

            if ($jumlahPemesanan >= $jadwalTerkunci->kapasitas) {
                $this->reject('Kapasitas jadwal sudah penuh.');
            }

            $pemesananPenghalangAda = PemesananDonor::query()
                ->where('id_pendonor', $pendonor->id_pendonor)
                ->where('id_jadwal', $jadwalTerkunci->id_jadwal)
                ->whereIn('status_pemesanan', self::SAME_SCHEDULE_BLOCKING_STATUSES)
                ->exists();

            if ($pemesananPenghalangAda) {
                $this->reject('Pemesanan untuk jadwal ini sudah pernah dibuat.');
            }

            $informasiDonorBerikutnya = $calculator->calculate(
                $pendonor,
                $tanggalJadwal
            );

            if (! $informasiDonorBerikutnya['interval_terpenuhi']) {
                $this->reject('Jadwal belum memenuhi interval donor minimal dua bulan.');
            }

            if (! $informasiDonorBerikutnya['frekuensi_terpenuhi']) {
                $this->reject('Batas frekuensi donor tahun kalender tersebut sudah tercapai.');
            }

            PemesananDonor::create([
                'id_pendonor' => $pendonor->id_pendonor,
                'id_jadwal' => $jadwalTerkunci->id_jadwal,
                'waktu_pemesanan' => now(),
                'kode_checkin' => null,
                'waktu_checkin' => null,
                'status_pemesanan' => 'TERJADWAL',
            ]);
        });

        return redirect()
            ->route('pendonor.pemesanan.index')
            ->with('success', 'Pemesanan donor berhasil dibuat.');
    }

    public function cancel(Request $request, string $pemesanan): RedirectResponse
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        DB::transaction(function () use ($pemesanan, $pendonor): void {
            $pemesananTerkunci = PemesananDonor::query()
                ->whereKey($pemesanan)
                ->where('id_pendonor', $pendonor->id_pendonor)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pemesananTerkunci->status_pemesanan !== 'TERJADWAL') {
                $this->reject('Pemesanan ini tidak dapat dibatalkan.');
            }

            $jadwal = JadwalPelayanan::query()
                ->whereKey($pemesananTerkunci->id_jadwal)
                ->firstOrFail();

            if ($jadwal->tanggal->lt(today('Asia/Jakarta'))) {
                $this->reject('Pemesanan dengan jadwal yang sudah lewat tidak dapat dibatalkan.');
            }

            $pemesananTerkunci->update([
                'status_pemesanan' => 'DIBATALKAN',
            ]);
        });

        return redirect()
            ->route('pendonor.pemesanan.index')
            ->with('success', 'Pemesanan donor berhasil dibatalkan.');
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'pemesanan' => $message,
        ]);
    }
}

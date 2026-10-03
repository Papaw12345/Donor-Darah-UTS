<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminJadwalController extends Controller
{
    public function index(): View
    {
        $today = CarbonImmutable::today('Asia/Jakarta')->toDateString();
        $jadwalMendatang = JadwalPelayanan::query()
            ->whereDate('tanggal', '>=', $today)
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();
        $jadwalRiwayat = JadwalPelayanan::query()
            ->whereDate('tanggal', '<', $today)
            ->orderByDesc('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        return view('admin.jadwal.index', [
            'jadwalMendatang' => $jadwalMendatang,
            'jadwalRiwayat' => $jadwalRiwayat,
        ]);
    }

    public function create(): View
    {
        return view('admin.jadwal.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        JadwalPelayanan::create([
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'kapasitas' => $validated['kapasitas'],
            'status_jadwal' => $validated['status_jadwal'],
        ]);

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal pelayanan berhasil ditambahkan.');
    }

    public function edit(JadwalPelayanan $jadwal): View
    {
        return view('admin.jadwal.edit', ['jadwal' => $jadwal]);
    }

    public function update(Request $request, JadwalPelayanan $jadwal): RedirectResponse
    {
        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        DB::transaction(function () use ($jadwal, $validated): void {
            $jadwalTerkunci = JadwalPelayanan::query()
                ->whereKey($jadwal->id_jadwal)
                ->lockForUpdate()
                ->firstOrFail();

            $jadwalTerkunci->update([
                'tanggal' => $validated['tanggal'],
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'kapasitas' => $validated['kapasitas'],
                'status_jadwal' => $validated['status_jadwal'],
            ]);

            if ($validated['status_jadwal'] === 'DIBATALKAN') {
                PemesananDonor::query()
                    ->where('id_jadwal', $jadwalTerkunci->id_jadwal)
                    ->where('status_pemesanan', 'TERJADWAL')
                    ->update(['status_pemesanan' => 'DIBATALKAN']);
            }
        });

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal pelayanan berhasil diperbarui.');
    }

    public function destroy(JadwalPelayanan $jadwal): RedirectResponse
    {
        if ($jadwal->pemesananDonor()->exists()) {
            return redirect()
                ->route('admin.jadwal.index')
                ->withErrors([
                    'jadwal' => 'Jadwal yang sudah memiliki pemesanan tidak dapat dihapus. Gunakan status DIBATALKAN jika jadwal tidak ingin digunakan lagi.',
                ]);
        }

        $jadwal->delete();

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal pelayanan berhasil dihapus.');
    }

    private function validationRules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'status_jadwal' => [
                'required',
                Rule::in(['DIBUKA', 'DITUTUP', 'DIBATALKAN']),
            ],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ];
    }
}

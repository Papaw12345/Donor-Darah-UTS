<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelayanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminJadwalController extends Controller
{
    public function index(): View
    {
        $jadwal = JadwalPelayanan::query()
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        return view('admin.jadwal.index', ['jadwal' => $jadwal]);
    }

    public function create(): View
    {
        return view('admin.jadwal.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

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
        $validated = $request->validate($this->validationRules());

        $jadwal->update([
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'kapasitas' => $validated['kapasitas'],
            'status_jadwal' => $validated['status_jadwal'],
        ]);

        return redirect()
            ->route('admin.jadwal.index')
            ->with('success', 'Jadwal pelayanan berhasil diperbarui.');
    }

    private function validationRules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'status_jadwal' => [
                'required',
                Rule::in(['DIBUKA', 'DITUTUP', 'DIBATALKAN']),
            ],
        ];
    }
}

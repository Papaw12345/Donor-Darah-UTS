<?php

namespace App\Http\Controllers;

use App\Models\PertanyaanKuesioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPertanyaanKuesionerController extends Controller
{
    public function index(): View
    {
        $pertanyaan = PertanyaanKuesioner::query()
            ->orderBy('urutan')
            ->orderBy('id_pertanyaan')
            ->get();

        return view('admin.pertanyaan.index', ['pertanyaan' => $pertanyaan]);
    }

    public function create(): View
    {
        return view('admin.pertanyaan.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        PertanyaanKuesioner::create([
            'teks_pertanyaan' => $validated['teks_pertanyaan'],
            'kategori' => $validated['kategori'] ?? null,
            'jenis_jawaban' => $validated['jenis_jawaban'],
            'urutan' => $validated['urutan'],
            'status_aktif' => (bool) $validated['status_aktif'],
        ]);

        return redirect()
            ->route('admin.pertanyaan.index')
            ->with('success', 'Pertanyaan kuesioner berhasil ditambahkan.');
    }

    public function edit(PertanyaanKuesioner $pertanyaan): View
    {
        return view('admin.pertanyaan.edit', ['pertanyaan' => $pertanyaan]);
    }

    public function update(Request $request, PertanyaanKuesioner $pertanyaan): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $pertanyaan->update([
            'teks_pertanyaan' => $validated['teks_pertanyaan'],
            'kategori' => $validated['kategori'] ?? null,
            'jenis_jawaban' => $validated['jenis_jawaban'],
            'urutan' => $validated['urutan'],
            'status_aktif' => (bool) $validated['status_aktif'],
        ]);

        return redirect()
            ->route('admin.pertanyaan.index')
            ->with('success', 'Pertanyaan kuesioner berhasil diperbarui.');
    }

    private function validationRules(): array
    {
        return [
            'teks_pertanyaan' => ['required', 'string'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'jenis_jawaban' => [
                'required',
                Rule::in(['YA_TIDAK', 'TEKS']),
            ],
            'urutan' => ['required', 'integer'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }
}

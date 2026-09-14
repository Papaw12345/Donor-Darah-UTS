<?php

namespace App\Http\Controllers;

use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JenisKomponenDarah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAmbangPersediaanController extends Controller
{
    public function index(): View
    {
        $ambang = AmbangPersediaan::query()
            ->with(['jenisKomponenDarah', 'golonganDarah'])
            ->orderBy('id_jenis_komponen')
            ->orderBy('id_golongan_darah')
            ->get();

        return view('admin.ambang.index', ['ambang' => $ambang]);
    }

    public function create(): View
    {
        return view('admin.ambang.create', [
            'jenisKomponen' => JenisKomponenDarah::query()
                ->orderBy('id_jenis_komponen')
                ->get(),
            'golonganDarah' => GolonganDarah::query()
                ->orderBy('id_golongan_darah')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_jenis_komponen' => [
                'required',
                'integer',
                'exists:jenis_komponen_darah,id_jenis_komponen',
            ],
            'id_golongan_darah' => [
                'required',
                'integer',
                'exists:golongan_darah,id_golongan_darah',
            ],
            'jumlah_minimum' => ['required', 'integer', 'min:0'],
        ]);

        $combinationExists = AmbangPersediaan::query()
            ->where('id_jenis_komponen', $validated['id_jenis_komponen'])
            ->where('id_golongan_darah', $validated['id_golongan_darah'])
            ->exists();

        if ($combinationExists) {
            throw ValidationException::withMessages([
                'id_golongan_darah' => 'Kombinasi jenis komponen dan golongan darah sudah dikonfigurasi.',
            ]);
        }

        AmbangPersediaan::create([
            'id_jenis_komponen' => $validated['id_jenis_komponen'],
            'id_golongan_darah' => $validated['id_golongan_darah'],
            'jumlah_minimum' => $validated['jumlah_minimum'],
        ]);

        return redirect()
            ->route('admin.ambang.index')
            ->with('success', 'Ambang persediaan berhasil ditambahkan.');
    }

    public function edit(AmbangPersediaan $ambang): View
    {
        $ambang->load(['jenisKomponenDarah', 'golonganDarah']);

        return view('admin.ambang.edit', ['ambang' => $ambang]);
    }

    public function update(Request $request, AmbangPersediaan $ambang): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah_minimum' => ['required', 'integer', 'min:0'],
        ]);

        $ambang->update([
            'jumlah_minimum' => $validated['jumlah_minimum'],
        ]);

        return redirect()
            ->route('admin.ambang.index')
            ->with('success', 'Ambang persediaan berhasil diperbarui.');
    }
}

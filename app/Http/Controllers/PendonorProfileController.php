<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendonorProfileController extends Controller
{
    public function show(Request $request): View
    {
        $akun = $request->user();

        $pendonor = $akun->pendonor()
            ->with('golonganDarah')
            ->firstOrFail();

        return view('pendonor.profile', [
            'akun' => $akun,
            'pendonor' => $pendonor,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'alamat_kantor' => ['nullable', 'string'],
        ]);

        $pendonor->update($validated);

        return redirect()
            ->route('pendonor.profil.show')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}

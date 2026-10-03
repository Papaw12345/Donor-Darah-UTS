<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Petugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPetugasController extends Controller
{
    public function index(): View
    {
        $petugas = Petugas::query()
            ->join('akun', 'akun.id_akun', '=', 'petugas.id_akun')
            ->select('petugas.*')
            ->with('akun')
            ->where('akun.peran', 'PETUGAS')
            ->orderByRaw("CASE WHEN akun.status_akun = 'AKTIF' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('petugas.id_petugas')
            ->get();

        return view('admin.petugas.index', ['petugas' => $petugas]);
    }

    public function create(): View
    {
        return view('admin.petugas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('akun', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'nama_petugas' => ['required', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($validated): void {
            $akun = Akun::create([
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'peran' => 'PETUGAS',
                'status_akun' => 'AKTIF',
            ]);

            // Nomor PTG memakai PK hasil INSERT; nilai unik sementara memenuhi kolom wajib/UNIQUE hingga diganti dalam transaksi yang sama.
            $petugas = Petugas::create([
                'id_akun' => $akun->id_akun,
                'nomor_petugas' => 'TMP-'.bin2hex(random_bytes(16)),
                'nama_petugas' => $validated['nama_petugas'],
            ]);

            $petugas->update([
                'nomor_petugas' => 'PTG-'.str_pad((string) $petugas->id_petugas, 6, '0', STR_PAD_LEFT),
            ]);
        });

        return redirect()
            ->route('admin.petugas.index')
            ->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function edit(Petugas $petugas): View
    {
        $akun = $this->requirePetugasAccount($petugas);

        return view('admin.petugas.edit', [
            'petugas' => $petugas,
            'akun' => $akun,
        ]);
    }

    public function update(Request $request, Petugas $petugas): RedirectResponse
    {
        $akun = $this->requirePetugasAccount($petugas);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('akun', 'email')->ignore($akun->id_akun, 'id_akun'),
            ],
            'nama_petugas' => ['required', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($akun, $petugas, $validated): void {
            $akun->update([
                'email' => $validated['email'],
            ]);

            $petugas->update([
                'nama_petugas' => $validated['nama_petugas'],
            ]);
        });

        return redirect()
            ->route('admin.petugas.index')
            ->with('success', 'Data Petugas berhasil diperbarui.');
    }

    public function deactivate(Petugas $petugas): RedirectResponse
    {
        $akun = $this->requirePetugasAccount($petugas);

        $akun->update(['status_akun' => 'NONAKTIF']);

        return redirect()
            ->route('admin.petugas.index')
            ->with('success', 'Akun Petugas berhasil dinonaktifkan.');
    }

    public function activate(Petugas $petugas): RedirectResponse
    {
        $akun = $this->requirePetugasAccount($petugas);

        $akun->update(['status_akun' => 'AKTIF']);

        return redirect()
            ->route('admin.petugas.index')
            ->with('success', 'Akun Petugas berhasil diaktifkan.');
    }

    private function requirePetugasAccount(Petugas $petugas): Akun
    {
        $akun = $petugas->akun;

        abort_if(
            $akun === null || $akun->peran !== 'PETUGAS',
            409,
            'Relasi akun Petugas tidak konsisten.',
        );

        return $akun;
    }
}

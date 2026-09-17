<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use App\Models\UnitKomponenDarah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasDistribusiController extends Controller
{
    public function index(Request $request): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $tanggalAcuan = $this->tanggalAcuan();

        $units = UnitKomponenDarah::query()
            ->with(['jenisKomponenDarah', 'golonganDarah'])
            ->where('status_unit', 'TERSEDIA')
            ->whereDate('tanggal_kedaluwarsa', '>=', $tanggalAcuan)
            ->orderBy('id_unit')
            ->get();

        return view('petugas.distribusi-index', compact(
            'petugas',
            'tanggalAcuan',
            'units'
        ));
    }

    public function show(Request $request, UnitKomponenDarah $unit): View
    {
        $petugas = $this->authenticatedPetugas($request);
        $tanggalAcuan = $this->tanggalAcuan();

        $unit->load(['jenisKomponenDarah', 'golonganDarah']);
        $eligible = $this->isEligible($unit, $tanggalAcuan);

        return view('petugas.distribusi-show', compact(
            'petugas',
            'unit',
            'tanggalAcuan',
            'eligible'
        ));
    }

    public function store(Request $request, UnitKomponenDarah $unit): RedirectResponse
    {
        $this->authenticatedPetugas($request);

        DB::transaction(function () use ($unit): void {
            $lockedUnit = UnitKomponenDarah::query()
                ->whereKey($unit->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $this->isEligible($lockedUnit, $this->tanggalAcuan()),
                409,
                'Unit tidak lagi memenuhi syarat untuk didistribusikan.'
            );

            $lockedUnit->update([
                'status_unit' => 'DIDISTRIBUSIKAN',
                'waktu_distribusi' => now(),
            ]);
        });

        return redirect()
            ->route('petugas.distribusi.show', $unit)
            ->with('success', 'Distribusi unit berhasil dicatat.');
    }

    private function authenticatedPetugas(Request $request): Petugas
    {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        return $petugas;
    }

    private function tanggalAcuan(): string
    {
        return now('Asia/Jakarta')->toDateString();
    }

    private function isEligible(UnitKomponenDarah $unit, string $tanggalAcuan): bool
    {
        return $unit->status_unit === 'TERSEDIA'
            && $unit->tanggal_kedaluwarsa->toDateString() >= $tanggalAcuan;
    }
}

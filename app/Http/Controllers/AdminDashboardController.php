<?php

namespace App\Http\Controllers;

use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JenisKomponenDarah;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $activePetugasCount = Petugas::query()
            ->whereHas('akun', function (Builder $query): void {
                $query
                    ->where('peran', 'PETUGAS')
                    ->where('status_akun', 'AKTIF');
            })
            ->count();

        $openUpcomingScheduleCount = JadwalPelayanan::query()
            ->where('status_jadwal', 'DIBUKA')
            ->whereDate('tanggal', '>=', today())
            ->count();

        $activeQuestionCount = PertanyaanKuesioner::query()
            ->where('status_aktif', true)
            ->count();

        $configuredThresholdCount = AmbangPersediaan::query()->count();
        $expectedThresholdCount = JenisKomponenDarah::query()->count()
            * GolonganDarah::query()->count();
        $missingThresholdCount = $expectedThresholdCount - $configuredThresholdCount;

        return view('admin.dashboard', [
            'email' => $request->user()->email,
            'activePetugasCount' => $activePetugasCount,
            'openUpcomingScheduleCount' => $openUpcomingScheduleCount,
            'activeQuestionCount' => $activeQuestionCount,
            'configuredThresholdCount' => $configuredThresholdCount,
            'expectedThresholdCount' => $expectedThresholdCount,
            'missingThresholdCount' => $missingThresholdCount,
        ]);
    }
}

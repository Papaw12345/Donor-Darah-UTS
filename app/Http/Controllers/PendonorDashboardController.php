<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PendonorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $akun = $request->user();
        $pendonor = $akun->pendonor()
            ->with('golonganDarah')
            ->firstOrFail();

        return view('pendonor.dashboard', [
            'akun' => $akun,
            'pendonor' => $pendonor,
        ]);
    }
}

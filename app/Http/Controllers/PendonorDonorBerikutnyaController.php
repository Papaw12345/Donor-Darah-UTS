<?php

namespace App\Http\Controllers;

use App\Support\PendonorDonorBerikutnyaCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendonorDonorBerikutnyaController extends Controller
{
    public function index(
        Request $request,
        PendonorDonorBerikutnyaCalculator $calculator
    ): View {
        $pendonor = $request->user()
            ->pendonor()
            ->firstOrFail();

        return view('pendonor.donor-berikutnya', [
            'informasiDonorBerikutnya' => $calculator->calculate($pendonor),
        ]);
    }
}

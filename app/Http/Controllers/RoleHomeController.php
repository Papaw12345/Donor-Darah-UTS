<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleHomeController extends Controller
{
    public function pendonor(Request $request): View
    {
        return $this->show($request, 'Area Pendonor');
    }

    public function petugas(Request $request): View
    {
        return $this->show($request, 'Area Petugas');
    }

    public function admin(Request $request): View
    {
        return $this->show($request, 'Area Admin');
    }

    private function show(Request $request, string $title): View
    {
        return view('role-home', [
            'title' => $title,
            'email' => $request->user()->email,
        ]);
    }
}

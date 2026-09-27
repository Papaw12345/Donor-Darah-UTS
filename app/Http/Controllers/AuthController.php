<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Pendonor;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $hariIniWib = CarbonImmutable::today('Asia/Jakarta');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:akun,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'nik' => ['required', 'string', 'regex:/\A[0-9]{16}\z/', 'unique:pendonor,nik'],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['LAKI_LAKI', 'PEREMPUAN'])],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:'.$hariIniWib->toDateString()],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'alamat_kantor' => ['nullable', 'string'],
        ]);

        if (CarbonImmutable::parse($validated['tanggal_lahir'], 'Asia/Jakarta')
            ->addYearsNoOverflow(17)->gt($hariIniWib)) {
            throw ValidationException::withMessages([
                'tanggal_lahir' => 'Usia minimal saat pendaftaran adalah 17 tahun.',
            ]);
        }

        DB::transaction(function () use ($validated): void {
            $akun = Akun::create([
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'peran' => 'PENDONOR',
                'status_akun' => 'AKTIF',
            ]);

            $pendonor = Pendonor::create([
                'id_akun' => $akun->id_akun,
                'id_golongan_darah' => null,
                'nik' => $validated['nik'],
                'nomor_donor' => null,
                'nama_lengkap' => $validated['nama_lengkap'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'tempat_lahir' => $validated['tempat_lahir'],
                'alamat' => $validated['alamat'],
                'nomor_telepon' => $validated['nomor_telepon'],
                'pekerjaan' => $validated['pekerjaan'] ?? null,
                'alamat_kantor' => $validated['alamat_kantor'] ?? null,
            ]);

            $pendonor->update([
                'nomor_donor' => 'DNR-'.str_pad((string) $pendonor->id_pendonor, 6, '0', STR_PAD_LEFT),
            ]);
        });

        return redirect()
            ->route('login')
            ->with('success', 'Pendaftaran Pendonor berhasil. Silakan masuk menggunakan akun Anda.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status_akun' => 'AKTIF',
        ])) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $destination = match (Auth::user()->peran) {
            'PENDONOR' => '/pendonor',
            'PETUGAS' => '/petugas',
            'ADMIN' => '/admin',
            default => null,
        };

        if ($destination === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Email atau kata sandi tidak valid.'])
                ->withInput(['email' => $credentials['email']]);
        }

        return redirect($destination);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

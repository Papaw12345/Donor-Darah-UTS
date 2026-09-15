<?php

use App\Http\Controllers\AdminAmbangPersediaanController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminJadwalController;
use App\Http\Controllers\AdminPertanyaanKuesionerController;
use App\Http\Controllers\AdminPetugasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PendonorDashboardController;
use App\Http\Controllers\PendonorJadwalController;
use App\Http\Controllers\PendonorProfileController;
use App\Http\Controllers\RoleHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.authenticate');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/pendonor', [PendonorDashboardController::class, 'index'])
        ->middleware('role:PENDONOR')
        ->name('pendonor.home');

    Route::get('/pendonor/profil', [PendonorProfileController::class, 'show'])
        ->middleware('role:PENDONOR')
        ->name('pendonor.profil.show');

    Route::put('/pendonor/profil', [PendonorProfileController::class, 'update'])
        ->middleware('role:PENDONOR')
        ->name('pendonor.profil.update');

    Route::get('/pendonor/jadwal', [PendonorJadwalController::class, 'index'])
        ->middleware('role:PENDONOR')
        ->name('pendonor.jadwal.index');

    Route::get('/petugas', [RoleHomeController::class, 'petugas'])
        ->middleware('role:PETUGAS')
        ->name('petugas.home');

    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/admin', [AdminDashboardController::class, 'index'])
            ->name('admin.home');

        Route::get('/admin/petugas', [AdminPetugasController::class, 'index'])
            ->name('admin.petugas.index');
        Route::get('/admin/petugas/create', [AdminPetugasController::class, 'create'])
            ->name('admin.petugas.create');
        Route::post('/admin/petugas', [AdminPetugasController::class, 'store'])
            ->name('admin.petugas.store');
        Route::get('/admin/petugas/{petugas}/edit', [AdminPetugasController::class, 'edit'])
            ->name('admin.petugas.edit');
        Route::put('/admin/petugas/{petugas}', [AdminPetugasController::class, 'update'])
            ->name('admin.petugas.update');
        Route::patch('/admin/petugas/{petugas}/nonaktifkan', [AdminPetugasController::class, 'deactivate'])
            ->name('admin.petugas.deactivate');
        Route::patch('/admin/petugas/{petugas}/aktifkan', [AdminPetugasController::class, 'activate'])
            ->name('admin.petugas.activate');

        Route::get('/admin/jadwal', [AdminJadwalController::class, 'index'])
            ->name('admin.jadwal.index');
        Route::get('/admin/jadwal/create', [AdminJadwalController::class, 'create'])
            ->name('admin.jadwal.create');
        Route::post('/admin/jadwal', [AdminJadwalController::class, 'store'])
            ->name('admin.jadwal.store');
        Route::get('/admin/jadwal/{jadwal}/edit', [AdminJadwalController::class, 'edit'])
            ->name('admin.jadwal.edit');
        Route::put('/admin/jadwal/{jadwal}', [AdminJadwalController::class, 'update'])
            ->name('admin.jadwal.update');

        Route::get('/admin/pertanyaan', [AdminPertanyaanKuesionerController::class, 'index'])
            ->name('admin.pertanyaan.index');
        Route::get('/admin/pertanyaan/create', [AdminPertanyaanKuesionerController::class, 'create'])
            ->name('admin.pertanyaan.create');
        Route::post('/admin/pertanyaan', [AdminPertanyaanKuesionerController::class, 'store'])
            ->name('admin.pertanyaan.store');
        Route::get('/admin/pertanyaan/{pertanyaan}/edit', [AdminPertanyaanKuesionerController::class, 'edit'])
            ->name('admin.pertanyaan.edit');
        Route::put('/admin/pertanyaan/{pertanyaan}', [AdminPertanyaanKuesionerController::class, 'update'])
            ->name('admin.pertanyaan.update');

        Route::get('/admin/ambang', [AdminAmbangPersediaanController::class, 'index'])
            ->name('admin.ambang.index');
        Route::get('/admin/ambang/create', [AdminAmbangPersediaanController::class, 'create'])
            ->name('admin.ambang.create');
        Route::post('/admin/ambang', [AdminAmbangPersediaanController::class, 'store'])
            ->name('admin.ambang.store');
        Route::get('/admin/ambang/{ambang}/edit', [AdminAmbangPersediaanController::class, 'edit'])
            ->name('admin.ambang.edit');
        Route::put('/admin/ambang/{ambang}', [AdminAmbangPersediaanController::class, 'update'])
            ->name('admin.ambang.update');
    });
});

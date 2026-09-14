<?php

use App\Http\Controllers\AuthController;
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
    Route::get('/pendonor', [RoleHomeController::class, 'pendonor'])
        ->middleware('role:PENDONOR')
        ->name('pendonor.home');

    Route::get('/petugas', [RoleHomeController::class, 'petugas'])
        ->middleware('role:PETUGAS')
        ->name('petugas.home');

    Route::get('/admin', [RoleHomeController::class, 'admin'])
        ->middleware('role:ADMIN')
        ->name('admin.home');
});

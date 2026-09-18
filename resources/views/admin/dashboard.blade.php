@extends('layouts.app')

@section('title', 'Dashboard Admin | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Area Admin</p>
                <h1 class="page-title">Dashboard Admin</h1>
                <p class="page-description">Kelola akun Petugas dan konfigurasi yang diperlukan untuk pelayanan donor.</p>
            </div>
        </header>

        @include('partials.alerts')

        <section class="page-section" aria-labelledby="akun-admin">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="akun-admin">Akun Admin</h2>
                    <p class="section-description">Admin tidak memiliki profil terpisah.</p>
                </div>
            </div>
            <dl class="info-grid">
                <div class="info-block">
                    <dt>Email akun</dt>
                    <dd>{{ $email }}</dd>
                </div>
                <div class="info-block">
                    <dt>Peran</dt>
                    <dd>ADMIN</dd>
                </div>
            </dl>
        </section>

        {{-- Konfigurasi Admin --}}
        <section class="page-section" aria-labelledby="konfigurasi-admin">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="konfigurasi-admin">Konfigurasi Admin</h2>
                    <p class="section-description">Pilih data administrasi yang akan dikelola.</p>
                </div>
            </div>
            <div class="action-group">
                <a class="button button-primary" href="{{ route('admin.petugas.index') }}">Kelola Petugas</a>
                <a class="button button-secondary" href="{{ route('admin.jadwal.index') }}">Jadwal Pelayanan</a>
                <a class="button button-secondary" href="{{ route('admin.pertanyaan.index') }}">Pertanyaan Kuesioner</a>
                <a class="button button-secondary" href="{{ route('admin.ambang.index') }}">Ambang Persediaan</a>
            </div>
        </section>

        {{-- Ringkasan administrasi --}}
        <section class="page-section" aria-labelledby="ringkasan-administrasi">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="ringkasan-administrasi">Ringkasan Administrasi</h2>
                    <p class="section-description">Ringkasan dari data konfigurasi yang sudah tersedia.</p>
                </div>
            </div>
            <dl class="summary-grid">
                <div class="summary-block">
                    <dt class="summary-label">Petugas aktif</dt>
                    <dd>{{ $activePetugasCount }}</dd>
                </div>
                <div class="summary-block">
                    <dt class="summary-label">Jadwal dibuka untuk hari ini dan mendatang</dt>
                    <dd>{{ $openUpcomingScheduleCount }}</dd>
                </div>
                <div class="summary-block">
                    <dt class="summary-label">Pertanyaan kuesioner aktif</dt>
                    <dd>{{ $activeQuestionCount }}</dd>
                </div>
                <div class="summary-block">
                    <dt class="summary-label">Kombinasi ambang persediaan terkonfigurasi</dt>
                    <dd>{{ $configuredThresholdCount }} dari {{ $expectedThresholdCount }}</dd>
                </div>
                <div class="summary-block">
                    <dt class="summary-label">Kombinasi ambang persediaan belum dikonfigurasi</dt>
                    <dd>{{ $missingThresholdCount }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection

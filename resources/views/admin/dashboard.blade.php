@extends('layouts.app')

@section('title', 'Dashboard Admin | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Dashboard Admin</h1>
            </div>
        </header>

        @include('partials.alerts')

        <section class="page-section" aria-labelledby="akun-admin">
            <h2 class="section-title" id="akun-admin">Akun Admin</h2>
            <dl class="identity-panel identity-grid">
                <div class="identity-item">
                    <dt>Email akun</dt>
                    <dd>{{ $email }}</dd>
                </div>
                <div class="identity-item">
                    <dt>Peran</dt>
                    <dd>ADMIN</dd>
                </div>
            </dl>
        </section>

        {{-- Konfigurasi Admin --}}
        <section class="page-section" aria-labelledby="konfigurasi-admin">
            <h2 class="section-title" id="konfigurasi-admin">Konfigurasi Admin</h2>
            <div class="action-group admin-actions">
                <a class="button button-primary" href="{{ route('admin.petugas.index') }}">Kelola Petugas</a>
                <a class="button button-secondary" href="{{ route('admin.jadwal.index') }}">Jadwal Pelayanan</a>
                <a class="button button-secondary" href="{{ route('admin.pertanyaan.index') }}">Pertanyaan Kuesioner</a>
                <a class="button button-secondary" href="{{ route('admin.ambang.index') }}">Ambang Persediaan</a>
            </div>
        </section>

        {{-- Ringkasan administrasi --}}
        <section class="page-section" aria-labelledby="ringkasan-administrasi">
            <h2 class="section-title" id="ringkasan-administrasi">Ringkasan Administrasi</h2>
            <dl class="dashboard-summary admin-summary">
                <div class="dashboard-summary-item">
                    <dt class="summary-label">Petugas aktif</dt>
                    <dd class="summary-value">{{ $activePetugasCount }}</dd>
                </div>
                <div class="dashboard-summary-item">
                    <dt class="summary-label">Jadwal dibuka untuk hari ini dan mendatang</dt>
                    <dd class="summary-value">{{ $openUpcomingScheduleCount }}</dd>
                </div>
                <div class="dashboard-summary-item">
                    <dt class="summary-label">Pertanyaan kuesioner aktif</dt>
                    <dd class="summary-value">{{ $activeQuestionCount }}</dd>
                </div>
                <div class="dashboard-summary-item">
                    <dt class="summary-label">Kombinasi ambang persediaan terkonfigurasi</dt>
                    <dd class="summary-value">{{ $configuredThresholdCount }} dari {{ $expectedThresholdCount }}</dd>
                </div>
                <div class="dashboard-summary-item">
                    <dt class="summary-label">Kombinasi ambang persediaan belum dikonfigurasi</dt>
                    <dd class="summary-value">{{ $missingThresholdCount }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Donor Darah UDD')

@section('content')
    {{-- Hero --}}
    <section class="hero" aria-labelledby="hero-title">
        <div class="container hero-content">
            <div class="hero-copy">
                <p class="eyebrow">Sistem Informasi Donor Darah</p>
                <h1 id="hero-title">Sistem Informasi Donor dan Persediaan Darah</h1>
                <p class="hero-lead">
                    Aplikasi ini membantu pengelolaan proses donor pada satu UDD, mulai dari jadwal
                    dan pemesanan sampai pencatatan unit dan persediaan darah.
                </p>

                <div class="hero-actions">
                    @auth
                        @php
                            $homeRoute = match (auth()->user()->peran) {
                                'PENDONOR' => 'pendonor.home',
                                'PETUGAS' => 'petugas.home',
                                'ADMIN' => 'admin.home',
                                default => null,
                            };
                        @endphp

                        @if ($homeRoute !== null)
                            <a class="button button-primary" href="{{ route($homeRoute) }}">Buka Dashboard</a>
                        @endif
                    @else
                        <a class="button button-primary" href="{{ route('register') }}">Daftar sebagai Pendonor</a>
                        <a class="button button-secondary" href="{{ route('login') }}">Masuk</a>
                    @endauth
                </div>

                <p class="hero-note">Registrasi mandiri hanya untuk Pendonor.</p>
            </div>
        </div>
    </section>

    {{-- Alur donor --}}
    <section class="container process-section" aria-labelledby="process-title">
        <div class="section-heading">
            <p class="eyebrow">Alur Donor</p>
            <h2 id="process-title">Proses awal donor dalam sistem</h2>
        </div>

        <div class="process-grid">
            <article class="process-card">
                <span class="process-number">01</span>
                <h3 class="process-title">Pilih Jadwal</h3>
                <p class="process-description">Lihat jadwal pelayanan yang tersedia dan buat pemesanan donor.</p>
            </article>
            <article class="process-card">
                <span class="process-number">02</span>
                <h3 class="process-title">Kuesioner &amp; Check-in</h3>
                <p class="process-description">Isi kuesioner pradonasi dan gunakan kode check-in saat datang ke UDD.</p>
            </article>
            <article class="process-card">
                <span class="process-number">03</span>
                <h3 class="process-title">Proses Donor</h3>
                <p class="process-description">Petugas melanjutkan check-in, seleksi, penyumbangan, dan pencatatan unit darah.</p>
            </article>
        </div>
    </section>
@endsection

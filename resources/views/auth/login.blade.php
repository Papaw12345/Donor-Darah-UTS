@extends('layouts.app')

@section('title', 'Masuk | Donor Darah UDD')
@section('main-class', 'auth-main')

@section('content')
    <div class="auth-shell">
        {{-- Login intro --}}
        <section class="auth-intro" aria-labelledby="login-intro-title">
            <div>
                <p class="eyebrow">Login</p>
                <h1 id="login-intro-title">Masuk ke Sistem</h1>
                <p>Gunakan email dan password akun Anda.</p>
            </div>
            <p class="auth-intro-note">Menu yang tersedia mengikuti peran akun.</p>
        </section>

        {{-- Login form --}}
        <section class="auth-panel" aria-labelledby="login-form-title">
            <header class="auth-panel-header">
                <h2 id="login-form-title">Login</h2>
                <p>Masukkan data akun yang terdaftar.</p>
            </header>

            @include('partials.alerts')

            <form class="form-stack" method="POST" action="{{ route('login.authenticate') }}">
                @csrf

                <div class="form-field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="form-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button class="button button-primary button-full" type="submit">Masuk</button>
                </div>
            </form>

            <p class="auth-switch">
                Belum memiliki akun?
                <a href="{{ route('register') }}">Daftar sebagai Pendonor</a>
            </p>
        </section>
    </div>
@endsection

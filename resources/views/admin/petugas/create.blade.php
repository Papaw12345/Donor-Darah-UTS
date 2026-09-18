@extends('layouts.app')

@section('title', 'Tambah Petugas | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Administrasi Akun</p>
                <h1 class="page-title">Tambah Petugas</h1>
                <p class="page-description">Buat akun dan profil Petugas UDD.</p>
            </div>
            <a class="button button-secondary" href="{{ route('admin.petugas.index') }}">Batal</a>
        </header>

        @include('partials.alerts')

        <section class="page-section" aria-labelledby="form-petugas">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="form-petugas">Data Petugas</h2>
                    <p class="section-description">Seluruh field berikut diperlukan untuk membuat akun Petugas.</p>
                </div>
            </div>

            <form class="form-panel" method="POST" action="{{ route('admin.petugas.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field form-field-full">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                    <div class="form-field">
                        <label for="nomor_petugas">Nomor Petugas</label>
                        <input id="nomor_petugas" type="text" name="nomor_petugas" value="{{ old('nomor_petugas') }}" maxlength="50" required>
                    </div>
                    <div class="form-field">
                        <label for="nama_petugas">Nama Petugas</label>
                        <input id="nama_petugas" type="text" name="nama_petugas" value="{{ old('nama_petugas') }}" maxlength="150" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Simpan Petugas</button>
                    <a class="button button-secondary" href="{{ route('admin.petugas.index') }}">Batal</a>
                </div>
            </form>
        </section>
    </div>
@endsection

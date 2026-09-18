@extends('layouts.app')

@section('title', 'Edit Petugas | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Administrasi Akun</p>
                <h1 class="page-title">Edit Petugas</h1>
                <p class="page-description">Perbarui email dan data profil Petugas.</p>
            </div>
            <a class="button button-secondary" href="{{ route('admin.petugas.index') }}">Batal</a>
        </header>

        @include('partials.alerts')

        <dl class="context-strip">
            <div>
                <dt>Nomor Petugas</dt>
                <dd>{{ $petugas->nomor_petugas }}</dd>
            </div>
            <div>
                <dt>Nama Petugas</dt>
                <dd>{{ $petugas->nama_petugas }}</dd>
            </div>
            <div>
                <dt>Status akun</dt>
                <dd>
                    <span class="status-badge {{ $akun->status_akun === 'AKTIF' ? 'status-success' : 'status-neutral' }}">
                        {{ $akun->status_akun }}
                    </span>
                </dd>
            </div>
        </dl>

        <section class="page-section" aria-labelledby="form-petugas">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="form-petugas">Data Petugas</h2>
                    <p class="section-description">Status akun dikelola dari daftar Petugas.</p>
                </div>
            </div>

            <form class="form-panel" method="POST" action="{{ route('admin.petugas.update', $petugas) }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field form-field-full">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $akun->email) }}" required>
                    </div>
                    <div class="form-field">
                        <label for="nomor_petugas">Nomor Petugas</label>
                        <input id="nomor_petugas" type="text" name="nomor_petugas" value="{{ old('nomor_petugas', $petugas->nomor_petugas) }}" maxlength="50" required>
                    </div>
                    <div class="form-field">
                        <label for="nama_petugas">Nama Petugas</label>
                        <input id="nama_petugas" type="text" name="nama_petugas" value="{{ old('nama_petugas', $petugas->nama_petugas) }}" maxlength="150" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Perbarui Petugas</button>
                    <a class="button button-secondary" href="{{ route('admin.petugas.index') }}">Batal</a>
                </div>
            </form>
        </section>
    </div>
@endsection

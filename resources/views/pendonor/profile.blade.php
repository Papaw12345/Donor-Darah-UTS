@extends('layouts.app')

@section('title', 'Profil Saya | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Profil Saya</h1>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </header>

        @include('partials.alerts')

        <section class="page-section identity-panel" aria-labelledby="data-tetap">
            <div class="section-header"><div><h2 class="section-title" id="data-tetap">Data Identitas</h2><p class="section-description">Data identitas tidak dapat diubah.</p></div></div>
            <dl class="identity-grid">
                <div class="identity-item"><dt>Email akun</dt><dd>{{ $akun->email }}</dd></div>
                <div class="identity-item"><dt>NIK</dt><dd>{{ $pendonor->nik }}</dd></div>
                <div class="identity-item"><dt>Nomor donor</dt><dd>{{ $pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
                <div class="identity-item"><dt>Jenis kelamin</dt><dd>{{ $pendonor->jenis_kelamin === 'LAKI_LAKI' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                <div class="identity-item"><dt>Tanggal lahir</dt><dd>{{ $pendonor->tanggal_lahir->format('d-m-Y') }}</dd></div>
                <div class="identity-item">
                    <dt>Golongan darah</dt>
                    <dd>
                        @if ($pendonor->golonganDarah)
                            {{ $pendonor->golonganDarah->abo }} {{ $pendonor->golonganDarah->rhesus === 'POSITIF' ? '+' : '-' }}
                        @else
                            Belum dikonfirmasi UDD
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="page-section" aria-labelledby="data-dapat-diubah">
            <div class="section-header"><h2 class="section-title" id="data-dapat-diubah">Data yang Dapat Diperbarui</h2></div>
            <form class="form-panel" method="POST" action="{{ route('pendonor.profil.update') }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input id="nama_lengkap" type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $pendonor->nama_lengkap) }}" maxlength="150" required>
                    </div>
                    <div class="form-field">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input id="tempat_lahir" type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $pendonor->tempat_lahir) }}" maxlength="100" required>
                    </div>
                    <div class="form-field form-field-full">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" required>{{ old('alamat', $pendonor->alamat) }}</textarea>
                    </div>
                    <div class="form-field">
                        <label for="nomor_telepon">Nomor Telepon</label>
                        <input id="nomor_telepon" type="text" name="nomor_telepon" value="{{ old('nomor_telepon', $pendonor->nomor_telepon) }}" maxlength="20" required>
                    </div>
                    <div class="form-field">
                        <label for="pekerjaan">Pekerjaan <span class="form-hint">(opsional)</span></label>
                        <input id="pekerjaan" type="text" name="pekerjaan" value="{{ old('pekerjaan', $pendonor->pekerjaan) }}" maxlength="100">
                    </div>
                    <div class="form-field form-field-full">
                        <label for="alamat_kantor">Alamat Kantor <span class="form-hint">(opsional)</span></label>
                        <textarea id="alamat_kantor" name="alamat_kantor">{{ old('alamat_kantor', $pendonor->alamat_kantor) }}</textarea>
                    </div>
                </div>
                <div class="form-actions"><button class="button button-primary" type="submit">Simpan Perubahan</button></div>
            </form>
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Registrasi Pendonor | Donor Darah UDD')
@section('main-class', 'auth-main')

@section('content')
    <div class="auth-shell auth-shell-register">
        <section class="auth-intro" aria-labelledby="register-intro-title">
            <div>
                <p class="eyebrow">Registrasi Pendonor</p>
                <h1 id="register-intro-title">Daftar sebagai Pendonor</h1>
                <p>Isi data akun dan identitas untuk membuat akun Pendonor.</p>
            </div>
            <p class="auth-intro-note">Golongan darah tidak diisi saat registrasi. Data tersebut dapat dicatat saat proses pelayanan UDD.</p>
        </section>

        <section class="auth-panel" aria-labelledby="register-form-title">
            <header class="auth-panel-header">
                <h2 id="register-form-title">Data Registrasi</h2>
                <p>Lengkapi data yang diperlukan.</p>
            </header>

            @include('partials.alerts')

            <p class="required-note"><span class="required-mark">*</span> Wajib diisi</p>

            <form method="POST" action="{{ route('register.store') }}">
                @csrf

                {{-- Informasi akun --}}
                <fieldset class="form-section">
                    <legend>Informasi akun</legend>
                    <div class="form-grid">
                        <div class="form-field form-field-full">
                            <label for="email">Email <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" required autofocus autocomplete="email">
                        </div>

                        <div class="form-field">
                            <label for="password">Password <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="password" type="password" name="password" minlength="8" maxlength="255" required autocomplete="new-password" aria-describedby="password-hint">
                            <p class="form-hint" id="password-hint">Gunakan minimal 8 karakter.</p>
                        </div>

                        <div class="form-field">
                            <label for="password_confirmation">Konfirmasi Password <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" maxlength="255" required autocomplete="new-password">
                        </div>
                    </div>
                </fieldset>

                {{-- Identitas pendonor --}}
                <fieldset class="form-section">
                    <legend>Identitas pendonor</legend>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="nik">NIK <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="nik" type="text" name="nik" value="{{ old('nik') }}" maxlength="20" required inputmode="numeric">
                        </div>

                        <div class="form-field">
                            <label for="nomor_donor">Nomor Donor <span class="form-hint">(opsional)</span></label>
                            <input id="nomor_donor" type="text" name="nomor_donor" value="{{ old('nomor_donor') }}" maxlength="50">
                        </div>

                        <div class="form-field form-field-full">
                            <label for="nama_lengkap">Nama Lengkap <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="nama_lengkap" type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" maxlength="150" required autocomplete="name">
                        </div>

                        <div class="form-field">
                            <label for="jenis_kelamin">Jenis Kelamin <span class="required-mark" aria-hidden="true">*</span></label>
                            <select id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih jenis kelamin</option>
                                <option value="LAKI_LAKI" @selected(old('jenis_kelamin') === 'LAKI_LAKI')>Laki-laki</option>
                                <option value="PEREMPUAN" @selected(old('jenis_kelamin') === 'PEREMPUAN')>Perempuan</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="tanggal_lahir">Tanggal Lahir <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required>
                        </div>

                        <div class="form-field form-field-full">
                            <label for="tempat_lahir">Tempat Lahir <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="tempat_lahir" type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}" maxlength="100" required>
                        </div>
                    </div>
                </fieldset>

                {{-- Kontak dan pekerjaan --}}
                <fieldset class="form-section">
                    <legend>Kontak dan pekerjaan</legend>
                    <div class="form-grid">
                        <div class="form-field form-field-full">
                            <label for="alamat">Alamat <span class="required-mark" aria-hidden="true">*</span></label>
                            <textarea id="alamat" name="alamat" required autocomplete="street-address">{{ old('alamat') }}</textarea>
                        </div>

                        <div class="form-field">
                            <label for="nomor_telepon">Nomor Telepon <span class="required-mark" aria-hidden="true">*</span></label>
                            <input id="nomor_telepon" type="tel" name="nomor_telepon" value="{{ old('nomor_telepon') }}" maxlength="20" required autocomplete="tel">
                        </div>

                        <div class="form-field">
                            <label for="pekerjaan">Pekerjaan <span class="form-hint">(opsional)</span></label>
                            <input id="pekerjaan" type="text" name="pekerjaan" value="{{ old('pekerjaan') }}" maxlength="100" autocomplete="organization-title">
                        </div>

                        <div class="form-field form-field-full">
                            <label for="alamat_kantor">Alamat Kantor <span class="form-hint">(opsional)</span></label>
                            <textarea id="alamat_kantor" name="alamat_kantor">{{ old('alamat_kantor') }}</textarea>
                        </div>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Daftar sebagai Pendonor</button>
                    <a class="button button-ghost" href="{{ route('login') }}">Sudah punya akun</a>
                </div>
            </form>
        </section>
    </div>
@endsection

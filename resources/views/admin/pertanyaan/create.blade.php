@extends('layouts.app')

@section('title', 'Tambah Pertanyaan Kuesioner | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Konfigurasi Kuesioner</p>
                <h1 class="page-title">Tambah Pertanyaan Kuesioner</h1>
                <p class="page-description">Tambahkan pertanyaan yang dapat digunakan dalam kuesioner pradonasi.</p>
            </div>
            <a class="button button-secondary" href="{{ route('admin.pertanyaan.index') }}">Batal</a>
        </header>

        @include('partials.alerts')

        <section class="page-section" aria-labelledby="form-pertanyaan">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="form-pertanyaan">Data Pertanyaan</h2>
                    <p class="section-description">Kategori bersifat opsional dan membantu pengelompokan tampilan.</p>
                </div>
            </div>

            <form class="form-panel" method="POST" action="{{ route('admin.pertanyaan.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field form-field-full">
                        <label for="teks_pertanyaan">Pertanyaan</label>
                        <textarea id="teks_pertanyaan" name="teks_pertanyaan" required>{{ old('teks_pertanyaan') }}</textarea>
                    </div>
                    <div class="form-field">
                        <label for="kategori">Kategori <span class="form-hint">(opsional)</span></label>
                        <input id="kategori" type="text" name="kategori" value="{{ old('kategori') }}" maxlength="100">
                    </div>
                    <div class="form-field">
                        <label for="jenis_jawaban">Jenis Jawaban</label>
                        <select id="jenis_jawaban" name="jenis_jawaban" required>
                            @foreach (['YA_TIDAK', 'TEKS'] as $jenis)
                                <option value="{{ $jenis }}" @selected(old('jenis_jawaban') === $jenis)>{{ $jenis }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="urutan">Urutan</label>
                        <input id="urutan" type="number" name="urutan" value="{{ old('urutan') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="status_aktif">Status</label>
                        <select id="status_aktif" name="status_aktif" required>
                            <option value="1" @selected((string) old('status_aktif', '1') === '1')>Aktif</option>
                            <option value="0" @selected((string) old('status_aktif', '1') === '0')>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Simpan Pertanyaan</button>
                    <a class="button button-secondary" href="{{ route('admin.pertanyaan.index') }}">Batal</a>
                </div>
            </form>
        </section>
    </div>
@endsection

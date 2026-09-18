@extends('layouts.app')

@section('title', 'Edit Ambang Persediaan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Konfigurasi Persediaan</p>
                <h1 class="page-title">Edit Ambang Persediaan</h1>
                <p class="page-description">Perbarui jumlah minimum untuk kombinasi yang sudah ditetapkan.</p>
            </div>
            <a class="button button-secondary" href="{{ route('admin.ambang.index') }}">Batal</a>
        </header>

        @include('partials.alerts')

        <dl class="context-strip">
            <div>
                <dt>Jenis Komponen</dt>
                <dd>{{ $ambang->jenisKomponenDarah->kode_komponen }} - {{ $ambang->jenisKomponenDarah->nama_komponen }}</dd>
            </div>
            <div>
                <dt>Golongan Darah</dt>
                <dd>{{ $ambang->golonganDarah->abo }} {{ ucfirst(strtolower($ambang->golonganDarah->rhesus)) }}</dd>
            </div>
            <div>
                <dt>Jumlah Minimum Saat Ini</dt>
                <dd>{{ $ambang->jumlah_minimum }}</dd>
            </div>
        </dl>

        <section class="page-section" aria-labelledby="form-ambang">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="form-ambang">Jumlah Minimum</h2>
                    <p class="section-description">Jenis komponen dan golongan darah tidak diubah dari halaman ini.</p>
                </div>
            </div>

            <form class="form-panel" method="POST" action="{{ route('admin.ambang.update', $ambang) }}">
                @csrf
                @method('PUT')

                <div class="form-field">
                    <label for="jumlah_minimum">Jumlah Minimum</label>
                    <input id="jumlah_minimum" type="number" name="jumlah_minimum" value="{{ old('jumlah_minimum', $ambang->jumlah_minimum) }}" min="0" required>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Perbarui Ambang</button>
                    <a class="button button-secondary" href="{{ route('admin.ambang.index') }}">Batal</a>
                </div>
            </form>
        </section>
    </div>
@endsection

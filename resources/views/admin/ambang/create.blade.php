@extends('layouts.app')

@section('title', 'Tambah Ambang Persediaan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Tambah Ambang Persediaan</h1>
            </div>
            <a class="button button-secondary" href="{{ route('admin.ambang.index') }}">Batal</a>
        </header>

        @include('partials.alerts')

        <form class="form-panel page-section" method="POST" action="{{ route('admin.ambang.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label for="id_jenis_komponen">Jenis Komponen</label>
                        <select id="id_jenis_komponen" name="id_jenis_komponen" required>
                            <option value="">Pilih jenis komponen</option>
                            @foreach ($jenisKomponen as $jenis)
                                <option value="{{ $jenis->id_jenis_komponen }}" @selected((string) old('id_jenis_komponen') === (string) $jenis->id_jenis_komponen)>
                                    {{ $jenis->kode_komponen }} - {{ $jenis->nama_komponen }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="id_golongan_darah">Golongan Darah</label>
                        <select id="id_golongan_darah" name="id_golongan_darah" required>
                            <option value="">Pilih golongan darah</option>
                            @foreach ($golonganDarah as $golongan)
                                <option value="{{ $golongan->id_golongan_darah }}" @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)>
                                    {{ $golongan->abo }} {{ ucfirst(strtolower($golongan->rhesus)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="jumlah_minimum">Jumlah Minimum</label>
                        <input id="jumlah_minimum" type="number" name="jumlah_minimum" value="{{ old('jumlah_minimum') }}" min="0" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Simpan Ambang</button>
                </div>
        </form>
    </div>
@endsection

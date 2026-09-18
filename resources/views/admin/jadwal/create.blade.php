@extends('layouts.app')

@section('title', 'Tambah Jadwal Pelayanan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Tambah Jadwal Pelayanan</h1>
            </div>
            <a class="button button-secondary" href="{{ route('admin.jadwal.index') }}">Batal</a>
        </header>

        @include('partials.alerts')

        <form class="form-panel page-section" method="POST" action="{{ route('admin.jadwal.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label for="tanggal">Tanggal</label>
                        <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="kapasitas">Kapasitas</label>
                        <input id="kapasitas" type="number" name="kapasitas" value="{{ old('kapasitas') }}" min="1" required>
                    </div>
                    <div class="form-field">
                        <label for="jam_mulai">Jam Mulai</label>
                        <input id="jam_mulai" type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="jam_selesai">Jam Selesai</label>
                        <input id="jam_selesai" type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="status_jadwal">Status</label>
                        <select id="status_jadwal" name="status_jadwal" required>
                            @foreach (['DIBUKA', 'DITUTUP', 'DIBATALKAN'] as $status)
                                <option value="{{ $status }}" @selected(old('status_jadwal', 'DIBUKA') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Simpan Jadwal</button>
                </div>
        </form>
    </div>
@endsection

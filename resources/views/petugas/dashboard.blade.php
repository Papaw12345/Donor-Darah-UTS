@extends('layouts.app')

@section('title', 'Dashboard Petugas | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Area Petugas</p>
                <h1 class="page-title">Dashboard Petugas</h1>
                <p class="page-description">Ringkasan kegiatan donor dan kondisi persediaan pada tanggal operasional.</p>
            </div>
        </header>

        @include('partials.alerts')

        {{-- Identitas dan operasional --}}
        <section class="page-section" aria-labelledby="identitas-petugas">
            <div class="section-header"><div><h2 class="section-title" id="identitas-petugas">Identitas Petugas</h2><p class="section-description">Akun Petugas yang sedang digunakan.</p></div></div>
            <dl class="info-grid">
                <div class="info-block"><dt>Nama petugas</dt><dd>{{ $petugas->nama_petugas }}</dd></div>
                <div class="info-block"><dt>Nomor petugas</dt><dd>{{ $petugas->nomor_petugas }}</dd></div>
                <div class="info-block"><dt>Email akun</dt><dd>{{ $akun->email }}</dd></div>
            </dl>
        </section>

        <section class="page-section" aria-labelledby="operasional-petugas">
            <div class="section-header"><div><h2 class="section-title" id="operasional-petugas">Operasional Petugas</h2><p class="section-description">Pilih proses operasional yang akan dikerjakan.</p></div></div>
            <nav class="action-group" aria-label="Operasional Petugas">
                <a class="button button-primary" href="{{ route('petugas.check-in.index') }}">Check-in Pendonor</a>
                <a class="button button-secondary" href="{{ route('petugas.pelulusan.index') }}">Pelulusan</a>
                <a class="button button-secondary" href="{{ route('petugas.distribusi.index') }}">Distribusi</a>
                <a class="button button-secondary" href="{{ route('petugas.persediaan.index') }}">Persediaan</a>
                <a class="button button-secondary" href="{{ route('petugas.persediaan-rendah.index') }}">Persediaan Rendah</a>
                <a class="button button-secondary" href="{{ route('petugas.pemanggilan.index') }}">Pemanggilan Pendonor</a>
            </nav>
        </section>

        {{-- Ringkasan operasional --}}
        <section class="page-section" aria-labelledby="kegiatan-donor-hari-ini">
            <div class="section-header"><div><h2 class="section-title" id="kegiatan-donor-hari-ini">Kegiatan Donor Hari Ini</h2><p class="section-description">Tanggal operasional WIB: {{ $tanggalAcuan }}</p></div></div>
            <dl class="summary-grid">
                @foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'TIDAK_HADIR'] as $status)
                    <div class="summary-block"><dt class="summary-label">{{ $status }}</dt><dd>{{ $kegiatanHariIni[$status] }}</dd></div>
                @endforeach
                <div class="summary-block"><dt class="summary-label">Pendonor Sedang Diproses</dt><dd>{{ $jumlahPendonorDiproses }}</dd></div>
                <div class="summary-block"><dt class="summary-label">Kondisi Persediaan</dt><dd>Total unit tersedia: {{ $totalPersediaanTersedia }}</dd></div>
            </dl>
        </section>

        {{-- Persediaan rendah --}}
        <section class="page-section" aria-labelledby="persediaan-rendah">
            <div class="section-header"><div><h2 class="section-title" id="persediaan-rendah">Persediaan Rendah</h2><p class="section-description">Jumlah kombinasi persediaan rendah: {{ $persediaanRendah->count() }}</p></div></div>
            @if ($jumlahAmbangPersediaan === 0)
                <div class="empty-state">Belum ada konfigurasi ambang persediaan.</div>
            @elseif ($persediaanRendah->isEmpty())
                <div class="empty-state">Tidak ada persediaan yang berada pada atau di bawah ambang.</div>
            @else
                <div class="table-container">
                    <table class="data-table table-compact">
                        <thead><tr><th scope="col">Komponen</th><th scope="col">ABO</th><th scope="col">Rhesus</th><th scope="col">Jumlah Persediaan</th><th scope="col">Jumlah Minimum</th></tr></thead>
                        <tbody>@foreach ($persediaanRendah as $item)<tr><td>{{ $item->kode_komponen }} - {{ $item->nama_komponen }}</td><td>{{ $item->abo }}</td><td>{{ $item->rhesus }}</td><td>{{ $item->jumlah_persediaan }}</td><td>{{ $item->jumlah_minimum }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

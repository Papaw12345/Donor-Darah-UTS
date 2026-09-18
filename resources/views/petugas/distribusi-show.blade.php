@extends('layouts.app')

@section('title', 'Detail Distribusi | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pengelolaan Unit</p><h1 class="page-title">Distribusi Unit</h1><p class="page-description">Periksa data unit sebelum mencatat distribusi.</p></div><a class="button button-secondary" href="{{ route('petugas.distribusi.index') }}">Kembali ke Distribusi</a></header>
        @include('partials.alerts')
        <section class="page-section" aria-labelledby="detail-distribusi"><div class="section-header"><div><h2 class="section-title" id="detail-distribusi">Detail Unit</h2><p class="section-description">Tanggal acuan WIB: {{ $tanggalAcuan }}</p></div></div><dl class="info-grid">
            <div class="info-block"><dt>Nomor Unit</dt><dd>{{ $unit->nomor_unit }}</dd></div><div class="info-block"><dt>Jenis Komponen</dt><dd>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</dd></div>
            <div class="info-block"><dt>Golongan Darah</dt><dd>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</dd></div><div class="info-block"><dt>Tanggal Pembuatan</dt><dd>{{ $unit->tanggal_pembuatan->toDateString() }}</dd></div>
            <div class="info-block"><dt>Tanggal Kedaluwarsa</dt><dd>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</dd></div><div class="info-block"><dt>Status</dt><dd><span class="status-badge {{ $unit->status_unit === 'TERSEDIA' ? 'status-success' : 'status-neutral' }}">{{ $unit->status_unit }}</span></dd></div>
            @if ($unit->waktu_distribusi)<div class="info-block"><dt>Waktu Distribusi</dt><dd>{{ $unit->waktu_distribusi }}</dd></div>@endif
        </dl></section>
        @if ($eligible)
            <form class="action-panel" method="POST" action="{{ route('petugas.distribusi.store', $unit) }}">@csrf<p class="section-description">Unit ini masih tersedia dan belum kedaluwarsa pada tanggal acuan WIB {{ $tanggalAcuan }}.</p><button class="button button-primary" type="submit">Distribusikan Unit</button></form>
        @else
            <div class="notice-panel"><strong>Riwayat distribusi hanya-baca.</strong><p>Unit ini tidak dapat didistribusikan dari status dan tanggal saat ini.</p></div>
        @endif
    </div>
@endsection

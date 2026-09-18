@extends('layouts.app')

@section('title', 'Detail Pelulusan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pengelolaan Unit</p><h1 class="page-title">Pelulusan Unit</h1><p class="page-description">Periksa data unit sebelum mencatat hasil pelulusan.</p></div><a class="button button-secondary" href="{{ route('petugas.pelulusan.index') }}">Kembali ke Pelulusan</a></header>
        @include('partials.alerts')
        <section class="page-section" aria-labelledby="detail-unit"><div class="section-header"><div><h2 class="section-title" id="detail-unit">Detail Unit</h2><p class="section-description">Data unit dan riwayat pelulusannya.</p></div></div><dl class="info-grid">
            <div class="info-block"><dt>Nomor Unit</dt><dd>{{ $unit->nomor_unit }}</dd></div><div class="info-block"><dt>Jenis</dt><dd>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</dd></div>
            <div class="info-block"><dt>Golongan</dt><dd>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</dd></div><div class="info-block"><dt>Tanggal Pembuatan</dt><dd>{{ $unit->tanggal_pembuatan->toDateString() }}</dd></div>
            <div class="info-block"><dt>Tanggal Kedaluwarsa</dt><dd>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</dd></div><div class="info-block"><dt>Petugas Pencatat</dt><dd>{{ $unit->petugasPencatat->nama_petugas }}</dd></div>
            <div class="info-block"><dt>Status</dt><dd><span class="status-badge {{ $unit->status_unit === 'TERSEDIA' ? 'status-success' : ($unit->status_unit === 'DITOLAK' ? 'status-danger' : 'status-warning') }}">{{ $unit->status_unit }}</span></dd></div>
            @if ($unit->waktu_pelulusan)<div class="info-block"><dt>Petugas Pelulus</dt><dd>{{ $unit->petugasPelulus?->nama_petugas ?? 'Tidak tersedia' }}</dd></div><div class="info-block"><dt>Waktu Pelulusan</dt><dd>{{ $unit->waktu_pelulusan }}</dd></div><div class="info-block"><dt>Catatan Pelulusan</dt><dd>{{ $unit->catatan_pelulusan ?? '-' }}</dd></div>@endif
        </dl></section>
        @if ($unit->status_unit === 'MENUNGGU_PELULUSAN')
            <section class="page-section" aria-labelledby="form-pelulusan"><div class="section-header"><div><h2 class="section-title" id="form-pelulusan">Form Pelulusan</h2><p class="section-description">Hasil hanya dapat disimpan satu kali.</p></div></div><form class="form-panel" method="POST" action="{{ route('petugas.pelulusan.store', $unit) }}">@csrf<div class="form-grid">
                <div class="form-field"><label for="hasil_pelulusan">Hasil</label><select id="hasil_pelulusan" name="hasil_pelulusan" required><option value="">Pilih</option><option value="TERSEDIA" @selected(old('hasil_pelulusan') === 'TERSEDIA')>TERSEDIA</option><option value="DITOLAK" @selected(old('hasil_pelulusan') === 'DITOLAK')>DITOLAK</option></select></div>
                <div class="form-field"><label for="catatan_pelulusan">Catatan</label><textarea id="catatan_pelulusan" name="catatan_pelulusan">{{ old('catatan_pelulusan') }}</textarea></div>
            </div><div class="form-actions"><button class="button button-primary" type="submit">Simpan Pelulusan</button></div></form></section>
        @else
            <div class="notice-panel"><strong>Riwayat pelulusan hanya-baca.</strong><p>Unit ini sudah memiliki hasil pelulusan dan tidak dapat diproses ulang.</p></div>
        @endif
    </div>
@endsection

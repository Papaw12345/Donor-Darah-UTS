@extends('layouts.app')

@section('title', 'Buat Pemberitahuan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pemanggilan Pendonor</p><h1 class="page-title">Buat Pemberitahuan</h1><p class="page-description">Kirim satu pemberitahuan internal kepada Pendonor yang dipilih.</p></div><a class="button button-secondary" href="{{ route('petugas.pemanggilan.index', ['id_ambang' => $ambang->id_ambang]) }}">Kembali ke Kandidat Pendonor</a></header>
        @include('partials.alerts')
        <section class="page-section" aria-labelledby="konteks-persediaan"><div class="section-header"><div><h2 class="section-title" id="konteks-persediaan">Kondisi Persediaan Rendah</h2><p class="section-description">Petugas: {{ $petugas->nama_petugas }}</p></div></div><dl class="info-grid">
            <div class="info-block"><dt>Komponen</dt><dd>{{ $ambang->jenisKomponenDarah->kode_komponen }} - {{ $ambang->jenisKomponenDarah->nama_komponen }}</dd></div><div class="info-block"><dt>Golongan darah</dt><dd>{{ $ambang->golonganDarah->abo }} {{ $ambang->golonganDarah->rhesus }}</dd></div>
            <div class="info-block"><dt>Persediaan</dt><dd>Jumlah persediaan saat ini: {{ $jumlahPersediaan }}</dd></div><div class="info-block"><dt>Ambang</dt><dd>Jumlah minimum: {{ $ambang->jumlah_minimum }}</dd></div>
        </dl></section>
        <section class="page-section" aria-labelledby="target-pendonor"><div class="section-header"><div><h2 class="section-title" id="target-pendonor">Target Pendonor</h2><p class="section-description">Pemberitahuan hanya dikirim ke akun Pendonor berikut.</p></div></div><dl class="info-grid">
            <div class="info-block"><dt>Nomor donor</dt><dd>{{ $pendonor->nomor_donor }}</dd></div><div class="info-block"><dt>Nama lengkap</dt><dd>{{ $pendonor->nama_lengkap }}</dd></div><div class="info-block"><dt>Golongan darah</dt><dd>{{ $pendonor->golonganDarah->abo }} {{ $pendonor->golonganDarah->rhesus }}</dd></div>
        </dl></section>
        <section class="page-section" aria-labelledby="form-pemberitahuan"><div class="section-header"><div><h2 class="section-title" id="form-pemberitahuan">Isi Pemberitahuan</h2><p class="section-description">Pesan akan tersedia di dalam aplikasi Pendonor.</p></div></div><form class="form-panel" method="POST" action="{{ route('petugas.pemberitahuan.store', ['ambang' => $ambang, 'pendonor' => $pendonor]) }}">@csrf<div class="form-field"><label for="isi_pesan">Isi Pesan</label><textarea id="isi_pesan" name="isi_pesan">{{ old('isi_pesan') }}</textarea>@error('isi_pesan')<p class="form-hint" role="alert">{{ $message }}</p>@enderror</div><div class="form-actions"><button class="button button-primary" type="submit">Kirim Pemberitahuan</button></div></form></section>
    </div>
@endsection

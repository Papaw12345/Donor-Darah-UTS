@extends('layouts.app')

@section('title', 'Unit Komponen Darah | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pencatatan Unit</p><h1 class="page-title">Unit Komponen Darah</h1><p class="page-description">Catat unit komponen yang berasal dari penyumbangan berhasil.</p></div><a class="button button-secondary" href="{{ route('petugas.penyumbangan.show', $penyumbangan->seleksiDonor) }}">Kembali ke Penyumbangan</a></header>
        @include('partials.alerts')

        {{-- Konteks penyumbangan --}}
        <section class="page-section" aria-labelledby="konteks-penyumbangan"><div class="section-header"><div><h2 class="section-title" id="konteks-penyumbangan">Konteks Penyumbangan</h2><p class="section-description">Petugas login: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p></div></div><dl class="info-grid">
            <div class="info-block"><dt>ID Penyumbangan</dt><dd>{{ $penyumbangan->id_penyumbangan }}</dd></div><div class="info-block"><dt>Hasil Penyumbangan</dt><dd><span class="status-badge status-success">{{ $penyumbangan->hasil_penyumbangan }}</span></dd></div>
            <div class="info-block"><dt>Waktu Pengambilan</dt><dd>{{ $penyumbangan->waktu_pengambilan->format('Y-m-d H:i:s') }}</dd></div><div class="info-block"><dt>Nama Pendonor</dt><dd>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nama_lengkap }}</dd></div>
            <div class="info-block"><dt>Nomor Donor</dt><dd>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
        </dl></section>

        {{-- Unit komponen --}}
        <section class="page-section" aria-labelledby="unit-tersimpan"><div class="section-header"><div><h2 class="section-title" id="unit-tersimpan">Unit Tersimpan</h2><p class="section-description">Seluruh unit yang sudah dicatat dari penyumbangan ini.</p></div></div>
            @if ($penyumbangan->unitKomponenDarah->isEmpty())<div class="empty-state">Belum ada unit komponen darah.</div>@else<div class="table-container"><table class="data-table"><thead><tr><th scope="col">Nomor Unit</th><th scope="col">Jenis Komponen</th><th scope="col">Golongan Darah</th><th scope="col">Tanggal Pembuatan</th><th scope="col">Tanggal Kedaluwarsa</th><th scope="col">Status</th><th scope="col">Petugas Pencatat</th></tr></thead><tbody>
                @foreach ($penyumbangan->unitKomponenDarah as $unit)<tr><td>{{ $unit->nomor_unit }}</td><td>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</td><td>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</td><td>{{ $unit->tanggal_pembuatan->toDateString() }}</td><td>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</td><td><span class="status-badge status-warning">{{ $unit->status_unit }}</span></td><td>{{ $unit->petugasPencatat->nama_petugas }} ({{ $unit->petugasPencatat->nomor_petugas }})</td></tr>@endforeach
            </tbody></table></div>@endif
        </section>

        <section class="page-section" aria-labelledby="form-unit"><div class="section-header"><div><h2 class="section-title" id="form-unit">Form Unit Komponen</h2><p class="section-description">Unit baru disimpan dengan status awal MENUNGGU_PELULUSAN.</p></div></div>
            <form class="form-panel" method="POST" action="{{ route('petugas.unit-komponen.store', $penyumbangan) }}">@csrf<div class="form-grid">
                <div class="form-field"><label for="nomor_unit">Nomor unit</label><input id="nomor_unit" name="nomor_unit" type="text" value="{{ old('nomor_unit') }}" required></div>
                <div class="form-field"><label for="id_jenis_komponen">Jenis komponen</label><select id="id_jenis_komponen" name="id_jenis_komponen" required><option value="">Pilih jenis</option>@foreach ($jenisKomponen as $jenis)<option value="{{ $jenis->id_jenis_komponen }}" @selected((string) old('id_jenis_komponen') === (string) $jenis->id_jenis_komponen)>{{ $jenis->kode_komponen }} - {{ $jenis->nama_komponen }}</option>@endforeach</select></div>
                <div class="form-field"><label for="id_golongan_darah">Golongan darah</label><select id="id_golongan_darah" name="id_golongan_darah" required><option value="">Pilih golongan</option>@foreach ($golonganDarah as $golongan)<option value="{{ $golongan->id_golongan_darah }}" @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)>{{ $golongan->abo }} {{ $golongan->rhesus }}</option>@endforeach</select></div>
                <div class="form-field"><label for="tanggal_pembuatan">Tanggal pembuatan</label><input id="tanggal_pembuatan" name="tanggal_pembuatan" type="date" value="{{ old('tanggal_pembuatan') }}" required></div>
                <div class="form-field"><label for="tanggal_kedaluwarsa">Tanggal kedaluwarsa</label><input id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa" type="date" value="{{ old('tanggal_kedaluwarsa') }}" required></div>
            </div><div class="form-actions"><button class="button button-primary" type="submit">Simpan Unit Komponen</button></div></form>
        </section>
    </div>
@endsection

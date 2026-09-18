@extends('layouts.app')

@section('title', 'Penyumbangan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pelayanan Donor</p><h1 class="page-title">Penyumbangan</h1><p class="page-description">Catat hasil penyumbangan untuk Pendonor yang telah dinyatakan LAYAK.</p></div><a class="button button-secondary" href="{{ route('petugas.seleksi.show', $pemesanan) }}">Kembali ke Seleksi</a></header>
        @include('partials.alerts')

        {{-- Konteks penyumbangan --}}
        <section class="page-section" aria-labelledby="konteks-penyumbangan">
            <div class="section-header"><div><h2 class="section-title" id="konteks-penyumbangan">Konteks Penyumbangan</h2><p class="section-description">Petugas login: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p></div></div>
            <dl class="info-grid">
                <div class="info-block"><dt>Nama Pendonor</dt><dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd></div><div class="info-block"><dt>Nomor Donor</dt><dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
                <div class="info-block"><dt>ID Seleksi</dt><dd>{{ $seleksi->id_seleksi }}</dd></div><div class="info-block"><dt>ID Pemesanan</dt><dd>{{ $pemesanan->id_pemesanan }}</dd></div>
                <div class="info-block"><dt>Status Pemesanan</dt><dd>{{ $pemesanan->status_pemesanan }}</dd></div><div class="info-block"><dt>Waktu Check-in</dt><dd>{{ $pemesanan->waktu_checkin?->format('Y-m-d H:i:s') ?? 'Belum tersedia' }}</dd></div>
                <div class="info-block"><dt>Keputusan Seleksi</dt><dd><span class="status-badge status-success">{{ $seleksi->keputusan_seleksi }}</span></dd></div><div class="info-block"><dt>Berat Badan Seleksi</dt><dd>{{ $seleksi->berat_badan }} kg</dd></div>
            </dl>
        </section>

        @if ($penyumbangan === null)
            <section class="page-section" aria-labelledby="form-penyumbangan">
                <div class="section-header"><div><h2 class="section-title" id="form-penyumbangan">Form Penyumbangan</h2><p class="section-description">Isi waktu pengambilan, volume bila dicatat, dan hasil penyumbangan.</p></div></div>
                <form class="form-panel" method="POST" action="{{ route('petugas.penyumbangan.store', $seleksi) }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-field"><label for="waktu_pengambilan">Waktu pengambilan</label><input id="waktu_pengambilan" name="waktu_pengambilan" type="datetime-local" value="{{ old('waktu_pengambilan') }}" required></div>
                        <div class="form-field"><label for="volume_ml">Volume (mL)</label><select id="volume_ml" name="volume_ml"><option value="">Tidak dicatat</option>@foreach ([350, 450] as $volume)<option value="{{ $volume }}" @selected((string) old('volume_ml') === (string) $volume)>{{ $volume }}</option>@endforeach</select></div>
                        <div class="form-field"><label for="hasil_penyumbangan">Hasil penyumbangan</label><select id="hasil_penyumbangan" name="hasil_penyumbangan" required><option value="">Pilih hasil</option>@foreach (['BERHASIL', 'GAGAL'] as $hasil)<option value="{{ $hasil }}" @selected(old('hasil_penyumbangan') === $hasil)>{{ $hasil }}</option>@endforeach</select></div>
                        <div class="form-field"><label for="alasan_gagal">Alasan gagal</label><textarea id="alasan_gagal" name="alasan_gagal">{{ old('alasan_gagal') }}</textarea></div>
                    </div>
                    <div class="form-actions"><button class="button button-primary" type="submit">Simpan Penyumbangan</button></div>
                </form>
            </section>
        @else
            <section class="page-section" aria-labelledby="penyumbangan-tersimpan">
                <div class="section-header"><div><h2 class="section-title" id="penyumbangan-tersimpan">Penyumbangan Tersimpan</h2><p class="section-description">Data penyumbangan berikut menjadi riwayat pelayanan.</p></div></div>
                <dl class="info-grid">
                    <div class="info-block"><dt>Waktu Pengambilan</dt><dd>{{ $penyumbangan->waktu_pengambilan->format('Y-m-d H:i:s') }}</dd></div><div class="info-block"><dt>Volume</dt><dd>{{ $penyumbangan->volume_ml === null ? '-' : $penyumbangan->volume_ml . ' mL' }}</dd></div>
                    <div class="info-block"><dt>Hasil Penyumbangan</dt><dd><span class="status-badge {{ $penyumbangan->hasil_penyumbangan === 'BERHASIL' ? 'status-success' : 'status-danger' }}">{{ $penyumbangan->hasil_penyumbangan }}</span></dd></div><div class="info-block"><dt>Alasan Gagal</dt><dd>{{ $penyumbangan->alasan_gagal ?? '-' }}</dd></div>
                    <div class="info-block"><dt>Petugas Pencatat</dt><dd>@if ($penyumbangan->petugasPencatat !== null){{ $penyumbangan->petugasPencatat->nama_petugas }} ({{ $penyumbangan->petugasPencatat->nomor_petugas }})@else Tidak tersedia @endif</dd></div>
                </dl>
                @if ($penyumbangan->hasil_penyumbangan === 'BERHASIL')<div class="form-actions"><a class="button button-primary" href="{{ route('petugas.unit-komponen.show', $penyumbangan) }}">Unit Komponen Darah</a></div>@endif
            </section>
        @endif
    </div>
@endsection

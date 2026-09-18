@extends('layouts.app')

@section('title', 'Check-in Pendonor | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main"><p class="eyebrow">Pelayanan Donor</p><h1 class="page-title">Check-in Pendonor</h1><p class="page-description">Cari pemesanan menggunakan kode check-in dan periksa data kunjungan sebelum konfirmasi.</p></div>
            <a class="button button-secondary" href="{{ route('petugas.home') }}">Kembali ke Dashboard Petugas</a>
        </header>

        @include('partials.alerts')
        @if ($lookupError !== null)<div class="alert alert-error" role="alert">{{ $lookupError }}</div>@endif

        {{-- Pencarian kode --}}
        <section class="page-section" aria-labelledby="pencarian-kode">
            <div class="section-header"><div><h2 class="section-title" id="pencarian-kode">Cari Pemesanan</h2><p class="section-description">Petugas: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p></div></div>
            <form class="form-panel" method="GET" action="{{ route('petugas.check-in.index') }}">
                <div class="form-field"><label for="kode_checkin">Kode check-in</label><input id="kode_checkin" name="kode_checkin" type="text" value="{{ $normalizedCode ?? '' }}" autocomplete="off" required>@if ($errors->has('kode_checkin'))<p class="form-hint" role="alert">{{ $errors->first('kode_checkin') }}</p>@endif</div>
                <div class="form-actions"><button class="button button-primary" type="submit">Cari Pemesanan</button></div>
            </form>
        </section>

        @if ($pemesanan !== null)
            {{-- Data kunjungan --}}
            <section class="page-section" aria-labelledby="hasil-lookup">
                <div class="section-header"><div><h2 class="section-title" id="hasil-lookup">Data Kunjungan</h2><p class="section-description">Pastikan data berikut sesuai dengan Pendonor yang datang.</p></div></div>
                <dl class="info-grid">
                    <div class="info-block"><dt>Nama Pendonor</dt><dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd></div>
                    <div class="info-block"><dt>Nomor Donor</dt><dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
                    <div class="info-block"><dt>Tanggal Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</dd></div>
                    <div class="info-block"><dt>Waktu Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->jam_mulai }} - {{ $pemesanan->jadwalPelayanan->jam_selesai }}</dd></div>
                    <div class="info-block"><dt>Status Jadwal</dt><dd><span class="status-badge status-neutral">{{ $pemesanan->jadwalPelayanan->status_jadwal }}</span></dd></div>
                    <div class="info-block"><dt>Status Pemesanan</dt><dd><span class="status-badge {{ $pemesanan->status_pemesanan === 'CHECK_IN' ? 'status-success' : 'status-warning' }}">{{ $pemesanan->status_pemesanan }}</span></dd></div>
                    <div class="info-block"><dt>Kode Check-in</dt><dd class="code-value">{{ $pemesanan->kode_checkin }}</dd></div>
                    <div class="info-block"><dt>Kuesioner Pradonasi</dt><dd>{{ $pemesanan->kuesionerPradonasi !== null ? 'Tersedia' : 'Belum tersedia' }}</dd></div>
                </dl>
                <div class="action-panel">
                    <div>@if (!$canCheckIn)<p class="section-description" role="status">{{ $eligibilityMessage }}</p>@else<p class="section-description">Data memenuhi syarat untuk dikonfirmasi pada tahap check-in.</p>@endif</div>
                    <div class="action-group">
                        @if ($canCheckIn)<form class="inline-form" method="POST" action="{{ route('petugas.check-in.store') }}">@csrf<input type="hidden" name="kode_checkin" value="{{ $normalizedCode }}"><button class="button button-primary" type="submit">Konfirmasi Check-in</button></form>@endif
                        @if ($pemesanan->status_pemesanan === 'CHECK_IN' && $pemesanan->waktu_checkin !== null && $pemesanan->kuesionerPradonasi !== null)<a class="button button-secondary" href="{{ route('petugas.kuesioner.show', $pemesanan) }}">Lihat Kuesioner</a>@endif
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

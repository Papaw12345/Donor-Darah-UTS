@extends('layouts.app')

@section('title', 'Kode Check-in | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Kedatangan Pendonor</p>
                <h1 class="page-title">Kode Check-in</h1>
                <p class="page-description">Tunjukkan kode teks ini kepada Petugas saat datang ke UDD.</p>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.pemesanan.index') }}">Kembali ke Pemesanan Saya</a>
        </header>

        <dl class="context-strip">
            <div><dt>Tanggal jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->tanggal->format('d-m-Y') }}</dd></div>
            <div><dt>Waktu jadwal</dt><dd>{{ substr($pemesanan->jadwalPelayanan->jam_mulai, 0, 5) }} - {{ substr($pemesanan->jadwalPelayanan->jam_selesai, 0, 5) }}</dd></div>
            <div><dt>Status pemesanan</dt><dd><span class="status-badge {{ $pemesanan->status_pemesanan === 'TERJADWAL' ? 'status-warning' : 'status-neutral' }}">{{ $pemesanan->status_pemesanan }}</span></dd></div>
        </dl>

        @include('partials.alerts')

        @if ($pemesanan->kode_checkin !== null)
            <section class="checkin-panel" aria-labelledby="kode-checkin-tersimpan">
                <p class="summary-label">Kode untuk pemesanan ini</p>
                <h2 id="kode-checkin-tersimpan">Kode Anda</h2>
                <p class="checkin-code"><code>{{ $pemesanan->kode_checkin }}</code></p>
                <p class="checkin-note">Kode tetap sama untuk pemesanan ini.</p>
            </section>
        @else
            <div class="empty-state">{{ $pesanTidakTersedia }}</div>
        @endif
    </div>
@endsection

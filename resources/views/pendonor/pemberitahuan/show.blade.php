@extends('layouts.app')

@section('title', 'Detail Pemberitahuan | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Pemberitahuan Pendonor</p>
                <h1 class="page-title">Detail Pemberitahuan</h1>
                <p class="page-description">Informasi lengkap pemberitahuan dari Petugas UDD.</p>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.pemberitahuan.index') }}">Kembali ke Daftar Pemberitahuan</a>
        </header>

        @include('partials.alerts')

        <article class="message-detail">
            <div class="message-detail-header">
                <div>
                    <p class="summary-label">Dikirim oleh</p>
                    <h2>{{ $pemberitahuan->petugasPengirim?->nama_petugas ?? '-' }}</h2>
                </div>
                <span class="status-badge {{ $pemberitahuan->waktu_dibaca === null ? 'status-warning' : 'status-neutral' }}">{{ $pemberitahuan->waktu_dibaca === null ? 'Belum dibaca' : 'Sudah dibaca' }}</span>
            </div>
            <dl class="message-meta-grid">
                <div><dt>Waktu dibuat</dt><dd>{{ $pemberitahuan->waktu_dibuat->format('d-m-Y H:i') }}</dd></div>
                <div><dt>Waktu dibaca</dt><dd>{{ $pemberitahuan->waktu_dibaca?->format('d-m-Y H:i') ?? '-' }}</dd></div>
            </dl>
            <section class="message-body" aria-labelledby="isi-pesan">
                <h3 id="isi-pesan">Isi pesan</h3>
                <p>{{ $pemberitahuan->isi_pesan }}</p>
            </section>
        </article>

        @if ($pemberitahuan->waktu_dibaca === null)
            <form class="form-actions" method="POST" action="{{ route('pendonor.pemberitahuan.read', $pemberitahuan) }}">
                @csrf
                @method('PATCH')
                <button class="button button-primary" type="submit">Tandai Sudah Dibaca</button>
            </form>
        @else
            <p class="read-confirmation">Pemberitahuan ini sudah dibaca.</p>
        @endif
    </div>
@endsection

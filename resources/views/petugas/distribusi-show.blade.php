@extends('layouts.app')

@section('title', 'Detail Distribusi | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Distribusi Unit</h1>
            </div>
            <a class="button button-secondary" href="{{ route('petugas.distribusi.index') }}">Kembali ke Distribusi</a>
        </header>

        @include('partials.alerts')

        @php
            $isKedaluwarsa =
                $unit->status_unit === 'TERSEDIA'
                && $unit->tanggal_kedaluwarsa->toDateString() < $tanggalAcuan;
        @endphp

        <section class="page-section" aria-labelledby="detail-distribusi">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="detail-distribusi">Detail Unit</h2>
                    <p class="section-description">
                        Tanggal acuan: {{ \Carbon\CarbonImmutable::parse($tanggalAcuan, 'Asia/Jakarta')->format('d-m-Y') }}
                    </p>
                </div>
            </div>

            <dl class="identity-panel identity-grid">
                <div class="identity-item">
                    <dt>Nomor Unit</dt>
                    <dd>{{ $unit->nomor_unit }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Jenis Komponen</dt>
                    <dd>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Golongan Darah</dt>
                    <dd>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Tanggal Pembuatan</dt>
                    <dd>{{ $unit->tanggal_pembuatan->toDateString() }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Tanggal Kedaluwarsa</dt>
                    <dd>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Status</dt>
                    <dd>
                        <span class="status-badge {{ $unit->status_unit === 'TERSEDIA' ? 'status-success' : 'status-neutral' }}">
                            {{ $unit->status_unit }}
                        </span>
                    </dd>
                </div>

                @if ($isKedaluwarsa)
                    <div class="identity-item">
                        <dt>Kondisi Saat Ini</dt>
                        <dd><span class="status-badge status-neutral">Kedaluwarsa</span></dd>
                    </div>
                @endif

                @if ($unit->waktu_distribusi)
                    <div class="identity-item">
                        <dt>Waktu Distribusi</dt>
                        <dd>{{ $unit->waktu_distribusi }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        @if ($eligible)
            <form class="action-panel" method="POST" action="{{ route('petugas.distribusi.store', $unit) }}">
                @csrf
                <p class="section-description">Unit masih tersedia dan belum kedaluwarsa pada tanggal acuan.</p>
                <button class="button button-primary" type="submit">Distribusikan Unit</button>
            </form>
        @else
            <div class="notice-panel">
                <strong>Riwayat distribusi hanya-baca.</strong>

                @if ($unit->status_unit === 'DIDISTRIBUSIKAN')
                    <p>Unit ini sudah didistribusikan.</p>
                @elseif ($isKedaluwarsa)
                    <p>Unit telah melewati tanggal kedaluwarsa dan tidak dapat didistribusikan.</p>
                @else
                    <p>Unit ini tidak dapat didistribusikan dari status dan tanggal saat ini.</p>
                @endif
            </div>
        @endif
    </div>
@endsection
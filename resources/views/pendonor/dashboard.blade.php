@extends('layouts.app')

@section('title', 'Dashboard Pendonor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Selamat datang, {{ $pendonor->nama_lengkap }}</h1>
            </div>
            <div class="action-group">
                <a class="button button-secondary" href="{{ route('pendonor.riwayat.index') }}">Riwayat Donor</a>
                <a class="button button-secondary" href="{{ route('pendonor.profil.show') }}">Lihat Profil</a>
            </div>
        </header>

        @include('partials.alerts')

        {{-- Ringkasan --}}
        <section class="dashboard-summary" aria-label="Ringkasan Pendonor">
            <div class="dashboard-summary-item">
                <p class="summary-label">Donor Berikutnya</p>
                @if ($informasiDonorBerikutnya['donor_pertama'])
                    <p class="summary-value">Anda dapat mencoba donor pertama.</p>
                @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
                    <p class="summary-value">Anda dapat mencoba donor kembali.</p>
                @else
                    <p class="summary-value">{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</p>
                @endif
                <a class="text-link" href="{{ route('pendonor.donor-berikutnya.index') }}">Lihat Detail</a>
            </div>

            <div class="dashboard-summary-item">
                <p class="summary-label">Pemesanan</p>
                <p class="summary-value">
                    {{ $pemesananAktif->isEmpty() ? 'Belum ada pemesanan aktif.' : 'Pemesanan aktif tersedia.' }}
                </p>
                <a class="text-link" href="{{ route('pendonor.pemesanan.index') }}">Lihat Pemesanan</a>
            </div>

            <div class="dashboard-summary-item">
                <p class="summary-label">Pemberitahuan</p>
                <p class="summary-value">
                    {{ $jumlahPemberitahuanBelumDibaca === 0 ? 'Belum ada pemberitahuan baru.' : $jumlahPemberitahuanBelumDibaca.' belum dibaca.' }}
                </p>
                <a class="text-link" href="{{ route('pendonor.pemberitahuan.index') }}">Lihat Pemberitahuan</a>
            </div>
        </section>

        <section class="page-section identity-panel" aria-labelledby="informasi-pendonor">
            <div class="section-header">
                <h2 class="section-title" id="informasi-pendonor">Informasi Pendonor</h2>
            </div>

            <dl class="identity-grid">
                <div class="identity-item"><dt>Nama lengkap</dt><dd>{{ $pendonor->nama_lengkap }}</dd></div>
                <div class="identity-item"><dt>Email akun</dt><dd>{{ $akun->email }}</dd></div>
                <div class="identity-item"><dt>Nomor donor</dt><dd>{{ $pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
                <div class="identity-item">
                    <dt>Golongan darah</dt>
                    <dd>
                        @if ($pendonor->golonganDarah)
                            {{ $pendonor->golonganDarah->abo }} {{ $pendonor->golonganDarah->rhesus === 'POSITIF' ? '+' : '-' }}
                        @else
                            Belum dikonfirmasi UDD
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        {{-- Pemesanan aktif --}}
        <section class="page-section" aria-labelledby="pemesanan-aktif">
            <div class="section-header">
                <h2 class="section-title" id="pemesanan-aktif">Pemesanan</h2>
                <a class="button button-secondary button-small" href="{{ route('pendonor.pemesanan.index') }}">Lihat Pemesanan</a>
            </div>

            @if ($pemesananAktif->isEmpty())
                <div class="empty-state">Belum ada pemesanan aktif.</div>
            @else
                <div class="table-container">
                    <table class="data-table table-compact">
                        <thead><tr><th scope="col">Tanggal</th><th scope="col">Jam Pelayanan</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach ($pemesananAktif as $pemesanan)
                                <tr>
                                    <td>{{ $pemesanan->jadwalPelayanan->tanggal->format('d-m-Y') }}</td>
                                    <td>{{ substr($pemesanan->jadwalPelayanan->jam_mulai, 0, 5) }} - {{ substr($pemesanan->jadwalPelayanan->jam_selesai, 0, 5) }}</td>
                                    <td><span class="status-badge {{ $pemesanan->status_pemesanan === 'CHECK_IN' ? 'status-success' : 'status-warning' }}">{{ $pemesanan->status_pemesanan }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Pemberitahuan terbaru --}}
        <section class="page-section" aria-labelledby="pemberitahuan-terbaru">
            <div class="section-header">
                <h2 class="section-title" id="pemberitahuan-terbaru">Pemberitahuan</h2>
                <a class="button button-secondary button-small" href="{{ route('pendonor.pemberitahuan.index') }}">Lihat Pemberitahuan</a>
            </div>

            @if ($pemberitahuanTerbaru->isEmpty())
                <div class="empty-state">Belum ada pemberitahuan.</div>
            @else
                <div class="notification-list">
                    @foreach ($pemberitahuanTerbaru as $item)
                        <article class="notification-item {{ $item->waktu_dibaca === null ? 'is-unread' : '' }}">
                            <div class="notification-meta">
                                <span>{{ $item->waktu_dibuat->format('d-m-Y H:i') }}</span>
                                <span>{{ $item->petugasPengirim?->nama_petugas ?? '-' }}</span>
                                <span class="status-badge {{ $item->waktu_dibaca === null ? 'status-warning' : 'status-neutral' }}">{{ $item->waktu_dibaca === null ? 'Belum dibaca' : 'Sudah dibaca' }}</span>
                            </div>
                            <p class="notification-message">{{ $item->isi_pesan }}</p>
                            <a class="text-link" href="{{ route('pendonor.pemberitahuan.show', $item) }}">Lihat Detail</a>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection

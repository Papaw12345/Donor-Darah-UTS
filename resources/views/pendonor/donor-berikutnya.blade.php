@extends('layouts.app')

@section('title', 'Informasi Donor Berikutnya | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Informasi Donor Berikutnya</h1>
                <p class="page-description">Perkiraan berdasarkan riwayat penyumbangan berhasil.</p>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </header>

        <section class="page-section" aria-labelledby="status-donor-berikutnya">
            <div class="section-header">
                <div>
                    <p class="summary-label">Status berdasarkan riwayat</p>
                    <h2 class="section-title" id="status-donor-berikutnya">
                        @if ($informasiDonorBerikutnya['donor_pertama'])
                            Kesempatan donor pertama
                        @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
                            Dapat mencoba donor kembali
                        @else
                            Belum dapat mencoba donor kembali
                        @endif
                    </h2>
                </div>
            </div>

            @if ($informasiDonorBerikutnya['donor_pertama'])
                <p>Anda belum memiliki riwayat donor berhasil. Berdasarkan riwayat, Anda dapat mencoba donor pertama sekarang.</p>
            @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
                <p>Berdasarkan riwayat donor berhasil, Anda sudah dapat mencoba donor kembali.</p>
            @else
                <p>Anda belum dapat mencoba donor kembali berdasarkan riwayat donor berhasil.</p>
                <p>Perkiraan paling awal untuk mencoba donor kembali: <strong>{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</strong>.</p>
            @endif
        </section>

        <section class="page-section" aria-labelledby="dasar-perhitungan">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="dasar-perhitungan">Dasar Perhitungan</h2>
                </div>
            </div>

            <dl class="identity-panel identity-grid">
                <div class="identity-item">
                    <dt>Donor berhasil terakhir</dt>
                    <dd>{{ $informasiDonorBerikutnya['tanggal_donor_terakhir']?->format('d-m-Y') ?? 'Belum ada' }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Donor berhasil tahun {{ $informasiDonorBerikutnya['tanggal_acuan']->year }}</dt>
                    <dd>{{ $informasiDonorBerikutnya['jumlah_donor_tahun_ini'] }} dari batas {{ $informasiDonorBerikutnya['batas_tahunan'] }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Perkiraan tanggal donor berikutnya</dt>
                    <dd>{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Interval donor ulang</dt>
                    <dd>
                        @if ($informasiDonorBerikutnya['donor_pertama'])
                            Belum berlaku karena belum ada donor berhasil sebelumnya.
                        @elseif ($informasiDonorBerikutnya['interval_terpenuhi'])
                            Interval dua bulan kalender sudah terpenuhi.
                        @else
                            Terpenuhi pada {{ $informasiDonorBerikutnya['tanggal_interval_terpenuhi']->format('d-m-Y') }}.
                        @endif
                    </dd>
                </div>

                <div class="identity-item">
                    <dt>Batas frekuensi tahunan</dt>
                    <dd>
                        @if ($informasiDonorBerikutnya['frekuensi_terpenuhi'])
                            Jumlah donor berhasil tahun berjalan masih di bawah batas.
                        @else
                            Terbuka kembali pada {{ $informasiDonorBerikutnya['tanggal_frekuensi_terpenuhi']->format('d-m-Y') }}.
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <aside class="notice-panel" aria-label="Catatan kelayakan medis">
            <strong>Catatan</strong>
            <p>Informasi ini bukan keputusan kelayakan medis akhir. Kuesioner pradonasi, pemeriksaan, dan seleksi Petugas tetap berlaku.</p>
        </aside>
    </div>
@endsection
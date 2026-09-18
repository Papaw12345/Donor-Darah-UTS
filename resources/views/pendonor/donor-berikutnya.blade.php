@extends('layouts.app')

@section('title', 'Informasi Donor Berikutnya | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Riwayat Donor Ulang</p>
                <h1 class="page-title">Informasi Donor Berikutnya</h1>
                <p class="page-description">Perkiraan waktu berdasarkan interval dan frekuensi penyumbangan berhasil.</p>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </header>

        <section class="eligibility-panel" aria-labelledby="status-donor-berikutnya">
            <p class="summary-label">Status berdasarkan riwayat</p>
            <h2 id="status-donor-berikutnya">
                @if ($informasiDonorBerikutnya['donor_pertama'])
                    Kesempatan donor pertama
                @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
                    Dapat mencoba donor kembali
                @else
                    Belum dapat mencoba donor kembali
                @endif
            </h2>
            @if ($informasiDonorBerikutnya['donor_pertama'])
                <p>Anda belum memiliki riwayat donor berhasil. Berdasarkan riwayat, Anda dapat mencoba donor pertama sekarang.</p>
            @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
                <p>Berdasarkan riwayat donor berhasil, Anda sudah dapat mencoba donor kembali.</p>
            @else
                <p>Anda belum dapat mencoba donor kembali berdasarkan riwayat donor berhasil.</p>
                <p>Perkiraan paling awal untuk mencoba donor kembali: <strong>{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</strong>.</p>
            @endif
        </section>

        <section class="page-section" aria-labelledby="rincian-perhitungan">
            <div class="section-header"><div><h2 class="section-title" id="rincian-perhitungan">Rincian Perhitungan</h2><p class="section-description">Informasi dihitung pada saat halaman dibuka dan tidak disimpan sebagai field baru.</p></div></div>
            <dl class="info-grid">
                <div class="info-block"><dt>Tanggal acuan</dt><dd>{{ $informasiDonorBerikutnya['tanggal_acuan']->format('d-m-Y') }}</dd></div>
                <div class="info-block"><dt>Donor berhasil terakhir</dt><dd>{{ $informasiDonorBerikutnya['tanggal_donor_terakhir']?->format('d-m-Y') ?? 'Belum ada' }}</dd></div>
                <div class="info-block"><dt>Donor berhasil tahun {{ $informasiDonorBerikutnya['tanggal_acuan']->year }}</dt><dd>{{ $informasiDonorBerikutnya['jumlah_donor_tahun_ini'] }} dari batas {{ $informasiDonorBerikutnya['batas_tahunan'] }}</dd></div>
                <div class="info-block"><dt>Perkiraan tanggal donor berikutnya</dt><dd>{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</dd></div>
            </dl>
        </section>

        <section class="rule-grid" aria-label="Ketentuan donor ulang">
            <article class="rule-block">
                <h2>Batas Interval</h2>
                @if ($informasiDonorBerikutnya['donor_pertama'])
                    <p>Belum ada donor berhasil sebelumnya, sehingga pembatasan interval donor ulang belum berlaku.</p>
                @elseif ($informasiDonorBerikutnya['interval_terpenuhi'])
                    <p>Interval dua bulan kalender dari donor berhasil terakhir sudah terpenuhi.</p>
                @else
                    <p>Interval dua bulan kalender terpenuhi pada {{ $informasiDonorBerikutnya['tanggal_interval_terpenuhi']->format('d-m-Y') }}.</p>
                @endif
            </article>
            <article class="rule-block">
                <h2>Batas Frekuensi Tahunan</h2>
                @if ($informasiDonorBerikutnya['frekuensi_terpenuhi'])
                    <p>Jumlah donor berhasil pada tahun berjalan masih di bawah batas tahunan.</p>
                @else
                    <p>Batas tahunan sudah tercapai. Batas frekuensi terbuka kembali pada {{ $informasiDonorBerikutnya['tanggal_frekuensi_terpenuhi']->format('d-m-Y') }}.</p>
                @endif
            </article>
        </section>

        <aside class="notice-panel" aria-label="Catatan kelayakan medis">
            <strong>Catatan</strong>
            <p>Informasi ini bukan keputusan kelayakan medis akhir. Kuesioner pradonasi, pemeriksaan, dan seleksi Petugas tetap berlaku.</p>
        </aside>
    </div>
@endsection

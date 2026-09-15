<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Donor Berikutnya</title>
</head>
<body>
    <main>
        <h1>Informasi Donor Berikutnya</h1>

        @if ($informasiDonorBerikutnya['donor_pertama'])
            <p>Anda belum memiliki riwayat donor berhasil. Berdasarkan riwayat, Anda dapat mencoba donor pertama sekarang.</p>
        @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
            <p>Berdasarkan riwayat donor berhasil, Anda sudah dapat mencoba donor kembali.</p>
        @else
            <p>Anda belum dapat mencoba donor kembali berdasarkan riwayat donor berhasil.</p>
            <p>Perkiraan paling awal untuk mencoba donor kembali: <strong>{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</strong>.</p>
        @endif

        <dl>
            <dt>Tanggal acuan</dt>
            <dd>{{ $informasiDonorBerikutnya['tanggal_acuan']->format('d-m-Y') }}</dd>

            <dt>Donor berhasil terakhir</dt>
            <dd>
                {{ $informasiDonorBerikutnya['tanggal_donor_terakhir']?->format('d-m-Y') ?? 'Belum ada' }}
            </dd>

            <dt>Donor berhasil tahun {{ $informasiDonorBerikutnya['tanggal_acuan']->year }}</dt>
            <dd>{{ $informasiDonorBerikutnya['jumlah_donor_tahun_ini'] }} dari batas {{ $informasiDonorBerikutnya['batas_tahunan'] }}</dd>

            <dt>Perkiraan tanggal donor berikutnya</dt>
            <dd>{{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}</dd>
        </dl>

        <section aria-labelledby="penjelasan-interval">
            <h2 id="penjelasan-interval">Batas Interval</h2>

            @if ($informasiDonorBerikutnya['donor_pertama'])
                <p>Belum ada donor berhasil sebelumnya, sehingga pembatasan interval donor ulang belum berlaku.</p>
            @elseif ($informasiDonorBerikutnya['interval_terpenuhi'])
                <p>Interval dua bulan kalender dari donor berhasil terakhir sudah terpenuhi.</p>
            @else
                <p>Interval dua bulan kalender terpenuhi pada {{ $informasiDonorBerikutnya['tanggal_interval_terpenuhi']->format('d-m-Y') }}.</p>
            @endif
        </section>

        <section aria-labelledby="penjelasan-frekuensi">
            <h2 id="penjelasan-frekuensi">Batas Frekuensi Tahunan</h2>

            @if ($informasiDonorBerikutnya['frekuensi_terpenuhi'])
                <p>Jumlah donor berhasil pada tahun berjalan masih di bawah batas tahunan.</p>
            @else
                <p>Batas tahunan sudah tercapai. Batas frekuensi terbuka kembali pada {{ $informasiDonorBerikutnya['tanggal_frekuensi_terpenuhi']->format('d-m-Y') }}.</p>
            @endif
        </section>

        <p>
            Informasi ini bukan keputusan kelayakan medis akhir. Kuesioner pradonasi,
            pemeriksaan, dan seleksi Petugas tetap berlaku.
        </p>

        <p>
            <a href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </p>
    </main>
</body>
</html>

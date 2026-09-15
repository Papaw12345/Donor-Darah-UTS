<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Check-in</title>
</head>
<body>
    <main>
        <h1>Kode Check-in</h1>

        <dl>
            <dt>Tanggal jadwal</dt>
            <dd>{{ $pemesanan->jadwalPelayanan->tanggal->format('d-m-Y') }}</dd>

            <dt>Waktu jadwal</dt>
            <dd>{{ substr($pemesanan->jadwalPelayanan->jam_mulai, 0, 5) }} - {{ substr($pemesanan->jadwalPelayanan->jam_selesai, 0, 5) }}</dd>

            <dt>Status pemesanan</dt>
            <dd>{{ $pemesanan->status_pemesanan }}</dd>
        </dl>

        @if (session('success'))
            <div role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($pemesanan->kode_checkin !== null)
            <section aria-labelledby="kode-checkin-tersimpan">
                <h2 id="kode-checkin-tersimpan">Kode Anda</h2>
                <p><strong>{{ $pemesanan->kode_checkin }}</strong></p>
            </section>
        @elseif ($pesanTidakTersedia !== null)
            <p>{{ $pesanTidakTersedia }}</p>
        @else
            <form method="POST" action="{{ route('pendonor.kode-checkin.generate', $pemesanan) }}">
                @csrf
                <button type="submit">Buat Kode Check-in</button>
            </form>
        @endif

        <p>
            <a href="{{ route('pendonor.pemesanan.index') }}">Kembali ke Pemesanan Saya</a>
        </p>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemberitahuan</title>
</head>
<body>
    <main>
        <h1>Detail Pemberitahuan</h1>

        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

        <dl>
            <dt>Petugas pengirim</dt>
            <dd>{{ $pemberitahuan->petugasPengirim?->nama_petugas ?? '-' }}</dd>

            <dt>Waktu dibuat</dt>
            <dd>{{ $pemberitahuan->waktu_dibuat->format('d-m-Y H:i') }}</dd>

            <dt>Status</dt>
            <dd>{{ $pemberitahuan->waktu_dibaca === null ? 'Belum dibaca' : 'Sudah dibaca' }}</dd>

            <dt>Waktu dibaca</dt>
            <dd>{{ $pemberitahuan->waktu_dibaca?->format('d-m-Y H:i') ?? '-' }}</dd>

            <dt>Isi pesan</dt>
            <dd>{{ $pemberitahuan->isi_pesan }}</dd>
        </dl>

        @if ($pemberitahuan->waktu_dibaca === null)
            <form method="POST" action="{{ route('pendonor.pemberitahuan.read', $pemberitahuan) }}">
                @csrf
                @method('PATCH')
                <button type="submit">Tandai Sudah Dibaca</button>
            </form>
        @else
            <p>Pemberitahuan ini sudah dibaca.</p>
        @endif

        <p><a href="{{ route('pendonor.pemberitahuan.index') }}">Kembali ke Daftar Pemberitahuan</a></p>
        <p><a href="{{ route('pendonor.home') }}">Kembali ke Dashboard Pendonor</a></p>
    </main>
</body>
</html>

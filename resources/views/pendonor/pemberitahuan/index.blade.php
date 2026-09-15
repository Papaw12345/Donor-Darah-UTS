<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan</title>
</head>
<body>
    <main>
        <h1>Pemberitahuan</h1>

        @if ($pemberitahuan->isEmpty())
            <p>Belum ada pemberitahuan.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Waktu Dibuat</th>
                        <th>Petugas Pengirim</th>
                        <th>Pesan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pemberitahuan as $item)
                        <tr>
                            <td>{{ $item->waktu_dibuat->format('d-m-Y H:i') }}</td>
                            <td>{{ $item->petugasPengirim?->nama_petugas ?? '-' }}</td>
                            <td>{{ $item->isi_pesan }}</td>
                            <td>{{ $item->waktu_dibaca === null ? 'Belum dibaca' : 'Sudah dibaca' }}</td>
                            <td>
                                <a href="{{ route('pendonor.pemberitahuan.show', $item) }}">Lihat Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p><a href="{{ route('pendonor.home') }}">Kembali ke Dashboard Pendonor</a></p>
    </main>
</body>
</html>

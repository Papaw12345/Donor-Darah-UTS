<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Donor</title>
</head>
<body>
    <main>
        <h1>Riwayat Donor</h1>

        @if ($riwayat->isEmpty())
            <p>Belum ada riwayat penyumbangan.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Waktu Pengambilan</th>
                        <th>Volume</th>
                        <th>Hasil</th>
                        <th>Alasan Gagal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayat as $penyumbangan)
                        <tr>
                            <td>{{ $penyumbangan->waktu_pengambilan->format('d-m-Y H:i') }}</td>
                            <td>{{ $penyumbangan->volume_ml !== null ? $penyumbangan->volume_ml.' mL' : '-' }}</td>
                            <td>{{ $penyumbangan->hasil_penyumbangan }}</td>
                            <td>{{ $penyumbangan->alasan_gagal ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p>
            <a href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </p>
    </main>
</body>
</html>

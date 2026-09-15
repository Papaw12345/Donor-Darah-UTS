<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Donor</title>
</head>
<body>
    <main>
        <h1>Jadwal Donor</h1>

        <p>
            Menampilkan jadwal pelayanan yang masih dibuka dan masih memiliki kapasitas.
        </p>

        @if ($jadwal->isEmpty())
            <p>Belum ada jadwal donor yang tersedia.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Jam Mulai</th>
                        <th scope="col">Jam Selesai</th>
                        <th scope="col">Kapasitas</th>
                        <th scope="col">Sisa Kapasitas</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jadwal as $item)
                        <tr>
                            <td>{{ $item->tanggal->format('d-m-Y') }}</td>
                            <td>{{ substr($item->jam_mulai, 0, 5) }}</td>
                            <td>{{ substr($item->jam_selesai, 0, 5) }}</td>
                            <td>{{ $item->kapasitas }}</td>
                            <td>{{ $item->kapasitas - $item->jumlah_pemesanan_berlaku }}</td>
                            <td>{{ $item->status_jadwal }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p>
            <a href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Donor Saya</title>
</head>
<body>
    <main>
        <h1>Pemesanan Donor Saya</h1>

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

        @if ($pemesanan->isEmpty())
            <p>Belum ada pemesanan donor.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Jam Mulai</th>
                        <th scope="col">Jam Selesai</th>
                        <th scope="col">Waktu Pemesanan</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pemesanan as $item)
                        <tr>
                            <td>{{ $item->jadwalPelayanan->tanggal->format('d-m-Y') }}</td>
                            <td>{{ substr($item->jadwalPelayanan->jam_mulai, 0, 5) }}</td>
                            <td>{{ substr($item->jadwalPelayanan->jam_selesai, 0, 5) }}</td>
                            <td>{{ $item->waktu_pemesanan->format('d-m-Y H:i') }}</td>
                            <td>{{ $item->status_pemesanan }}</td>
                            <td>
                                <a href="{{ route('pendonor.kuesioner.show', $item) }}">Kuesioner Pradonasi</a>

                                @if (
                                    $item->status_pemesanan === 'TERJADWAL'
                                    && $item->jadwalPelayanan->tanggal->gte(today('Asia/Jakarta'))
                                )
                                    <form method="POST" action="{{ route('pendonor.pemesanan.cancel', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">Batalkan</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p>
            <a href="{{ route('pendonor.jadwal.index') }}">Lihat Jadwal Donor</a>
        </p>

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

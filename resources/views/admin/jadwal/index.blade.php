<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelayanan</title>
</head>
<body>
    <main>
        <h1>Jadwal Pelayanan</h1>

        @if (session('success'))
            <div role="status">
                {{ session('success') }}
            </div>
        @endif

        <nav aria-label="Navigasi Admin">
            <a href="{{ route('admin.home') }}">Dashboard Admin</a>
            <a href="{{ route('admin.jadwal.create') }}">Tambah Jadwal</a>
        </nav>

        @if ($jadwal->isEmpty())
            <p>Belum ada jadwal pelayanan.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Jam Mulai</th>
                        <th scope="col">Jam Selesai</th>
                        <th scope="col">Kapasitas</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jadwal as $item)
                        <tr>
                            <td>{{ $item->tanggal->format('d-m-Y') }}</td>
                            <td>{{ substr($item->jam_mulai, 0, 5) }}</td>
                            <td>{{ substr($item->jam_selesai, 0, 5) }}</td>
                            <td>{{ $item->kapasitas }}</td>
                            <td>{{ $item->status_jadwal }}</td>
                            <td>
                                <a href="{{ route('admin.jadwal.edit', $item) }}">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

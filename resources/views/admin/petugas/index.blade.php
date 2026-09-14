<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Petugas</title>
</head>
<body>
    <main>
        <h1>Kelola Petugas</h1>

        @if (session('success'))
            <div role="status">
                {{ session('success') }}
            </div>
        @endif

        <nav aria-label="Navigasi Admin">
            <a href="{{ route('admin.home') }}">Dashboard Admin</a>
            <a href="{{ route('admin.petugas.create') }}">Tambah Petugas</a>
        </nav>

        @if ($petugas->isEmpty())
            <p>Belum ada petugas terdaftar.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Nomor Petugas</th>
                        <th scope="col">Nama Petugas</th>
                        <th scope="col">Email</th>
                        <th scope="col">Status Akun</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($petugas as $item)
                        <tr>
                            <td>{{ $item->nomor_petugas }}</td>
                            <td>{{ $item->nama_petugas }}</td>
                            <td>{{ $item->akun->email }}</td>
                            <td>{{ $item->akun->status_akun }}</td>
                            <td>
                                <a href="{{ route('admin.petugas.edit', $item) }}">Edit</a>

                                @if ($item->akun->status_akun === 'AKTIF')
                                    <form method="POST" action="{{ route('admin.petugas.deactivate', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">Nonaktifkan</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.petugas.activate', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">Aktifkan</button>
                                    </form>
                                @endif
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

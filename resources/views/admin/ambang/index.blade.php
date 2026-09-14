<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambang Persediaan</title>
</head>
<body>
    <main>
        <h1>Ambang Persediaan</h1>

        @if (session('success'))
            <div role="status">
                {{ session('success') }}
            </div>
        @endif

        <nav aria-label="Navigasi Admin">
            <a href="{{ route('admin.home') }}">Dashboard Admin</a>
            <a href="{{ route('admin.ambang.create') }}">Tambah Ambang</a>
        </nav>

        @if ($ambang->isEmpty())
            <p>Belum ada ambang persediaan yang dikonfigurasi.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Jenis Komponen</th>
                        <th scope="col">Golongan Darah</th>
                        <th scope="col">Jumlah Minimum</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ambang as $item)
                        <tr>
                            <td>
                                {{ $item->jenisKomponenDarah->kode_komponen }}
                                - {{ $item->jenisKomponenDarah->nama_komponen }}
                            </td>
                            <td>
                                {{ $item->golonganDarah->abo }}
                                {{ ucfirst(strtolower($item->golonganDarah->rhesus)) }}
                            </td>
                            <td>{{ $item->jumlah_minimum }}</td>
                            <td>
                                <a href="{{ route('admin.ambang.edit', $item) }}">Edit</a>
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

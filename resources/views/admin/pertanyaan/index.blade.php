<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pertanyaan Kuesioner</title>
</head>
<body>
    <main>
        <h1>Pertanyaan Kuesioner</h1>

        @if (session('success'))
            <div role="status">
                {{ session('success') }}
            </div>
        @endif

        <nav aria-label="Navigasi Admin">
            <a href="{{ route('admin.home') }}">Dashboard Admin</a>
            <a href="{{ route('admin.pertanyaan.create') }}">Tambah Pertanyaan</a>
        </nav>

        @if ($pertanyaan->isEmpty())
            <p>Belum ada pertanyaan kuesioner.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Urutan</th>
                        <th scope="col">Pertanyaan</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Jenis Jawaban</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pertanyaan as $item)
                        <tr>
                            <td>{{ $item->urutan }}</td>
                            <td>{{ $item->teks_pertanyaan }}</td>
                            <td>{{ $item->kategori ?? '-' }}</td>
                            <td>{{ $item->jenis_jawaban }}</td>
                            <td>{{ $item->status_aktif ? 'Aktif' : 'Nonaktif' }}</td>
                            <td>
                                <a href="{{ route('admin.pertanyaan.edit', $item) }}">Edit</a>
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

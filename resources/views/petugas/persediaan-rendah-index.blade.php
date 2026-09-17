<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persediaan Rendah</title>
</head>
<body>
    <main>
        <h1>Persediaan Rendah</h1>
        <p>Petugas: {{ $petugas->nama_petugas }}</p>
        <p>Tanggal acuan WIB: {{ $tanggalAcuan }}</p>

        @if ($jumlahAmbangPersediaan === 0)
            <p>Belum ada konfigurasi ambang persediaan.</p>
        @elseif ($persediaanRendah->isEmpty())
            <p>Tidak ada persediaan yang berada pada atau di bawah ambang.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Jenis Komponen</th>
                        <th>ABO</th>
                        <th>Rhesus</th>
                        <th>Jumlah Persediaan</th>
                        <th>Jumlah Minimum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($persediaanRendah as $item)
                        <tr>
                            <td>{{ $item->kode_komponen }} - {{ $item->nama_komponen }}</td>
                            <td>{{ $item->abo }}</td>
                            <td>{{ $item->rhesus }}</td>
                            <td>{{ $item->jumlah_persediaan }}</td>
                            <td>{{ $item->jumlah_minimum }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p><a href="{{ route('petugas.home') }}">Kembali ke Dashboard</a></p>
    </main>
</body>
</html>

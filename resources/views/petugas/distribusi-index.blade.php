<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi Unit</title>
</head>
<body>
    <main>
        <h1>Distribusi Unit</h1>
        <p>Petugas: {{ $petugas->nama_petugas }}</p>
        <p>Tanggal acuan WIB: {{ $tanggalAcuan }}</p>

        @if ($units->isEmpty())
            <p>Tidak ada unit yang dapat didistribusikan.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nomor Unit</th>
                        <th>Jenis Komponen</th>
                        <th>Golongan Darah</th>
                        <th>Tanggal Pembuatan</th>
                        <th>Tanggal Kedaluwarsa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td>{{ $unit->nomor_unit }}</td>
                            <td>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</td>
                            <td>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</td>
                            <td>{{ $unit->tanggal_pembuatan->toDateString() }}</td>
                            <td>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</td>
                            <td>{{ $unit->status_unit }}</td>
                            <td><a href="{{ route('petugas.distribusi.show', $unit) }}">Lihat Distribusi</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p><a href="{{ route('petugas.home') }}">Kembali ke Dashboard</a></p>
    </main>
</body>
</html>

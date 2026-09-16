<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas</title>
</head>
<body>
    <main>
        <h1>Dashboard Petugas</h1>

        <section aria-labelledby="identitas-petugas">
            <h2 id="identitas-petugas">Identitas Petugas</h2>

            <dl>
                <dt>Nama petugas</dt>
                <dd>{{ $petugas->nama_petugas }}</dd>

                <dt>Nomor petugas</dt>
                <dd>{{ $petugas->nomor_petugas }}</dd>

                <dt>Email akun</dt>
                <dd>{{ $akun->email }}</dd>
            </dl>
        </section>

        <nav aria-label="Operasional Petugas">
            <a href="{{ route('petugas.check-in.index') }}">Check-in Pendonor</a>
            <a href="{{ route('petugas.pelulusan.index') }}">Pelulusan</a>
        </nav>

        <section aria-labelledby="kegiatan-donor-hari-ini">
            <h2 id="kegiatan-donor-hari-ini">Kegiatan Donor Hari Ini</h2>
            <p>Tanggal operasional WIB: {{ $tanggalAcuan }}</p>

            <dl>
                <dt>TERJADWAL</dt>
                <dd>{{ $kegiatanHariIni['TERJADWAL'] }}</dd>

                <dt>CHECK_IN</dt>
                <dd>{{ $kegiatanHariIni['CHECK_IN'] }}</dd>

                <dt>SELESAI</dt>
                <dd>{{ $kegiatanHariIni['SELESAI'] }}</dd>

                <dt>TIDAK_HADIR</dt>
                <dd>{{ $kegiatanHariIni['TIDAK_HADIR'] }}</dd>
            </dl>
        </section>

        <section aria-labelledby="pendonor-diproses">
            <h2 id="pendonor-diproses">Pendonor Sedang Diproses</h2>
            <p>{{ $jumlahPendonorDiproses }}</p>
        </section>

        <section aria-labelledby="kondisi-persediaan">
            <h2 id="kondisi-persediaan">Kondisi Persediaan</h2>
            <p>Total unit tersedia: {{ $totalPersediaanTersedia }}</p>
        </section>

        <section aria-labelledby="persediaan-rendah">
            <h2 id="persediaan-rendah">Persediaan Rendah</h2>
            <p>Jumlah kombinasi persediaan rendah: {{ $persediaanRendah->count() }}</p>

            @if ($jumlahAmbangPersediaan === 0)
                <p>Belum ada konfigurasi ambang persediaan.</p>
            @elseif ($persediaanRendah->isEmpty())
                <p>Tidak ada persediaan yang berada pada atau di bawah ambang.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Komponen</th>
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
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

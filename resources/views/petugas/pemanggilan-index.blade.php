<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemanggilan Pendonor</title>
</head>
<body>
    <main>
        <h1>Pemanggilan Pendonor</h1>
        <p>Petugas: {{ $petugas->nama_petugas }}</p>
        <p>Tanggal acuan WIB: {{ $tanggalAcuan }}</p>

        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

        <section aria-labelledby="kondisi-persediaan-rendah">
            <h2 id="kondisi-persediaan-rendah">Kondisi Persediaan Rendah Saat Ini</h2>

            @if ($persediaanRendah->isEmpty())
                <p>Tidak ada kondisi persediaan rendah yang memerlukan pemanggilan Pendonor.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Jenis Komponen</th>
                            <th>ABO</th>
                            <th>Rhesus</th>
                            <th>Jumlah Persediaan</th>
                            <th>Jumlah Minimum</th>
                            <th>Aksi</th>
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
                                <td>
                                    <a href="{{ route('petugas.pemanggilan.index', ['id_ambang' => $item->id_ambang]) }}">Lihat Kandidat</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section aria-labelledby="kandidat-pendonor">
            <h2 id="kandidat-pendonor">Kandidat Pendonor</h2>

            @if ($ambangTerpilihTidakRendah)
                <p>Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.</p>
            @elseif ($persediaanRendah->isNotEmpty() && $ambangTerpilih === null)
                <p>Pilih kondisi persediaan rendah untuk melihat kandidat Pendonor.</p>
            @elseif ($ambangTerpilih !== null)
                <p>
                    Kondisi terpilih:
                    {{ $ambangTerpilih->kode_komponen }} - {{ $ambangTerpilih->nama_komponen }},
                    {{ $ambangTerpilih->abo }} {{ $ambangTerpilih->rhesus }}
                    ({{ $ambangTerpilih->jumlah_persediaan }} / minimum {{ $ambangTerpilih->jumlah_minimum }})
                </p>
                <p>Daftar ini berdasarkan eligibility historis dan bukan keputusan kelayakan medis akhir.</p>

                @if ($kandidatPendonor->isEmpty())
                    <p>Tidak ada Pendonor yang memenuhi kriteria pemanggilan untuk kondisi persediaan ini.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Nomor Donor</th>
                                <th>Nama Pendonor</th>
                                <th>ABO</th>
                                <th>Rhesus</th>
                                <th>Donor Berhasil Terakhir</th>
                                <th>Donor Berhasil Tahun Ini</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kandidatPendonor as $kandidat)
                                <tr>
                                    <td>{{ $kandidat->nomor_donor }}</td>
                                    <td>{{ $kandidat->nama_lengkap }}</td>
                                    <td>{{ $kandidat->abo }}</td>
                                    <td>{{ $kandidat->rhesus }}</td>
                                    <td>{{ $kandidat->tanggal_donor_terakhir?->format('d-m-Y') ?? '-' }}</td>
                                    <td>{{ $kandidat->jumlah_donor_tahun_ini }}</td>
                                    <td>
                                        <a href="{{ route('petugas.pemberitahuan.create', ['ambang' => $ambangTerpilih->id_ambang, 'pendonor' => $kandidat->id_pendonor]) }}">Buat Pemberitahuan</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif
        </section>

        <p><a href="{{ route('petugas.home') }}">Kembali ke Dashboard</a></p>
    </main>
</body>
</html>

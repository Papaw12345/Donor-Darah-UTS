<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Pradonasi Pendonor</title>
</head>
<body>
    <main>
        <h1>Kuesioner Pradonasi Pendonor</h1>
        <p>Petugas: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p>

        <section aria-labelledby="konteks-kunjungan">
            <h2 id="konteks-kunjungan">Konteks Kunjungan</h2>

            <dl>
                <dt>Nama Pendonor</dt>
                <dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd>

                <dt>Nomor Donor</dt>
                <dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>

                <dt>ID Pemesanan</dt>
                <dd>{{ $pemesanan->id_pemesanan }}</dd>

                <dt>Tanggal Jadwal</dt>
                <dd>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</dd>

                <dt>Waktu Jadwal</dt>
                <dd>{{ $pemesanan->jadwalPelayanan->jam_mulai }} - {{ $pemesanan->jadwalPelayanan->jam_selesai }}</dd>

                <dt>Status Pemesanan</dt>
                <dd>{{ $pemesanan->status_pemesanan }}</dd>

                <dt>Kode Check-in</dt>
                <dd>{{ $pemesanan->kode_checkin }}</dd>

                <dt>Waktu Check-in</dt>
                <dd>{{ $pemesanan->waktu_checkin->format('Y-m-d H:i:s') }}</dd>

                <dt>Waktu Pengisian Kuesioner</dt>
                <dd>{{ $kuesioner->waktu_pengisian->format('Y-m-d H:i:s') }}</dd>
            </dl>
        </section>

        <section aria-labelledby="jawaban-kuesioner">
            <h2 id="jawaban-kuesioner">Jawaban Kuesioner Tersimpan</h2>

            <table>
                <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Pertanyaan</th>
                        <th>Kategori</th>
                        <th>Jenis Jawaban</th>
                        <th>Jawaban</th>
                        <th>Status Pertanyaan Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jawaban as $item)
                        <tr>
                            <td>{{ $item->pertanyaanKuesioner->urutan }}</td>
                            <td>{{ $item->pertanyaanKuesioner->teks_pertanyaan }}</td>
                            <td>{{ $item->pertanyaanKuesioner->kategori ?? 'Tidak ada' }}</td>
                            <td>{{ $item->pertanyaanKuesioner->jenis_jawaban }}</td>
                            <td>{{ $item->jawaban }}</td>
                            <td>{{ $item->pertanyaanKuesioner->status_aktif ? 'AKTIF' : 'NONAKTIF' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @if ($pemesanan->seleksiDonor !== null)
            <p>
                <a href="{{ route('petugas.seleksi.show', $pemesanan) }}">Lihat Seleksi</a>
            </p>
        @elseif (
            $pemesanan->status_pemesanan === 'CHECK_IN'
            && $pemesanan->waktu_checkin !== null
        )
            <p>
                <a href="{{ route('petugas.seleksi.show', $pemesanan) }}">Seleksi Donor</a>
            </p>
        @endif

        <p><a href="{{ route('petugas.check-in.index') }}">Kembali ke Check-in Pendonor</a></p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

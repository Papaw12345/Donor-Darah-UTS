<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pendonor</title>
</head>
<body>
    <main>
        <h1>Dashboard Pendonor</h1>

        <section aria-labelledby="informasi-pendonor">
            <h2 id="informasi-pendonor">Informasi Pendonor</h2>

            <dl>
                <dt>Nama lengkap</dt>
                <dd>{{ $pendonor->nama_lengkap }}</dd>

                <dt>Email akun</dt>
                <dd>{{ $akun->email }}</dd>

                <dt>Nomor donor</dt>
                <dd>{{ $pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>

                <dt>Golongan darah</dt>
                <dd>
                    @if ($pendonor->golonganDarah)
                        {{ $pendonor->golonganDarah->abo }} {{ $pendonor->golonganDarah->rhesus === 'POSITIF' ? '+' : '-' }}
                    @else
                        Belum dikonfirmasi UDD
                    @endif
                </dd>
            </dl>
        </section>

        <section aria-labelledby="pemesanan-aktif">
            <h2 id="pemesanan-aktif">Pemesanan Aktif</h2>

            @if ($pemesananAktif->isEmpty())
                <p>Tidak ada pemesanan donor aktif.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Pelayanan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pemesananAktif as $pemesanan)
                            <tr>
                                <td>{{ $pemesanan->jadwalPelayanan->tanggal->format('d-m-Y') }}</td>
                                <td>
                                    {{ substr($pemesanan->jadwalPelayanan->jam_mulai, 0, 5) }}
                                    -
                                    {{ substr($pemesanan->jadwalPelayanan->jam_selesai, 0, 5) }}
                                </td>
                                <td>{{ $pemesanan->status_pemesanan }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <p>
                <a href="{{ route('pendonor.pemesanan.index') }}">Lihat Semua Pemesanan</a>
            </p>
        </section>

        <section aria-labelledby="informasi-donor-berikutnya">
            <h2 id="informasi-donor-berikutnya">Informasi Donor Berikutnya</h2>

            @if ($informasiDonorBerikutnya['donor_pertama'])
                <p>Anda belum memiliki riwayat donor berhasil. Berdasarkan riwayat, Anda dapat mencoba donor pertama sekarang.</p>
            @elseif ($informasiDonorBerikutnya['dapat_mencoba_sekarang'])
                <p>Berdasarkan riwayat donor berhasil, Anda sudah dapat mencoba donor kembali.</p>
            @else
                <p>Perkiraan paling awal untuk mencoba donor kembali: {{ $informasiDonorBerikutnya['tanggal_donor_berikutnya']->format('d-m-Y') }}.</p>
            @endif

            <p>
                <a href="{{ route('pendonor.donor-berikutnya.index') }}">Lihat Informasi Donor Berikutnya</a>
            </p>
        </section>

        <section aria-labelledby="pemberitahuan-terbaru">
            <h2 id="pemberitahuan-terbaru">Pemberitahuan</h2>

            <p>Belum dibaca: {{ $jumlahPemberitahuanBelumDibaca }}</p>

            @if ($pemberitahuanTerbaru->isEmpty())
                <p>Belum ada pemberitahuan.</p>
            @else
                <ul>
                    @foreach ($pemberitahuanTerbaru as $item)
                        <li>
                            {{ $item->waktu_dibuat->format('d-m-Y H:i') }}
                            - {{ $item->petugasPengirim?->nama_petugas ?? '-' }}
                            - {{ $item->isi_pesan }}
                            - {{ $item->waktu_dibaca === null ? 'Belum dibaca' : 'Sudah dibaca' }}
                            - <a href="{{ route('pendonor.pemberitahuan.show', $item) }}">Lihat</a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p>
                <a href="{{ route('pendonor.pemberitahuan.index') }}">Lihat Semua Pemberitahuan</a>
            </p>
        </section>

        <p>
            <a href="{{ route('pendonor.profil.show') }}">Profil Saya</a>
        </p>

        <p>
            <a href="{{ route('pendonor.jadwal.index') }}">Jadwal Donor</a>
        </p>

        <p>
            <a href="{{ route('pendonor.pemesanan.index') }}">Pemesanan Donor Saya</a>
        </p>

        <p>
            <a href="{{ route('pendonor.riwayat.index') }}">Riwayat Donor</a>
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

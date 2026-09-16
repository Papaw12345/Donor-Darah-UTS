<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Pendonor</title>
</head>
<body>
    <main>
        <h1>Check-in Pendonor</h1>
        <p>Petugas: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p>

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        @if ($errors->has('kode_checkin'))
            <p role="alert">{{ $errors->first('kode_checkin') }}</p>
        @endif

        @if ($lookupError !== null)
            <p role="alert">{{ $lookupError }}</p>
        @endif

        <form method="GET" action="{{ route('petugas.check-in.index') }}">
            <label for="kode_checkin">Kode check-in</label>
            <input
                id="kode_checkin"
                name="kode_checkin"
                type="text"
                value="{{ $normalizedCode ?? '' }}"
                autocomplete="off"
                required
            >
            <button type="submit">Cari Pemesanan</button>
        </form>

        @if ($pemesanan !== null)
            <section aria-labelledby="hasil-lookup">
                <h2 id="hasil-lookup">Data Kunjungan</h2>

                <dl>
                    <dt>Nama Pendonor</dt>
                    <dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd>

                    <dt>Nomor Donor</dt>
                    <dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>

                    <dt>Tanggal Jadwal</dt>
                    <dd>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</dd>

                    <dt>Waktu Jadwal</dt>
                    <dd>{{ $pemesanan->jadwalPelayanan->jam_mulai }} - {{ $pemesanan->jadwalPelayanan->jam_selesai }}</dd>

                    <dt>Status Jadwal</dt>
                    <dd>{{ $pemesanan->jadwalPelayanan->status_jadwal }}</dd>

                    <dt>Status Pemesanan</dt>
                    <dd>{{ $pemesanan->status_pemesanan }}</dd>

                    <dt>Kode Check-in</dt>
                    <dd>{{ $pemesanan->kode_checkin }}</dd>

                    <dt>Kuesioner Pradonasi</dt>
                    <dd>{{ $pemesanan->kuesionerPradonasi !== null ? 'Tersedia' : 'Belum tersedia' }}</dd>
                </dl>

                @if ($canCheckIn)
                    <form method="POST" action="{{ route('petugas.check-in.store') }}">
                        @csrf
                        <input type="hidden" name="kode_checkin" value="{{ $normalizedCode }}">
                        <button type="submit">Konfirmasi Check-in</button>
                    </form>
                @else
                    <p role="status">{{ $eligibilityMessage }}</p>
                @endif

                @if (
                    $pemesanan->status_pemesanan === 'CHECK_IN'
                    && $pemesanan->waktu_checkin !== null
                    && $pemesanan->kuesionerPradonasi !== null
                )
                    <p>
                        <a href="{{ route('petugas.kuesioner.show', $pemesanan) }}">Lihat Kuesioner</a>
                    </p>
                @endif
            </section>
        @endif

        <p><a href="{{ route('petugas.home') }}">Kembali ke Dashboard Petugas</a></p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

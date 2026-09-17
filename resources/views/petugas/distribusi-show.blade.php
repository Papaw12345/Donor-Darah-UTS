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

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        <dl>
            <dt>Nomor Unit</dt>
            <dd>{{ $unit->nomor_unit }}</dd>

            <dt>Jenis Komponen</dt>
            <dd>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</dd>

            <dt>Golongan Darah</dt>
            <dd>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</dd>

            <dt>Tanggal Pembuatan</dt>
            <dd>{{ $unit->tanggal_pembuatan->toDateString() }}</dd>

            <dt>Tanggal Kedaluwarsa</dt>
            <dd>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</dd>

            <dt>Status</dt>
            <dd>{{ $unit->status_unit }}</dd>

            @if ($unit->waktu_distribusi)
                <dt>Waktu Distribusi</dt>
                <dd>{{ $unit->waktu_distribusi }}</dd>
            @endif
        </dl>

        @if ($eligible)
            <form method="POST" action="{{ route('petugas.distribusi.store', $unit) }}">
                @csrf
                <p>Unit ini masih tersedia dan belum kedaluwarsa pada tanggal acuan WIB {{ $tanggalAcuan }}.</p>
                <button type="submit">Distribusikan Unit</button>
            </form>
        @else
            <p>Riwayat distribusi hanya-baca. Unit ini tidak dapat didistribusikan dari status dan tanggal saat ini.</p>
        @endif

        <p><a href="{{ route('petugas.distribusi.index') }}">Kembali ke Distribusi</a></p>
    </main>
</body>
</html>

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

        <p>
            <a href="{{ route('pendonor.profil.show') }}">Profil Saya</a>
        </p>

        <p>
            <a href="{{ route('pendonor.jadwal.index') }}">Jadwal Donor</a>
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
</head>
<body>
    <main>
        <h1>Dashboard Admin</h1>
        <p>Akun: {{ $email }}</p>

        <nav aria-label="Navigasi Admin">
            <a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a>
            <a href="{{ route('admin.jadwal.index') }}">Jadwal Pelayanan</a>
            <a href="{{ route('admin.pertanyaan.index') }}">Pertanyaan Kuesioner</a>
            <a href="{{ route('admin.ambang.index') }}">Ambang Persediaan</a>
        </nav>

        <section aria-labelledby="ringkasan-administrasi">
            <h2 id="ringkasan-administrasi">Ringkasan Administrasi</h2>

            <dl>
                <dt>Petugas aktif</dt>
                <dd>{{ $activePetugasCount }}</dd>

                <dt>Jadwal dibuka untuk hari ini dan mendatang</dt>
                <dd>{{ $openUpcomingScheduleCount }}</dd>

                <dt>Pertanyaan kuesioner aktif</dt>
                <dd>{{ $activeQuestionCount }}</dd>

                <dt>Kombinasi ambang persediaan terkonfigurasi</dt>
                <dd>{{ $configuredThresholdCount }} dari {{ $expectedThresholdCount }}</dd>

                <dt>Kombinasi ambang persediaan belum dikonfigurasi</dt>
                <dd>{{ $missingThresholdCount }}</dd>
            </dl>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

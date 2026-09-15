<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>
</head>
<body>
    <main>
        <h1>Profil Saya</h1>

        <p>
            <a href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </p>

        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section aria-labelledby="data-tetap">
            <h2 id="data-tetap">Data Identitas</h2>

            <dl>
                <dt>Email akun</dt>
                <dd>{{ $akun->email }}</dd>

                <dt>NIK</dt>
                <dd>{{ $pendonor->nik }}</dd>

                <dt>Nomor donor</dt>
                <dd>{{ $pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>

                <dt>Jenis kelamin</dt>
                <dd>
                    {{ $pendonor->jenis_kelamin === 'LAKI_LAKI' ? 'Laki-laki' : 'Perempuan' }}
                </dd>

                <dt>Tanggal lahir</dt>
                <dd>{{ $pendonor->tanggal_lahir->format('d-m-Y') }}</dd>

                <dt>Golongan darah</dt>
                <dd>
                    @if ($pendonor->golonganDarah)
                        {{ $pendonor->golonganDarah->abo }}
                        {{ $pendonor->golonganDarah->rhesus === 'POSITIF' ? '+' : '-' }}
                    @else
                        Belum dikonfirmasi UDD
                    @endif
                </dd>
            </dl>
        </section>

        <section aria-labelledby="data-dapat-diubah">
            <h2 id="data-dapat-diubah">Data yang Dapat Diperbarui</h2>

            <form method="POST" action="{{ route('pendonor.profil.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input
                        id="nama_lengkap"
                        type="text"
                        name="nama_lengkap"
                        value="{{ old('nama_lengkap', $pendonor->nama_lengkap) }}"
                        maxlength="150"
                        required
                    >
                </div>

                <div>
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input
                        id="tempat_lahir"
                        type="text"
                        name="tempat_lahir"
                        value="{{ old('tempat_lahir', $pendonor->tempat_lahir) }}"
                        maxlength="100"
                        required
                    >
                </div>

                <div>
                    <label for="alamat">Alamat</label>
                    <textarea
                        id="alamat"
                        name="alamat"
                        required
                    >{{ old('alamat', $pendonor->alamat) }}</textarea>
                </div>

                <div>
                    <label for="nomor_telepon">Nomor Telepon</label>
                    <input
                        id="nomor_telepon"
                        type="text"
                        name="nomor_telepon"
                        value="{{ old('nomor_telepon', $pendonor->nomor_telepon) }}"
                        maxlength="20"
                        required
                    >
                </div>

                <div>
                    <label for="pekerjaan">Pekerjaan (opsional)</label>
                    <input
                        id="pekerjaan"
                        type="text"
                        name="pekerjaan"
                        value="{{ old('pekerjaan', $pendonor->pekerjaan) }}"
                        maxlength="100"
                    >
                </div>

                <div>
                    <label for="alamat_kantor">Alamat Kantor (opsional)</label>
                    <textarea
                        id="alamat_kantor"
                        name="alamat_kantor"
                    >{{ old('alamat_kantor', $pendonor->alamat_kantor) }}</textarea>
                </div>

                <button type="submit">Simpan Perubahan</button>
            </form>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

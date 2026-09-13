<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pendonor</title>
</head>
<body>
    <main>
        <h1>Registrasi Pendonor</h1>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" required autofocus autocomplete="email">
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" minlength="8" maxlength="255" required autocomplete="new-password">
            </div>

            <div>
                <label for="password_confirmation">Konfirmasi Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" maxlength="255" required autocomplete="new-password">
            </div>

            <div>
                <label for="nik">NIK</label>
                <input id="nik" type="text" name="nik" value="{{ old('nik') }}" maxlength="20" required>
            </div>

            <div>
                <label for="nomor_donor">Nomor Donor (opsional)</label>
                <input id="nomor_donor" type="text" name="nomor_donor" value="{{ old('nomor_donor') }}" maxlength="50">
            </div>

            <div>
                <label for="nama_lengkap">Nama Lengkap</label>
                <input id="nama_lengkap" type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" maxlength="150" required autocomplete="name">
            </div>

            <div>
                <label for="jenis_kelamin">Jenis Kelamin</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">Pilih jenis kelamin</option>
                    <option value="LAKI_LAKI" @selected(old('jenis_kelamin') === 'LAKI_LAKI')>Laki-laki</option>
                    <option value="PEREMPUAN" @selected(old('jenis_kelamin') === 'PEREMPUAN')>Perempuan</option>
                </select>
            </div>

            <div>
                <label for="tanggal_lahir">Tanggal Lahir</label>
                <input id="tanggal_lahir" type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required>
            </div>

            <div>
                <label for="tempat_lahir">Tempat Lahir</label>
                <input id="tempat_lahir" type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}" maxlength="100" required>
            </div>

            <div>
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat" required>{{ old('alamat') }}</textarea>
            </div>

            <div>
                <label for="nomor_telepon">Nomor Telepon</label>
                <input id="nomor_telepon" type="text" name="nomor_telepon" value="{{ old('nomor_telepon') }}" maxlength="20" required autocomplete="tel">
            </div>

            <div>
                <label for="pekerjaan">Pekerjaan (opsional)</label>
                <input id="pekerjaan" type="text" name="pekerjaan" value="{{ old('pekerjaan') }}" maxlength="100">
            </div>

            <div>
                <label for="alamat_kantor">Alamat Kantor (opsional)</label>
                <textarea id="alamat_kantor" name="alamat_kantor">{{ old('alamat_kantor') }}</textarea>
            </div>

            <button type="submit">Daftar</button>
        </form>

        <p>
            Sudah memiliki akun?
            <a href="{{ route('login') }}">Kembali ke login</a>
        </p>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Petugas</title>
</head>
<body>
    <main>
        <h1>Tambah Petugas</h1>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.petugas.store') }}">
            @csrf

            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required>
            </div>

            <div>
                <label for="password_confirmation">Konfirmasi Password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div>
                <label for="nomor_petugas">Nomor Petugas</label>
                <input
                    id="nomor_petugas"
                    type="text"
                    name="nomor_petugas"
                    value="{{ old('nomor_petugas') }}"
                    maxlength="50"
                    required
                >
            </div>

            <div>
                <label for="nama_petugas">Nama Petugas</label>
                <input
                    id="nama_petugas"
                    type="text"
                    name="nama_petugas"
                    value="{{ old('nama_petugas') }}"
                    maxlength="150"
                    required
                >
            </div>

            <button type="submit">Simpan Petugas</button>
        </form>

        <a href="{{ route('admin.petugas.index') }}">Batal</a>
    </main>
</body>
</html>

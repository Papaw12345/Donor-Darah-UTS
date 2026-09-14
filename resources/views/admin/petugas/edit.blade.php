<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Petugas</title>
</head>
<body>
    <main>
        <h1>Edit Petugas</h1>
        <p>Status akun: {{ $akun->status_akun }}</p>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.petugas.update', $petugas) }}">
            @csrf
            @method('PUT')

            <div>
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $akun->email) }}"
                    required
                >
            </div>

            <div>
                <label for="nomor_petugas">Nomor Petugas</label>
                <input
                    id="nomor_petugas"
                    type="text"
                    name="nomor_petugas"
                    value="{{ old('nomor_petugas', $petugas->nomor_petugas) }}"
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
                    value="{{ old('nama_petugas', $petugas->nama_petugas) }}"
                    maxlength="150"
                    required
                >
            </div>

            <button type="submit">Perbarui Petugas</button>
        </form>

        <a href="{{ route('admin.petugas.index') }}">Batal</a>
    </main>
</body>
</html>

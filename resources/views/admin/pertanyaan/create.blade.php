<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pertanyaan Kuesioner</title>
</head>
<body>
    <main>
        <h1>Tambah Pertanyaan Kuesioner</h1>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pertanyaan.store') }}">
            @csrf

            <div>
                <label for="teks_pertanyaan">Pertanyaan</label>
                <textarea id="teks_pertanyaan" name="teks_pertanyaan" required>{{ old('teks_pertanyaan') }}</textarea>
            </div>

            <div>
                <label for="kategori">Kategori</label>
                <input id="kategori" type="text" name="kategori" value="{{ old('kategori') }}" maxlength="100">
            </div>

            <div>
                <label for="jenis_jawaban">Jenis Jawaban</label>
                <select id="jenis_jawaban" name="jenis_jawaban" required>
                    @foreach (['YA_TIDAK', 'TEKS'] as $jenis)
                        <option value="{{ $jenis }}" @selected(old('jenis_jawaban') === $jenis)>
                            {{ $jenis }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="urutan">Urutan</label>
                <input id="urutan" type="number" name="urutan" value="{{ old('urutan') }}" required>
            </div>

            <div>
                <label for="status_aktif">Status</label>
                <select id="status_aktif" name="status_aktif" required>
                    <option value="1" @selected((string) old('status_aktif', '1') === '1')>Aktif</option>
                    <option value="0" @selected((string) old('status_aktif', '1') === '0')>Nonaktif</option>
                </select>
            </div>

            <button type="submit">Simpan Pertanyaan</button>
        </form>

        <a href="{{ route('admin.pertanyaan.index') }}">Batal</a>
    </main>
</body>
</html>

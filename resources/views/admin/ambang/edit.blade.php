<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ambang Persediaan</title>
</head>
<body>
    <main>
        <h1>Edit Ambang Persediaan</h1>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <dl>
            <dt>Jenis Komponen</dt>
            <dd>
                {{ $ambang->jenisKomponenDarah->kode_komponen }}
                - {{ $ambang->jenisKomponenDarah->nama_komponen }}
            </dd>

            <dt>Golongan Darah</dt>
            <dd>
                {{ $ambang->golonganDarah->abo }}
                {{ ucfirst(strtolower($ambang->golonganDarah->rhesus)) }}
            </dd>
        </dl>

        <form method="POST" action="{{ route('admin.ambang.update', $ambang) }}">
            @csrf
            @method('PUT')

            <div>
                <label for="jumlah_minimum">Jumlah Minimum</label>
                <input
                    id="jumlah_minimum"
                    type="number"
                    name="jumlah_minimum"
                    value="{{ old('jumlah_minimum', $ambang->jumlah_minimum) }}"
                    min="0"
                    required
                >
            </div>

            <button type="submit">Perbarui Ambang</button>
        </form>

        <a href="{{ route('admin.ambang.index') }}">Batal</a>
    </main>
</body>
</html>

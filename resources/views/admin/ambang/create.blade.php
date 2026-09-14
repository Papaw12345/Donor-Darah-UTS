<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ambang Persediaan</title>
</head>
<body>
    <main>
        <h1>Tambah Ambang Persediaan</h1>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.ambang.store') }}">
            @csrf

            <div>
                <label for="id_jenis_komponen">Jenis Komponen</label>
                <select id="id_jenis_komponen" name="id_jenis_komponen" required>
                    <option value="">Pilih jenis komponen</option>
                    @foreach ($jenisKomponen as $jenis)
                        <option
                            value="{{ $jenis->id_jenis_komponen }}"
                            @selected((string) old('id_jenis_komponen') === (string) $jenis->id_jenis_komponen)
                        >
                            {{ $jenis->kode_komponen }} - {{ $jenis->nama_komponen }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="id_golongan_darah">Golongan Darah</label>
                <select id="id_golongan_darah" name="id_golongan_darah" required>
                    <option value="">Pilih golongan darah</option>
                    @foreach ($golonganDarah as $golongan)
                        <option
                            value="{{ $golongan->id_golongan_darah }}"
                            @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)
                        >
                            {{ $golongan->abo }} {{ ucfirst(strtolower($golongan->rhesus)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="jumlah_minimum">Jumlah Minimum</label>
                <input
                    id="jumlah_minimum"
                    type="number"
                    name="jumlah_minimum"
                    value="{{ old('jumlah_minimum') }}"
                    min="0"
                    required
                >
            </div>

            <button type="submit">Simpan Ambang</button>
        </form>

        <a href="{{ route('admin.ambang.index') }}">Batal</a>
    </main>
</body>
</html>

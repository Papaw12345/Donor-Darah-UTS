<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jadwal Pelayanan</title>
</head>
<body>
    <main>
        <h1>Tambah Jadwal Pelayanan</h1>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.jadwal.store') }}">
            @csrf

            <div>
                <label for="tanggal">Tanggal</label>
                <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal') }}" required>
            </div>

            <div>
                <label for="jam_mulai">Jam Mulai</label>
                <input id="jam_mulai" type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required>
            </div>

            <div>
                <label for="jam_selesai">Jam Selesai</label>
                <input id="jam_selesai" type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required>
            </div>

            <div>
                <label for="kapasitas">Kapasitas</label>
                <input
                    id="kapasitas"
                    type="number"
                    name="kapasitas"
                    value="{{ old('kapasitas') }}"
                    min="1"
                    required
                >
            </div>

            <div>
                <label for="status_jadwal">Status</label>
                <select id="status_jadwal" name="status_jadwal" required>
                    @foreach (['DIBUKA', 'DITUTUP', 'DIBATALKAN'] as $status)
                        <option value="{{ $status }}" @selected(old('status_jadwal', 'DIBUKA') === $status)>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit">Simpan Jadwal</button>
        </form>

        <a href="{{ route('admin.jadwal.index') }}">Batal</a>
    </main>
</body>
</html>

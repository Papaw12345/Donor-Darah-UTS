<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyumbangan</title>
</head>
<body>
    <main>
        <h1>Penyumbangan</h1>
        <p>Petugas login: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p>

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        <section aria-labelledby="konteks-penyumbangan">
            <h2 id="konteks-penyumbangan">Konteks Penyumbangan</h2>
            <dl>
                <dt>Nama Pendonor</dt>
                <dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd>

                <dt>Nomor Donor</dt>
                <dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>

                <dt>ID Seleksi</dt>
                <dd>{{ $seleksi->id_seleksi }}</dd>

                <dt>ID Pemesanan</dt>
                <dd>{{ $pemesanan->id_pemesanan }}</dd>

                <dt>Status Pemesanan</dt>
                <dd>{{ $pemesanan->status_pemesanan }}</dd>

                <dt>Waktu Check-in</dt>
                <dd>{{ $pemesanan->waktu_checkin?->format('Y-m-d H:i:s') ?? 'Belum tersedia' }}</dd>

                <dt>Keputusan Seleksi</dt>
                <dd>{{ $seleksi->keputusan_seleksi }}</dd>

                <dt>Berat Badan Seleksi</dt>
                <dd>{{ $seleksi->berat_badan }} kg</dd>
            </dl>
        </section>

        @if ($penyumbangan === null)
            <section aria-labelledby="form-penyumbangan">
                <h2 id="form-penyumbangan">Form Penyumbangan</h2>

                @if ($errors->any())
                    <div role="alert">
                        <p>Periksa kembali data penyumbangan.</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('petugas.penyumbangan.store', $seleksi) }}">
                    @csrf

                    <div>
                        <label for="waktu_pengambilan">Waktu pengambilan</label>
                        <input id="waktu_pengambilan" name="waktu_pengambilan" type="datetime-local" value="{{ old('waktu_pengambilan') }}" required>
                    </div>

                    <div>
                        <label for="volume_ml">Volume (mL)</label>
                        <select id="volume_ml" name="volume_ml">
                            <option value="">Tidak dicatat</option>
                            @foreach ([350, 450] as $volume)
                                <option value="{{ $volume }}" @selected((string) old('volume_ml') === (string) $volume)>{{ $volume }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="hasil_penyumbangan">Hasil penyumbangan</label>
                        <select id="hasil_penyumbangan" name="hasil_penyumbangan" required>
                            <option value="">Pilih hasil</option>
                            @foreach (['BERHASIL', 'GAGAL'] as $hasil)
                                <option value="{{ $hasil }}" @selected(old('hasil_penyumbangan') === $hasil)>{{ $hasil }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="alasan_gagal">Alasan gagal</label>
                        <textarea id="alasan_gagal" name="alasan_gagal">{{ old('alasan_gagal') }}</textarea>
                    </div>

                    <button type="submit">Simpan Penyumbangan</button>
                </form>
            </section>
        @else
            <section aria-labelledby="penyumbangan-tersimpan">
                <h2 id="penyumbangan-tersimpan">Penyumbangan Tersimpan</h2>
                <dl>
                    <dt>Waktu Pengambilan</dt>
                    <dd>{{ $penyumbangan->waktu_pengambilan->format('Y-m-d H:i:s') }}</dd>

                    <dt>Volume</dt>
                    <dd>{{ $penyumbangan->volume_ml === null ? '-' : $penyumbangan->volume_ml . ' mL' }}</dd>

                    <dt>Hasil Penyumbangan</dt>
                    <dd>{{ $penyumbangan->hasil_penyumbangan }}</dd>

                    <dt>Alasan Gagal</dt>
                    <dd>{{ $penyumbangan->alasan_gagal ?? '-' }}</dd>

                    <dt>Petugas Pencatat</dt>
                    <dd>
                        @if ($penyumbangan->petugasPencatat !== null)
                            {{ $penyumbangan->petugasPencatat->nama_petugas }} ({{ $penyumbangan->petugasPencatat->nomor_petugas }})
                        @else
                            Tidak tersedia
                        @endif
                    </dd>
                </dl>
                @if ($penyumbangan->hasil_penyumbangan === 'BERHASIL')
                    <p><a href="{{ route('petugas.unit-komponen.show', $penyumbangan) }}">Unit Komponen Darah</a></p>
                @endif
            </section>
        @endif

        <p><a href="{{ route('petugas.seleksi.show', $pemesanan) }}">Kembali ke Seleksi</a></p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

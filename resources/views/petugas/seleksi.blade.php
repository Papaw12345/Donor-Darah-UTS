<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleksi Donor</title>
</head>
<body>
    <main>
        <h1>Seleksi Donor</h1>
        <p>Petugas login: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p>

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        <section aria-labelledby="konteks-kunjungan">
            <h2 id="konteks-kunjungan">Konteks Kunjungan</h2>

            <dl>
                <dt>Nama Pendonor</dt>
                <dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd>

                <dt>Nomor Donor</dt>
                <dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>

                <dt>ID Pemesanan</dt>
                <dd>{{ $pemesanan->id_pemesanan }}</dd>

                <dt>Tanggal Jadwal</dt>
                <dd>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</dd>

                <dt>Waktu Jadwal</dt>
                <dd>{{ $pemesanan->jadwalPelayanan->jam_mulai }} - {{ $pemesanan->jadwalPelayanan->jam_selesai }}</dd>

                <dt>Status Jadwal</dt>
                <dd>{{ $pemesanan->jadwalPelayanan->status_jadwal }}</dd>

                <dt>Waktu Check-in</dt>
                <dd>{{ $pemesanan->waktu_checkin?->format('Y-m-d H:i:s') ?? 'Belum tersedia' }}</dd>

                <dt>Golongan Darah</dt>
                <dd>
                    @if ($pemesanan->pendonor->golonganDarah !== null)
                        {{ $pemesanan->pendonor->golonganDarah->abo }} {{ $pemesanan->pendonor->golonganDarah->rhesus }}
                    @else
                        Belum terkonfirmasi
                    @endif
                </dd>

                <dt>Status Pemesanan</dt>
                <dd>{{ $pemesanan->status_pemesanan }}</dd>
            </dl>
        </section>

        @if ($seleksi === null)
            <section aria-labelledby="form-seleksi">
                <h2 id="form-seleksi">Form Seleksi Donor</h2>

                @if ($errors->any())
                    <div role="alert">
                        <p>Periksa kembali data seleksi.</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('petugas.seleksi.store', $pemesanan) }}">
                    @csrf

                    <div>
                        <label for="berat_badan">Berat badan</label>
                        <input id="berat_badan" name="berat_badan" type="number" step="0.01" value="{{ old('berat_badan') }}" required>
                    </div>

                    <div>
                        <label for="tekanan_sistolik">Tekanan sistolik</label>
                        <input id="tekanan_sistolik" name="tekanan_sistolik" type="number" step="1" value="{{ old('tekanan_sistolik') }}" required>
                    </div>

                    <div>
                        <label for="tekanan_diastolik">Tekanan diastolik</label>
                        <input id="tekanan_diastolik" name="tekanan_diastolik" type="number" step="1" value="{{ old('tekanan_diastolik') }}" required>
                    </div>

                    <div>
                        <label for="denyut_nadi">Denyut nadi</label>
                        <input id="denyut_nadi" name="denyut_nadi" type="number" step="1" value="{{ old('denyut_nadi') }}" required>
                    </div>

                    <div>
                        <label for="suhu_tubuh">Suhu tubuh</label>
                        <input id="suhu_tubuh" name="suhu_tubuh" type="number" step="0.1" value="{{ old('suhu_tubuh') }}" required>
                    </div>

                    <div>
                        <label for="kadar_hb">Kadar Hb</label>
                        <input id="kadar_hb" name="kadar_hb" type="number" step="0.1" value="{{ old('kadar_hb') }}" required>
                    </div>

                    <div>
                        <label for="hasil_pemeriksaan_kesehatan">Hasil pemeriksaan kesehatan</label>
                        <textarea id="hasil_pemeriksaan_kesehatan" name="hasil_pemeriksaan_kesehatan">{{ old('hasil_pemeriksaan_kesehatan') }}</textarea>
                    </div>

                    <div>
                        <label for="keputusan_seleksi">Keputusan seleksi</label>
                        <select id="keputusan_seleksi" name="keputusan_seleksi" required>
                            <option value="">Pilih keputusan</option>
                            @foreach (['LAYAK', 'DITUNDA', 'DITOLAK'] as $keputusan)
                                <option value="{{ $keputusan }}" @selected(old('keputusan_seleksi') === $keputusan)>{{ $keputusan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="alasan_keputusan">Alasan keputusan</label>
                        <textarea id="alasan_keputusan" name="alasan_keputusan">{{ old('alasan_keputusan') }}</textarea>
                    </div>

                    @if ($pemesanan->pendonor->id_golongan_darah === null)
                        <div>
                            <label for="id_golongan_darah">Golongan darah terkonfirmasi</label>
                            <select id="id_golongan_darah" name="id_golongan_darah">
                                <option value="">Belum dikonfirmasi</option>
                                @foreach ($golonganDarah as $golongan)
                                    <option value="{{ $golongan->id_golongan_darah }}" @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)>
                                        {{ $golongan->abo }} {{ $golongan->rhesus }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="submit">Simpan Seleksi</button>
                </form>
            </section>
        @else
            <section aria-labelledby="seleksi-tersimpan">
                <h2 id="seleksi-tersimpan">Seleksi Tersimpan</h2>

                <dl>
                    <dt>Berat Badan</dt>
                    <dd>{{ $seleksi->berat_badan }}</dd>

                    <dt>Tekanan Sistolik</dt>
                    <dd>{{ $seleksi->tekanan_sistolik }}</dd>

                    <dt>Tekanan Diastolik</dt>
                    <dd>{{ $seleksi->tekanan_diastolik }}</dd>

                    <dt>Denyut Nadi</dt>
                    <dd>{{ $seleksi->denyut_nadi }}</dd>

                    <dt>Suhu Tubuh</dt>
                    <dd>{{ $seleksi->suhu_tubuh }}</dd>

                    <dt>Kadar Hb</dt>
                    <dd>{{ $seleksi->kadar_hb }}</dd>

                    <dt>Hasil Pemeriksaan Kesehatan</dt>
                    <dd>{{ $seleksi->hasil_pemeriksaan_kesehatan ?? 'Tidak ada' }}</dd>

                    <dt>Keputusan Seleksi</dt>
                    <dd>{{ $seleksi->keputusan_seleksi }}</dd>

                    <dt>Alasan Keputusan</dt>
                    <dd>{{ $seleksi->alasan_keputusan ?? 'Tidak ada' }}</dd>

                    <dt>Petugas Pencatat</dt>
                    <dd>
                        @if ($seleksi->petugas !== null)
                            {{ $seleksi->petugas->nama_petugas }} ({{ $seleksi->petugas->nomor_petugas }})
                        @else
                            Tidak tersedia
                        @endif
                    </dd>

                    <dt>Waktu Seleksi</dt>
                    <dd>{{ $seleksi->waktu_seleksi->format('Y-m-d H:i:s') }}</dd>
                </dl>

                @if ($seleksi->keputusan_seleksi === 'LAYAK')
                    <p>
                        <a href="{{ route('petugas.penyumbangan.show', $seleksi) }}">
                            {{ $seleksi->penyumbangan === null ? 'Catat Penyumbangan' : 'Lihat Penyumbangan' }}
                        </a>
                    </p>
                @endif
            </section>
        @endif

        <p><a href="{{ route('petugas.kuesioner.show', $pemesanan) }}">Kembali ke Kuesioner</a></p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>

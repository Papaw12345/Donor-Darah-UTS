<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pemberitahuan</title>
</head>
<body>
    <main>
        <h1>Buat Pemberitahuan</h1>
        <p>Petugas: {{ $petugas->nama_petugas }}</p>

        <section aria-labelledby="konteks-persediaan">
            <h2 id="konteks-persediaan">Kondisi Persediaan Rendah</h2>
            <p>Komponen: {{ $ambang->jenisKomponenDarah->kode_komponen }} - {{ $ambang->jenisKomponenDarah->nama_komponen }}</p>
            <p>Golongan darah: {{ $ambang->golonganDarah->abo }} {{ $ambang->golonganDarah->rhesus }}</p>
            <p>Jumlah persediaan saat ini: {{ $jumlahPersediaan }}</p>
            <p>Jumlah minimum: {{ $ambang->jumlah_minimum }}</p>
        </section>

        <section aria-labelledby="target-pendonor">
            <h2 id="target-pendonor">Target Pendonor</h2>
            <p>Nomor donor: {{ $pendonor->nomor_donor }}</p>
            <p>Nama lengkap: {{ $pendonor->nama_lengkap }}</p>
            <p>Golongan darah: {{ $pendonor->golonganDarah->abo }} {{ $pendonor->golonganDarah->rhesus }}</p>
        </section>

        <form method="POST" action="{{ route('petugas.pemberitahuan.store', ['ambang' => $ambang, 'pendonor' => $pendonor]) }}">
            @csrf

            <div>
                <label for="isi_pesan">Isi Pesan</label>
                <textarea id="isi_pesan" name="isi_pesan">{{ old('isi_pesan') }}</textarea>
                @error('isi_pesan')
                    <p>{{ $message }}</p>
                @enderror
            </div>

            <button type="submit">Kirim Pemberitahuan</button>
        </form>

        <p>
            <a href="{{ route('petugas.pemanggilan.index', ['id_ambang' => $ambang->id_ambang]) }}">Kembali ke Kandidat Pendonor</a>
        </p>
    </main>
</body>
</html>

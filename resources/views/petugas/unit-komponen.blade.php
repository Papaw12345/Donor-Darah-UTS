<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Unit Komponen Darah</title></head>
<body>
<main>
    <h1>Unit Komponen Darah</h1>
    <p>Petugas login: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p>
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <section><h2>Konteks Penyumbangan</h2><dl>
        <dt>ID Penyumbangan</dt><dd>{{ $penyumbangan->id_penyumbangan }}</dd>
        <dt>Hasil Penyumbangan</dt><dd>{{ $penyumbangan->hasil_penyumbangan }}</dd>
        <dt>Waktu Pengambilan</dt><dd>{{ $penyumbangan->waktu_pengambilan->format('Y-m-d H:i:s') }}</dd>
        <dt>Nama Pendonor</dt><dd>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nama_lengkap }}</dd>
        <dt>Nomor Donor</dt><dd>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>
    </dl></section>
    <section><h2>Unit Tersimpan</h2>
        @if ($penyumbangan->unitKomponenDarah->isEmpty())<p>Belum ada unit komponen darah.</p>
        @else <table><thead><tr><th>Nomor Unit</th><th>Jenis Komponen</th><th>Golongan Darah</th><th>Tanggal Pembuatan</th><th>Tanggal Kedaluwarsa</th><th>Status</th><th>Petugas Pencatat</th></tr></thead><tbody>
        @foreach ($penyumbangan->unitKomponenDarah as $unit)<tr><td>{{ $unit->nomor_unit }}</td><td>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</td><td>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</td><td>{{ $unit->tanggal_pembuatan->toDateString() }}</td><td>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</td><td>{{ $unit->status_unit }}</td><td>{{ $unit->petugasPencatat->nama_petugas }} ({{ $unit->petugasPencatat->nomor_petugas }})</td></tr>@endforeach
        </tbody></table>@endif
    </section>
    <section><h2>Form Unit Komponen</h2>
        @if ($errors->any())<div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('petugas.unit-komponen.store', $penyumbangan) }}">@csrf
            <div><label for="nomor_unit">Nomor unit</label><input id="nomor_unit" name="nomor_unit" type="text" value="{{ old('nomor_unit') }}" required></div>
            <div><label for="id_jenis_komponen">Jenis komponen</label><select id="id_jenis_komponen" name="id_jenis_komponen" required><option value="">Pilih jenis</option>@foreach ($jenisKomponen as $jenis)<option value="{{ $jenis->id_jenis_komponen }}" @selected((string) old('id_jenis_komponen') === (string) $jenis->id_jenis_komponen)>{{ $jenis->kode_komponen }} - {{ $jenis->nama_komponen }}</option>@endforeach</select></div>
            <div><label for="id_golongan_darah">Golongan darah</label><select id="id_golongan_darah" name="id_golongan_darah" required><option value="">Pilih golongan</option>@foreach ($golonganDarah as $golongan)<option value="{{ $golongan->id_golongan_darah }}" @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)>{{ $golongan->abo }} {{ $golongan->rhesus }}</option>@endforeach</select></div>
            <div><label for="tanggal_pembuatan">Tanggal pembuatan</label><input id="tanggal_pembuatan" name="tanggal_pembuatan" type="date" value="{{ old('tanggal_pembuatan') }}" required></div>
            <div><label for="tanggal_kedaluwarsa">Tanggal kedaluwarsa</label><input id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa" type="date" value="{{ old('tanggal_kedaluwarsa') }}" required></div>
            <button type="submit">Simpan Unit Komponen</button>
        </form>
    </section>
    <p><a href="{{ route('petugas.penyumbangan.show', $penyumbangan->seleksiDonor) }}">Kembali ke Penyumbangan</a></p>
    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
</main>
</body></html>

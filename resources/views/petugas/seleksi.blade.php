@extends('layouts.app')

@section('title', 'Seleksi Donor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pelayanan Donor</p><h1 class="page-title">Seleksi Donor</h1><p class="page-description">Catat hasil pemeriksaan dan keputusan seleksi untuk kunjungan yang sudah check-in.</p></div><a class="button button-secondary" href="{{ route('petugas.kuesioner.show', $pemesanan) }}">Kembali ke Kuesioner</a></header>
        @include('partials.alerts')

        {{-- Data pemesanan --}}
        <section class="page-section" aria-labelledby="konteks-kunjungan">
            <div class="section-header"><div><h2 class="section-title" id="konteks-kunjungan">Konteks Kunjungan</h2><p class="section-description">Petugas login: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p></div></div>
            <dl class="info-grid">
                <div class="info-block"><dt>Nama Pendonor</dt><dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd></div><div class="info-block"><dt>Nomor Donor</dt><dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
                <div class="info-block"><dt>ID Pemesanan</dt><dd>{{ $pemesanan->id_pemesanan }}</dd></div><div class="info-block"><dt>Tanggal Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</dd></div>
                <div class="info-block"><dt>Waktu Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->jam_mulai }} - {{ $pemesanan->jadwalPelayanan->jam_selesai }}</dd></div><div class="info-block"><dt>Status Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->status_jadwal }}</dd></div>
                <div class="info-block"><dt>Waktu Check-in</dt><dd>{{ $pemesanan->waktu_checkin?->format('Y-m-d H:i:s') ?? 'Belum tersedia' }}</dd></div>
                <div class="info-block"><dt>Golongan Darah</dt><dd>@if ($pemesanan->pendonor->golonganDarah !== null){{ $pemesanan->pendonor->golonganDarah->abo }} {{ $pemesanan->pendonor->golonganDarah->rhesus }}@else Belum terkonfirmasi @endif</dd></div>
                <div class="info-block"><dt>Status Pemesanan</dt><dd><span class="status-badge status-success">{{ $pemesanan->status_pemesanan }}</span></dd></div>
            </dl>
        </section>

        @if ($seleksi === null)
            {{-- Seleksi donor --}}
            <section class="page-section" aria-labelledby="form-seleksi">
                <div class="section-header"><div><h2 class="section-title" id="form-seleksi">Form Seleksi Donor</h2><p class="section-description">Lengkapi hasil pemeriksaan dan keputusan seleksi.</p></div></div>
                <form class="form-panel" method="POST" action="{{ route('petugas.seleksi.store', $pemesanan) }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-field"><label for="berat_badan">Berat badan</label><input id="berat_badan" name="berat_badan" type="number" step="0.01" value="{{ old('berat_badan') }}" required></div>
                        <div class="form-field"><label for="tekanan_sistolik">Tekanan sistolik</label><input id="tekanan_sistolik" name="tekanan_sistolik" type="number" step="1" value="{{ old('tekanan_sistolik') }}" required></div>
                        <div class="form-field"><label for="tekanan_diastolik">Tekanan diastolik</label><input id="tekanan_diastolik" name="tekanan_diastolik" type="number" step="1" value="{{ old('tekanan_diastolik') }}" required></div>
                        <div class="form-field"><label for="denyut_nadi">Denyut nadi</label><input id="denyut_nadi" name="denyut_nadi" type="number" step="1" value="{{ old('denyut_nadi') }}" required></div>
                        <div class="form-field"><label for="suhu_tubuh">Suhu tubuh</label><input id="suhu_tubuh" name="suhu_tubuh" type="number" step="0.1" value="{{ old('suhu_tubuh') }}" required></div>
                        <div class="form-field"><label for="kadar_hb">Kadar Hb</label><input id="kadar_hb" name="kadar_hb" type="number" step="0.1" value="{{ old('kadar_hb') }}" required></div>
                        <div class="form-field form-field-full"><label for="hasil_pemeriksaan_kesehatan">Hasil pemeriksaan kesehatan</label><textarea id="hasil_pemeriksaan_kesehatan" name="hasil_pemeriksaan_kesehatan">{{ old('hasil_pemeriksaan_kesehatan') }}</textarea></div>
                        <div class="form-field"><label for="keputusan_seleksi">Keputusan seleksi</label><select id="keputusan_seleksi" name="keputusan_seleksi" required><option value="">Pilih keputusan</option>@foreach (['LAYAK', 'DITUNDA', 'DITOLAK'] as $keputusan)<option value="{{ $keputusan }}" @selected(old('keputusan_seleksi') === $keputusan)>{{ $keputusan }}</option>@endforeach</select></div>
                        <div class="form-field"><label for="alasan_keputusan">Alasan keputusan</label><textarea id="alasan_keputusan" name="alasan_keputusan">{{ old('alasan_keputusan') }}</textarea></div>
                        @if ($pemesanan->pendonor->id_golongan_darah === null)
                            <div class="form-field"><label for="id_golongan_darah">Golongan darah terkonfirmasi</label><select id="id_golongan_darah" name="id_golongan_darah"><option value="">Belum dikonfirmasi</option>@foreach ($golonganDarah as $golongan)<option value="{{ $golongan->id_golongan_darah }}" @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)>{{ $golongan->abo }} {{ $golongan->rhesus }}</option>@endforeach</select></div>
                        @endif
                    </div>
                    <div class="form-actions"><button class="button button-primary" type="submit">Simpan Seleksi</button></div>
                </form>
            </section>
        @else
            <section class="page-section" aria-labelledby="seleksi-tersimpan">
                <div class="section-header"><div><h2 class="section-title" id="seleksi-tersimpan">Seleksi Tersimpan</h2><p class="section-description">Hasil seleksi tersimpan ditampilkan sebagai riwayat pelayanan.</p></div></div>
                <dl class="info-grid">
                    <div class="info-block"><dt>Berat Badan</dt><dd>{{ $seleksi->berat_badan }}</dd></div><div class="info-block"><dt>Tekanan Sistolik</dt><dd>{{ $seleksi->tekanan_sistolik }}</dd></div>
                    <div class="info-block"><dt>Tekanan Diastolik</dt><dd>{{ $seleksi->tekanan_diastolik }}</dd></div><div class="info-block"><dt>Denyut Nadi</dt><dd>{{ $seleksi->denyut_nadi }}</dd></div>
                    <div class="info-block"><dt>Suhu Tubuh</dt><dd>{{ $seleksi->suhu_tubuh }}</dd></div><div class="info-block"><dt>Kadar Hb</dt><dd>{{ $seleksi->kadar_hb }}</dd></div>
                    <div class="info-block"><dt>Hasil Pemeriksaan Kesehatan</dt><dd>{{ $seleksi->hasil_pemeriksaan_kesehatan ?? 'Tidak ada' }}</dd></div><div class="info-block"><dt>Keputusan Seleksi</dt><dd><span class="status-badge {{ $seleksi->keputusan_seleksi === 'LAYAK' ? 'status-success' : ($seleksi->keputusan_seleksi === 'DITOLAK' ? 'status-danger' : 'status-warning') }}">{{ $seleksi->keputusan_seleksi }}</span></dd></div>
                    <div class="info-block"><dt>Alasan Keputusan</dt><dd>{{ $seleksi->alasan_keputusan ?? 'Tidak ada' }}</dd></div><div class="info-block"><dt>Petugas Pencatat</dt><dd>@if ($seleksi->petugas !== null){{ $seleksi->petugas->nama_petugas }} ({{ $seleksi->petugas->nomor_petugas }})@else Tidak tersedia @endif</dd></div>
                    <div class="info-block"><dt>Waktu Seleksi</dt><dd>{{ $seleksi->waktu_seleksi->format('Y-m-d H:i:s') }}</dd></div>
                </dl>
                @if ($seleksi->keputusan_seleksi === 'LAYAK')<div class="form-actions"><a class="button button-primary" href="{{ route('petugas.penyumbangan.show', $seleksi) }}">{{ $seleksi->penyumbangan === null ? 'Catat Penyumbangan' : 'Lihat Penyumbangan' }}</a></div>@endif
            </section>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Kuesioner Pradonasi | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main"><p class="eyebrow">Review Kuesioner</p><h1 class="page-title">Kuesioner Pradonasi Pendonor</h1><p class="page-description">Jawaban tersimpan ditampilkan sebagai informasi untuk proses seleksi donor.</p></div>
            <a class="button button-secondary" href="{{ route('petugas.check-in.index') }}">Kembali ke Check-in Pendonor</a>
        </header>
        @include('partials.alerts')

        {{-- Konteks kunjungan --}}
        <section class="page-section" aria-labelledby="konteks-kunjungan">
            <div class="section-header"><div><h2 class="section-title" id="konteks-kunjungan">Konteks Kunjungan</h2><p class="section-description">Petugas: {{ $petugas->nama_petugas }} ({{ $petugas->nomor_petugas }})</p></div></div>
            <dl class="info-grid">
                <div class="info-block"><dt>Nama Pendonor</dt><dd>{{ $pemesanan->pendonor->nama_lengkap }}</dd></div><div class="info-block"><dt>Nomor Donor</dt><dd>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd></div>
                <div class="info-block"><dt>ID Pemesanan</dt><dd>{{ $pemesanan->id_pemesanan }}</dd></div><div class="info-block"><dt>Tanggal Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</dd></div>
                <div class="info-block"><dt>Waktu Jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->jam_mulai }} - {{ $pemesanan->jadwalPelayanan->jam_selesai }}</dd></div><div class="info-block"><dt>Status Pemesanan</dt><dd><span class="status-badge status-success">{{ $pemesanan->status_pemesanan }}</span></dd></div>
                <div class="info-block"><dt>Kode Check-in</dt><dd class="code-value">{{ $pemesanan->kode_checkin }}</dd></div><div class="info-block"><dt>Waktu Check-in</dt><dd>{{ $pemesanan->waktu_checkin->format('Y-m-d H:i:s') }}</dd></div>
                <div class="info-block"><dt>Waktu Pengisian Kuesioner</dt><dd>{{ $kuesioner->waktu_pengisian->format('Y-m-d H:i:s') }}</dd></div>
            </dl>
        </section>

        {{-- Jawaban kuesioner --}}
        <section class="page-section" aria-labelledby="jawaban-kuesioner">
            <div class="section-header"><div><h2 class="section-title" id="jawaban-kuesioner">Jawaban Kuesioner Tersimpan</h2><p class="section-description">Riwayat jawaban ditampilkan tanpa perubahan atau interpretasi medis.</p></div></div>
            <div class="table-container"><table class="data-table"><thead><tr><th scope="col">Urutan</th><th scope="col">Pertanyaan</th><th scope="col">Kategori</th><th scope="col">Jenis Jawaban</th><th scope="col">Jawaban</th><th scope="col">Status Pertanyaan Saat Ini</th></tr></thead><tbody>
                @foreach ($jawaban as $item)<tr><td>{{ $item->pertanyaanKuesioner->urutan }}</td><td>{{ $item->pertanyaanKuesioner->teks_pertanyaan }}</td><td>{{ $item->pertanyaanKuesioner->kategori ?? 'Tidak ada' }}</td><td>{{ $item->pertanyaanKuesioner->jenis_jawaban }}</td><td>{{ $item->jawaban }}</td><td><span class="status-badge {{ $item->pertanyaanKuesioner->status_aktif ? 'status-success' : 'status-neutral' }}">{{ $item->pertanyaanKuesioner->status_aktif ? 'AKTIF' : 'NONAKTIF' }}</span></td></tr>@endforeach
            </tbody></table></div>
        </section>

        <div class="action-panel"><p class="section-description">Lanjutkan ke seleksi sesuai state pemesanan yang tersimpan.</p><div class="action-group">
            @if ($pemesanan->seleksiDonor !== null)<a class="button button-primary" href="{{ route('petugas.seleksi.show', $pemesanan) }}">Lihat Seleksi</a>
            @elseif ($pemesanan->status_pemesanan === 'CHECK_IN' && $pemesanan->waktu_checkin !== null)<a class="button button-primary" href="{{ route('petugas.seleksi.show', $pemesanan) }}">Seleksi Donor</a>@endif
        </div></div>
    </div>
@endsection

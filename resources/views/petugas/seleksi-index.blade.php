@extends('layouts.app')

@section('title', 'Queue Seleksi Donor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><h1 class="page-title">Seleksi Donor</h1><p>Pemesanan yang sudah check-in dan menunggu seleksi.</p></div></header>
        @include('partials.alerts')
        <section class="page-section" aria-label="Daftar pemesanan menunggu seleksi">
            @if ($pemesananMenunggu->isEmpty())
                <div class="empty-state">Tidak ada pemesanan yang menunggu seleksi.</div>
            @else
                <div class="table-container"><table class="data-table">
                    <thead><tr><th scope="col">Pendonor</th><th scope="col">Nomor Donor</th><th scope="col">Jadwal</th><th scope="col">Waktu Check-in</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($pemesananMenunggu as $pemesanan)
                            <tr>
                                <td>{{ $pemesanan->pendonor->nama_lengkap }}</td>
                                <td>{{ $pemesanan->pendonor->nomor_donor ?? 'Belum tersedia' }}</td>
                                <td>{{ $pemesanan->jadwalPelayanan->tanggal->toDateString() }}</td>
                                <td>{{ $pemesanan->waktu_checkin->format('Y-m-d H:i:s') }}</td>
                                <td><a class="button button-secondary button-small" href="{{ route('petugas.seleksi.show', $pemesanan) }}">Buka Seleksi</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>
    </div>
@endsection

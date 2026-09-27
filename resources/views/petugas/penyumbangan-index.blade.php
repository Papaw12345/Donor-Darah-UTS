@extends('layouts.app')

@section('title', 'Penyumbangan | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><h1 class="page-title">Penyumbangan</h1><p>Seleksi dengan keputusan Layak yang menunggu pencatatan penyumbangan.</p></div></header>
        @include('partials.alerts')
        <section class="page-section" aria-label="Daftar seleksi menunggu penyumbangan">
            @if ($seleksiMenunggu->isEmpty())
                <div class="empty-state">Tidak ada seleksi yang menunggu penyumbangan.</div>
            @else
                <div class="table-container"><table class="data-table">
                    <thead><tr><th scope="col">Pendonor</th><th scope="col">Nomor Donor</th><th scope="col">Jadwal</th><th scope="col">Waktu Seleksi</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($seleksiMenunggu as $seleksi)
                            <tr>
                                <td>{{ $seleksi->pemesananDonor->pendonor->nama_lengkap }}</td>
                                <td>{{ $seleksi->pemesananDonor->pendonor->nomor_donor ?? 'Belum tersedia' }}</td>
                                <td>{{ $seleksi->pemesananDonor->jadwalPelayanan->tanggal->toDateString() }}</td>
                                <td>{{ $seleksi->waktu_seleksi->format('Y-m-d H:i:s') }}</td>
                                <td><a class="button button-secondary button-small" href="{{ route('petugas.penyumbangan.show', $seleksi) }}">Buka Penyumbangan</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>
    </div>
@endsection

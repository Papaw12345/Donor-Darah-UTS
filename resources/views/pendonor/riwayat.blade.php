@extends('layouts.app')

@section('title', 'Riwayat Donor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Riwayat Penyumbangan</p>
                <h1 class="page-title">Riwayat Donor</h1>
                <p class="page-description">Daftar transaksi penyumbangan yang telah dicatat oleh Petugas UDD.</p>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </header>

        {{-- Riwayat donor --}}
        <section class="page-section" aria-labelledby="daftar-riwayat">
            <div class="section-header">
                <div><h2 class="section-title" id="daftar-riwayat">Daftar Penyumbangan</h2><p class="section-description">Riwayat ini bersifat hanya-baca.</p></div>
            </div>
            @if ($riwayat->isEmpty())
                <div class="empty-state">Belum ada riwayat penyumbangan.</div>
            @else
                <div class="table-container">
                    <table class="data-table table-compact">
                        <thead><tr><th scope="col">Waktu Pengambilan</th><th scope="col">Volume</th><th scope="col">Hasil</th><th scope="col">Alasan Gagal</th></tr></thead>
                        <tbody>
                            @foreach ($riwayat as $penyumbangan)
                                <tr>
                                    <td>{{ $penyumbangan->waktu_pengambilan->format('d-m-Y H:i') }}</td>
                                    <td>{{ $penyumbangan->volume_ml !== null ? $penyumbangan->volume_ml.' mL' : '-' }}</td>
                                    <td><span class="status-badge {{ $penyumbangan->hasil_penyumbangan === 'BERHASIL' ? 'status-success' : 'status-danger' }}">{{ $penyumbangan->hasil_penyumbangan }}</span></td>
                                    <td>{{ $penyumbangan->alasan_gagal ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

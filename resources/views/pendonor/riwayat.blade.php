@extends('layouts.app')

@section('title', 'Riwayat Donor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Riwayat Donor</h1>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </header>

        {{-- Riwayat donor --}}
        <div class="page-section">
            @if ($riwayat->isEmpty())
                <div class="empty-state">Belum ada riwayat donor.</div>
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
        </div>
    </div>
@endsection

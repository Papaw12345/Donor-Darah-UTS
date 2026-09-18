@extends('layouts.app')

@section('title', 'Jadwal Donor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Jadwal Donor</h1>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.pemesanan.index') }}">Lihat Pemesanan Saya</a>
        </header>

        @include('partials.alerts')

        {{-- Jadwal donor --}}
        <div class="page-section">
            @if ($jadwal->isEmpty())
                <div class="empty-state">Belum ada jadwal yang tersedia.</div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th scope="col">Tanggal</th><th scope="col">Jam Mulai</th><th scope="col">Jam Selesai</th><th scope="col">Kapasitas</th><th scope="col">Sisa Kapasitas</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($jadwal as $item)
                                <tr>
                                    <td>{{ $item->tanggal->format('d-m-Y') }}</td>
                                    <td>{{ substr($item->jam_mulai, 0, 5) }}</td>
                                    <td>{{ substr($item->jam_selesai, 0, 5) }}</td>
                                    <td>{{ $item->kapasitas }}</td>
                                    <td>{{ $item->kapasitas - $item->jumlah_pemesanan_berlaku }}</td>
                                    <td><span class="status-badge status-success">{{ $item->status_jadwal }}</span></td>
                                    <td><form class="inline-form" method="POST" action="{{ route('pendonor.pemesanan.store', $item) }}">@csrf<button class="button button-primary button-small" type="submit">Buat Pemesanan</button></form></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

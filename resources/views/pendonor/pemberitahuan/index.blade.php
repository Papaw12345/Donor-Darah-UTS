@extends('layouts.app')

@section('title', 'Pemberitahuan | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Pemberitahuan</h1>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.home') }}">Kembali ke Dashboard</a>
        </header>

        {{-- Pemberitahuan --}}
        <div class="page-section">
            @if ($pemberitahuan->isEmpty())
                <div class="empty-state">Belum ada pemberitahuan.</div>
            @else
                <div class="table-container">
                    <table class="data-table notification-table">
                        <thead><tr><th scope="col">Waktu Dibuat</th><th scope="col">Petugas Pengirim</th><th scope="col">Pesan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($pemberitahuan as $item)
                                <tr class="{{ $item->waktu_dibaca === null ? 'row-unread' : '' }}">
                                    <td>{{ $item->waktu_dibuat->format('d-m-Y H:i') }}</td>
                                    <td>{{ $item->petugasPengirim?->nama_petugas ?? '-' }}</td>
                                    <td class="message-cell">{{ $item->isi_pesan }}</td>
                                    <td><span class="status-badge {{ $item->waktu_dibaca === null ? 'status-warning' : 'status-neutral' }}">{{ $item->waktu_dibaca === null ? 'Belum dibaca' : 'Sudah dibaca' }}</span></td>
                                    <td><a class="button button-secondary button-small" href="{{ route('pendonor.pemberitahuan.show', $item) }}">Lihat Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

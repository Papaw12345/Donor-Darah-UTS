@extends('layouts.app')

@section('title', 'Pemesanan Saya | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Pemesanan Saya</h1>
            </div>
            <a class="button button-primary" href="{{ route('pendonor.jadwal.index') }}">Lihat Jadwal Donor</a>
        </header>

        @include('partials.alerts')

        <div class="page-section">
            @if ($pemesanan->isEmpty())
                <div class="empty-state">Tidak ada pemesanan aktif.</div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th scope="col">Tanggal</th><th scope="col">Jam Mulai</th><th scope="col">Jam Selesai</th><th scope="col">Waktu Pemesanan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($pemesanan as $item)
                                @php
                                    $statusClass = match ($item->status_pemesanan) {
                                        'CHECK_IN', 'SELESAI' => 'status-success',
                                        'TERJADWAL' => 'status-warning',
                                        'DIBATALKAN', 'TIDAK_HADIR' => 'status-danger',
                                        default => 'status-neutral',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $item->jadwalPelayanan->tanggal->format('d-m-Y') }}</td>
                                    <td>{{ substr($item->jadwalPelayanan->jam_mulai, 0, 5) }}</td>
                                    <td>{{ substr($item->jadwalPelayanan->jam_selesai, 0, 5) }}</td>
                                    <td>{{ $item->waktu_pemesanan->format('d-m-Y H:i') }}</td>
                                    <td><span class="status-badge {{ $statusClass }}">{{ $item->status_pemesanan }}</span></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="button button-secondary button-small" href="{{ route('pendonor.kuesioner.show', $item) }}">Kuesioner Pradonasi</a>
                                            <a class="button button-secondary button-small" href="{{ route('pendonor.kode-checkin.show', $item) }}">Kode Check-in</a>
                                            @if ($item->status_pemesanan === 'TERJADWAL' && $item->jadwalPelayanan->tanggal->gte(today('Asia/Jakarta')))
                                                <form class="inline-form" method="POST" action="{{ route('pendonor.pemesanan.cancel', $item) }}">@csrf @method('PATCH')<button class="button button-danger button-small" type="submit">Batalkan</button></form>
                                            @else
                                                <span class="table-no-action">-</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

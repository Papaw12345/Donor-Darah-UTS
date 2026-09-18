@extends('layouts.app')

@section('title', 'Riwayat Pelayanan Donor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Riwayat Pelayanan Donor</h1>
            </div>
        </header>

        @include('partials.alerts')

        @if ($riwayatPelayanan->isEmpty())
            <div class="empty-state">Belum ada riwayat pelayanan donor.</div>
        @else
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Pendonor</th>
                            <th scope="col">Waktu Pelayanan</th>
                            <th scope="col">Hasil</th>
                            <th scope="col">Volume</th>
                            <th scope="col">Unit</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayatPelayanan as $item)
                            @php
                                $hasilLabel = match ($item->hasil_pelayanan) {
                                    'DITUNDA' => 'Ditunda',
                                    'DITOLAK' => 'Ditolak',
                                    'GAGAL' => 'Gagal',
                                    'BERHASIL' => 'Berhasil',
                                    default => $item->hasil_pelayanan,
                                };
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $item->nama_lengkap }}</strong>
                                    <br>
                                    <span class="text-muted">
                                        {{ $item->nomor_donor ?? 'Nomor donor belum tersedia' }}
                                    </span>
                                </td>
                                <td>
                                    {{ \Carbon\CarbonImmutable::parse($item->waktu_pelayanan)->format('d-m-Y H:i') }}
                                </td>
                                <td>{{ $hasilLabel }}</td>
                                <td>
                                    {{ $item->volume_ml !== null ? $item->volume_ml.' mL' : '-' }}
                                </td>
                                <td>
                                    {{ $item->hasil_pelayanan === 'BERHASIL'
                                        ? (int) $item->jumlah_unit
                                        : '-' }}
                                </td>
                                <td>
                                    @if (
                                        $item->hasil_pelayanan === 'BERHASIL'
                                        && $item->id_penyumbangan !== null
                                    )
                                        <a
                                            class="text-link"
                                            href="{{ route('petugas.unit-komponen.show', ['penyumbangan' => $item->id_penyumbangan]) }}"
                                        >
                                            Unit Komponen
                                        </a>
                                    @elseif ($item->id_penyumbangan !== null)
                                        <a
                                            class="text-link"
                                            href="{{ route('petugas.penyumbangan.show', ['seleksi' => $item->id_seleksi]) }}"
                                        >
                                            Lihat Penyumbangan
                                        </a>
                                    @else
                                        <a
                                            class="text-link"
                                            href="{{ route('petugas.seleksi.show', ['pemesanan' => $item->id_pemesanan]) }}"
                                        >
                                            Lihat Seleksi
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
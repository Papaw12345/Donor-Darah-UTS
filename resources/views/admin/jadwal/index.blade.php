@extends('layouts.app')

@section('title', 'Jadwal Pelayanan | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Konfigurasi Pelayanan</p>
                <h1 class="page-title">Jadwal Pelayanan</h1>
                <p class="page-description">Atur tanggal, waktu, kapasitas, dan status jadwal donor.</p>
            </div>
            <a class="button button-primary" href="{{ route('admin.jadwal.create') }}">Tambah Jadwal</a>
        </header>

        @include('partials.alerts')

        <section class="page-section" aria-labelledby="daftar-jadwal">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="daftar-jadwal">Daftar Jadwal</h2>
                    <p class="section-description">Jadwal ditampilkan sesuai urutan data dari sistem.</p>
                </div>
            </div>

            @if ($jadwal->isEmpty())
                <div class="empty-state">Belum ada jadwal pelayanan.</div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Jam Mulai</th>
                                <th scope="col">Jam Selesai</th>
                                <th scope="col">Kapasitas</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jadwal as $item)
                                <tr>
                                    <td>{{ $item->tanggal->format('d-m-Y') }}</td>
                                    <td>{{ substr($item->jam_mulai, 0, 5) }}</td>
                                    <td>{{ substr($item->jam_selesai, 0, 5) }}</td>
                                    <td>{{ $item->kapasitas }}</td>
                                    <td>
                                        <span class="status-badge {{ $item->status_jadwal === 'DIBUKA' ? 'status-success' : ($item->status_jadwal === 'DIBATALKAN' ? 'status-danger' : 'status-neutral') }}">
                                            {{ $item->status_jadwal }}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="button button-secondary button-small" href="{{ route('admin.jadwal.edit', $item) }}">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

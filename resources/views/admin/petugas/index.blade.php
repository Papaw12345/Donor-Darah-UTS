@extends('layouts.app')

@section('title', 'Kelola Petugas | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Administrasi Akun</p>
                <h1 class="page-title">Kelola Petugas</h1>
                <p class="page-description">Lihat, buat, perbarui, serta aktifkan atau nonaktifkan akun Petugas.</p>
            </div>
            <a class="button button-primary" href="{{ route('admin.petugas.create') }}">Tambah Petugas</a>
        </header>

        @include('partials.alerts')

        <section class="page-section" aria-labelledby="daftar-petugas">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="daftar-petugas">Daftar Petugas</h2>
                    <p class="section-description">Status akun menentukan apakah Petugas dapat menggunakan aplikasi.</p>
                </div>
            </div>

            @if ($petugas->isEmpty())
                <div class="empty-state">Belum ada petugas terdaftar.</div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Nomor Petugas</th>
                                <th scope="col">Nama Petugas</th>
                                <th scope="col">Email</th>
                                <th scope="col">Status Akun</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($petugas as $item)
                                <tr>
                                    <td>{{ $item->nomor_petugas }}</td>
                                    <td>{{ $item->nama_petugas }}</td>
                                    <td>{{ $item->akun->email }}</td>
                                    <td>
                                        <span class="status-badge {{ $item->akun->status_akun === 'AKTIF' ? 'status-success' : 'status-neutral' }}">
                                            {{ $item->akun->status_akun }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="button button-secondary button-small" href="{{ route('admin.petugas.edit', $item) }}">Edit</a>

                                            @if ($item->akun->status_akun === 'AKTIF')
                                                <form class="inline-form" method="POST" action="{{ route('admin.petugas.deactivate', $item) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="button button-danger button-small" type="submit">Nonaktifkan</button>
                                                </form>
                                            @else
                                                <form class="inline-form" method="POST" action="{{ route('admin.petugas.activate', $item) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="button button-primary button-small" type="submit">Aktifkan</button>
                                                </form>
                                            @endif
                                        </div>
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

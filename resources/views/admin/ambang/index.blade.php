@extends('layouts.app')

@section('title', 'Ambang Persediaan | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Ambang Persediaan</h1>
            </div>
            <a class="button button-primary" href="{{ route('admin.ambang.create') }}">Tambah Ambang</a>
        </header>

        @include('partials.alerts')

        <section class="page-section">
            @if ($ambang->isEmpty())
                <div class="empty-state">Belum ada ambang persediaan yang dikonfigurasi.</div>
            @else
                <div class="table-container">
                    <table class="data-table table-compact">
                        <thead>
                            <tr>
                                <th scope="col">Jenis Komponen</th>
                                <th scope="col">Golongan Darah</th>
                                <th scope="col">Jumlah Minimum</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ambang as $item)
                                <tr>
                                    <td>{{ $item->jenisKomponenDarah->kode_komponen }} - {{ $item->jenisKomponenDarah->nama_komponen }}</td>
                                    <td>{{ $item->golonganDarah->abo }} {{ ucfirst(strtolower($item->golonganDarah->rhesus)) }}</td>
                                    <td>{{ $item->jumlah_minimum }}</td>
                                    <td>
                                        <a class="button button-secondary button-small" href="{{ route('admin.ambang.edit', $item) }}">Edit</a>
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

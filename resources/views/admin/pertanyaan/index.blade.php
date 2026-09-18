@extends('layouts.app')

@section('title', 'Pertanyaan Kuesioner | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Pertanyaan Kuesioner</h1>
            </div>
            <a class="button button-primary" href="{{ route('admin.pertanyaan.create') }}">Tambah Pertanyaan</a>
        </header>

        @include('partials.alerts')

        <section class="page-section">
            @if ($pertanyaan->isEmpty())
                <div class="empty-state">Belum ada pertanyaan kuesioner.</div>
            @else
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Urutan</th>
                                <th scope="col">Pertanyaan</th>
                                <th scope="col">Kategori</th>
                                <th scope="col">Jenis Jawaban</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pertanyaan as $item)
                                <tr>
                                    <td>{{ $item->urutan }}</td>
                                    <td class="message-cell">{{ $item->teks_pertanyaan }}</td>
                                    <td>{{ $item->kategori ?? '-' }}</td>
                                    <td>{{ $item->jenis_jawaban }}</td>
                                    <td>
                                        <span class="status-badge {{ $item->status_aktif ? 'status-success' : 'status-neutral' }}">
                                            {{ $item->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="button button-secondary button-small" href="{{ route('admin.pertanyaan.edit', $item) }}">Edit</a>
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

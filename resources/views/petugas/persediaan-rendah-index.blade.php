@extends('layouts.app')

@section('title', 'Persediaan Rendah | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><h1 class="page-title">Persediaan Rendah</h1><p class="page-description">Tanggal acuan: {{ \Carbon\CarbonImmutable::parse($tanggalAcuan, 'Asia/Jakarta')->format('d-m-Y') }}</p></div><a class="button button-secondary" href="{{ route('petugas.home') }}">Kembali ke Dashboard</a></header>
        @include('partials.alerts')
        <section class="page-section" aria-label="Daftar persediaan rendah">
            @if ($jumlahAmbangPersediaan === 0)<div class="empty-state">Belum ada konfigurasi ambang persediaan.</div>
            @elseif ($persediaanRendah->isEmpty())<div class="empty-state">Tidak ada persediaan yang berada pada atau di bawah ambang.</div>
            @else<div class="table-container"><table class="data-table table-compact"><thead><tr><th scope="col">Jenis Komponen</th><th scope="col">ABO</th><th scope="col">Rhesus</th><th scope="col">Jumlah Persediaan</th><th scope="col">Jumlah Minimum</th></tr></thead><tbody>
                @foreach ($persediaanRendah as $item)<tr><td>{{ $item->kode_komponen }} - {{ $item->nama_komponen }}</td><td>{{ $item->abo }}</td><td>{{ $item->rhesus }}</td><td><span class="status-badge status-danger">{{ $item->jumlah_persediaan }}</span></td><td>{{ $item->jumlah_minimum }}</td></tr>@endforeach
            </tbody></table></div>@endif
        </section>
    </div>
@endsection

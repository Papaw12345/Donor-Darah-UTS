@extends('layouts.app')

@section('title', 'Persediaan Darah | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><h1 class="page-title">Persediaan Darah</h1><p class="page-description">Tanggal acuan: {{ \Carbon\CarbonImmutable::parse($tanggalAcuan, 'Asia/Jakarta')->format('d-m-Y') }}</p></div></header>
        @include('partials.alerts')
        <section class="page-section" aria-label="Daftar persediaan darah">
            @if ($persediaan->isEmpty())<div class="empty-state">Belum ada unit yang termasuk persediaan tersedia.</div>@else<div class="table-container"><table class="data-table table-compact"><thead><tr><th scope="col">Jenis Komponen</th><th scope="col">ABO</th><th scope="col">Rhesus</th><th scope="col">Jumlah Persediaan</th></tr></thead><tbody>
                @foreach ($persediaan as $item)<tr><td>{{ $item->kode_komponen }} - {{ $item->nama_komponen }}</td><td>{{ $item->abo }}</td><td>{{ $item->rhesus }}</td><td><strong>{{ $item->jumlah_persediaan }}</strong></td></tr>@endforeach
            </tbody></table></div>@endif
        </section>
    </div>
@endsection

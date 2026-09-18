@extends('layouts.app')

@section('title', 'Pelulusan Unit | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><p class="eyebrow">Pengelolaan Unit</p><h1 class="page-title">Pelulusan Unit</h1><p class="page-description">Periksa unit yang masih berstatus MENUNGGU_PELULUSAN.</p></div><a class="button button-secondary" href="{{ route('petugas.home') }}">Kembali ke Dashboard</a></header>
        @include('partials.alerts')
        <section class="page-section" aria-labelledby="daftar-pelulusan"><div class="section-header"><div><h2 class="section-title" id="daftar-pelulusan">Unit Menunggu Pelulusan</h2><p class="section-description">Petugas: {{ $petugas->nama_petugas }}</p></div></div>
            @if ($units->isEmpty())<div class="empty-state">Tidak ada unit menunggu pelulusan.</div>@else<div class="table-container"><table class="data-table"><thead><tr><th scope="col">Nomor</th><th scope="col">Komponen</th><th scope="col">Golongan</th><th scope="col">Tanggal Pembuatan</th><th scope="col">Tanggal Kedaluwarsa</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
                @foreach ($units as $unit)<tr><td>{{ $unit->nomor_unit }}</td><td>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</td><td>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</td><td>{{ $unit->tanggal_pembuatan->toDateString() }}</td><td>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</td><td><span class="status-badge status-warning">{{ $unit->status_unit }}</span></td><td><a class="button button-secondary button-small" href="{{ route('petugas.pelulusan.show', $unit) }}">Lihat Pelulusan</a></td></tr>@endforeach
            </tbody></table></div>@endif
        </section>
    </div>
@endsection

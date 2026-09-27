@extends('layouts.app')

@section('title', 'Unit Komponen Darah | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><h1 class="page-title">Unit Komponen</h1><p>Sumber penyumbangan berhasil untuk pencatatan unit hasil pengolahan.</p></div></header>
        @include('partials.alerts')
        <section class="page-section" aria-label="Daftar sumber unit komponen">
            @if ($penyumbanganBerhasil->isEmpty())
                <div class="empty-state">Belum ada penyumbangan berhasil untuk dikelola.</div>
            @else
                <div class="table-container"><table class="data-table">
                    <thead><tr><th scope="col">Pendonor</th><th scope="col">Nomor Donor</th><th scope="col">Waktu Pengambilan</th><th scope="col">Unit Tercatat</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($penyumbanganBerhasil as $penyumbangan)
                            <tr>
                                <td>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nama_lengkap }}</td>
                                <td>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nomor_donor ?? 'Belum tersedia' }}</td>
                                <td>{{ $penyumbangan->waktu_pengambilan->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $penyumbangan->unit_komponen_darah_count }}</td>
                                <td><a class="button button-secondary button-small" href="{{ route('petugas.unit-komponen.show', $penyumbangan) }}">Kelola Unit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>
    </div>
@endsection

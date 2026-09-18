@extends('layouts.app')

@section('title', 'Pemanggilan Pendonor | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header"><div class="page-header-main"><h1 class="page-title">Pemanggilan Pendonor</h1><p class="page-description">Tanggal acuan: {{ \Carbon\CarbonImmutable::parse($tanggalAcuan, 'Asia/Jakarta')->format('d-m-Y') }}</p></div></header>
        @include('partials.alerts')

        {{-- Kondisi persediaan --}}
        <section class="page-section" aria-labelledby="kondisi-persediaan-rendah"><h2 class="section-title" id="kondisi-persediaan-rendah">Kondisi Persediaan Rendah</h2>
            @if ($persediaanRendah->isEmpty())<div class="empty-state">Tidak ada kondisi persediaan rendah yang memerlukan pemanggilan Pendonor.</div>@else<div class="table-container"><table class="data-table"><thead><tr><th scope="col">Jenis Komponen</th><th scope="col">ABO</th><th scope="col">Rhesus</th><th scope="col">Jumlah Persediaan</th><th scope="col">Jumlah Minimum</th><th scope="col">Aksi</th></tr></thead><tbody>
                @foreach ($persediaanRendah as $item)<tr><td>{{ $item->kode_komponen }} - {{ $item->nama_komponen }}</td><td>{{ $item->abo }}</td><td>{{ $item->rhesus }}</td><td>{{ $item->jumlah_persediaan }}</td><td>{{ $item->jumlah_minimum }}</td><td><a class="button button-secondary button-small" href="{{ route('petugas.pemanggilan.index', ['id_ambang' => $item->id_ambang]) }}">Lihat Kandidat</a></td></tr>@endforeach
            </tbody></table></div>@endif
        </section>

        {{-- Kandidat pendonor --}}
        <section class="page-section" aria-labelledby="kandidat-pendonor"><h2 class="section-title" id="kandidat-pendonor">Kandidat Pendonor</h2>
            @if ($ambangTerpilihTidakRendah)<div class="empty-state">Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.</div>
            @elseif ($persediaanRendah->isNotEmpty() && $ambangTerpilih === null)<div class="empty-state">Pilih kondisi persediaan rendah untuk melihat kandidat Pendonor.</div>
            @elseif ($ambangTerpilih !== null)
                <dl class="context-strip"><div><dt>Komponen</dt><dd>{{ $ambangTerpilih->kode_komponen }} - {{ $ambangTerpilih->nama_komponen }}</dd></div><div><dt>Golongan darah</dt><dd>{{ $ambangTerpilih->abo }} {{ $ambangTerpilih->rhesus }}</dd></div><div><dt>Persediaan / minimum</dt><dd>{{ $ambangTerpilih->jumlah_persediaan }} / {{ $ambangTerpilih->jumlah_minimum }}</dd></div></dl>
                <div class="notice-panel"><p>Kandidat tetap harus menjalani proses donor dan seleksi.</p></div>
                @if ($kandidatPendonor->isEmpty())<div class="empty-state">Tidak ada Pendonor yang memenuhi kriteria pemanggilan untuk kondisi persediaan ini.</div>@else<div class="table-container"><table class="data-table"><thead><tr><th scope="col">Nomor Donor</th><th scope="col">Nama Pendonor</th><th scope="col">ABO</th><th scope="col">Rhesus</th><th scope="col">Donor Berhasil Terakhir</th><th scope="col">Donor Berhasil Tahun Ini</th><th scope="col">Aksi</th></tr></thead><tbody>
                    @foreach ($kandidatPendonor as $kandidat)<tr><td>{{ $kandidat->nomor_donor }}</td><td>{{ $kandidat->nama_lengkap }}</td><td>{{ $kandidat->abo }}</td><td>{{ $kandidat->rhesus }}</td><td>{{ $kandidat->tanggal_donor_terakhir?->format('d-m-Y') ?? '-' }}</td><td>{{ $kandidat->jumlah_donor_tahun_ini }}</td><td><a class="button button-primary button-small" href="{{ route('petugas.pemberitahuan.create', ['ambang' => $ambangTerpilih->id_ambang, 'pendonor' => $kandidat->id_pendonor]) }}">Buat Pemberitahuan</a></td></tr>@endforeach
                </tbody></table></div>@endif
            @endif
        </section>
    </div>
@endsection

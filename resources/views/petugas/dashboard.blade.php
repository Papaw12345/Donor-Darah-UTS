@extends('layouts.app')

@section('title', 'Dashboard Petugas | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Dashboard Petugas</h1>
            </div>
        </header>

        @include('partials.alerts')

        {{-- Identitas Petugas --}}
        <section class="page-section" aria-labelledby="identitas-petugas">
            <h2 class="section-title" id="identitas-petugas">Identitas Petugas</h2>
            <dl class="identity-panel identity-grid petugas-identity-grid">
                <div class="identity-item"><dt>Nama Petugas</dt><dd>{{ $petugas->nama_petugas }}</dd></div>
                <div class="identity-item"><dt>Nomor Petugas</dt><dd>{{ $petugas->nomor_petugas }}</dd></div>
                <div class="identity-item"><dt>Email</dt><dd>{{ $akun->email }}</dd></div>
            </dl>
        </section>

        {{-- Kegiatan hari ini --}}
        <section class="page-section" aria-labelledby="kegiatan-donor-hari-ini">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="kegiatan-donor-hari-ini">Kegiatan Hari Ini</h2>
                </div>
            </div>
            <dl class="dashboard-summary petugas-summary">
                @foreach (['TERJADWAL' => 'Terjadwal', 'CHECK_IN' => 'Check-in', 'SELESAI' => 'Selesai', 'TIDAK_HADIR' => 'Tidak Hadir'] as $status => $label)
                    <div class="dashboard-summary-item"><dt class="summary-label">{{ $label }}</dt><dd>{{ $kegiatanHariIni[$status] }}</dd></div>
                @endforeach
                <div class="dashboard-summary-item"><dt class="summary-label">Pendonor Sedang Diproses</dt><dd>{{ $jumlahPendonorDiproses }}</dd></div>
                <div class="dashboard-summary-item"><dt class="summary-label">Persediaan</dt><dd>Total unit tersedia: {{ $totalPersediaanTersedia }}</dd></div>
            </dl>
        </section>

        {{-- Pendonor sedang diproses --}}
        <section class="page-section" aria-labelledby="pendonor-sedang-diproses">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="pendonor-sedang-diproses">Pendonor Sedang Diproses</h2>
                </div>
            </div>

            @if ($pendonorSedangDiproses->isEmpty())
                <div class="empty-state">Belum ada pendonor yang sedang diproses.</div>
            @else
                <div class="table-container">
                    <table class="data-table table-compact">
                        <thead>
                            <tr>
                                <th scope="col">Nama Pendonor</th>
                                <th scope="col">Nomor Donor</th>
                                <th scope="col">Tanggal Jadwal</th>
                                <th scope="col">Waktu Check-in</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendonorSedangDiproses as $item)
                                <tr>
                                    <td>{{ $item->nama_lengkap }}</td>
                                    <td>{{ $item->nomor_donor ?? 'Belum tersedia' }}</td>
                                    <td>{{ \Carbon\CarbonImmutable::parse($item->tanggal_jadwal)->format('d-m-Y') }}</td>
                                    <td>
                                        {{ $item->waktu_checkin
                                            ? \Carbon\CarbonImmutable::parse($item->waktu_checkin)->format('d-m-Y H:i')
                                            : 'Belum tercatat' }}
                                    </td>
                                    <td>
                                        <a
                                            class="text-link"
                                            href="{{ route('petugas.kuesioner.show', ['pemesanan' => $item->id_pemesanan]) }}"
                                        >
                                            Lanjutkan
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        {{-- Persediaan rendah --}}
        <section class="page-section" aria-labelledby="persediaan-rendah">
            <div class="section-header">
                <div>
                    <h2 class="section-title" id="persediaan-rendah">Persediaan Rendah</h2>
                    <p class="section-description">Jumlah kombinasi: {{ $persediaanRendah->count() }}</p>
                </div>
                <a class="text-link" href="{{ route('petugas.persediaan-rendah.index') }}">Lihat Persediaan Rendah</a>
            </div>
            @if ($jumlahAmbangPersediaan === 0)
                <div class="empty-state">Belum ada konfigurasi ambang persediaan.</div>
            @elseif ($persediaanRendah->isEmpty())
                <div class="empty-state">Tidak ada persediaan yang berada pada atau di bawah ambang.</div>
            @else
                <div class="table-container">
                    <table class="data-table table-compact">
                        <thead><tr><th scope="col">Komponen</th><th scope="col">ABO</th><th scope="col">Rhesus</th><th scope="col">Jumlah Persediaan</th><th scope="col">Jumlah Minimum</th></tr></thead>
                        <tbody>@foreach ($persediaanRendah as $item)<tr><td>{{ $item->kode_komponen }} - {{ $item->nama_komponen }}</td><td>{{ $item->abo }}</td><td>{{ $item->rhesus }}</td><td>{{ $item->jumlah_persediaan }}</td><td>{{ $item->jumlah_minimum }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

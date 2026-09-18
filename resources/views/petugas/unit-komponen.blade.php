@extends('layouts.app')

@section('title', 'Unit Komponen Darah | Donor Darah UDD')

@section('content')
    <div class="container page-shell">
        <header class="page-header">
            <div class="page-header-main">
                <h1 class="page-title">Unit Komponen Darah</h1>
            </div>
            <a class="button button-secondary" href="{{ route('petugas.penyumbangan.show', $penyumbangan->seleksiDonor) }}">Kembali ke Penyumbangan</a>
        </header>

        @include('partials.alerts')

        {{-- Konteks penyumbangan --}}
        <section class="page-section" aria-labelledby="konteks-penyumbangan">
            <h2 class="section-title" id="konteks-penyumbangan">Data Penyumbangan</h2>

            <dl class="identity-panel identity-grid">
                <div class="identity-item">
                    <dt>ID Penyumbangan</dt>
                    <dd>{{ $penyumbangan->id_penyumbangan }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Hasil Penyumbangan</dt>
                    <dd><span class="status-badge status-success">{{ $penyumbangan->hasil_penyumbangan }}</span></dd>
                </div>

                <div class="identity-item">
                    <dt>Waktu Pengambilan</dt>
                    <dd>{{ $penyumbangan->waktu_pengambilan->format('Y-m-d H:i:s') }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Nama Pendonor</dt>
                    <dd>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nama_lengkap }}</dd>
                </div>

                <div class="identity-item">
                    <dt>Nomor Donor</dt>
                    <dd>{{ $penyumbangan->seleksiDonor->pemesananDonor->pendonor->nomor_donor ?? 'Belum tersedia' }}</dd>
                </div>
            </dl>
        </section>

        {{-- Unit komponen --}}
        <section class="page-section" aria-labelledby="unit-tersimpan">
            <h2 class="section-title" id="unit-tersimpan">Unit Tersimpan</h2>

            @if ($penyumbangan->unitKomponenDarah->isEmpty())
                <div class="empty-state">Belum ada unit komponen darah.</div>
            @else
                <div class="table-container">
                    <table class="data-table unit-history-table">
                        <thead>
                            <tr>
                                <th scope="col">Nomor Unit</th>
                                <th scope="col">Jenis Komponen</th>
                                <th scope="col">Golongan Darah</th>
                                <th scope="col">Tanggal Pembuatan</th>
                                <th scope="col">Tanggal Kedaluwarsa</th>
                                <th scope="col">Status</th>
                                <th scope="col">Petugas Pencatat</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penyumbangan->unitKomponenDarah as $unit)
                                @php
                                    $isKedaluwarsa =
                                        $unit->status_unit === 'TERSEDIA'
                                        && $unit->tanggal_kedaluwarsa->toDateString() < $tanggalAcuan;

                                    if ($isKedaluwarsa) {
                                        $statusLabel = 'Kedaluwarsa';
                                        $statusClass = 'status-neutral';
                                    } elseif ($unit->status_unit === 'MENUNGGU_PELULUSAN') {
                                        $statusLabel = 'Menunggu Pelulusan';
                                        $statusClass = 'status-warning';
                                    } elseif ($unit->status_unit === 'TERSEDIA') {
                                        $statusLabel = 'Tersedia';
                                        $statusClass = 'status-success';
                                    } elseif ($unit->status_unit === 'DITOLAK') {
                                        $statusLabel = 'Ditolak';
                                        $statusClass = 'status-danger';
                                    } else {
                                        $statusLabel = 'Didistribusikan';
                                        $statusClass = 'status-neutral';
                                    }

                                    $detailRoute = in_array(
                                        $unit->status_unit,
                                        ['MENUNGGU_PELULUSAN', 'DITOLAK'],
                                        true
                                    )
                                        ? route('petugas.pelulusan.show', $unit)
                                        : route('petugas.distribusi.show', $unit);
                                @endphp

                                <tr>
                                    <td>{{ $unit->nomor_unit }}</td>
                                    <td>{{ $unit->jenisKomponenDarah->kode_komponen }} - {{ $unit->jenisKomponenDarah->nama_komponen }}</td>
                                    <td>{{ $unit->golonganDarah->abo }} {{ $unit->golonganDarah->rhesus }}</td>
                                    <td>{{ $unit->tanggal_pembuatan->toDateString() }}</td>
                                    <td>{{ $unit->tanggal_kedaluwarsa->toDateString() }}</td>
                                    <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td>{{ $unit->petugasPencatat->nama_petugas }} ({{ $unit->petugasPencatat->nomor_petugas }})</td>
                                    <td><a class="text-link" href="{{ $detailRoute }}">Lihat</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="page-section" aria-labelledby="form-unit">
            <h2 class="section-title" id="form-unit">Form Unit Komponen</h2>

            <form class="form-panel" method="POST" action="{{ route('petugas.unit-komponen.store', $penyumbangan) }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label for="nomor_unit">Nomor unit</label>
                        <input id="nomor_unit" name="nomor_unit" type="text" value="{{ old('nomor_unit') }}" required>
                    </div>

                    <div class="form-field">
                        <label for="id_jenis_komponen">Jenis komponen</label>
                        <select id="id_jenis_komponen" name="id_jenis_komponen" required>
                            <option value="">Pilih jenis</option>
                            @foreach ($jenisKomponen as $jenis)
                                <option value="{{ $jenis->id_jenis_komponen }}" @selected((string) old('id_jenis_komponen') === (string) $jenis->id_jenis_komponen)>
                                    {{ $jenis->kode_komponen }} - {{ $jenis->nama_komponen }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="id_golongan_darah">Golongan darah</label>
                        <select id="id_golongan_darah" name="id_golongan_darah" required>
                            <option value="">Pilih golongan</option>
                            @foreach ($golonganDarah as $golongan)
                                <option value="{{ $golongan->id_golongan_darah }}" @selected((string) old('id_golongan_darah') === (string) $golongan->id_golongan_darah)>
                                    {{ $golongan->abo }} {{ $golongan->rhesus }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="tanggal_pembuatan">Tanggal pembuatan</label>
                        <input id="tanggal_pembuatan" name="tanggal_pembuatan" type="date" value="{{ old('tanggal_pembuatan') }}" required>
                    </div>

                    <div class="form-field">
                        <label for="tanggal_kedaluwarsa">Tanggal kedaluwarsa</label>
                        <input id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa" type="date" value="{{ old('tanggal_kedaluwarsa') }}" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Simpan Unit Komponen</button>
                </div>
            </form>
        </section>
    </div>
@endsection
@extends('layouts.app')

@section('title', 'Kuesioner Pradonasi | Donor Darah UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">Tahap Pradonasi</p>
                <h1 class="page-title">Kuesioner Pradonasi</h1>
                <p class="page-description">Jawab pertanyaan yang tersedia untuk pemesanan donor ini.</p>
            </div>
            <a class="button button-secondary" href="{{ route('pendonor.pemesanan.index') }}">Kembali ke Pemesanan Saya</a>
        </header>

        <dl class="context-strip">
            <div><dt>Tanggal jadwal</dt><dd>{{ $pemesanan->jadwalPelayanan->tanggal->format('d-m-Y') }}</dd></div>
            <div><dt>Jam pelayanan</dt><dd>{{ substr($pemesanan->jadwalPelayanan->jam_mulai, 0, 5) }} - {{ substr($pemesanan->jadwalPelayanan->jam_selesai, 0, 5) }}</dd></div>
        </dl>

        @include('partials.alerts')

        @if ($kuesioner)
            {{-- Jawaban tersimpan --}}
            <section class="page-section" aria-labelledby="jawaban-tersimpan">
                <div class="section-header">
                    <div><h2 class="section-title" id="jawaban-tersimpan">Jawaban Tersimpan</h2><p class="section-description">Kuesioner telah dikirim pada {{ $kuesioner->waktu_pengisian->format('d-m-Y H:i') }}.</p></div>
                    <span class="status-badge status-success">Sudah dikirim</span>
                </div>
                <dl class="answer-list">
                    @foreach ($kuesioner->jawabanKuesioner as $jawaban)
                        <div class="answer-item"><dt>{{ $jawaban->pertanyaanKuesioner->teks_pertanyaan }}</dt><dd>{{ $jawaban->jawaban }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @elseif ($pesanTidakTersedia)
            <div class="empty-state">{{ $pesanTidakTersedia }}</div>
        @else
            {{-- Pertanyaan aktif --}}
            <form method="POST" action="{{ route('pendonor.kuesioner.store', $pemesanan) }}">
                @csrf
                <div class="question-list">
                    @foreach ($pertanyaanAktif as $pertanyaan)
                        <fieldset class="question-card">
                            <legend>{{ $pertanyaan->teks_pertanyaan }}</legend>
                            @if ($pertanyaan->kategori)
                                <p class="question-category">Kategori: {{ $pertanyaan->kategori }}</p>
                            @endif
                            @if ($pertanyaan->jenis_jawaban === 'YA_TIDAK')
                                <div class="choice-group">
                                    <label class="choice-option"><input type="radio" name="answers[{{ $pertanyaan->id_pertanyaan }}]" value="YA" @checked(old("answers.{$pertanyaan->id_pertanyaan}") === 'YA')><span>YA</span></label>
                                    <label class="choice-option"><input type="radio" name="answers[{{ $pertanyaan->id_pertanyaan }}]" value="TIDAK" @checked(old("answers.{$pertanyaan->id_pertanyaan}") === 'TIDAK')><span>TIDAK</span></label>
                                </div>
                            @elseif ($pertanyaan->jenis_jawaban === 'TEKS')
                                <label class="form-field"><span>Jawaban</span><textarea name="answers[{{ $pertanyaan->id_pertanyaan }}]">{{ old("answers.{$pertanyaan->id_pertanyaan}") }}</textarea></label>
                            @endif
                        </fieldset>
                    @endforeach
                </div>
                <div class="form-actions"><button class="button button-primary" type="submit">Kirim Kuesioner</button></div>
            </form>
        @endif
    </div>
@endsection

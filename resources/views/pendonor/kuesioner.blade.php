<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Pradonasi</title>
</head>
<body>
    <main>
        <h1>Kuesioner Pradonasi</h1>

        <p>
            Jadwal: {{ $pemesanan->jadwalPelayanan->tanggal->format('d-m-Y') }}
            {{ substr($pemesanan->jadwalPelayanan->jam_mulai, 0, 5) }}–{{ substr($pemesanan->jadwalPelayanan->jam_selesai, 0, 5) }}
        </p>

        @if (session('success'))
            <div role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($kuesioner)
            <p>Kuesioner telah dikirim pada {{ $kuesioner->waktu_pengisian->format('d-m-Y H:i') }}.</p>

            <dl>
                @foreach ($kuesioner->jawabanKuesioner as $jawaban)
                    <dt>{{ $jawaban->pertanyaanKuesioner->teks_pertanyaan }}</dt>
                    <dd>{{ $jawaban->jawaban }}</dd>
                @endforeach
            </dl>
        @elseif ($pesanTidakTersedia)
            <p>{{ $pesanTidakTersedia }}</p>
        @else
            <form method="POST" action="{{ route('pendonor.kuesioner.store', $pemesanan) }}">
                @csrf

                @foreach ($pertanyaanAktif as $pertanyaan)
                    <fieldset>
                        <legend>{{ $pertanyaan->teks_pertanyaan }}</legend>

                        @if ($pertanyaan->kategori)
                            <p>Kategori: {{ $pertanyaan->kategori }}</p>
                        @endif

                        @if ($pertanyaan->jenis_jawaban === 'YA_TIDAK')
                            <label>
                                <input
                                    type="radio"
                                    name="answers[{{ $pertanyaan->id_pertanyaan }}]"
                                    value="YA"
                                    @checked(old("answers.{$pertanyaan->id_pertanyaan}") === 'YA')
                                >
                                YA
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    name="answers[{{ $pertanyaan->id_pertanyaan }}]"
                                    value="TIDAK"
                                    @checked(old("answers.{$pertanyaan->id_pertanyaan}") === 'TIDAK')
                                >
                                TIDAK
                            </label>
                        @elseif ($pertanyaan->jenis_jawaban === 'TEKS')
                            <label>
                                Jawaban
                                <textarea name="answers[{{ $pertanyaan->id_pertanyaan }}]">{{ old("answers.{$pertanyaan->id_pertanyaan}") }}</textarea>
                            </label>
                        @endif
                    </fieldset>
                @endforeach

                <button type="submit">Kirim Kuesioner</button>
            </form>
        @endif

        <p>
            <a href="{{ route('pendonor.pemesanan.index') }}">Kembali ke Pemesanan Saya</a>
        </p>
    </main>
</body>
</html>

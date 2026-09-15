<?php

namespace App\Support;

use App\Models\Pendonor;
use App\Models\Penyumbangan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class PendonorDonorBerikutnyaCalculator
{
    public function calculate(Pendonor $pendonor): array
    {
        $tanggalAcuan = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        $riwayatBerhasil = Penyumbangan::query()
            ->whereHas(
                'seleksiDonor.pemesananDonor',
                fn (Builder $query) => $query->where('id_pendonor', $pendonor->id_pendonor)
            )
            ->where('hasil_penyumbangan', 'BERHASIL')
            ->whereDate('waktu_pengambilan', '<=', $tanggalAcuan->toDateString());

        $waktuDonorTerakhir = (clone $riwayatBerhasil)
            ->max('waktu_pengambilan');

        $tanggalDonorTerakhir = $waktuDonorTerakhir === null
            ? null
            : CarbonImmutable::parse((string) $waktuDonorTerakhir, 'Asia/Jakarta')->startOfDay();

        $tanggalIntervalTerpenuhi = $tanggalDonorTerakhir?->addMonthsNoOverflow(2);
        $jumlahDonorTahunIni = (clone $riwayatBerhasil)
            ->whereYear('waktu_pengambilan', $tanggalAcuan->year)
            ->count();
        $batasTahunan = $pendonor->jenis_kelamin === 'LAKI_LAKI' ? 6 : 4;
        $tanggalFrekuensiTerpenuhi = $jumlahDonorTahunIni >= $batasTahunan
            ? CarbonImmutable::create($tanggalAcuan->year + 1, 1, 1, 0, 0, 0, 'Asia/Jakarta')
            : null;

        $tanggalDonorBerikutnya = $tanggalAcuan;

        foreach ([$tanggalIntervalTerpenuhi, $tanggalFrekuensiTerpenuhi] as $batasTanggal) {
            if ($batasTanggal !== null && $batasTanggal->gt($tanggalDonorBerikutnya)) {
                $tanggalDonorBerikutnya = $batasTanggal;
            }
        }

        return [
            'tanggal_acuan' => $tanggalAcuan,
            'donor_pertama' => $tanggalDonorTerakhir === null,
            'tanggal_donor_terakhir' => $tanggalDonorTerakhir,
            'tanggal_interval_terpenuhi' => $tanggalIntervalTerpenuhi,
            'interval_terpenuhi' => $tanggalIntervalTerpenuhi === null
                || $tanggalIntervalTerpenuhi->lte($tanggalAcuan),
            'jumlah_donor_tahun_ini' => $jumlahDonorTahunIni,
            'batas_tahunan' => $batasTahunan,
            'tanggal_frekuensi_terpenuhi' => $tanggalFrekuensiTerpenuhi,
            'frekuensi_terpenuhi' => $tanggalFrekuensiTerpenuhi === null,
            'tanggal_donor_berikutnya' => $tanggalDonorBerikutnya,
            'dapat_mencoba_sekarang' => $tanggalDonorBerikutnya->equalTo($tanggalAcuan),
        ];
    }
}

<?php

namespace App\Support;

use App\Models\UnitKomponenDarah;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class PersediaanDarahQuery
{
    public function eligibleUnitsQuery(string $tanggalAcuan): EloquentBuilder
    {
        return UnitKomponenDarah::query()
            ->where('status_unit', 'TERSEDIA')
            ->whereDate('tanggal_kedaluwarsa', '>=', $tanggalAcuan);
    }

    public function countsByCombinationQuery(string $tanggalAcuan): Builder
    {
        return $this->eligibleUnitsQuery($tanggalAcuan)
            ->toBase()
            ->select([
                'id_jenis_komponen',
                'id_golongan_darah',
            ])
            ->selectRaw('COUNT(*) AS jumlah_persediaan')
            ->groupBy('id_jenis_komponen', 'id_golongan_darah');
    }

    public function countForCombination(
        int $idJenisKomponen,
        int $idGolonganDarah,
        string $tanggalAcuan
    ): int {
        return $this->eligibleUnitsQuery($tanggalAcuan)
            ->where('id_jenis_komponen', $idJenisKomponen)
            ->where('id_golongan_darah', $idGolonganDarah)
            ->count();
    }

    public function lowStockBaseQuery(string $tanggalAcuan): Builder
    {
        $jumlahPerKombinasi = $this->countsByCombinationQuery($tanggalAcuan);

        return DB::table('ambang_persediaan')
            ->leftJoinSub(
                $jumlahPerKombinasi,
                'persediaan',
                function (JoinClause $join): void {
                    $join->on(
                        'persediaan.id_jenis_komponen',
                        '=',
                        'ambang_persediaan.id_jenis_komponen'
                    )->on(
                        'persediaan.id_golongan_darah',
                        '=',
                        'ambang_persediaan.id_golongan_darah'
                    );
                }
            )
            ->join(
                'jenis_komponen_darah',
                'jenis_komponen_darah.id_jenis_komponen',
                '=',
                'ambang_persediaan.id_jenis_komponen'
            )
            ->join(
                'golongan_darah',
                'golongan_darah.id_golongan_darah',
                '=',
                'ambang_persediaan.id_golongan_darah'
            )
            ->whereRaw(
                'COALESCE(persediaan.jumlah_persediaan, 0) '
                .'<= ambang_persediaan.jumlah_minimum'
            )
            ->select([
                'ambang_persediaan.id_ambang',
                'ambang_persediaan.id_jenis_komponen',
                'ambang_persediaan.id_golongan_darah',
                'ambang_persediaan.jumlah_minimum',
                'jenis_komponen_darah.kode_komponen',
                'jenis_komponen_darah.nama_komponen',
                'golongan_darah.abo',
                'golongan_darah.rhesus',
            ])
            ->selectRaw(
                'COALESCE(persediaan.jumlah_persediaan, 0) '
                .'AS jumlah_persediaan'
            );
    }

    public function isUnitEligible(
        UnitKomponenDarah $unit,
        string $tanggalAcuan
    ): bool {
        return $unit->status_unit === 'TERSEDIA'
            && $unit->tanggal_kedaluwarsa->toDateString() >= $tanggalAcuan;
    }
}

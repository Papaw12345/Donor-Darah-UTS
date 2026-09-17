<?php

namespace App\Http\Controllers;

use App\Models\Pendonor;
use App\Support\PendonorDonorBerikutnyaCalculator;
use App\Support\PersediaanDarahQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetugasPemanggilanController extends Controller
{
    public function index(
        Request $request,
        PendonorDonorBerikutnyaCalculator $calculator,
        PersediaanDarahQuery $persediaanQuery
    ): View|Response {
        $petugas = $request->user()->petugas()->first();

        abort_if(
            $petugas === null,
            409,
            'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
        );

        $tanggalAcuan = now('Asia/Jakarta')->toDateString();
        $persediaanRendah = $persediaanQuery
            ->lowStockBaseQuery($tanggalAcuan)
            ->orderBy('jenis_komponen_darah.kode_komponen')
            ->orderBy('golongan_darah.abo')
            ->orderBy('golongan_darah.rhesus')
            ->orderBy('ambang_persediaan.id_ambang')
            ->get();

        $ambangTerpilih = null;
        $ambangTerpilihTidakRendah = false;
        $kandidatPendonor = collect();
        $idAmbangInput = $request->query('id_ambang');

        if ($idAmbangInput !== null) {
            $idAmbangValid = is_string($idAmbangInput)
                && preg_match('/\A[1-9][0-9]*\z/D', $idAmbangInput) === 1
                && filter_var(
                    $idAmbangInput,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                ) !== false;

            if (! $idAmbangValid) {
                return response('Kondisi persediaan tidak ditemukan.', 404);
            }

            $idAmbang = (int) $idAmbangInput;
            $ambangTerpilih = $persediaanRendah->first(
                fn (object $ambang): bool => (int) $ambang->id_ambang === $idAmbang
            );

            if ($ambangTerpilih === null) {
                $ambangAda = DB::table('ambang_persediaan')
                    ->where('id_ambang', $idAmbang)
                    ->exists();

                if (! $ambangAda) {
                    return response('Kondisi persediaan tidak ditemukan.', 404);
                }

                $ambangTerpilihTidakRendah = true;
            } else {
                $kandidatPendonor = $this->kandidatPendonor(
                    (int) $ambangTerpilih->id_golongan_darah,
                    $calculator
                );
            }
        }

        return view('petugas.pemanggilan-index', compact(
            'petugas',
            'tanggalAcuan',
            'persediaanRendah',
            'ambangTerpilih',
            'ambangTerpilihTidakRendah',
            'kandidatPendonor'
        ));
    }

    private function kandidatPendonor(
        int $idGolonganDarah,
        PendonorDonorBerikutnyaCalculator $calculator
    ): Collection {
        return Pendonor::query()
            ->select([
                'id_pendonor',
                'id_akun',
                'id_golongan_darah',
                'nomor_donor',
                'nama_lengkap',
                'jenis_kelamin',
            ])
            ->with('golonganDarah:id_golongan_darah,abo,rhesus')
            ->where('id_golongan_darah', $idGolonganDarah)
            ->whereHas('akun', function (Builder $query): void {
                $query->where('peran', 'PENDONOR')
                    ->where('status_akun', 'AKTIF');
            })
            ->orderBy('nama_lengkap')
            ->orderBy('id_pendonor')
            ->get()
            ->map(function (Pendonor $pendonor) use ($calculator): ?object {
                $informasi = $calculator->calculate($pendonor);

                if ($informasi['dapat_mencoba_sekarang'] !== true) {
                    return null;
                }

                return (object) [
                    'id_pendonor' => $pendonor->id_pendonor,
                    'nomor_donor' => $pendonor->nomor_donor,
                    'nama_lengkap' => $pendonor->nama_lengkap,
                    'abo' => $pendonor->golonganDarah->abo,
                    'rhesus' => $pendonor->golonganDarah->rhesus,
                    'tanggal_donor_terakhir' => $informasi['tanggal_donor_terakhir'],
                    'jumlah_donor_tahun_ini' => $informasi['jumlah_donor_tahun_ini'],
                ];
            })
            ->filter()
            ->values();
    }
}

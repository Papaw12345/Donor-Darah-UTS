<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JenisKomponenDarah;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use App\Models\UnitKomponenDarah;
use App\Support\PendonorDonorBerikutnyaCalculator;
use App\Support\PersediaanDarahQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase9BusinessRulesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private ?Petugas $recorder = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-16 03:00:00', 'UTC'));
    }

    public function test_donor_repeat_calculator_supports_default_and_explicit_reference_dates(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-30 03:00:00', 'UTC'));
        $pendonor = $this->createPendonor('LAKI_LAKI');
        $this->createDonation($pendonor, '2026-01-31 09:00:00', 'BERHASIL');
        $this->createDonation($pendonor, '2026-03-20 09:00:00', 'GAGAL');
        $this->createDonation($pendonor, '2026-04-01 09:00:00', 'BERHASIL');

        $calculator = app(PendonorDonorBerikutnyaCalculator::class);
        $default = $calculator->calculate($pendonor);

        $this->assertSame('2026-03-30', $default['tanggal_acuan']->toDateString());
        $this->assertSame('2026-01-31', $default['tanggal_donor_terakhir']->toDateString());
        $this->assertSame('2026-03-31', $default['tanggal_interval_terpenuhi']->toDateString());
        $this->assertSame(1, $default['jumlah_donor_tahun_ini']);
        $this->assertFalse($default['interval_terpenuhi']);
        $this->assertFalse($default['dapat_mencoba_sekarang']);

        $explicit = $calculator->calculate(
            $pendonor,
            CarbonImmutable::parse('2026-03-31', 'Asia/Jakarta')
        );

        $this->assertSame('2026-03-31', $explicit['tanggal_acuan']->toDateString());
        $this->assertSame('2026-01-31', $explicit['tanggal_donor_terakhir']->toDateString());
        $this->assertSame(1, $explicit['jumlah_donor_tahun_ini']);
        $this->assertTrue($explicit['interval_terpenuhi']);
        $this->assertTrue($explicit['dapat_mencoba_sekarang']);
    }

    public function test_donor_repeat_calculator_enforces_male_and_female_annual_boundaries(): void
    {
        $male = $this->createPendonor('LAKI_LAKI');
        $female = $this->createPendonor('PEREMPUAN');

        foreach (range(1, 6) as $month) {
            $this->createDonation(
                $male,
                sprintf('2026-%02d-01 09:00:00', $month),
                'BERHASIL'
            );
        }

        foreach (range(1, 4) as $month) {
            $this->createDonation(
                $female,
                sprintf('2026-%02d-02 09:00:00', $month),
                'BERHASIL'
            );
        }

        $reference = CarbonImmutable::parse('2026-12-31', 'Asia/Jakarta');
        $calculator = app(PendonorDonorBerikutnyaCalculator::class);
        $maleResult = $calculator->calculate($male, $reference);
        $femaleResult = $calculator->calculate($female, $reference);

        $this->assertSame(6, $maleResult['jumlah_donor_tahun_ini']);
        $this->assertSame(6, $maleResult['batas_tahunan']);
        $this->assertFalse($maleResult['frekuensi_terpenuhi']);
        $this->assertSame('2027-01-01', $maleResult['tanggal_donor_berikutnya']->toDateString());

        $this->assertSame(4, $femaleResult['jumlah_donor_tahun_ini']);
        $this->assertSame(4, $femaleResult['batas_tahunan']);
        $this->assertFalse($femaleResult['frekuensi_terpenuhi']);
        $this->assertSame('2027-01-01', $femaleResult['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_booking_uses_the_selected_schedule_date_as_donor_repeat_reference(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonation($pendonor, '2026-09-30 09:00:00', 'BERHASIL');
        $tooEarly = $this->createSchedule('2026-11-29');
        $boundary = $this->createSchedule('2026-11-30');

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $tooEarly))
            ->assertSessionHasErrors([
                'pemesanan' => 'Jadwal belum memenuhi interval donor minimal dua bulan.',
            ]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $boundary))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertDatabaseMissing('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $tooEarly->id_jadwal,
        ]);
        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $boundary->id_jadwal,
            'status_pemesanan' => 'TERJADWAL',
        ]);
    }

    public function test_schedule_listing_and_booking_recheck_share_capacity_behavior(): void
    {
        foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'TIDAK_HADIR'] as $status) {
            $schedule = $this->createSchedule('2026-10-'.str_pad(
                (string) (20 + $this->sequence),
                2,
                '0',
                STR_PAD_LEFT
            ), 1);
            $this->createBooking($this->createPendonor(), $schedule, $status);

            $requester = $this->createPendonor();
            $this->actingAs($requester->akun)
                ->post(route('pendonor.pemesanan.store', $schedule))
                ->assertSessionHasErrors([
                    'pemesanan' => 'Kapasitas jadwal sudah penuh.',
                ]);
        }

        $cancelledSchedule = $this->createSchedule('2026-12-20', 1);
        $this->createBooking(
            $this->createPendonor(),
            $cancelledSchedule,
            'DIBATALKAN'
        );
        $requester = $this->createPendonor();

        $this->actingAs($requester->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertViewHas('jadwal', function ($jadwal) use ($cancelledSchedule): bool {
                $ids = $jadwal->pluck('id_jadwal');

                return $ids->contains($cancelledSchedule->id_jadwal)
                    && $ids->count() === 1;
            });

        $this->actingAs($requester->akun)
            ->post(route('pendonor.pemesanan.store', $cancelledSchedule))
            ->assertRedirect(route('pendonor.pemesanan.index'));
    }

    public function test_inventory_queries_share_eligibility_grouping_and_low_stock_rules(): void
    {
        $reference = '2026-09-16';
        $workflow = $this->createUnitWorkflow();
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $tc = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $bNegative = $this->createBloodGroup('B', 'NEGATIF');

        $today = $this->createUnit($workflow, $wb, $aPositive, 'TERSEDIA', $reference);
        $future = $this->createUnit($workflow, $wb, $aPositive, 'TERSEDIA', '2026-09-20');
        $otherCombination = $this->createUnit($workflow, $prc, $aPositive, 'TERSEDIA', '2026-09-20');
        $expired = $this->createUnit($workflow, $wb, $bNegative, 'TERSEDIA', '2026-09-15');
        $pending = $this->createUnit($workflow, $wb, $bNegative, 'MENUNGGU_PELULUSAN', '2026-09-20');
        $distributed = $this->createUnit($workflow, $wb, $bNegative, 'DIDISTRIBUSIKAN', '2026-09-20');
        $unconfigured = $this->createUnit($workflow, $tc, $bNegative, 'TERSEDIA', '2026-09-20');

        $zeroThreshold = $this->createThreshold($wb, $bNegative, 0);
        $equalThreshold = $this->createThreshold($wb, $aPositive, 2);
        $aboveThreshold = $this->createThreshold($prc, $aPositive, 0);

        $query = app(PersediaanDarahQuery::class);
        $eligibleIds = $query->eligibleUnitsQuery($reference)
            ->orderBy('id_unit')
            ->pluck('id_unit')
            ->all();

        $this->assertSame([
            $today->id_unit,
            $future->id_unit,
            $otherCombination->id_unit,
            $unconfigured->id_unit,
        ], $eligibleIds);
        $this->assertTrue($query->isUnitEligible($today, $reference));
        $this->assertFalse($query->isUnitEligible($expired, $reference));
        $this->assertFalse($query->isUnitEligible($pending, $reference));
        $this->assertFalse($query->isUnitEligible($distributed, $reference));

        $counts = $query->countsByCombinationQuery($reference)
            ->get()
            ->keyBy(fn (object $row): string => $row->id_jenis_komponen.'-'.$row->id_golongan_darah);

        $this->assertCount(3, $counts);
        $this->assertSame(2, (int) $counts[$wb->id_jenis_komponen.'-'.$aPositive->id_golongan_darah]->jumlah_persediaan);
        $this->assertSame(1, (int) $counts[$prc->id_jenis_komponen.'-'.$aPositive->id_golongan_darah]->jumlah_persediaan);
        $this->assertSame(1, (int) $counts[$tc->id_jenis_komponen.'-'.$bNegative->id_golongan_darah]->jumlah_persediaan);
        $this->assertSame(count($eligibleIds), (int) $counts->sum('jumlah_persediaan'));

        $lowStock = $query->lowStockBaseQuery($reference)
            ->orderBy('ambang_persediaan.id_ambang')
            ->get();

        $this->assertSame([
            $zeroThreshold->id_ambang,
            $equalThreshold->id_ambang,
        ], $lowStock->pluck('id_ambang')->all());
        $this->assertSame([0, 2], $lowStock->pluck('jumlah_persediaan')->map(fn ($count): int => (int) $count)->all());
        $this->assertFalse($lowStock->pluck('id_ambang')->contains($aboveThreshold->id_ambang));
        $this->assertSame(
            2,
            $query->countForCombination(
                $wb->id_jenis_komponen,
                $aPositive->id_golongan_darah,
                $reference
            )
        );
    }

    public function test_distribution_removes_the_unit_from_shared_eligible_inventory(): void
    {
        $petugas = $this->getRecorder();
        $workflow = $this->createUnitWorkflow($petugas);
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('O', 'POSITIF');
        $unit = $this->createUnit(
            $workflow,
            $component,
            $bloodGroup,
            'TERSEDIA',
            '2026-09-16'
        );
        $query = app(PersediaanDarahQuery::class);

        $this->assertSame(1, $query->eligibleUnitsQuery('2026-09-16')->count());

        $this->actingAs($petugas->akun)
            ->post(route('petugas.distribusi.store', $unit))
            ->assertRedirect(route('petugas.distribusi.show', $unit));

        $this->assertSame('DIDISTRIBUSIKAN', $unit->fresh()->status_unit);
        $this->assertSame(0, $query->eligibleUnitsQuery('2026-09-16')->count());
    }

    private function createAccount(
        string $role = 'PENDONOR',
        string $status = 'AKTIF'
    ): Akun {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase9-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPendonor(string $jenisKelamin = 'LAKI_LAKI'): Pendonor
    {
        $account = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => "P9-DNR-{$number}",
            'nama_lengkap' => "Pendonor Phase 9 {$number}",
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '081'.str_pad((string) $number, 9, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createSchedule(
        string $date,
        int $capacity = 10
    ): JadwalPelayanan {
        return JadwalPelayanan::create([
            'tanggal' => $date,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => $capacity,
            'status_jadwal' => 'DIBUKA',
        ]);
    }

    private function createBooking(
        Pendonor $pendonor,
        JadwalPelayanan $schedule,
        string $status
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => now(),
            'kode_checkin' => null,
            'waktu_checkin' => $status === 'CHECK_IN' ? now() : null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createDonation(
        Pendonor $pendonor,
        string $dateTime,
        string $result
    ): Penyumbangan {
        $schedule = $this->createSchedule(
            CarbonImmutable::parse($dateTime, 'Asia/Jakarta')->toDateString()
        );
        $booking = $this->createBooking($pendonor, $schedule, 'SELESAI');
        $recorder = $this->getRecorder();
        $selection = SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $recorder->id_petugas,
            'waktu_seleksi' => $dateTime,
            'berat_badan' => 60,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ]);

        return Penyumbangan::create([
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $recorder->id_petugas,
            'waktu_pengambilan' => $dateTime,
            'volume_ml' => $result === 'BERHASIL' ? 350 : null,
            'hasil_penyumbangan' => $result,
            'alasan_gagal' => $result === 'GAGAL' ? 'Fixture gagal' : null,
        ]);
    }

    private function getRecorder(): Petugas
    {
        if ($this->recorder !== null) {
            return $this->recorder;
        }

        $account = $this->createAccount('PETUGAS');

        return $this->recorder = Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'P9-PETUGAS-'.$this->sequence,
            'nama_petugas' => 'Petugas Phase 9',
        ]);
    }

    private function createUnitWorkflow(?Petugas $recorder = null): array
    {
        $recorder ??= $this->getRecorder();
        $donor = $this->createPendonor();
        $donation = $this->createDonation(
            $donor,
            '2026-09-01 09:00:00',
            'BERHASIL'
        );

        return compact('recorder', 'donation');
    }

    private function createComponent(
        string $code,
        string $name
    ): JenisKomponenDarah {
        return JenisKomponenDarah::create([
            'kode_komponen' => $code,
            'nama_komponen' => $name,
        ]);
    }

    private function createBloodGroup(string $abo, string $rhesus): GolonganDarah
    {
        return GolonganDarah::create([
            'abo' => $abo,
            'rhesus' => $rhesus,
        ]);
    }

    private function createThreshold(
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        int $minimum
    ): AmbangPersediaan {
        return AmbangPersediaan::create([
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'jumlah_minimum' => $minimum,
        ]);
    }

    private function createUnit(
        array $workflow,
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        string $status,
        string $expiryDate
    ): UnitKomponenDarah {
        $number = ++$this->sequence;
        $released = in_array(
            $status,
            ['TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'],
            true
        );

        return UnitKomponenDarah::create([
            'nomor_unit' => "P9-UNIT-{$number}",
            'id_penyumbangan' => $workflow['donation']->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $workflow['recorder']->id_petugas,
            'id_petugas_pelulus' => $released
                ? $workflow['recorder']->id_petugas
                : null,
            'tanggal_pembuatan' => '2026-09-01',
            'tanggal_kedaluwarsa' => $expiryDate,
            'waktu_pelulusan' => $released ? '2026-09-02 10:00:00' : null,
            'status_unit' => $status,
            'catatan_pelulusan' => $released ? 'Lulus' : null,
            'waktu_distribusi' => $status === 'DIDISTRIBUSIKAN'
                ? '2026-09-10 10:00:00'
                : null,
        ]);
    }
}

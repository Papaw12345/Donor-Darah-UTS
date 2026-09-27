<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class Phase12BatchCSelectionBloodGroupTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private Petugas $petugas;

    private GolonganDarah $bloodGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-16 03:00:00', 'UTC'));
        $account = $this->account('PETUGAS');
        $this->petugas = Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'P12C-TEST',
            'nama_petugas' => 'Petugas Seleksi',
        ]);
        $this->bloodGroup = GolonganDarah::create(['abo' => 'A', 'rhesus' => 'POSITIF']);
        $this->actingAs($account);
    }

    public function test_required_fields_enum_reasons_and_column_capacity(): void
    {
        [, $booking] = $this->fixture();

        $this->post(route('petugas.seleksi.store', $booking), [])
            ->assertSessionHasErrors([
                'berat_badan', 'tekanan_sistolik', 'tekanan_diastolik',
                'denyut_nadi', 'suhu_tubuh', 'kadar_hb',
                'hasil_pemeriksaan_kesehatan', 'keputusan_seleksi',
            ]);

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'keputusan_seleksi' => 'OTOMATIS',
        ]))->assertSessionHasErrors('keputusan_seleksi');

        foreach (['DITUNDA', 'DITOLAK'] as $decision) {
            $this->post(route('petugas.seleksi.store', $booking), $this->payload([
                'keputusan_seleksi' => $decision,
                'alasan_keputusan' => null,
            ]))->assertSessionHasErrors('alasan_keputusan');
        }

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'hasil_pemeriksaan_kesehatan' => null,
        ]))->assertSessionHasErrors('hasil_pemeriksaan_kesehatan');

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'berat_badan' => '1000.00',
            'tekanan_sistolik' => 32768,
            'tekanan_diastolik' => -32769,
            'denyut_nadi' => '12.5',
            'suhu_tubuh' => '1000.0',
            'kadar_hb' => '12.34',
        ]))->assertSessionHasErrors([
            'berat_badan', 'tekanan_sistolik', 'tekanan_diastolik',
            'denyut_nadi', 'suhu_tubuh', 'kadar_hb',
        ]);

        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    #[DataProvider('nonPositiveMeasurements')]
    public function test_every_measurement_rejects_zero_and_negative(string $field, int|float|string $value): void
    {
        [, $booking] = $this->fixture();

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            $field => $value,
            'keputusan_seleksi' => 'DITUNDA',
            'alasan_keputusan' => 'Keputusan petugas.',
        ]))->assertSessionHasErrors($field);

        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public static function nonPositiveMeasurements(): array
    {
        $cases = [];
        foreach (['berat_badan', 'tekanan_sistolik', 'tekanan_diastolik', 'denyut_nadi', 'suhu_tubuh', 'kadar_hb'] as $field) {
            $cases[$field.' zero'] = [$field, 0];
            $cases[$field.' negative'] = [$field, -1];
        }

        return $cases;
    }

    #[DataProvider('acceptedLayakBoundaries')]
    public function test_layak_accepts_each_objective_boundary(array $measurements): void
    {
        [, $booking] = $this->fixture();

        $this->post(route('petugas.seleksi.store', $booking), $this->payload($measurements))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('petugas.seleksi.show', $booking));

        $this->assertSame('LAYAK', SeleksiDonor::query()->sole()->keputusan_seleksi);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
    }

    public static function acceptedLayakBoundaries(): array
    {
        return [
            'baseline' => [[]],
            'weight 45' => [['berat_badan' => 45]],
            'systolic 90' => [['tekanan_sistolik' => 90, 'tekanan_diastolik' => 60]],
            'systolic 160' => [['tekanan_sistolik' => 160]],
            'diastolic 60' => [['tekanan_diastolik' => 60]],
            'diastolic 100 and difference 21' => [['tekanan_sistolik' => 121, 'tekanan_diastolik' => 100]],
            'pulse 50' => [['denyut_nadi' => 50]],
            'pulse 100' => [['denyut_nadi' => 100]],
            'temperature 36.5' => [['suhu_tubuh' => 36.5]],
            'temperature 37.5' => [['suhu_tubuh' => 37.5]],
            'hemoglobin 12.5' => [['kadar_hb' => 12.5]],
            'hemoglobin 17' => [['kadar_hb' => 17]],
        ];
    }

    #[DataProvider('rejectedLayakMeasurements')]
    public function test_layak_rejects_out_of_range_measurements(array $measurements): void
    {
        [, $booking] = $this->fixture();

        $this->post(route('petugas.seleksi.store', $booking), $this->payload($measurements))
            ->assertSessionHasErrors('keputusan_seleksi');

        $this->assertDatabaseCount('seleksi_donor', 0);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
    }

    public static function rejectedLayakMeasurements(): array
    {
        return [
            'weight below 45' => [['berat_badan' => 44.99]],
            'systolic below 90' => [['tekanan_sistolik' => 89, 'tekanan_diastolik' => 60]],
            'systolic above 160' => [['tekanan_sistolik' => 161]],
            'diastolic below 60' => [['tekanan_diastolik' => 59]],
            'diastolic above 100' => [['tekanan_diastolik' => 101]],
            'pressure difference exactly 20' => [['tekanan_sistolik' => 120, 'tekanan_diastolik' => 100]],
            'pulse below 50' => [['denyut_nadi' => 49]],
            'pulse above 100' => [['denyut_nadi' => 101]],
            'temperature below 36.5' => [['suhu_tubuh' => 36.4]],
            'temperature above 37.5' => [['suhu_tubuh' => 37.6]],
            'hemoglobin below 12.5' => [['kadar_hb' => 12.4]],
            'hemoglobin above 17' => [['kadar_hb' => 17.1]],
        ];
    }

    public function test_age_is_checked_on_selection_date_in_jakarta_without_maximum(): void
    {
        [, $exactBirthday] = $this->fixture(birthDate: '2009-09-16');
        $this->post(route('petugas.seleksi.store', $exactBirthday), $this->payload())
            ->assertSessionHasNoErrors();

        [, $underSeventeen] = $this->fixture(birthDate: '2009-09-17');
        $this->post(route('petugas.seleksi.store', $underSeventeen), $this->payload())
            ->assertSessionHasErrors('keputusan_seleksi');

        [, $overSixtyFive] = $this->fixture(birthDate: '1950-01-01');
        $this->post(route('petugas.seleksi.store', $overSixtyFive), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('seleksi_donor', 2);
    }

    #[DataProvider('otherDecisions')]
    public function test_ditunda_and_ditolak_keep_positive_outside_gate_values_and_reason(string $decision): void
    {
        [, $booking] = $this->fixture(birthDate: '2009-09-17');
        $payload = $this->payload([
            'berat_badan' => 1,
            'tekanan_sistolik' => 1,
            'tekanan_diastolik' => 1,
            'denyut_nadi' => 1,
            'suhu_tubuh' => 0.1,
            'kadar_hb' => 0.1,
            'keputusan_seleksi' => $decision,
            'alasan_keputusan' => 'Keputusan Petugas.',
        ]);

        $this->post(route('petugas.seleksi.store', $booking), $payload)
            ->assertSessionHasNoErrors();

        $selection = SeleksiDonor::query()->sole();
        $this->assertSame($decision, $selection->keputusan_seleksi);
        $this->assertSame('Keputusan Petugas.', $selection->alasan_keputusan);
        $this->assertSame('SELESAI', $booking->fresh()->status_pemesanan);
    }

    public static function otherDecisions(): array
    {
        return ['DITUNDA' => ['DITUNDA'], 'DITOLAK' => ['DITOLAK']];
    }

    #[DataProvider('allDecisions')]
    public function test_null_blood_group_requires_valid_master_for_every_decision(string $decision): void
    {
        [$donor, $booking] = $this->fixture(confirmed: false);
        $payload = $this->payload([
            'keputusan_seleksi' => $decision,
            'alasan_keputusan' => $decision === 'LAYAK' ? null : 'Keputusan Petugas.',
        ]);

        $this->post(route('petugas.seleksi.store', $booking), $payload)
            ->assertSessionHasErrors('id_golongan_darah');
        $this->post(route('petugas.seleksi.store', $booking), array_merge($payload, [
            'id_golongan_darah' => 999999,
        ]))->assertSessionHasErrors('id_golongan_darah');
        $this->assertNull($donor->fresh()->id_golongan_darah);
        $this->assertDatabaseCount('seleksi_donor', 0);

        $this->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('name="id_golongan_darah" required', false);

        $this->post(route('petugas.seleksi.store', $booking), array_merge($payload, [
            'id_golongan_darah' => $this->bloodGroup->id_golongan_darah,
        ]))->assertSessionHasNoErrors();

        $this->assertSame($this->bloodGroup->id_golongan_darah, $donor->fresh()->id_golongan_darah);
        $this->assertSame($decision, SeleksiDonor::query()->sole()->keputusan_seleksi);
    }

    public static function allDecisions(): array
    {
        return ['LAYAK' => ['LAYAK'], 'DITUNDA' => ['DITUNDA'], 'DITOLAK' => ['DITOLAK']];
    }

    public function test_blood_group_update_rolls_back_if_selection_insert_fails(): void
    {
        [$donor, $booking] = $this->fixture(confirmed: false);
        SeleksiDonor::creating(function (): void {
            throw new RuntimeException('Simulated selection insert failure.');
        });

        $this->withoutExceptionHandling();
        try {
            $this->post(route('petugas.seleksi.store', $booking), $this->payload([
                'id_golongan_darah' => $this->bloodGroup->id_golongan_darah,
            ]));
            $this->fail('Selection creation should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated selection insert failure.', $exception->getMessage());
        } finally {
            SeleksiDonor::flushEventListeners();
        }

        $this->assertNull($donor->fresh()->id_golongan_darah);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_confirmed_blood_group_is_read_only_and_hostile_change_is_rejected(): void
    {
        [$donor, $booking] = $this->fixture();
        $otherGroup = GolonganDarah::create(['abo' => 'B', 'rhesus' => 'NEGATIF']);

        $this->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('A Positif')
            ->assertDontSee('name="id_golongan_darah"', false);

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'id_golongan_darah' => $otherGroup->id_golongan_darah,
        ]))->assertSessionHasErrors('id_golongan_darah');

        $this->assertSame($this->bloodGroup->id_golongan_darah, $donor->fresh()->id_golongan_darah);
        $this->assertDatabaseCount('seleksi_donor', 0);

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'id_golongan_darah' => $this->bloodGroup->id_golongan_darah,
        ]))->assertSessionHasNoErrors();

        $this->assertSame($this->bloodGroup->id_golongan_darah, $donor->fresh()->id_golongan_darah);
    }

    public function test_second_petugas_cannot_replace_selection_or_blood_group(): void
    {
        [$donor, $booking] = $this->fixture(confirmed: false);
        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'id_golongan_darah' => $this->bloodGroup->id_golongan_darah,
        ]))->assertSessionHasNoErrors();
        $firstSelection = SeleksiDonor::query()->sole()->getAttributes();

        $secondAccount = $this->account('PETUGAS');
        Petugas::create([
            'id_akun' => $secondAccount->id_akun,
            'nomor_petugas' => 'P12C-SECOND',
            'nama_petugas' => 'Petugas Kedua',
        ]);
        $otherGroup = GolonganDarah::create(['abo' => 'O', 'rhesus' => 'NEGATIF']);

        $this->actingAs($secondAccount)
            ->post(route('petugas.seleksi.store', $booking), $this->payload([
                'id_golongan_darah' => $otherGroup->id_golongan_darah,
            ]))->assertStatus(409);

        $this->assertSame($firstSelection, SeleksiDonor::query()->sole()->getAttributes());
        $this->assertSame($this->bloodGroup->id_golongan_darah, $donor->fresh()->id_golongan_darah);
        $this->assertDatabaseCount('seleksi_donor', 1);
    }

    public function test_layak_keeps_checkin_and_allows_existing_donation_flow_after_schedule_end(): void
    {
        [$donor, $booking] = $this->fixture(scheduleOverrides: [
            'tanggal' => '2020-01-01',
            'status_jadwal' => 'DIBATALKAN',
        ]);

        $this->post(route('petugas.seleksi.store', $booking), $this->payload([
            'id_petugas' => 999999,
            'id_pendonor' => 999999,
            'waktu_seleksi' => '2000-01-01 00:00:00',
        ]))->assertSessionHasNoErrors();

        $selection = SeleksiDonor::query()->sole();
        $this->assertSame($this->petugas->id_petugas, $selection->id_petugas);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $selection->waktu_seleksi->format('Y-m-d H:i:s'));
        $this->assertSame($donor->id_pendonor, $booking->id_pendonor);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);

        $this->get(route('petugas.penyumbangan.show', $selection))->assertOk();
        $this->post(route('petugas.penyumbangan.store', $selection), [
            'waktu_pengambilan' => '2026-09-16T10:00',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('penyumbangan', 1);
    }

    private function account(string $role): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase12c-{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function fixture(
        string $birthDate = '1995-01-01',
        bool $confirmed = true,
        array $scheduleOverrides = []
    ): array {
        $account = $this->account('PENDONOR');
        $number = $this->sequence;
        $donor = Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $confirmed ? $this->bloodGroup->id_golongan_darah : null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => $birthDate,
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0819'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
        ]);
        $schedule = JadwalPelayanan::create(array_merge([
            'tanggal' => '2026-09-16',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ], $scheduleOverrides));
        $booking = PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2026-09-15 09:00:00',
            'kode_checkin' => 'UDD-'.strtoupper(str_pad(dechex($number), 12, '0', STR_PAD_LEFT)),
            'waktu_checkin' => '2026-09-16 08:00:00',
            'status_pemesanan' => 'CHECK_IN',
        ]);
        $question = PertanyaanKuesioner::create([
            'teks_pertanyaan' => "Pertanyaan {$number}",
            'kategori' => null,
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ]);
        $questionnaire = KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => '2026-09-16 07:30:00',
        ]);
        JawabanKuesioner::create([
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);

        return [$donor, $booking];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'berat_badan' => '60.00',
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => '36.7',
            'kadar_hb' => '13.5',
            'hasil_pemeriksaan_kesehatan' => 'Pemeriksaan selesai.',
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ], $overrides);
    }
}

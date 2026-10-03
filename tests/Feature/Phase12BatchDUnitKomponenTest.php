<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JenisKomponenDarah;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use App\Models\UnitKomponenDarah;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12BatchDUnitKomponenTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private Petugas $petugas;

    private GolonganDarah $bloodGroup;

    private JenisKomponenDarah $component;

    protected function setUp(): void
    {
        parent::setUp();

        $account = $this->account('PETUGAS');
        $this->petugas = Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'P12D-TEST',
            'nama_petugas' => 'Petugas Unit',
        ]);
        $this->bloodGroup = GolonganDarah::create(['abo' => 'A', 'rhesus' => 'POSITIF']);
        $this->component = JenisKomponenDarah::create([
            'kode_komponen' => 'WB',
            'nama_komponen' => 'Whole Blood',
        ]);
        $this->actingAs($account);
    }

    public function test_form_only_exposes_three_inputs_and_uses_source_blood_group(): void
    {
        [$donor, $donation] = $this->fixture();

        $page = $this->get(route('petugas.unit-komponen.show', $donation))
            ->assertOk()
            ->assertSee('Pencatatan Unit Hasil Pengolahan')
            ->assertSee('A Positif');
        foreach (['id_jenis_komponen', 'tanggal_pembuatan', 'tanggal_kedaluwarsa'] as $field) {
            $page->assertSee('name="'.$field.'"', false);
        }
        $page->assertDontSee('name="nomor_unit"', false)
            ->assertDontSee('name="id_golongan_darah"', false);

        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('petugas.unit-komponen.show', $donation));

        $unit = UnitKomponenDarah::query()->sole();
        $this->assertSame($this->expectedNumber($unit), $unit->nomor_unit);
        $this->assertSame($donor->id_golongan_darah, $unit->id_golongan_darah);
        $this->assertSame($donation->id_penyumbangan, $unit->id_penyumbangan);
        $this->assertSame($this->petugas->id_petugas, $unit->id_petugas_pencatat);
        $this->assertSame('MENUNGGU_PELULUSAN', $unit->status_unit);
        $this->assertNull($unit->id_petugas_pelulus);
        $this->assertNull($unit->waktu_pelulusan);
        $this->assertNull($unit->catatan_pelulusan);
        $this->assertNull($unit->waktu_distribusi);
        $this->assertSame($this->payload()['tanggal_kedaluwarsa'], $unit->tanggal_kedaluwarsa->toDateString());
        $this->assertDatabaseMissing('unit_komponen_darah', ['nomor_unit' => 'TMP-']);
        $this->assertSame(0, UnitKomponenDarah::query()->where('nomor_unit', 'like', 'TMP-%')->count());

        $this->get(route('petugas.unit-komponen.show', $donation))
            ->assertOk()
            ->assertSee($unit->nomor_unit)
            ->assertSee('A Positif');
    }

    public function test_expired_available_unit_shows_stored_status_and_separate_current_condition(): void
    {
        [, $donation] = $this->fixture();
        $unit = UnitKomponenDarah::create(array_merge($this->payload(), [
            'nomor_unit' => 'UNIT-EXPIRY-TEST',
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_golongan_darah' => $this->bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $this->petugas->id_petugas,
            'status_unit' => 'TERSEDIA',
        ]));
        $before = $unit->fresh()->getAttributes();

        // Tanggal kedaluwarsa masih berlaku sampai akhir hari WIB.
        $this->travelTo(CarbonImmutable::parse('2026-10-20 16:59:59', 'UTC'));
        $this->get(route('petugas.unit-komponen.show', $donation))
            ->assertOk()
            ->assertSee('<span class="status-badge status-success">Tersedia</span>', false)
            ->assertDontSee('Kondisi Saat Ini: Kedaluwarsa');

        // UTC masih 20 Oktober, tetapi di Jakarta sudah 21 Oktober.
        $this->travelTo(CarbonImmutable::parse('2026-10-20 17:00:00', 'UTC'));
        $this->get(route('petugas.unit-komponen.show', $donation))
            ->assertOk()
            ->assertSeeInOrder([
                '<th scope="col">Status</th>',
                $unit->nomor_unit,
                '<span class="status-badge status-success">Tersedia</span>',
                '<div class="form-hint">Kondisi Saat Ini: Kedaluwarsa</div>',
            ], false);

        $this->assertSame('TERSEDIA', $unit->fresh()->status_unit);
        $this->assertSame($before, $unit->fresh()->getAttributes());
    }

    public function test_multiple_units_from_one_successful_donation_may_repeat_component(): void
    {
        [, $donation] = $this->fixture();

        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload())
            ->assertSessionHasNoErrors();
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
            'tanggal_kedaluwarsa' => '2026-10-21',
        ]))->assertSessionHasNoErrors();

        $units = UnitKomponenDarah::query()->orderBy('id_unit')->get();
        $this->assertCount(2, $units);
        $this->assertNotSame($units[0]->nomor_unit, $units[1]->nomor_unit);
        foreach ($units as $unit) {
            $this->assertSame($this->expectedNumber($unit), $unit->nomor_unit);
            $this->assertSame($donation->id_penyumbangan, $unit->id_penyumbangan);
            $this->assertSame($this->component->id_jenis_komponen, $unit->id_jenis_komponen);
            $this->assertSame('MENUNGGU_PELULUSAN', $unit->status_unit);
        }
        $this->assertSame(0, UnitKomponenDarah::query()->where('nomor_unit', 'like', 'TMP-%')->count());
    }

    public function test_hostile_number_is_rejected_even_when_it_matches_predicted_final_format(): void
    {
        [, $donation] = $this->fixture();

        foreach (['UNIT-MANUAL', 'UNT-000001', null] as $number) {
            $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
                'nomor_unit' => $number,
            ]))->assertSessionHasErrors('nomor_unit');
        }

        $this->assertDatabaseCount('unit_komponen_darah', 0);
    }

    public function test_hostile_blood_group_is_rejected_even_when_it_matches_source(): void
    {
        [, $donation] = $this->fixture();
        $other = GolonganDarah::create(['abo' => 'B', 'rhesus' => 'NEGATIF']);

        foreach ([$this->bloodGroup->id_golongan_darah, $other->id_golongan_darah, null] as $groupId) {
            $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
                'id_golongan_darah' => $groupId,
            ]))->assertSessionHasErrors('id_golongan_darah');
        }

        $this->assertDatabaseCount('unit_komponen_darah', 0);
    }

    public function test_missing_source_blood_group_and_failed_donation_cannot_create_units(): void
    {
        [$donor, $donation] = $this->fixture(confirmed: false);
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload())
            ->assertStatus(409);
        $this->assertNull($donor->fresh()->id_golongan_darah);

        [, $failed] = $this->fixture(outcome: 'GAGAL');
        $this->get(route('petugas.unit-komponen.show', $failed))->assertStatus(409);
        $this->post(route('petugas.unit-komponen.store', $failed), $this->payload())
            ->assertStatus(409);

        $this->assertDatabaseCount('unit_komponen_darah', 0);
    }

    public function test_date_validation_rejects_earlier_expiry_and_accepts_equal_or_later(): void
    {
        [, $donation] = $this->fixture();

        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
            'tanggal_kedaluwarsa' => '2026-10-14',
        ]))->assertSessionHasErrors('tanggal_kedaluwarsa');
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
            'tanggal_pembuatan' => 'invalid',
        ]))->assertSessionHasErrors('tanggal_pembuatan');
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
            'tanggal_kedaluwarsa' => 'invalid',
        ]))->assertSessionHasErrors('tanggal_kedaluwarsa');
        $this->assertDatabaseCount('unit_komponen_darah', 0);

        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
            'tanggal_kedaluwarsa' => '2026-10-15',
        ]))->assertSessionHasNoErrors();
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload([
            'tanggal_kedaluwarsa' => '2026-10-20',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(
            ['2026-10-15', '2026-10-20'],
            UnitKomponenDarah::query()->orderBy('id_unit')->get()
                ->map(fn (UnitKomponenDarah $unit) => $unit->tanggal_kedaluwarsa->toDateString())->all()
        );
    }

    public function test_route_and_authenticated_petugas_remain_authoritative(): void
    {
        [, $target] = $this->fixture();
        [, $other] = $this->fixture();
        $otherAccount = $this->account('PETUGAS');
        $otherPetugas = Petugas::create([
            'id_akun' => $otherAccount->id_akun,
            'nomor_petugas' => 'P12D-OTHER',
            'nama_petugas' => 'Petugas Lain',
        ]);

        $this->post(route('petugas.unit-komponen.store', $target), $this->payload([
            'id_penyumbangan' => $other->id_penyumbangan,
            'id_pendonor' => $other->seleksiDonor->pemesananDonor->id_pendonor,
            'id_petugas_pencatat' => $otherPetugas->id_petugas,
            'status_unit' => 'TERSEDIA',
            'id_petugas_pelulus' => $otherPetugas->id_petugas,
        ]))->assertSessionHasNoErrors();

        $unit = UnitKomponenDarah::query()->sole();
        $this->assertSame($target->id_penyumbangan, $unit->id_penyumbangan);
        $this->assertSame($this->petugas->id_petugas, $unit->id_petugas_pencatat);
        $this->assertSame('MENUNGGU_PELULUSAN', $unit->status_unit);
        $this->assertNull($unit->id_petugas_pelulus);
        $this->assertSame(
            $target->seleksiDonor->pemesananDonor->pendonor->id_golongan_darah,
            $unit->id_golongan_darah
        );
        $this->assertDatabaseMissing('unit_komponen_darah', [
            'id_penyumbangan' => $other->id_penyumbangan,
        ]);
    }

    public function test_new_unit_can_be_processed_by_existing_release_flow(): void
    {
        [, $donation] = $this->fixture();
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload())
            ->assertSessionHasNoErrors();
        $unit = UnitKomponenDarah::query()->sole();

        $this->get(route('petugas.pelulusan.index'))
            ->assertOk()
            ->assertSee($unit->nomor_unit);
        $this->post(route('petugas.pelulusan.store', $unit), [
            'hasil_pelulusan' => 'TERSEDIA',
            'catatan_pelulusan' => 'Lulus pemeriksaan mutu.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('TERSEDIA', $unit->fresh()->status_unit);
        $this->assertSame($this->expectedNumber($unit), $unit->fresh()->nomor_unit);
    }

    public function test_final_number_collision_rolls_back_new_unit(): void
    {
        [, $donation] = $this->fixture();
        $this->post(route('petugas.unit-komponen.store', $donation), $this->payload())
            ->assertSessionHasNoErrors();
        $first = UnitKomponenDarah::query()->sole();
        $reservedFinalNumber = 'UNT-'.str_pad((string) ($first->id_unit + 2), 6, '0', STR_PAD_LEFT);

        UnitKomponenDarah::create([
            'nomor_unit' => $reservedFinalNumber,
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_jenis_komponen' => $this->component->id_jenis_komponen,
            'id_golongan_darah' => $this->bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $this->petugas->id_petugas,
            'tanggal_pembuatan' => '2026-10-15',
            'tanggal_kedaluwarsa' => '2026-10-20',
            'status_unit' => 'MENUNGGU_PELULUSAN',
        ]);

        $this->withoutExceptionHandling();
        try {
            $this->post(route('petugas.unit-komponen.store', $donation), $this->payload());
            $this->fail('Final-number collision should have failed.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('UNIQUE', strtoupper($exception->getMessage()));
        }

        $this->assertDatabaseCount('unit_komponen_darah', 2);
        $this->assertSame(0, UnitKomponenDarah::query()->where('nomor_unit', 'like', 'TMP-%')->count());
    }

    private function expectedNumber(UnitKomponenDarah $unit): string
    {
        return 'UNT-'.str_pad((string) $unit->id_unit, 6, '0', STR_PAD_LEFT);
    }

    private function account(string $role): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase12d-{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function fixture(bool $confirmed = true, string $outcome = 'BERHASIL'): array
    {
        $account = $this->account('PENDONOR');
        $number = $this->sequence;
        $donor = Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $confirmed ? $this->bloodGroup->id_golongan_darah : null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0819'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
        ]);
        $schedule = JadwalPelayanan::create([
            'tanggal' => '2020-01-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBATALKAN',
        ]);
        $booking = PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2020-01-01 07:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => '2020-01-01 08:00:00',
            'status_pemesanan' => 'SELESAI',
        ]);
        $selection = SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $this->petugas->id_petugas,
            'waktu_seleksi' => '2020-01-01 08:30:00',
            'berat_badan' => 60,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => 'Pemeriksaan selesai.',
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ]);
        $donation = Penyumbangan::create([
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $this->petugas->id_petugas,
            'waktu_pengambilan' => '2020-01-01 09:00:00',
            'volume_ml' => $outcome === 'BERHASIL' ? 350 : null,
            'hasil_penyumbangan' => $outcome,
            'alasan_gagal' => $outcome === 'GAGAL' ? 'Tidak berhasil.' : null,
        ]);

        return [$donor, $donation];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id_jenis_komponen' => $this->component->id_jenis_komponen,
            'tanggal_pembuatan' => '2026-10-15',
            'tanggal_kedaluwarsa' => '2026-10-20',
        ], $overrides);
    }
}

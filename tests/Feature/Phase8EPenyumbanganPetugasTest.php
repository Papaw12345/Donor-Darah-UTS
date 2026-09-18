<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasPenyumbanganController;
use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8EPenyumbanganPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_exact_routes_and_access_require_active_petugas(): void
    {
        $getRoute = Route::getRoutes()->getByName('petugas.penyumbangan.show');
        $postRoute = Route::getRoutes()->getByName('petugas.penyumbangan.store');

        $this->assertNotNull($getRoute);
        $this->assertSame('petugas/seleksi/{seleksi}/penyumbangan', $getRoute->uri());
        $this->assertSame(['GET', 'HEAD'], $getRoute->methods());
        $this->assertSame(PetugasPenyumbanganController::class.'@show', $getRoute->getActionName());
        $this->assertNotNull($postRoute);
        $this->assertSame('petugas/seleksi/{seleksi}/penyumbangan', $postRoute->uri());
        $this->assertSame(['POST'], $postRoute->methods());
        $this->assertSame(PetugasPenyumbanganController::class.'@store', $postRoute->getActionName());

        foreach ([$getRoute, $postRoute] as $route) {
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('active', $route->gatherMiddleware());
            $this->assertContains('role:PETUGAS', $route->gatherMiddleware());
        }

        $this->assertNull(Route::getRoutes()->getByName('petugas.penyumbangan.edit'));
        $this->assertNull(Route::getRoutes()->getByName('petugas.penyumbangan.update'));
        $this->assertNull(Route::getRoutes()->getByName('petugas.penyumbangan.destroy'));

        [, , $seleksi] = $this->createFixture();
        $this->get(route('petugas.penyumbangan.show', $seleksi))->assertRedirect(route('login'));
        $this->post(route('petugas.penyumbangan.store', $seleksi), $this->validPayload())
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $akun = $this->createAccount($role);
            $this->actingAs($akun)
                ->get(route('petugas.penyumbangan.show', $seleksi))
                ->assertForbidden();
            $this->actingAs($akun)
                ->post(route('petugas.penyumbangan.store', $seleksi), $this->validPayload())
                ->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)
            ->get(route('petugas.penyumbangan.show', $seleksi))
            ->assertRedirect(route('login'));
    }

    public function test_missing_profile_route_binding_and_hostile_ids_cannot_change_authority(): void
    {
        [, $targetBooking, $targetSeleksi] = $this->createFixture(donorName: 'Pendonor Target');
        [$otherDonor, $otherBooking, $otherSeleksi] = $this->createFixture(donorName: 'Pendonor Lain');
        $accountWithoutProfile = $this->createAccount('PETUGAS');
        $countBefore = Petugas::query()->count();

        $this->actingAs($accountWithoutProfile)
            ->get(route('petugas.penyumbangan.show', $targetSeleksi))
            ->assertStatus(409);
        $this->assertSame($countBefore, Petugas::query()->count());

        $petugas = $this->createPetugas();
        $hostilePetugas = $this->createPetugas();
        $query = [
            'seleksi' => $targetSeleksi,
            'id_seleksi' => $otherSeleksi->id_seleksi,
            'id_pemesanan' => $otherBooking->id_pemesanan,
            'id_pendonor' => $otherDonor->id_pendonor,
            'id_petugas_pencatat' => $hostilePetugas->id_petugas,
            'id_penyumbangan' => 999999,
        ];

        $this->actingAs($petugas->akun)
            ->get(route('petugas.penyumbangan.show', $query))
            ->assertOk()
            ->assertSee('Pendonor Target')
            ->assertDontSee('Pendonor Lain');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $targetSeleksi), array_merge($this->validPayload(), $query))
            ->assertRedirect(route('petugas.penyumbangan.show', $targetSeleksi));

        $donation = Penyumbangan::query()->sole();
        $this->assertSame($targetSeleksi->id_seleksi, $donation->id_seleksi);
        $this->assertSame($petugas->id_petugas, $donation->id_petugas_pencatat);
        $this->assertSame($targetBooking->id_pemesanan, $targetSeleksi->id_pemesanan);
        $this->assertDatabaseMissing('penyumbangan', ['id_seleksi' => $otherSeleksi->id_seleksi]);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.penyumbangan.show', ['seleksi' => 999999999]))
            ->assertNotFound();
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', ['seleksi' => 999999999]), $this->validPayload())
            ->assertNotFound();
    }

    public function test_new_form_requires_layak_checked_in_selection_but_not_current_schedule_state(): void
    {
        $petugas = $this->createPetugas();
        [$donor, $booking, $seleksi] = $this->createFixture(
            scheduleOverrides: [
                'tanggal' => '2020-01-01',
                'jam_mulai' => '01:00',
                'jam_selesai' => '02:00',
                'status_jadwal' => 'DIBATALKAN',
            ],
            donorName: 'Pendonor Historis',
            donorNumber: 'DNR-8E'
        );
        $before = [$booking->getAttributes(), $seleksi->getAttributes()];

        $this->actingAs($petugas->akun)
            ->get(route('petugas.penyumbangan.show', $seleksi))
            ->assertOk()
            ->assertSee('Form Penyumbangan')
            ->assertSee('Pendonor Historis')
            ->assertSee('DNR-8E')
            ->assertSee('name="waktu_pengambilan"', false)
            ->assertSee('name="volume_ml"', false)
            ->assertSee('BERHASIL')
            ->assertSee('GAGAL');

        $this->assertEquals($before[0], $booking->fresh()->getAttributes());
        $this->assertEquals($before[1], $seleksi->fresh()->getAttributes());
        $this->assertSame($donor->id_pendonor, $booking->id_pendonor);

        foreach (['DITUNDA', 'DITOLAK'] as $decision) {
            [, $invalidBooking, $invalidSeleksi] = $this->createFixture(decision: $decision);
            $this->actingAs($petugas->akun)
                ->get(route('petugas.penyumbangan.show', $invalidSeleksi))
                ->assertStatus(409);
            $this->actingAs($petugas->akun)
                ->post(route('petugas.penyumbangan.store', $invalidSeleksi), $this->validPayload())
                ->assertStatus(409);
            $this->assertSame('CHECK_IN', $invalidBooking->fresh()->status_pemesanan);
        }

        foreach (['TERJADWAL', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            [, $invalidBooking, $invalidSeleksi] = $this->createFixture(status: $status);
            $this->actingAs($petugas->akun)
                ->get(route('petugas.penyumbangan.show', $invalidSeleksi))
                ->assertStatus(409);
            $this->assertDatabaseMissing('penyumbangan', ['id_seleksi' => $invalidSeleksi->id_seleksi]);
            $this->assertSame($status, $invalidBooking->fresh()->status_pemesanan);
        }

        [, $missingCheckinBooking, $missingCheckinSeleksi] = $this->createFixture(checkinAt: null);
        $this->actingAs($petugas->akun)
            ->get(route('petugas.penyumbangan.show', $missingCheckinSeleksi))
            ->assertStatus(409);
        $this->assertNull($missingCheckinBooking->fresh()->waktu_checkin);
    }

    public function test_request_datetime_validation_volume_rules_and_weight_boundaries(): void
    {
        $petugas = $this->createPetugas();

        [, $booking, $seleksi] = $this->createFixture(weight: 45);
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $seleksi), $this->validPayload([
                'waktu_pengambilan' => '2024-04-05T09:15',
                'volume_ml' => 350,
            ]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('penyumbangan', [
            'id_seleksi' => $seleksi->id_seleksi,
            'waktu_pengambilan' => '2024-04-05 09:15:00',
            'volume_ml' => 350,
        ]);
        $this->assertSame('SELESAI', $booking->fresh()->status_pemesanan);

        [, , $at450] = $this->createFixture(weight: 55);
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $at450), $this->validPayload(['volume_ml' => 450]))
            ->assertSessionHasNoErrors();

        foreach ([[44.99, 350], [54.99, 450]] as [$weight, $volume]) {
            [, $invalidBooking, $invalidSeleksi] = $this->createFixture(weight: $weight);
            $this->actingAs($petugas->akun)
                ->post(route('petugas.penyumbangan.store', $invalidSeleksi), $this->validPayload(['volume_ml' => $volume]))
                ->assertSessionHasErrors('volume_ml');
            $this->assertSame('CHECK_IN', $invalidBooking->fresh()->status_pemesanan);
            $this->assertDatabaseMissing('penyumbangan', ['id_seleksi' => $invalidSeleksi->id_seleksi]);
        }

        [, $failedBooking, $failedSeleksi] = $this->createFixture(weight: 44.99);
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $failedSeleksi), $this->validPayload([
                'hasil_penyumbangan' => 'GAGAL',
                'volume_ml' => 350,
            ]))
            ->assertSessionHasErrors('volume_ml');
        $this->assertSame('CHECK_IN', $failedBooking->fresh()->status_pemesanan);

        [, $validationBooking, $validationSeleksi] = $this->createFixture();
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $validationSeleksi), $this->validPayload([
                'waktu_pengambilan' => null,
                'hasil_penyumbangan' => 'LAIN',
                'volume_ml' => 500,
            ]))
            ->assertSessionHasErrors(['waktu_pengambilan', 'hasil_penyumbangan', 'volume_ml']);
        $this->assertSame('CHECK_IN', $validationBooking->fresh()->status_pemesanan);
        $this->assertDatabaseMissing('penyumbangan', ['id_seleksi' => $validationSeleksi->id_seleksi]);

        [, , $successWithoutVolume] = $this->createFixture();
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $successWithoutVolume), $this->validPayload(['volume_ml' => null]))
            ->assertSessionHasErrors('volume_ml');
    }

    public function test_success_and_failed_donations_are_atomic_and_failure_accepts_null_volume_and_reason(): void
    {
        $petugas = $this->createPetugas();
        [, $successBooking, $successSeleksi] = $this->createFixture();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $successSeleksi), $this->validPayload())
            ->assertRedirect(route('petugas.penyumbangan.show', $successSeleksi));
        $this->assertDatabaseHas('penyumbangan', [
            'id_seleksi' => $successSeleksi->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'hasil_penyumbangan' => 'BERHASIL',
        ]);
        $this->assertSame('SELESAI', $successBooking->fresh()->status_pemesanan);

        [, $failedBooking, $failedSeleksi] = $this->createFixture();
        $this->actingAs($petugas->akun)
            ->post(route('petugas.penyumbangan.store', $failedSeleksi), $this->validPayload([
                'hasil_penyumbangan' => 'GAGAL',
                'volume_ml' => null,
                'alasan_gagal' => null,
            ]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('penyumbangan', [
            'id_seleksi' => $failedSeleksi->id_seleksi,
            'volume_ml' => null,
            'hasil_penyumbangan' => 'GAGAL',
            'alasan_gagal' => null,
        ]);
        $this->assertSame('SELESAI', $failedBooking->fresh()->status_pemesanan);
        $this->assertDatabaseCount('unit_komponen_darah', 0);
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_existing_donation_is_historical_read_only_and_second_submit_preserves_first_transaction(): void
    {
        $recorder = $this->createPetugas();
        $secondPetugas = $this->createPetugas();
        [$donor, $booking, $seleksi] = $this->createFixture(
            scheduleOverrides: ['tanggal' => '2020-01-01', 'status_jadwal' => 'DIBATALKAN']
        );
        $this->actingAs($recorder->akun)
            ->post(route('petugas.penyumbangan.store', $seleksi), $this->validPayload([
                'waktu_pengambilan' => '2020-01-01T09:00',
                'hasil_penyumbangan' => 'GAGAL',
                'volume_ml' => null,
                'alasan_gagal' => null,
            ]))
            ->assertRedirect(route('petugas.penyumbangan.show', $seleksi));

        $donation = Penyumbangan::query()->sole();
        $before = [$booking->fresh()->getAttributes(), $seleksi->fresh()->getAttributes(), $donation->getAttributes()];

        $page = $this->actingAs($secondPetugas->akun)
            ->get(route('petugas.penyumbangan.show', $seleksi))
            ->assertOk()
            ->assertSee('Penyumbangan Tersimpan')
            ->assertSee('2020-01-01 09:00:00')
            ->assertSee('GAGAL')
            ->assertSee('Petugas');
        $page->assertDontSee('Simpan Penyumbangan')
            ->assertDontSee('action="'.route('petugas.penyumbangan.store', $seleksi).'"', false)
            ->assertDontSee('Edit')
            ->assertDontSee('Hapus')
            ->assertDontSee('Catat Unit')
            ->assertDontSee('Pemberitahuan');

        $this->actingAs($secondPetugas->akun)
            ->post(route('petugas.penyumbangan.store', $seleksi), $this->validPayload([
                'volume_ml' => 450,
                'alasan_gagal' => 'Hostile overwrite',
            ]))
            ->assertStatus(409);
        $this->assertEquals($before[0], $booking->fresh()->getAttributes());
        $this->assertEquals($before[1], $seleksi->fresh()->getAttributes());
        $this->assertEquals($before[2], $donation->fresh()->getAttributes());
        $this->assertSame($donor->id_pendonor, $booking->id_pendonor);
        $this->assertDatabaseCount('penyumbangan', 1);
    }

    public function test_selection_page_navigation_reflects_donation_state_only_for_layak(): void
    {
        $petugas = $this->createPetugas();
        [, $booking, $layak] = $this->createFixture();
        [, , $ditunda] = $this->createFixture(decision: 'DITUNDA');
        [, , $ditolak] = $this->createFixture(decision: 'DITOLAK');

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('Catat Penyumbangan')
            ->assertSee(route('petugas.penyumbangan.show', $layak), false)
            ->assertDontSee('Unit Komponen')
            ->assertDontSee('Pemberitahuan');

        Penyumbangan::create([
            'id_seleksi' => $layak->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'waktu_pengambilan' => '2026-09-16 09:00:00',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ]);
        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('Lihat Penyumbangan');

        foreach ([$ditunda, $ditolak] as $selection) {
            $this->actingAs($petugas->akun)
                ->get(route('petugas.seleksi.show', $selection->pemesananDonor))
                ->assertOk()
                ->assertDontSee('Catat Penyumbangan')
                ->assertDontSee('Lihat Penyumbangan');
        }
    }

    private function createFixture(
        string $decision = 'LAYAK',
        string $status = 'CHECK_IN',
        ?string $checkinAt = '2026-09-16 08:00:00',
        float $weight = 60,
        array $scheduleOverrides = [],
        ?string $donorName = null,
        ?string $donorNumber = null
    ): array {
        $donor = $this->createPendonor($donorName, $donorNumber);
        $jadwal = JadwalPelayanan::create(array_merge([
            'tanggal' => '2026-09-16',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ], $scheduleOverrides));
        $booking = PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'waktu_pemesanan' => '2026-09-16 07:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => $checkinAt,
            'status_pemesanan' => $status,
        ]);
        $selector = $this->createPetugas();
        $seleksi = SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $selector->id_petugas,
            'waktu_seleksi' => '2026-09-16 08:30:00',
            'berat_badan' => $weight,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => $decision,
            'alasan_keputusan' => null,
        ]);

        return [$donor, $booking, $seleksi];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'waktu_pengambilan' => '2026-09-16T09:00',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ], $overrides);
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8e-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(string $status = 'AKTIF'): Petugas
    {
        $akun = $this->createAccount('PETUGAS', $status);
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => "P8E-{$number}",
            'nama_petugas' => "Petugas {$number}",
        ]);
    }

    private function createPendonor(?string $name = null, ?string $donorNumber = null): Pendonor
    {
        $akun = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $akun->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => $donorNumber,
            'nama_lengkap' => $name ?? "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0817'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }
}

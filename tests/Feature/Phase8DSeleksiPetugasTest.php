<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasSeleksiController;
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
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8DSeleksiPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private int $codeSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-16 03:00:00', 'UTC'));
    }

    public function test_exact_routes_and_access_require_active_petugas(): void
    {
        $getRoute = Route::getRoutes()->getByName('petugas.seleksi.show');
        $postRoute = Route::getRoutes()->getByName('petugas.seleksi.store');

        $this->assertNotNull($getRoute);
        $this->assertSame('petugas/pemesanan/{pemesanan}/seleksi', $getRoute->uri());
        $this->assertSame(['GET', 'HEAD'], $getRoute->methods());
        $this->assertSame(PetugasSeleksiController::class.'@show', $getRoute->getActionName());

        $this->assertNotNull($postRoute);
        $this->assertSame('petugas/pemesanan/{pemesanan}/seleksi', $postRoute->uri());
        $this->assertSame(['POST'], $postRoute->methods());
        $this->assertSame(PetugasSeleksiController::class.'@store', $postRoute->getActionName());

        foreach ([$getRoute, $postRoute] as $route) {
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('active', $route->gatherMiddleware());
            $this->assertContains('role:PETUGAS', $route->gatherMiddleware());
        }

        $this->assertNull(Route::getRoutes()->getByName('petugas.seleksi.edit'));
        $this->assertNull(Route::getRoutes()->getByName('petugas.seleksi.update'));
        $this->assertNull(Route::getRoutes()->getByName('petugas.seleksi.destroy'));

        [, $booking] = $this->createValidFixture();

        $this->get(route('petugas.seleksi.show', $booking))
            ->assertRedirect(route('login'));
        $this->post(route('petugas.seleksi.store', $booking), $this->validPayload())
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $account = $this->createAccount($role);

            $this->actingAs($account)
                ->get(route('petugas.seleksi.show', $booking))
                ->assertForbidden();
            $this->actingAs($account)
                ->post(route('petugas.seleksi.store', $booking), $this->validPayload())
                ->assertForbidden();
        }

        $inactivePetugas = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactivePetugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertRedirect(route('login'));
        $this->actingAs($inactivePetugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload())
            ->assertRedirect(route('login'));

        $activePetugas = $this->createPetugas();
        $this->actingAs($activePetugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk();
    }

    public function test_active_petugas_without_profile_is_rejected_without_auto_creation(): void
    {
        $account = $this->createAccount('PETUGAS');
        [, $booking] = $this->createValidFixture();
        $countBefore = Petugas::query()->count();

        $this->actingAs($account)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');

        $this->actingAs($account)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload())
            ->assertStatus(409);

        $this->assertSame($countBefore, Petugas::query()->count());
        $this->assertNull($account->petugas()->first());
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_route_bound_booking_is_the_only_target_authority_and_missing_target_is_404(): void
    {
        $petugas = $this->createPetugas(name: 'Petugas Login');
        [$targetDonor, $targetBooking, $targetQuestionnaire] = $this->createValidFixture(
            donorName: 'Pendonor Target'
        );
        [$otherDonor, $otherBooking, $otherQuestionnaire] = $this->createValidFixture(
            donorName: 'Pendonor Lain'
        );
        $otherPetugas = $this->createPetugas(name: 'Petugas Hostile');

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', [
                'pemesanan' => $targetBooking,
                'id_pemesanan' => $otherBooking->id_pemesanan,
                'id_pendonor' => $otherDonor->id_pendonor,
                'id_petugas' => $otherPetugas->id_petugas,
                'id_seleksi' => 999999,
                'id_kuesioner' => $otherQuestionnaire->id_kuesioner,
                'kode_checkin' => $otherBooking->kode_checkin,
            ]))
            ->assertOk()
            ->assertSee('Pendonor Target')
            ->assertDontSee('Pendonor Lain');

        $payload = array_merge($this->validPayload(), [
            'id_pemesanan' => $otherBooking->id_pemesanan,
            'id_pendonor' => $otherDonor->id_pendonor,
            'id_petugas' => $otherPetugas->id_petugas,
            'id_seleksi' => 999999,
            'id_kuesioner' => $otherQuestionnaire->id_kuesioner,
            'kode_checkin' => $otherBooking->kode_checkin,
        ]);

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $targetBooking), $payload)
            ->assertRedirect(route('petugas.seleksi.show', $targetBooking));

        $selection = SeleksiDonor::query()->sole();
        $this->assertSame($targetBooking->id_pemesanan, $selection->id_pemesanan);
        $this->assertSame($petugas->id_petugas, $selection->id_petugas);
        $this->assertSame($targetDonor->id_pendonor, $targetBooking->id_pendonor);
        $this->assertDatabaseMissing('seleksi_donor', [
            'id_pemesanan' => $otherBooking->id_pemesanan,
        ]);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', ['pemesanan' => 999999999]))
            ->assertNotFound();
        $this->actingAs($petugas->akun)
            ->post(
                route('petugas.seleksi.store', ['pemesanan' => 999999999]),
                $this->validPayload()
            )
            ->assertNotFound();

        $this->assertSame($targetQuestionnaire->id_pemesanan, $targetBooking->id_pemesanan);
    }

    public function test_new_form_requires_checked_in_booking_and_ignores_schedule_filters(): void
    {
        $petugas = $this->createPetugas();
        [$donor, $booking] = $this->createValidFixture(
            donorName: 'Pendonor Historis',
            donorNumber: 'DNR-8D',
            scheduleOverrides: [
                'tanggal' => '2020-01-01',
                'jam_mulai' => '01:00',
                'jam_selesai' => '02:00',
                'status_jadwal' => 'DIBATALKAN',
            ]
        );
        $before = $booking->getAttributes();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('Form Seleksi Donor')
            ->assertSee('name="berat_badan"', false)
            ->assertSee('name="keputusan_seleksi"', false)
            ->assertSee('Pendonor Historis')
            ->assertSee('DNR-8D')
            ->assertSee((string) $booking->id_pemesanan)
            ->assertSee('2020-01-01')
            ->assertSee('01:00')
            ->assertSee('DIBATALKAN')
            ->assertSee('2026-09-16 08:00:00')
            ->assertSee('CHECK_IN');

        $this->assertSame($donor->id_pendonor, $booking->id_pendonor);
        $this->assertEquals($before, $booking->fresh()->getAttributes());
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_ineligible_booking_statuses_are_rejected_without_selection(): void
    {
        $petugas = $this->createPetugas();

        foreach (['TERJADWAL', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            [, $booking] = $this->createValidFixture(status: $status);
            $before = $booking->getAttributes();

            $this->actingAs($petugas->akun)
                ->get(route('petugas.seleksi.show', $booking))
                ->assertStatus(409)
                ->assertSee('Status pemesanan tidak sesuai');

            $this->actingAs($petugas->akun)
                ->post(route('petugas.seleksi.store', $booking), $this->validPayload())
                ->assertStatus(409);

            $this->assertEquals($before, $booking->fresh()->getAttributes(), $status);
            $this->assertDatabaseMissing('seleksi_donor', [
                'id_pemesanan' => $booking->id_pemesanan,
            ]);
        }
    }

    public function test_checked_in_booking_without_timestamp_is_rejected_without_repair(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createValidFixture(checkinAt: null);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertStatus(409)
            ->assertSee('waktu check-in belum tersedia');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload())
            ->assertStatus(409);

        $booking->refresh();
        $this->assertSame('CHECK_IN', $booking->status_pemesanan);
        $this->assertNull($booking->waktu_checkin);
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_missing_questionnaire_or_zero_answers_is_rejected_without_auto_repair(): void
    {
        $petugas = $this->createPetugas();
        [, $missingQuestionnaire] = $this->createValidFixture(withQuestionnaire: false);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $missingQuestionnaire))
            ->assertStatus(409)
            ->assertSee('kuesioner pradonasi belum tersedia');
        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $missingQuestionnaire), $this->validPayload())
            ->assertStatus(409);

        [, $zeroAnswer, $questionnaire] = $this->createValidFixture(withAnswer: false);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $zeroAnswer))
            ->assertStatus(409)
            ->assertSee('belum ada jawaban yang tersimpan');
        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $zeroAnswer), $this->validPayload())
            ->assertStatus(409);

        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseHas('kuesioner_pradonasi', [
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pemesanan' => $zeroAnswer->id_pemesanan,
        ]);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_required_fields_decision_values_and_column_capacity_are_validated(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), [])
            ->assertSessionHasErrors([
                'berat_badan',
                'tekanan_sistolik',
                'tekanan_diastolik',
                'denyut_nadi',
                'suhu_tubuh',
                'kadar_hb',
                'keputusan_seleksi',
            ]);

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), [
                'berat_badan' => '1000.00',
                'tekanan_sistolik' => 32768,
                'tekanan_diastolik' => -32769,
                'denyut_nadi' => '12.5',
                'suhu_tubuh' => '1000.0',
                'kadar_hb' => '12.34',
                'keputusan_seleksi' => 'OTOMATIS_LAYAK',
            ])
            ->assertSessionHasErrors([
                'berat_badan',
                'tekanan_sistolik',
                'tekanan_diastolik',
                'denyut_nadi',
                'suhu_tubuh',
                'kadar_hb',
                'keputusan_seleksi',
            ]);

        $this->assertDatabaseCount('seleksi_donor', 0);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
    }

    public function test_layak_uses_authenticated_petugas_server_time_allows_null_texts_and_creates_no_downstream_rows(): void
    {
        $petugas = $this->createPetugas(name: 'Petugas Pencatat');
        $hostilePetugas = $this->createPetugas(name: 'Petugas Hostile');
        [, $booking] = $this->createValidFixture();
        $payload = $this->validPayload([
            'berat_badan' => '0.00',
            'tekanan_sistolik' => -1,
            'tekanan_diastolik' => 0,
            'denyut_nadi' => 1,
            'suhu_tubuh' => '0.0',
            'kadar_hb' => '0.0',
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
            'id_petugas' => $hostilePetugas->id_petugas,
            'waktu_seleksi' => '2000-01-01 00:00:00',
        ]);

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $payload)
            ->assertRedirect(route('petugas.seleksi.show', $booking))
            ->assertSessionHas('success', 'Seleksi donor berhasil disimpan.');

        $selection = SeleksiDonor::query()->sole();
        $this->assertSame($booking->id_pemesanan, $selection->id_pemesanan);
        $this->assertSame($petugas->id_petugas, $selection->id_petugas);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $selection->waktu_seleksi->format('Y-m-d H:i:s'));
        $this->assertNull($selection->hasil_pemeriksaan_kesehatan);
        $this->assertNull($selection->alasan_keputusan);
        $this->assertSame('LAYAK', $selection->keputusan_seleksi);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
        $this->assertDatabaseCount('seleksi_donor', 1);
        $this->assertNoDownstreamRows();
    }

    public function test_ditunda_creates_selection_and_completes_booking_atomically(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'keputusan_seleksi' => 'DITUNDA',
                'alasan_keputusan' => 'Keputusan petugas.',
            ]))
            ->assertRedirect(route('petugas.seleksi.show', $booking));

        $this->assertDatabaseHas('seleksi_donor', [
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'keputusan_seleksi' => 'DITUNDA',
            'alasan_keputusan' => 'Keputusan petugas.',
        ]);
        $this->assertSame('SELESAI', $booking->fresh()->status_pemesanan);
        $this->assertDatabaseCount('seleksi_donor', 1);
        $this->assertNoDownstreamRows();
    }

    public function test_ditolak_creates_selection_and_completes_booking_atomically(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'keputusan_seleksi' => 'DITOLAK',
            ]))
            ->assertRedirect(route('petugas.seleksi.show', $booking));

        $this->assertDatabaseHas('seleksi_donor', [
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'keputusan_seleksi' => 'DITOLAK',
        ]);
        $this->assertSame('SELESAI', $booking->fresh()->status_pemesanan);
        $this->assertDatabaseCount('seleksi_donor', 1);
        $this->assertNoDownstreamRows();
    }

    public function test_donor_without_blood_group_may_remain_null(): void
    {
        $petugas = $this->createPetugas();
        [$donor, $booking] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertNull($donor->fresh()->id_golongan_darah);
        $this->assertDatabaseHas('seleksi_donor', [
            'id_pemesanan' => $booking->id_pemesanan,
        ]);
    }

    public function test_donor_without_blood_group_may_receive_an_existing_master_value(): void
    {
        $petugas = $this->createPetugas();
        $bloodGroup = $this->createBloodGroup('AB', 'NEGATIF');
        [$donor, $booking] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('name="id_golongan_darah"', false)
            ->assertSee('AB NEGATIF');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($bloodGroup->id_golongan_darah, $donor->fresh()->id_golongan_darah);
        $this->assertDatabaseCount('seleksi_donor', 1);
    }

    public function test_invalid_blood_group_is_rejected_and_existing_group_cannot_be_overwritten(): void
    {
        $petugas = $this->createPetugas();
        [, $invalidBooking] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $invalidBooking), $this->validPayload([
                'id_golongan_darah' => 999999,
            ]))
            ->assertSessionHasErrors('id_golongan_darah');

        $existing = $this->createBloodGroup('A', 'POSITIF');
        $hostile = $this->createBloodGroup('O', 'NEGATIF');
        [$donor, $booking] = $this->createValidFixture(bloodGroup: $existing);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('A POSITIF')
            ->assertDontSee('name="id_golongan_darah"', false);

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'id_golongan_darah' => $hostile->id_golongan_darah,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($existing->id_golongan_darah, $donor->fresh()->id_golongan_darah);
        $this->assertDatabaseCount('seleksi_donor', 1);
    }

    public function test_existing_selection_is_read_only_and_historically_viewable_without_mutation(): void
    {
        $viewer = $this->createPetugas(name: 'Petugas Viewer');
        $recorder = $this->createPetugas(name: 'Petugas Pencatat');
        [$donor, $booking, $questionnaire] = $this->createValidFixture(
            scheduleOverrides: [
                'tanggal' => '2020-01-01',
                'jam_mulai' => '01:00',
                'jam_selesai' => '02:00',
                'status_jadwal' => 'DIBATALKAN',
            ]
        );
        $booking->update(['status_pemesanan' => 'SELESAI']);
        $selection = $this->createSelection($booking, $recorder, [
            'keputusan_seleksi' => 'DITOLAK',
            'hasil_pemeriksaan_kesehatan' => 'Catatan tersimpan',
            'alasan_keputusan' => 'Alasan tersimpan',
        ]);
        $answer = $questionnaire->jawabanKuesioner()->sole();
        $before = [
            'donor' => $donor->getAttributes(),
            'booking' => $booking->fresh()->getAttributes(),
            'schedule' => $booking->jadwalPelayanan->getAttributes(),
            'questionnaire' => $questionnaire->getAttributes(),
            'answer' => $answer->getAttributes(),
            'selection' => $selection->getAttributes(),
        ];
        $counts = $this->businessTableCounts();

        $response = $this->actingAs($viewer->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('Seleksi Tersimpan')
            ->assertSee('2020-01-01')
            ->assertSee('DIBATALKAN')
            ->assertSee('SELESAI')
            ->assertSee('60.25')
            ->assertSee('120')
            ->assertSee('80')
            ->assertSee('72')
            ->assertSee('36.7')
            ->assertSee('13.5')
            ->assertSee('Catatan tersimpan')
            ->assertSee('DITOLAK')
            ->assertSee('Alasan tersimpan')
            ->assertSee('Petugas Pencatat')
            ->assertSee('2026-09-16 02:00:00')
            ->assertDontSee('Form Seleksi Donor')
            ->assertDontSee('name="berat_badan"', false)
            ->assertDontSee('Simpan Seleksi');

        $this->assertSame(1, substr_count($response->getContent(), '<form'));
        $this->actingAs($viewer->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk();

        $this->assertEquals($before['donor'], $donor->fresh()->getAttributes());
        $this->assertEquals($before['booking'], $booking->fresh()->getAttributes());
        $this->assertEquals($before['schedule'], $booking->jadwalPelayanan->fresh()->getAttributes());
        $this->assertEquals($before['questionnaire'], $questionnaire->fresh()->getAttributes());
        $this->assertEquals($before['answer'], $answer->fresh()->getAttributes());
        $this->assertEquals($before['selection'], $selection->fresh()->getAttributes());
        $this->assertSame($counts, $this->businessTableCounts());
    }

    public function test_sequential_double_submission_by_second_petugas_preserves_first_selection_and_blood_group(): void
    {
        $firstPetugas = $this->createPetugas(name: 'Petugas Pertama');
        $secondPetugas = $this->createPetugas(name: 'Petugas Kedua');
        $firstBloodGroup = $this->createBloodGroup('B', 'POSITIF');
        $secondBloodGroup = $this->createBloodGroup('O', 'NEGATIF');
        [$donor, $booking] = $this->createValidFixture();

        $this->actingAs($firstPetugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'id_golongan_darah' => $firstBloodGroup->id_golongan_darah,
                'hasil_pemeriksaan_kesehatan' => 'Data pertama',
            ]))
            ->assertSessionHasNoErrors();

        $firstSelection = SeleksiDonor::query()->sole();
        $firstAttributes = $firstSelection->getAttributes();
        $firstTimestamp = $firstSelection->getRawOriginal('waktu_seleksi');

        $this->travel(5)->minutes();

        $this->actingAs($secondPetugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'id_golongan_darah' => $secondBloodGroup->id_golongan_darah,
                'berat_badan' => '99.99',
                'hasil_pemeriksaan_kesehatan' => 'Data kedua hostile',
                'keputusan_seleksi' => 'DITOLAK',
            ]))
            ->assertStatus(409);

        $stored = SeleksiDonor::query()->sole();
        $this->assertSame($firstAttributes, $stored->getAttributes());
        $this->assertSame($firstPetugas->id_petugas, $stored->id_petugas);
        $this->assertSame($firstTimestamp, $stored->getRawOriginal('waktu_seleksi'));
        $this->assertSame('Data pertama', $stored->hasil_pemeriksaan_kesehatan);
        $this->assertSame($firstBloodGroup->id_golongan_darah, $donor->fresh()->id_golongan_darah);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
        $this->assertDatabaseCount('seleksi_donor', 1);
        $this->assertNoDownstreamRows();
    }

    public function test_new_selection_get_is_strictly_read_only(): void
    {
        $petugas = $this->createPetugas();
        [$donor, $booking, $questionnaire, $question] = $this->createValidFixture();
        $answer = $questionnaire->jawabanKuesioner()->sole();
        $before = [
            'donor' => $donor->getAttributes(),
            'booking' => $booking->getAttributes(),
            'schedule' => $booking->jadwalPelayanan->getAttributes(),
            'questionnaire' => $questionnaire->getAttributes(),
            'question' => $question->getAttributes(),
            'answer' => $answer->getAttributes(),
        ];
        $counts = $this->businessTableCounts();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk();
        $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk();

        $this->assertEquals($before['donor'], $donor->fresh()->getAttributes());
        $this->assertEquals($before['booking'], $booking->fresh()->getAttributes());
        $this->assertEquals($before['schedule'], $booking->jadwalPelayanan->fresh()->getAttributes());
        $this->assertEquals($before['questionnaire'], $questionnaire->fresh()->getAttributes());
        $this->assertEquals($before['question'], $question->fresh()->getAttributes());
        $this->assertEquals($before['answer'], $answer->fresh()->getAttributes());
        $this->assertSame($counts, $this->businessTableCounts());
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_navigation_switches_to_read_only_selection_and_exposes_no_phase_8e_controls(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createValidFixture();

        $questionnaireBefore = $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee(route('petugas.seleksi.show', $booking), false)
            ->assertSee('Seleksi Donor')
            ->assertDontSee('Lihat Seleksi')
            ->assertDontSee('name="berat_badan"', false)
            ->assertDontSee('Penyumbangan')
            ->assertDontSee('Unit Komponen')
            ->assertDontSee('Pelulusan')
            ->assertDontSee('Distribusi');

        $this->assertSame(1, substr_count($questionnaireBefore->getContent(), '<form'));

        $selectionPage = $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee(route('petugas.kuesioner.show', $booking), false)
            ->assertSee('Kembali ke Kuesioner')
            ->assertDontSee('Penyumbangan')
            ->assertDontSee('Catat Penyumbangan')
            ->assertDontSee('BERHASIL')
            ->assertDontSee('GAGAL')
            ->assertDontSee('Unit Komponen')
            ->assertDontSee('Pelulusan')
            ->assertDontSee('Distribusi')
            ->assertDontSee('Pemberitahuan');

        $this->assertSame(2, substr_count($selectionPage->getContent(), '<form'));

        $this->actingAs($petugas->akun)
            ->post(route('petugas.seleksi.store', $booking), $this->validPayload([
                'keputusan_seleksi' => 'DITUNDA',
            ]))
            ->assertSessionHasNoErrors();

        $questionnaireAfter = $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee(route('petugas.seleksi.show', $booking), false)
            ->assertSee('Lihat Seleksi')
            ->assertDontSee('Simpan Seleksi')
            ->assertDontSee('Penyumbangan');

        $this->assertSame(1, substr_count($questionnaireAfter->getContent(), '<form'));

        $existingPage = $this->actingAs($petugas->akun)
            ->get(route('petugas.seleksi.show', $booking))
            ->assertOk()
            ->assertSee('Seleksi Tersimpan')
            ->assertDontSee('Simpan Seleksi')
            ->assertDontSee('Penyumbangan');

        $this->assertSame(1, substr_count($existingPage->getContent(), '<form'));
        $this->assertNoDownstreamRows();
    }

    private function createValidFixture(
        string $status = 'CHECK_IN',
        ?string $checkinAt = '2026-09-16 08:00:00',
        array $scheduleOverrides = [],
        ?string $donorName = null,
        ?string $donorNumber = null,
        bool $withQuestionnaire = true,
        bool $withAnswer = true,
        ?GolonganDarah $bloodGroup = null
    ): array {
        $donor = $this->createPendonor($donorName, $donorNumber, $bloodGroup);
        $booking = $this->createBooking(
            $donor,
            $this->createSchedule($scheduleOverrides),
            $status,
            $checkinAt
        );
        $question = $this->createQuestion();
        $questionnaire = null;

        if ($withQuestionnaire) {
            $questionnaire = KuesionerPradonasi::create([
                'id_pemesanan' => $booking->id_pemesanan,
                'waktu_pengisian' => '2026-09-16 07:30:00',
            ]);

            if ($withAnswer) {
                JawabanKuesioner::create([
                    'id_kuesioner' => $questionnaire->id_kuesioner,
                    'id_pertanyaan' => $question->id_pertanyaan,
                    'jawaban' => 'YA',
                ]);
            }
        }

        return [$donor, $booking, $questionnaire, $question];
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8d-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(
        string $accountStatus = 'AKTIF',
        ?string $name = null
    ): Petugas {
        $account = $this->createAccount('PETUGAS', $accountStatus);
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => "P8D-{$number}",
            'nama_petugas' => $name ?? "Petugas {$number}",
        ]);
    }

    private function createPendonor(
        ?string $name = null,
        ?string $donorNumber = null,
        ?GolonganDarah $bloodGroup = null
    ): Pendonor {
        $account = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $bloodGroup?->id_golongan_darah,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => $donorNumber,
            'nama_lengkap' => $name ?? "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0819'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createSchedule(array $overrides = []): JadwalPelayanan
    {
        return JadwalPelayanan::create(array_merge([
            'tanggal' => '2026-09-16',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ], $overrides));
    }

    private function createBooking(
        Pendonor $donor,
        JadwalPelayanan $schedule,
        string $status = 'CHECK_IN',
        ?string $checkinAt = '2026-09-16 08:00:00'
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2026-09-15 09:00:00',
            'kode_checkin' => $this->nextCode(),
            'waktu_checkin' => $checkinAt,
            'status_pemesanan' => $status,
        ]);
    }

    private function createQuestion(): PertanyaanKuesioner
    {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => 'Pertanyaan seleksi '.$this->sequence,
            'kategori' => null,
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ]);
    }

    private function createBloodGroup(string $abo, string $rhesus): GolonganDarah
    {
        return GolonganDarah::create([
            'abo' => $abo,
            'rhesus' => $rhesus,
        ]);
    }

    private function createSelection(
        PemesananDonor $booking,
        Petugas $petugas,
        array $overrides = []
    ): SeleksiDonor {
        return SeleksiDonor::create(array_merge([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'waktu_seleksi' => '2026-09-16 02:00:00',
            'berat_badan' => '60.25',
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => '36.7',
            'kadar_hb' => '13.5',
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'berat_badan' => '60.25',
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => '36.7',
            'kadar_hb' => '13.5',
            'hasil_pemeriksaan_kesehatan' => 'Dicatat oleh petugas.',
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ], $overrides);
    }

    private function nextCode(): string
    {
        return 'UDD-'.strtoupper(str_pad(
            dechex(++$this->codeSequence),
            12,
            '0',
            STR_PAD_LEFT
        ));
    }

    private function assertNoDownstreamRows(): void
    {
        $this->assertDatabaseCount('penyumbangan', 0);
        $this->assertDatabaseCount('unit_komponen_darah', 0);
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    private function businessTableCounts(): array
    {
        return collect([
            'akun',
            'golongan_darah',
            'pendonor',
            'petugas',
            'jadwal_pelayanan',
            'pemesanan_donor',
            'kuesioner_pradonasi',
            'pertanyaan_kuesioner',
            'jawaban_kuesioner',
            'seleksi_donor',
            'penyumbangan',
            'jenis_komponen_darah',
            'unit_komponen_darah',
            'ambang_persediaan',
            'pemberitahuan',
        ])->mapWithKeys(fn (string $table): array => [
            $table => $this->getConnection()->table($table)->count(),
        ])->all();
    }
}

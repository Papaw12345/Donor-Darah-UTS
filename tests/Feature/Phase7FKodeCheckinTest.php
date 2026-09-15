<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7FKodeCheckinTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_guest_cannot_access_code_get_or_post(): void
    {
        [, $booking] = $this->createEligibleFixture();

        $this->get(route('pendonor.kode-checkin.show', $booking))
            ->assertRedirect(route('login'));

        $this->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('login'));
    }

    public function test_non_pendonor_cannot_access_code_get_or_post(): void
    {
        $admin = $this->createAccount('ADMIN');
        [, $booking] = $this->createEligibleFixture();

        $this->actingAs($admin)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertForbidden();
    }

    public function test_pendonor_cannot_view_another_pendonor_code_page(): void
    {
        $pendonor = $this->createPendonor();
        $bookingLain = $this->createBooking(
            $this->createPendonor(),
            $this->createSchedule(),
            'TERJADWAL',
            'UDD-A84C21EF07B9'
        );

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $bookingLain))
            ->assertNotFound()
            ->assertDontSee('UDD-A84C21EF07B9');
    }

    public function test_pendonor_cannot_generate_code_for_another_pendonor_booking(): void
    {
        $pendonor = $this->createPendonor();
        [, $bookingLain] = $this->createEligibleFixture();

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $bookingLain), [
                'id_pendonor' => $bookingLain->id_pendonor,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $bookingLain->id_pemesanan,
            'kode_checkin' => null,
        ]);
    }

    public function test_eligible_get_is_read_only_and_displays_generation_action(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee(route('pendonor.kode-checkin.generate', $booking), false)
            ->assertSee('Buat Kode Check-in')
            ->assertSee('name="_token"', false);

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $booking->id_pemesanan,
            'kode_checkin' => null,
        ]);
    }

    public function test_existing_code_is_displayed_without_regenerate_or_rotate_action(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule(),
            'TERJADWAL',
            'UDD-A84C21EF07B9'
        );

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee('UDD-A84C21EF07B9')
            ->assertDontSee(route('pendonor.kode-checkin.generate', $booking), false)
            ->assertDontSee('Buat Kode Check-in')
            ->assertDontSee('Rotasi');
    }

    public function test_booking_without_questionnaire_cannot_generate_code(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee('kuesioner pradonasi belum diisi')
            ->assertDontSee(route('pendonor.kode-checkin.generate', $booking), false);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertSessionHasErrors('kode_checkin');

        $this->assertNull($booking->fresh()->kode_checkin);
    }

    public function test_stored_questionnaire_row_is_sufficient_completion_proof(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => now(),
        ]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));

        $this->assertMatchesRegularExpression(
            '/^UDD-[0-9A-F]{12}$/',
            $booking->fresh()->kode_checkin
        );
    }

    public function test_today_booking_is_allowed_even_after_schedule_clock_time(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture([
            'tanggal' => '2026-09-15',
            'jam_mulai' => '06:00',
            'jam_selesai' => '07:00',
        ]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));

        $this->assertNotNull($booking->fresh()->kode_checkin);
    }

    public function test_future_scheduled_booking_is_allowed(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture(['tanggal' => '2026-10-01']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));

        $this->assertNotNull($booking->fresh()->kode_checkin);
    }

    public function test_past_schedule_is_rejected_for_new_code(): void
    {
        $this->assertNewCodeIsUnavailable('TERJADWAL', ['tanggal' => '2026-09-14']);
    }

    public function test_cancelled_schedule_is_rejected_for_new_code(): void
    {
        $this->assertNewCodeIsUnavailable('TERJADWAL', ['status_jadwal' => 'DIBATALKAN']);
    }

    public function test_closed_schedule_remains_allowed(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture([
            'status_jadwal' => 'DITUTUP',
        ]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));

        $this->assertNotNull($booking->fresh()->kode_checkin);
    }

    public function test_non_scheduled_booking_statuses_cannot_generate_new_code(): void
    {
        foreach (['CHECK_IN', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            $this->assertNewCodeIsUnavailable($status);
        }
    }

    public function test_generated_code_has_exact_format_is_stored_and_fits_column(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking))
            ->assertSessionHas('success');

        $code = $booking->fresh()->kode_checkin;

        $this->assertMatchesRegularExpression('/^UDD-[0-9A-F]{12}$/', $code);
        $this->assertSame(16, strlen($code));
        $this->assertLessThanOrEqual(50, strlen($code));
        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $booking->id_pemesanan,
            'kode_checkin' => $code,
        ]);
    }

    public function test_separate_bookings_receive_unique_codes(): void
    {
        [$firstDonor, $firstBooking] = $this->createEligibleFixture();
        [$secondDonor, $secondBooking] = $this->createEligibleFixture();

        $this->actingAs($firstDonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $firstBooking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $firstBooking));
        $this->actingAs($secondDonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $secondBooking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $secondBooking));

        $this->assertNotSame(
            $firstBooking->fresh()->kode_checkin,
            $secondBooking->fresh()->kode_checkin
        );
    }

    public function test_sequential_double_generation_retains_exactly_the_first_code(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));
        $firstCode = $booking->fresh()->kode_checkin;

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking))
            ->assertSessionHas('success', 'Kode check-in yang sudah ada tetap digunakan.');

        $this->assertSame($firstCode, $booking->fresh()->kode_checkin);
        $this->assertDatabaseCount('pemesanan_donor', 1);
    }

    public function test_existing_code_is_retained_after_booking_is_cancelled_and_repeated_post(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();
        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking));
        $firstCode = $booking->fresh()->kode_checkin;
        $booking->update(['status_pemesanan' => 'DIBATALKAN']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));

        $this->assertSame($firstCode, $booking->fresh()->kode_checkin);
    }

    public function test_existing_code_remains_viewable_after_schedule_date_is_past(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();
        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking));
        $code = $booking->fresh()->kode_checkin;
        $booking->jadwalPelayanan->update(['tanggal' => '2026-09-14']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee($code);
    }

    public function test_existing_code_remains_viewable_after_checkin_or_completed_status(): void
    {
        foreach (['CHECK_IN', 'SELESAI'] as $status) {
            $pendonor = $this->createPendonor();
            $code = $status === 'CHECK_IN' ? 'UDD-AAAAAAAAAAAA' : 'UDD-BBBBBBBBBBBB';
            $booking = $this->createBooking(
                $pendonor,
                $this->createSchedule(),
                $status,
                $code
            );

            $this->actingAs($pendonor->akun)
                ->get(route('pendonor.kode-checkin.show', $booking))
                ->assertOk()
                ->assertSee($code)
                ->assertDontSee(route('pendonor.kode-checkin.generate', $booking), false);
        }
    }

    public function test_generation_has_no_checkin_or_other_workflow_side_effects(): void
    {
        [$pendonor, $booking, $questionnaire, $question, $answer] = $this->createEligibleFixture();
        $otherBooking = $this->createBooking($pendonor, $this->createSchedule([
            'tanggal' => '2026-11-01',
        ]));

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));

        $booking->refresh();
        $this->assertSame('TERJADWAL', $booking->status_pemesanan);
        $this->assertNull($booking->waktu_checkin);
        $this->assertMatchesRegularExpression('/^UDD-[0-9A-F]{12}$/', $booking->kode_checkin);
        $this->assertDatabaseCount('seleksi_donor', 0);
        $this->assertDatabaseHas('kuesioner_pradonasi', [
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pemesanan' => $booking->id_pemesanan,
        ]);
        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_jawaban' => $answer->id_jawaban,
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);
        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $otherBooking->id_pemesanan,
            'kode_checkin' => null,
            'status_pemesanan' => 'TERJADWAL',
            'waktu_checkin' => null,
        ]);
    }

    public function test_booking_list_contains_real_code_link(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemesanan.index'))
            ->assertOk()
            ->assertSee(route('pendonor.kode-checkin.show', $booking), false)
            ->assertSee('Kode Check-in');
    }

    public function test_code_page_contains_no_qr_barcode_or_petugas_checkin_action(): void
    {
        [$pendonor, $booking] = $this->createEligibleFixture();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertDontSee('QR code')
            ->assertDontSee('barcode')
            ->assertDontSee('Petugas Check-in');
    }

    private function assertNewCodeIsUnavailable(
        string $bookingStatus,
        array $scheduleOverrides = []
    ): void {
        [$pendonor, $booking] = $this->createEligibleFixture(
            $scheduleOverrides,
            $bookingStatus
        );

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertViewHas(
                'pesanTidakTersedia',
                fn ($message) => is_string($message) && $message !== ''
            )
            ->assertDontSee(route('pendonor.kode-checkin.generate', $booking), false);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertSessionHasErrors('kode_checkin');

        $this->assertNull($booking->fresh()->kode_checkin);
    }

    private function createEligibleFixture(
        array $scheduleOverrides = [],
        string $bookingStatus = 'TERJADWAL'
    ): array {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule($scheduleOverrides),
            $bookingStatus
        );
        $question = $this->createQuestion();
        $questionnaire = KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => now(),
        ]);
        $answer = JawabanKuesioner::create([
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);

        return [$pendonor, $booking, $questionnaire, $question, $answer];
    }

    private function createAccount(string $role = 'PENDONOR'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7f-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function createPendonor(): Pendonor
    {
        $akun = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $akun->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0814'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createSchedule(array $overrides = []): JadwalPelayanan
    {
        return JadwalPelayanan::create(array_merge([
            'tanggal' => '2026-10-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 5,
            'status_jadwal' => 'DIBUKA',
        ], $overrides));
    }

    private function createBooking(
        Pendonor $pendonor,
        JadwalPelayanan $schedule,
        string $status = 'TERJADWAL',
        ?string $code = null
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => now(),
            'kode_checkin' => $code,
            'waktu_checkin' => null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createQuestion(): PertanyaanKuesioner
    {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => 'Apakah kondisi Anda sehat?',
            'kategori' => null,
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ]);
    }
}

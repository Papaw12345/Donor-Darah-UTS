<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasCheckinController;
use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8BCheckinPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private int $codeSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-09-16 00:30:00 in Asia/Jakarta.
        $this->travelTo(CarbonImmutable::parse('2026-09-15 17:30:00', 'UTC'));
    }

    public function test_routes_and_access_require_an_active_petugas_with_profile(): void
    {
        $getRoute = Route::getRoutes()->getByName('petugas.check-in.index');
        $postRoute = Route::getRoutes()->getByName('petugas.check-in.store');

        $this->assertNotNull($getRoute);
        $this->assertSame('petugas/check-in', $getRoute->uri());
        $this->assertSame(['GET', 'HEAD'], $getRoute->methods());
        $this->assertSame(PetugasCheckinController::class.'@index', $getRoute->getActionName());

        $this->assertNotNull($postRoute);
        $this->assertSame('petugas/check-in', $postRoute->uri());
        $this->assertSame(['POST'], $postRoute->methods());
        $this->assertSame(PetugasCheckinController::class.'@store', $postRoute->getActionName());

        foreach ([$getRoute, $postRoute] as $route) {
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('active', $route->gatherMiddleware());
            $this->assertContains('role:PETUGAS', $route->gatherMiddleware());
        }

        $this->get(route('petugas.check-in.index'))
            ->assertRedirect(route('login'));
        $this->post(route('petugas.check-in.store'), ['kode_checkin' => 'UDD-000000000001'])
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $account = $this->createAccount($role);

            $this->actingAs($account)
                ->get(route('petugas.check-in.index'))
                ->assertForbidden();
            $this->actingAs($account)
                ->post(route('petugas.check-in.store'), ['kode_checkin' => 'UDD-000000000001'])
                ->assertForbidden();
        }

        $inactivePetugas = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactivePetugas->akun)
            ->get(route('petugas.check-in.index'))
            ->assertRedirect(route('login'));
        $this->actingAs($inactivePetugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => 'UDD-000000000001'])
            ->assertRedirect(route('login'));

        $activePetugas = $this->createPetugas();
        $this->actingAs($activePetugas->akun)
            ->get(route('petugas.check-in.index'))
            ->assertOk();
    }

    public function test_active_petugas_without_profile_is_rejected_without_auto_creation(): void
    {
        $account = $this->createAccount('PETUGAS');
        $countBefore = Petugas::query()->count();

        $this->actingAs($account)
            ->get(route('petugas.check-in.index'))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');

        $this->actingAs($account)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => 'UDD-000000000001'])
            ->assertStatus(409);

        $this->assertSame($countBefore, Petugas::query()->count());
        $this->assertNull($account->petugas()->first());
    }

    public function test_get_without_code_renders_lookup_form_and_is_read_only(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createEligibleFixture();
        $before = $booking->getAttributes();

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index'))
            ->assertOk()
            ->assertSee('name="kode_checkin"', false)
            ->assertSee('method="GET"', false)
            ->assertDontSee('Konfirmasi Check-in');

        $this->assertNull($response->viewData('pemesanan'));
        $this->assertEquals($before, $booking->fresh()->getAttributes());
    }

    public function test_get_normalizes_code_displays_visit_data_and_ignores_client_ids(): void
    {
        $petugas = $this->createPetugas(name: 'Petugas Login');
        $otherPetugas = $this->createPetugas(name: 'Petugas Lain');
        [$pendonor, $booking, $questionnaire] = $this->createEligibleFixture(
            donorNumber: 'DNR-001',
            withDetailedAnswer: true
        );
        $otherBooking = $this->createBooking(
            $this->createPendonor('Pendonor Lain'),
            $this->createSchedule(),
            code: $this->nextCode()
        );
        $before = $booking->getAttributes();

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', [
                'kode_checkin' => '  '.strtolower($booking->kode_checkin).'  ',
                'id_petugas' => $otherPetugas->id_petugas,
                'id_pendonor' => $otherBooking->id_pendonor,
                'id_pemesanan' => $otherBooking->id_pemesanan,
            ]))
            ->assertOk()
            ->assertSee($pendonor->nama_lengkap)
            ->assertSee('DNR-001')
            ->assertSee('2026-09-16')
            ->assertSee('06:00')
            ->assertSee('DIBUKA')
            ->assertSee('Terjadwal')
            ->assertSee($booking->kode_checkin)
            ->assertSee('Tersedia')
            ->assertDontSee('Petugas Login')
            ->assertDontSee('Petugas Lain')
            ->assertDontSee('Pendonor Lain')
            ->assertDontSee('JAWABAN-RAHASIA')
            ->assertSee('Konfirmasi Check-in')
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('value="'.$booking->kode_checkin.'"', false);

        $this->assertSame($booking->id_pemesanan, $response->viewData('pemesanan')->id_pemesanan);
        $this->assertSame($petugas->id_petugas, $response->viewData('petugas')->id_petugas);
        $this->assertSame($booking->kode_checkin, $response->viewData('normalizedCode'));
        $this->assertTrue($response->viewData('canCheckIn'));
        $this->assertEquals($before, $booking->fresh()->getAttributes());
        $this->assertDatabaseHas('kuesioner_pradonasi', [
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pemesanan' => $booking->id_pemesanan,
        ]);
    }

    public function test_invalid_and_unknown_codes_are_controlled_and_do_not_mutate_data(): void
    {
        $petugas = $this->createPetugas();
        [, $booking] = $this->createEligibleFixture();
        $before = $booking->getAttributes();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => ' not-a-code ']))
            ->assertOk()
            ->assertSee('Format kode check-in tidak valid')
            ->assertDontSee('Konfirmasi Check-in');

        $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => 'UDD-FFFFFFFFFFFF']))
            ->assertOk()
            ->assertSee('Kode check-in tidak ditemukan.')
            ->assertDontSee('Konfirmasi Check-in');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => ' bad '])
            ->assertSessionHasErrors('kode_checkin');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => 'UDD-FFFFFFFFFFFF'])
            ->assertSessionHasErrors('kode_checkin');

        $this->assertEquals($before, $booking->fresh()->getAttributes());
    }

    public function test_new_checkin_before_start_accepts_open_or_closed_schedule(): void
    {
        $petugas = $this->createPetugas();

        foreach (['DIBUKA', 'DITUTUP'] as $scheduleStatus) {
            [, $booking] = $this->createEligibleFixture([
                'jam_mulai' => '01:00',
                'jam_selesai' => '02:00',
                'status_jadwal' => $scheduleStatus,
            ]);

            $this->actingAs($petugas->akun)
                ->post(route('petugas.check-in.store'), [
                    'kode_checkin' => '  '.strtolower($booking->kode_checkin).'  ',
                ])
                ->assertRedirect(route('petugas.check-in.index', [
                    'kode_checkin' => $booking->kode_checkin,
                ]))
                ->assertSessionHas('success', 'Check-in Pendonor berhasil.');

            $booking->refresh();
            $this->assertSame('CHECK_IN', $booking->status_pemesanan);
            $this->assertNotNull($booking->waktu_checkin);
        }
    }

    public function test_new_checkin_allows_before_cutoff_and_at_exact_end_in_wib(): void
    {
        $petugas = $this->createPetugas();
        $cases = [
            '2026-09-16 09:59:00' => '16:59:00',
            '2026-09-16 10:00:00' => '17:00:00',
        ];

        foreach ($cases as $utcTime => $expectedWibTime) {
            $this->travelTo(CarbonImmutable::parse($utcTime, 'UTC'));
            [, $booking] = $this->createEligibleFixture([
                'tanggal' => '2026-09-16',
                'jam_mulai' => '09:00',
                'jam_selesai' => '17:00',
            ]);

            $this->assertSame($expectedWibTime, now('Asia/Jakarta')->format('H:i:s'));

            $this->actingAs($petugas->akun)
                ->post(route('petugas.check-in.store'), [
                    'kode_checkin' => $booking->kode_checkin,
                ])
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success', 'Check-in Pendonor berhasil.');

            $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
            $this->assertNotNull($booking->fresh()->waktu_checkin);
        }
    }

    public function test_new_checkin_after_end_is_rejected_without_partial_mutation(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:01', 'UTC'));

        $petugas = $this->createPetugas();
        [, $booking, $questionnaire] = $this->createEligibleFixture([
            'tanggal' => '2026-09-16',
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:00',
        ]);
        $bookingBefore = $booking->getAttributes();
        $questionnaireBefore = $questionnaire->getAttributes();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => $booking->kode_checkin]))
            ->assertOk()
            ->assertSee('Waktu pelayanan untuk jadwal ini sudah berakhir.')
            ->assertDontSee('Konfirmasi Check-in');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => $booking->kode_checkin])
            ->assertSessionHasErrors([
                'kode_checkin' => 'Waktu pelayanan untuk jadwal ini sudah berakhir.',
            ]);

        $this->assertEquals($bookingBefore, $booking->fresh()->getAttributes());
        $this->assertEquals($questionnaireBefore, $questionnaire->fresh()->getAttributes());
        $this->assertSame('TERJADWAL', $booking->fresh()->status_pemesanan);
        $this->assertNull($booking->fresh()->waktu_checkin);
        $this->assertDatabaseCount('seleksi_donor', 0);
    }

    public function test_new_checkin_uses_wib_date_at_utc_boundary(): void
    {
        $petugas = $this->createPetugas();
        [, $todayWib] = $this->createEligibleFixture(['tanggal' => '2026-09-16']);
        [, $utcCalendarDate] = $this->createEligibleFixture(['tanggal' => '2026-09-15']);

        $this->actingAs($petugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => $todayWib->kode_checkin])
            ->assertSessionHasNoErrors();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => $utcCalendarDate->kode_checkin])
            ->assertSessionHasErrors('kode_checkin');

        $this->assertSame('CHECK_IN', $todayWib->fresh()->status_pemesanan);
        $this->assertSame('TERJADWAL', $utcCalendarDate->fresh()->status_pemesanan);
    }

    public function test_ineligible_new_checkins_are_rejected_without_mutation_or_confirmation(): void
    {
        $petugas = $this->createPetugas();
        $cases = [
            'cancelled schedule' => [['status_jadwal' => 'DIBATALKAN'], 'TERJADWAL', true],
            'yesterday' => [['tanggal' => '2026-09-15'], 'TERJADWAL', true],
            'tomorrow' => [['tanggal' => '2026-09-17'], 'TERJADWAL', true],
            'missing questionnaire' => [[], 'TERJADWAL', false],
            'completed booking' => [[], 'SELESAI', true],
            'cancelled booking' => [[], 'DIBATALKAN', true],
            'no show booking' => [[], 'TIDAK_HADIR', true],
        ];

        foreach ($cases as $label => [$scheduleOverrides, $bookingStatus, $withQuestionnaire]) {
            [, $booking] = $this->createEligibleFixture(
                $scheduleOverrides,
                $bookingStatus,
                $withQuestionnaire
            );
            $before = $booking->getAttributes();

            $this->actingAs($petugas->akun)
                ->get(route('petugas.check-in.index', ['kode_checkin' => $booking->kode_checkin]))
                ->assertOk()
                ->assertDontSee('Konfirmasi Check-in');

            $this->actingAs($petugas->akun)
                ->post(route('petugas.check-in.store'), ['kode_checkin' => $booking->kode_checkin])
                ->assertSessionHasErrors('kode_checkin');

            $this->assertEquals($before, $booking->fresh()->getAttributes(), $label);
        }
    }

    public function test_success_changes_only_status_and_checkin_time_and_creates_no_downstream_rows(): void
    {
        $petugas = $this->createPetugas();
        [$pendonor, $booking, $questionnaire] = $this->createEligibleFixture(withDetailedAnswer: true);
        $otherBooking = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-17']),
            code: $this->nextCode()
        );
        $before = $booking->getAttributes();
        $questionnaireBefore = $questionnaire->getAttributes();
        $otherBefore = $otherBooking->getAttributes();

        $this->actingAs($petugas->akun)
            ->post(route('petugas.check-in.store'), [
                'kode_checkin' => $booking->kode_checkin,
                'id_petugas' => 999999,
                'id_pendonor' => $otherBooking->id_pendonor,
                'id_pemesanan' => $otherBooking->id_pemesanan,
            ])
            ->assertRedirect(route('petugas.check-in.index', [
                'kode_checkin' => $booking->kode_checkin,
            ]));

        $booking->refresh();
        $this->assertSame('CHECK_IN', $booking->status_pemesanan);
        $this->assertNotNull($booking->waktu_checkin);
        $this->assertSame($before['kode_checkin'], $booking->getRawOriginal('kode_checkin'));
        $this->assertSame($before['id_pendonor'], $booking->getRawOriginal('id_pendonor'));
        $this->assertSame($before['id_jadwal'], $booking->getRawOriginal('id_jadwal'));
        $this->assertSame($before['waktu_pemesanan'], $booking->getRawOriginal('waktu_pemesanan'));
        $this->assertEquals($questionnaireBefore, $questionnaire->fresh()->getAttributes());
        $this->assertEquals($otherBefore, $otherBooking->fresh()->getAttributes());
        $this->assertDatabaseCount('seleksi_donor', 0);
        $this->assertDatabaseCount('penyumbangan', 0);
        $this->assertDatabaseCount('unit_komponen_darah', 0);
        $this->assertDatabaseCount('pemberitahuan', 0);
        $this->assertDatabaseCount('pemesanan_donor', 2);
    }

    public function test_repeat_and_second_petugas_submissions_are_idempotent_and_get_is_read_only(): void
    {
        $firstPetugas = $this->createPetugas(name: 'Petugas Satu');
        $secondPetugas = $this->createPetugas(name: 'Petugas Dua');
        [, $booking] = $this->createEligibleFixture();

        $this->actingAs($firstPetugas->akun)
            ->post(route('petugas.check-in.store'), ['kode_checkin' => $booking->kode_checkin])
            ->assertSessionHas('success', 'Check-in Pendonor berhasil.');

        $firstTimestamp = $booking->fresh()->getRawOriginal('waktu_checkin');
        $firstCode = $booking->fresh()->kode_checkin;
        $booking->jadwalPelayanan->update([
            'tanggal' => '2026-09-15',
            'status_jadwal' => 'DIBATALKAN',
        ]);

        foreach ([$firstPetugas, $secondPetugas] as $petugas) {
            $this->actingAs($petugas->akun)
                ->post(route('petugas.check-in.store'), ['kode_checkin' => $booking->kode_checkin])
                ->assertRedirect(route('petugas.check-in.index', [
                    'kode_checkin' => $booking->kode_checkin,
                ]))
                ->assertSessionHas('success', 'Pemesanan ini sudah berhasil check-in.');
        }

        $beforeGet = $booking->fresh()->getAttributes();
        $this->actingAs($secondPetugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => $booking->kode_checkin]))
            ->assertOk()
            ->assertSee('Pemesanan ini sudah berhasil check-in.')
            ->assertDontSee('Konfirmasi Check-in');

        $booking->refresh();
        $this->assertSame($firstTimestamp, $booking->getRawOriginal('waktu_checkin'));
        $this->assertSame($firstCode, $booking->kode_checkin);
        $this->assertEquals($beforeGet, $booking->getAttributes());
        $this->assertDatabaseCount('pemesanan_donor', 1);
        $this->assertDatabaseCount('seleksi_donor', 0);
        $this->assertDatabaseCount('penyumbangan', 0);
        $this->assertDatabaseCount('unit_komponen_darah', 0);
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_inconsistent_states_are_rejected_and_never_repaired(): void
    {
        $petugas = $this->createPetugas();
        $states = [
            ['CHECK_IN', null, 'status CHECK_IN belum memiliki waktu check-in'],
            ['TERJADWAL', '2026-09-16 00:15:00', 'pemesanan TERJADWAL sudah memiliki waktu check-in'],
        ];

        foreach ($states as [$status, $timestamp, $message]) {
            [, $booking] = $this->createEligibleFixture(bookingStatus: $status);
            $booking->update(['waktu_checkin' => $timestamp]);
            $before = $booking->fresh()->getAttributes();

            $this->actingAs($petugas->akun)
                ->get(route('petugas.check-in.index', ['kode_checkin' => $booking->kode_checkin]))
                ->assertOk()
                ->assertSee($message)
                ->assertDontSee('Konfirmasi Check-in');

            $this->actingAs($petugas->akun)
                ->post(route('petugas.check-in.store'), ['kode_checkin' => $booking->kode_checkin])
                ->assertSessionHasErrors('kode_checkin');

            $this->assertEquals($before, $booking->fresh()->getAttributes());
        }
    }

    public function test_dashboard_and_checkin_ui_expose_only_working_phase_8b_navigation(): void
    {
        $petugas = $this->createPetugas();
        [, $eligible] = $this->createEligibleFixture();
        [, $ineligible] = $this->createEligibleFixture(bookingStatus: 'SELESAI');

        $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertSee(route('petugas.check-in.index'), false)
            ->assertSee('Check-in')
            ->assertDontSee('Seleksi Donor')
            ->assertDontSee('Penyumbangan')
            ->assertDontSee('Kirim Pemberitahuan');

        $eligibleResponse = $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => $eligible->kode_checkin]))
            ->assertOk()
            ->assertSee(route('petugas.check-in.store'), false)
            ->assertSee('Konfirmasi Check-in')
            ->assertDontSee('Jawaban Kuesioner')
            ->assertDontSee('Seleksi Donor')
            ->assertDontSee('Penyumbangan')
            ->assertDontSee('Unit Komponen')
            ->assertDontSee('Kirim Pemberitahuan');

        $this->assertSame(3, substr_count($eligibleResponse->getContent(), '<form'));

        $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => $ineligible->kode_checkin]))
            ->assertOk()
            ->assertDontSee('Konfirmasi Check-in');
    }

    private function createEligibleFixture(
        array $scheduleOverrides = [],
        string $bookingStatus = 'TERJADWAL',
        bool $withQuestionnaire = true,
        ?string $donorNumber = null,
        bool $withDetailedAnswer = false
    ): array {
        $pendonor = $this->createPendonor(donorNumber: $donorNumber);
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule($scheduleOverrides),
            $bookingStatus,
            $this->nextCode()
        );
        $questionnaire = null;

        if ($withQuestionnaire) {
            $questionnaire = KuesionerPradonasi::create([
                'id_pemesanan' => $booking->id_pemesanan,
                'waktu_pengisian' => '2026-09-15 10:00:00',
            ]);
        }

        if ($withDetailedAnswer && $questionnaire !== null) {
            $question = PertanyaanKuesioner::create([
                'teks_pertanyaan' => 'Pertanyaan internal yang tidak boleh tampil',
                'kategori' => null,
                'jenis_jawaban' => 'TEKS',
                'urutan' => 1,
                'status_aktif' => true,
            ]);
            JawabanKuesioner::create([
                'id_kuesioner' => $questionnaire->id_kuesioner,
                'id_pertanyaan' => $question->id_pertanyaan,
                'jawaban' => 'JAWABAN-RAHASIA',
            ]);
        }

        return [$pendonor, $booking, $questionnaire];
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8b-user{$number}@example.test",
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
            'nomor_petugas' => "P8B-{$number}",
            'nama_petugas' => $name ?? "Petugas {$number}",
        ]);
    }

    private function createPendonor(
        ?string $name = null,
        ?string $donorNumber = null
    ): Pendonor {
        $account = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $account->id_akun,
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

    private function createSchedule(array $overrides = []): JadwalPelayanan
    {
        return JadwalPelayanan::create(array_merge([
            'tanggal' => '2026-09-16',
            'jam_mulai' => '06:00',
            'jam_selesai' => '07:00',
            'kapasitas' => 10,
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
            'waktu_pemesanan' => '2026-09-15 09:00:00',
            'kode_checkin' => $code,
            'waktu_checkin' => null,
            'status_pemesanan' => $status,
        ]);
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
}

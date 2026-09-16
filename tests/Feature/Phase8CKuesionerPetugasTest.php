<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasKuesionerController;
use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8CKuesionerPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private int $codeSequence = 0;

    public function test_route_and_access_require_active_petugas_with_profile(): void
    {
        $route = Route::getRoutes()->getByName('petugas.kuesioner.show');

        $this->assertNotNull($route);
        $this->assertSame('petugas/pemesanan/{pemesanan}/kuesioner', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(PetugasKuesionerController::class.'@show', $route->getActionName());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('active', $route->gatherMiddleware());
        $this->assertContains('role:PETUGAS', $route->gatherMiddleware());

        [, $booking] = $this->createValidFixture();

        $this->get(route('petugas.kuesioner.show', $booking))
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $this->actingAs($this->createAccount($role))
                ->get(route('petugas.kuesioner.show', $booking))
                ->assertForbidden();
        }

        $inactivePetugas = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactivePetugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertRedirect(route('login'));

        $activePetugas = $this->createPetugas();
        $this->actingAs($activePetugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk();
    }

    public function test_active_petugas_without_profile_is_rejected_without_auto_creation(): void
    {
        $account = $this->createAccount('PETUGAS');
        [, $booking] = $this->createValidFixture();
        $countBefore = Petugas::query()->count();

        $this->actingAs($account)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');

        $this->assertSame($countBefore, Petugas::query()->count());
        $this->assertNull($account->petugas()->first());
    }

    public function test_route_bound_booking_is_the_only_target_authority_and_missing_booking_is_404(): void
    {
        $petugas = $this->createPetugas(name: 'Petugas Login');
        [$targetDonor, $targetBooking, $targetQuestionnaire, $targetQuestion] =
            $this->createValidFixture(donorName: 'Pendonor Target', answer: 'YA');
        [$otherDonor, $otherBooking, $otherQuestionnaire, $otherQuestion] =
            $this->createValidFixture(donorName: 'Pendonor Lain', answer: 'TIDAK');

        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', [
                'pemesanan' => $targetBooking,
                'id_petugas' => 999999,
                'id_pendonor' => $otherDonor->id_pendonor,
                'id_kuesioner' => $otherQuestionnaire->id_kuesioner,
                'kode_checkin' => $otherBooking->kode_checkin,
            ]))
            ->assertOk()
            ->assertSee($targetDonor->nama_lengkap)
            ->assertSee($targetBooking->kode_checkin)
            ->assertSee($targetQuestion->teks_pertanyaan)
            ->assertDontSee($otherDonor->nama_lengkap)
            ->assertDontSee($otherBooking->kode_checkin)
            ->assertDontSee($otherQuestion->teks_pertanyaan);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', ['pemesanan' => 999999999]))
            ->assertNotFound();

        $this->assertNotSame($targetQuestionnaire->id_kuesioner, $otherQuestionnaire->id_kuesioner);
    }

    public function test_only_checked_in_or_completed_booking_with_timestamp_is_viewable(): void
    {
        $petugas = $this->createPetugas();

        foreach (['CHECK_IN', 'SELESAI'] as $status) {
            [, $booking] = $this->createValidFixture(status: $status);

            $this->actingAs($petugas->akun)
                ->get(route('petugas.kuesioner.show', $booking))
                ->assertOk()
                ->assertSee($status);
        }

        foreach (['TERJADWAL', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            [, $booking] = $this->createValidFixture(status: $status);

            $this->actingAs($petugas->akun)
                ->get(route('petugas.kuesioner.show', $booking))
                ->assertStatus(409)
                ->assertSee('Status pemesanan tidak sesuai');
        }

        foreach (['CHECK_IN', 'SELESAI'] as $status) {
            [, $booking] = $this->createValidFixture(status: $status, checkinAt: null);

            $this->actingAs($petugas->akun)
                ->get(route('petugas.kuesioner.show', $booking))
                ->assertStatus(409)
                ->assertSee('waktu check-in belum tersedia');

            $this->assertNull($booking->fresh()->waktu_checkin);
            $this->assertSame($status, $booking->fresh()->status_pemesanan);
        }
    }

    public function test_historical_access_ignores_schedule_date_status_and_clock(): void
    {
        $petugas = $this->createPetugas();

        foreach (['CHECK_IN', 'SELESAI'] as $status) {
            [, $booking, , $question] = $this->createValidFixture(
                status: $status,
                scheduleOverrides: [
                    'tanggal' => '2020-01-01',
                    'jam_mulai' => '01:00',
                    'jam_selesai' => '02:00',
                    'status_jadwal' => 'DIBATALKAN',
                ]
            );

            $this->actingAs($petugas->akun)
                ->get(route('petugas.kuesioner.show', $booking))
                ->assertOk()
                ->assertSee('2020-01-01')
                ->assertSee($question->teks_pertanyaan);

            $this->assertSame('DIBATALKAN', $booking->jadwalPelayanan->status_jadwal);
        }
    }

    public function test_missing_questionnaire_or_zero_answers_returns_409_without_creating_data(): void
    {
        $petugas = $this->createPetugas();
        $donor = $this->createPendonor();
        $missingQuestionnaire = $this->createBooking($donor, $this->createSchedule());

        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $missingQuestionnaire))
            ->assertStatus(409)
            ->assertSee('Data kuesioner pradonasi belum tersedia');

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);

        $zeroAnswerBooking = $this->createBooking(
            $this->createPendonor(),
            $this->createSchedule()
        );
        KuesionerPradonasi::create([
            'id_pemesanan' => $zeroAnswerBooking->id_pemesanan,
            'waktu_pengisian' => '2026-09-16 07:30:00',
        ]);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $zeroAnswerBooking))
            ->assertStatus(409)
            ->assertSee('belum ada jawaban yang tersimpan');

        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
    }

    public function test_view_uses_only_stored_answers_including_inactive_questions_and_current_text(): void
    {
        $petugas = $this->createPetugas();
        $donor = $this->createPendonor(donorNumber: 'DNR-8C');
        $booking = $this->createBooking($donor, $this->createSchedule());
        $oldText = 'Redaksi lama pertanyaan tersimpan';
        $yesNo = $this->createQuestion($oldText, 'YA_TIDAK', false, 1, 'Riwayat');
        $text = $this->createQuestion('Jelaskan kondisi saat ini', 'TEKS', true, 2);
        $notAnswered = $this->createQuestion('Pertanyaan aktif yang dibuat kemudian', 'YA_TIDAK', true, 3);
        $questionnaire = $this->createQuestionnaire($booking, [
            $yesNo->id_pertanyaan => 'TIDAK',
            $text->id_pertanyaan => 'Tidak ada keluhan.',
        ]);
        $yesNo->update(['teks_pertanyaan' => 'Redaksi terkini pertanyaan tersimpan']);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($donor->nama_lengkap)
            ->assertSee('DNR-8C')
            ->assertSee((string) $booking->id_pemesanan)
            ->assertSee($booking->kode_checkin)
            ->assertSee('2026-09-16 08:00:00')
            ->assertSee('2026-09-16 07:30:00')
            ->assertSee('Redaksi terkini pertanyaan tersimpan')
            ->assertDontSee($oldText)
            ->assertDontSee($notAnswered->teks_pertanyaan)
            ->assertSee('Riwayat')
            ->assertSee('YA_TIDAK')
            ->assertSee('TIDAK')
            ->assertSee('NONAKTIF')
            ->assertSee('TEKS')
            ->assertSee('Tidak ada keluhan.')
            ->assertDontSee('Skor risiko')
            ->assertDontSee('Diagnosis')
            ->assertDontSee('Rekomendasi medis')
            ->assertDontSee('Kelayakan medis');

        $this->assertSame($booking->id_pemesanan, $questionnaire->id_pemesanan);
    }

    public function test_answers_are_ordered_by_question_order_then_question_id(): void
    {
        $petugas = $this->createPetugas();
        $booking = $this->createBooking($this->createPendonor(), $this->createSchedule());
        $last = $this->createQuestion('Pertanyaan urutan dua', order: 2);
        $firstTie = $this->createQuestion('Pertanyaan urutan satu A', order: 1);
        $secondTie = $this->createQuestion('Pertanyaan urutan satu B', order: 1);
        $this->createQuestionnaire($booking, [
            $last->id_pertanyaan => 'YA',
            $secondTie->id_pertanyaan => 'TIDAK',
            $firstTie->id_pertanyaan => 'YA',
        ]);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk()
            ->assertSeeInOrder([
                $firstTie->teks_pertanyaan,
                $secondTie->teks_pertanyaan,
                $last->teks_pertanyaan,
            ]);

        $this->assertSame(
            [
                $firstTie->id_pertanyaan,
                $secondTie->id_pertanyaan,
                $last->id_pertanyaan,
            ],
            $response->viewData('jawaban')
                ->pluck('id_pertanyaan')
                ->all()
        );
    }

    public function test_questionnaire_get_is_strictly_read_only(): void
    {
        $petugas = $this->createPetugas();
        [$donor, $booking, $questionnaire, $question] = $this->createValidFixture();
        $answer = $questionnaire->jawabanKuesioner()->sole();
        $schedule = $booking->jadwalPelayanan;
        $before = [
            'donor' => $donor->getAttributes(),
            'booking' => $booking->getAttributes(),
            'schedule' => $schedule->getAttributes(),
            'questionnaire' => $questionnaire->getAttributes(),
            'answer' => $answer->getAttributes(),
            'question' => $question->getAttributes(),
        ];
        $counts = collect([
            'akun',
            'pendonor',
            'petugas',
            'jadwal_pelayanan',
            'pemesanan_donor',
            'kuesioner_pradonasi',
            'jawaban_kuesioner',
            'pertanyaan_kuesioner',
            'seleksi_donor',
            'penyumbangan',
            'unit_komponen_darah',
            'pemberitahuan',
        ])->mapWithKeys(fn (string $table): array => [
            $table => $this->getConnection()->table($table)->count(),
        ]);

        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk();
        $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $booking))
            ->assertOk();

        $this->assertEquals($before['donor'], $donor->fresh()->getAttributes());
        $this->assertEquals($before['booking'], $booking->fresh()->getAttributes());
        $this->assertEquals($before['schedule'], $schedule->fresh()->getAttributes());
        $this->assertEquals($before['questionnaire'], $questionnaire->fresh()->getAttributes());
        $this->assertEquals($before['answer'], $answer->fresh()->getAttributes());
        $this->assertEquals($before['question'], $question->fresh()->getAttributes());

        foreach ($counts as $table => $count) {
            $this->assertSame($count, $this->getConnection()->table($table)->count());
        }
    }

    public function test_checkin_navigation_is_conditional_and_questionnaire_page_links_to_selection(): void
    {
        $petugas = $this->createPetugas();
        [, $checkedIn] = $this->createValidFixture();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index', ['kode_checkin' => $checkedIn->kode_checkin]))
            ->assertOk()
            ->assertSee(route('petugas.kuesioner.show', $checkedIn), false)
            ->assertSee('Lihat Kuesioner');

        foreach ([
            ['TERJADWAL', null, true],
            ['DIBATALKAN', null, true],
            ['TIDAK_HADIR', null, true],
            ['CHECK_IN', null, true],
            ['CHECK_IN', '2026-09-16 08:00:00', false],
            ['SELESAI', '2026-09-16 08:00:00', true],
        ] as [$status, $checkinAt, $withQuestionnaire]) {
            [, $booking] = $this->createValidFixture(
                status: $status,
                checkinAt: $checkinAt,
                withQuestionnaire: $withQuestionnaire
            );

            $this->actingAs($petugas->akun)
                ->get(route('petugas.check-in.index', ['kode_checkin' => $booking->kode_checkin]))
                ->assertOk()
                ->assertDontSee('Lihat Kuesioner');
        }

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.kuesioner.show', $checkedIn))
            ->assertOk()
            ->assertSee(route('petugas.check-in.index'), false)
            ->assertSee(route('logout'), false)
            ->assertSee(route('petugas.seleksi.show', $checkedIn), false)
            ->assertSee('Seleksi Donor')
            ->assertDontSee('LAYAK')
            ->assertDontSee('DITUNDA')
            ->assertDontSee('DITOLAK')
            ->assertDontSee('Penyumbangan')
            ->assertDontSee('Unit')
            ->assertDontSee('Pelulusan')
            ->assertDontSee('Distribusi')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Edit Kuesioner')
            ->assertDontSee('Tandai Ditinjau')
            ->assertDontSee('<textarea', false)
            ->assertDontSee('name="answers[', false);

        $this->assertSame(1, substr_count($response->getContent(), '<form'));
        $this->assertSame(1, substr_count($response->getContent(), '<button'));
    }

    private function createValidFixture(
        string $status = 'CHECK_IN',
        ?string $checkinAt = '2026-09-16 08:00:00',
        array $scheduleOverrides = [],
        ?string $donorName = null,
        ?string $donorNumber = null,
        string $answer = 'YA',
        bool $withQuestionnaire = true
    ): array {
        $donor = $this->createPendonor($donorName, $donorNumber);
        $booking = $this->createBooking(
            $donor,
            $this->createSchedule($scheduleOverrides),
            $status,
            $checkinAt
        );
        $question = $this->createQuestion('Pertanyaan tersimpan '.$this->sequence);
        $questionnaire = $withQuestionnaire
            ? $this->createQuestionnaire($booking, [$question->id_pertanyaan => $answer])
            : null;

        return [$donor, $booking, $questionnaire, $question];
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8c-user{$number}@example.test",
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
            'nomor_petugas' => "P8C-{$number}",
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
            'nomor_telepon' => '0818'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
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

    private function createQuestion(
        string $text,
        string $type = 'YA_TIDAK',
        bool $active = true,
        int $order = 1,
        ?string $category = null
    ): PertanyaanKuesioner {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => $text,
            'kategori' => $category,
            'jenis_jawaban' => $type,
            'urutan' => $order,
            'status_aktif' => $active,
        ]);
    }

    private function createQuestionnaire(
        PemesananDonor $booking,
        array $answers
    ): KuesionerPradonasi {
        $questionnaire = KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => '2026-09-16 07:30:00',
        ]);

        foreach ($answers as $questionId => $answer) {
            JawabanKuesioner::create([
                'id_kuesioner' => $questionnaire->id_kuesioner,
                'id_pertanyaan' => $questionId,
                'jawaban' => $answer,
            ]);
        }

        return $questionnaire;
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

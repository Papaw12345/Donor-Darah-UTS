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

class Phase7EKuesionerTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_guest_cannot_access_questionnaire_get_or_post(): void
    {
        $booking = $this->createBooking($this->createPendonor(), $this->createSchedule());

        $this->get(route('pendonor.kuesioner.show', $booking))
            ->assertRedirect(route('login'));

        $this->post(route('pendonor.kuesioner.store', $booking), ['answers' => []])
            ->assertRedirect(route('login'));
    }

    public function test_non_pendonor_cannot_access_questionnaire_get_or_post(): void
    {
        $admin = $this->createAccount('ADMIN');
        $booking = $this->createBooking($this->createPendonor(), $this->createSchedule());

        $this->actingAs($admin)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('pendonor.kuesioner.store', $booking), ['answers' => []])
            ->assertForbidden();
    }

    public function test_pendonor_cannot_view_another_pendonor_booking_or_questionnaire(): void
    {
        $pendonor = $this->createPendonor();
        $bookingLain = $this->createBooking($this->createPendonor(), $this->createSchedule());
        $question = $this->createQuestion('Pertanyaan rahasia');
        $this->createQuestionnaire($bookingLain, [$question->id_pertanyaan => 'YA']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $bookingLain))
            ->assertNotFound()
            ->assertDontSee('Pertanyaan rahasia');
    }

    public function test_pendonor_cannot_submit_for_another_pendonor_booking(): void
    {
        $pendonor = $this->createPendonor();
        $bookingLain = $this->createBooking($this->createPendonor(), $this->createSchedule());
        $question = $this->createQuestion();

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $bookingLain), [
                'id_pendonor' => $pendonor->id_pendonor,
                'answers' => [$question->id_pertanyaan => 'YA'],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
    }

    public function test_form_shows_only_active_questions_in_deterministic_order(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $last = $this->createQuestion('Urutan terakhir', 'YA_TIDAK', true, 2);
        $firstTie = $this->createQuestion('Urutan pertama A', 'YA_TIDAK', true, 1);
        $secondTie = $this->createQuestion('Urutan pertama B', 'TEKS', true, 1);
        $inactive = $this->createQuestion('Pertanyaan tidak aktif', 'YA_TIDAK', false, 0);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSeeInOrder([
                $firstTie->teks_pertanyaan,
                $secondTie->teks_pertanyaan,
                $last->teks_pertanyaan,
            ])
            ->assertDontSee($inactive->teks_pertanyaan)
            ->assertSee('value="YA"', false)
            ->assertSee('value="TIDAK"', false)
            ->assertSee('<textarea', false);
    }

    public function test_no_active_questions_shows_unavailable_and_cannot_create_questionnaire(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $this->createQuestion('Tidak aktif', 'YA_TIDAK', false);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee('tidak ada pertanyaan aktif')
            ->assertDontSee(route('pendonor.kuesioner.store', $booking), false);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $booking), ['answers' => []])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
    }

    public function test_missing_active_answer_is_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $answered = $this->createQuestion('Dijawab');
        $this->createQuestion('Tidak dijawab', 'TEKS');

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $booking), [
                'answers' => [$answered->id_pertanyaan => 'YA'],
            ])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
    }

    public function test_unexpected_or_inactive_question_answer_is_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $active = $this->createQuestion('Aktif');
        $inactive = $this->createQuestion('Tidak aktif', 'YA_TIDAK', false);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $booking), [
                'answers' => [
                    $active->id_pertanyaan => 'YA',
                    $inactive->id_pertanyaan => 'TIDAK',
                ],
            ])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
    }

    public function test_changed_active_question_set_is_rejected_atomically(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $original = $this->createQuestion('Pertanyaan awal');
        $newQuestion = $this->createQuestion('Pertanyaan baru', 'TEKS', false);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($original->teks_pertanyaan)
            ->assertDontSee($newQuestion->teks_pertanyaan);

        $newQuestion->update(['status_aktif' => true]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $booking), [
                'answers' => [$original->id_pertanyaan => 'YA'],
            ])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
    }

    public function test_yes_no_answer_accepts_ya_and_stores_it_canonically(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'YA'])
            ->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);
    }

    public function test_yes_no_answer_accepts_tidak_and_stores_it_canonically(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'TIDAK'])
            ->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'TIDAK',
        ]);
    }

    public function test_invalid_yes_no_answer_is_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'ya'])
            ->assertSessionHasErrors("answers.{$question->id_pertanyaan}");

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
    }

    public function test_text_answer_must_not_be_blank_after_trimming(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion('Jelaskan kondisi', 'TEKS');

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => " \t\n "])
            ->assertSessionHasErrors("answers.{$question->id_pertanyaan}");

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
    }

    public function test_valid_text_answer_is_stored_in_trimmed_form(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion('Jelaskan kondisi', 'TEKS');

        $this->submit($pendonor, $booking, [
            $question->id_pertanyaan => '  Tidak ada keluhan.  ',
        ])->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'Tidak ada keluhan.',
        ]);
    }

    public function test_today_scheduled_booking_is_allowed_after_schedule_clock_time(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule([
            'tanggal' => '2026-09-15',
            'jam_mulai' => '06:00',
            'jam_selesai' => '07:00',
        ]));
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'YA'])
            ->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
    }

    public function test_future_scheduled_booking_is_allowed(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-10-01'])
        );
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'TIDAK'])
            ->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
    }

    public function test_past_schedule_is_rejected_for_new_questionnaire(): void
    {
        $this->assertIneligibleBookingIsRejected(
            'TERJADWAL',
            ['tanggal' => '2026-09-14']
        );
    }

    public function test_cancelled_schedule_is_rejected_for_new_questionnaire(): void
    {
        $this->assertIneligibleBookingIsRejected(
            'TERJADWAL',
            ['status_jadwal' => 'DIBATALKAN']
        );
    }

    public function test_closed_schedule_remains_allowed_for_scheduled_booking(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule(['status_jadwal' => 'DITUTUP'])
        );
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'YA'])
            ->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
    }

    public function test_non_scheduled_booking_statuses_cannot_create_new_questionnaire(): void
    {
        foreach (['CHECK_IN', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            $this->assertIneligibleBookingIsRejected($status);
        }
    }

    public function test_valid_submission_creates_one_questionnaire_all_answers_and_timestamp(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $yesNo = $this->createQuestion('Pertanyaan pilihan', 'YA_TIDAK', true, 1);
        $text = $this->createQuestion('Pertanyaan teks', 'TEKS', true, 2);

        $this->submit($pendonor, $booking, [
            $yesNo->id_pertanyaan => 'TIDAK',
            $text->id_pertanyaan => '  Jawaban lengkap  ',
        ])->assertRedirect(route('pendonor.kuesioner.show', $booking))
            ->assertSessionHas('success');

        $kuesioner = KuesionerPradonasi::query()->sole();

        $this->assertSame($booking->id_pemesanan, $kuesioner->id_pemesanan);
        $this->assertNotNull($kuesioner->waktu_pengisian);
        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 2);
        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'id_pertanyaan' => $yesNo->id_pertanyaan,
            'jawaban' => 'TIDAK',
        ]);
        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'id_pertanyaan' => $text->id_pertanyaan,
            'jawaban' => 'Jawaban lengkap',
        ]);

        $booking->refresh();
        $this->assertSame('TERJADWAL', $booking->status_pemesanan);
        $this->assertNull($booking->kode_checkin);
        $this->assertNull($booking->waktu_checkin);
    }

    public function test_sequential_second_submission_is_rejected_without_replacing_answers(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion();

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'YA'])
            ->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'TIDAK'])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 1);
        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);
        $this->assertDatabaseMissing('jawaban_kuesioner', [
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'TIDAK',
        ]);
    }

    public function test_submitted_questionnaire_is_displayed_read_only(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion('Apakah Anda sehat?');
        $this->createQuestionnaire($booking, [$question->id_pertanyaan => 'YA']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan)
            ->assertSee('YA')
            ->assertDontSee(route('pendonor.kuesioner.store', $booking), false)
            ->assertDontSee('name="answers[', false);
    }

    public function test_existing_questionnaire_remains_viewable_after_booking_is_cancelled(): void
    {
        [$pendonor, $booking, $question] = $this->createHistoricalQuestionnaireFixture();
        $booking->update(['status_pemesanan' => 'DIBATALKAN']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan)
            ->assertSee('YA');
    }

    public function test_existing_questionnaire_remains_viewable_after_schedule_date_is_past(): void
    {
        [$pendonor, $booking, $question] = $this->createHistoricalQuestionnaireFixture();
        $booking->jadwalPelayanan->update(['tanggal' => '2026-09-14']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan)
            ->assertSee('YA');
    }

    public function test_answer_remains_viewable_after_question_becomes_inactive(): void
    {
        [$pendonor, $booking, $question] = $this->createHistoricalQuestionnaireFixture();
        $question->update(['status_aktif' => false]);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan)
            ->assertSee('YA');
    }

    public function test_questionnaire_page_contains_no_checkin_action_or_placeholder(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $this->createQuestion();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertDontSee('Check-in')
            ->assertDontSee('kode_checkin');
    }

    public function test_booking_list_contains_real_questionnaire_link(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemesanan.index'))
            ->assertOk()
            ->assertSee(route('pendonor.kuesioner.show', $booking), false)
            ->assertSee('Kuesioner Pradonasi');
    }

    private function assertIneligibleBookingIsRejected(
        string $status,
        array $scheduleOverrides = []
    ): void {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule($scheduleOverrides),
            $status
        );
        $question = $this->createQuestion();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertViewHas(
                'pesanTidakTersedia',
                fn ($message) => is_string($message) && $message !== ''
            )
            ->assertDontSee(route('pendonor.kuesioner.store', $booking), false);

        $this->submit($pendonor, $booking, [$question->id_pertanyaan => 'YA'])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
    }

    private function submit(Pendonor $pendonor, PemesananDonor $booking, array $answers)
    {
        return $this->actingAs($pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $booking), [
                'answers' => $answers,
            ]);
    }

    private function createAccount(string $role = 'PENDONOR'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7e-user{$number}@example.test",
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
            'nomor_telepon' => '0813'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
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
        string $status = 'TERJADWAL'
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => now(),
            'kode_checkin' => null,
            'waktu_checkin' => null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createQuestion(
        string $text = 'Apakah kondisi Anda sehat?',
        string $type = 'YA_TIDAK',
        bool $active = true,
        int $order = 1
    ): PertanyaanKuesioner {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => $text,
            'kategori' => null,
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
            'waktu_pengisian' => now(),
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

    private function createHistoricalQuestionnaireFixture(): array
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, $this->createSchedule());
        $question = $this->createQuestion('Pertanyaan historis');
        $this->createQuestionnaire($booking, [$question->id_pertanyaan => 'YA']);

        return [$pendonor, $booking, $question];
    }
}

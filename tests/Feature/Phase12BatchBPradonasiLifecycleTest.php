<?php

namespace Tests\Feature;

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
use RuntimeException;
use Tests\TestCase;

class Phase12BatchBPradonasiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_questionnaire_and_code_roll_back_together_when_answer_creation_fails(): void
    {
        $booking = $this->booking($this->schedule());
        $first = $this->question();
        $second = $this->question();
        $createdAnswers = 0;

        JawabanKuesioner::creating(function () use (&$createdAnswers): void {
            if (++$createdAnswers === 2) {
                throw new RuntimeException('Simulated answer failure');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($booking->pendonor->akun)
                ->post(route('pendonor.kuesioner.store', $booking), [
                    'answers' => [
                        $first->id_pertanyaan => 'YA',
                        $second->id_pertanyaan => 'TIDAK',
                    ],
                ]);
            $this->fail('Kegagalan jawaban kedua harus membatalkan transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated answer failure', $exception->getMessage());
        } finally {
            JawabanKuesioner::flushEventListeners();
        }

        $this->assertSame(2, $createdAnswers);
        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
        $this->assertNull($booking->fresh()->kode_checkin);
        $this->assertSame('TERJADWAL', $booking->fresh()->status_pemesanan);
    }

    public function test_failure_while_issuing_code_rolls_back_questionnaire_and_answers(): void
    {
        $booking = $this->booking($this->schedule());
        $question = $this->question();

        PemesananDonor::updating(function (PemesananDonor $item): void {
            if ($item->kode_checkin !== null) {
                throw new RuntimeException('Simulated code failure');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($booking->pendonor->akun)
                ->post(route('pendonor.kuesioner.store', $booking), [
                    'answers' => [$question->id_pertanyaan => 'YA'],
                ]);
            $this->fail('Kegagalan penerbitan kode harus membatalkan transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated code failure', $exception->getMessage());
        } finally {
            PemesananDonor::flushEventListeners();
        }

        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
        $this->assertNull($booking->fresh()->kode_checkin);
    }

    public function test_questionnaire_submit_does_not_rotate_an_existing_code(): void
    {
        $booking = $this->booking($this->schedule());
        $question = $this->question();
        $booking->update(['kode_checkin' => 'UDD-ABCDEF123456']);

        $this->actingAs($booking->pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $booking), [
                'answers' => [$question->id_pertanyaan => 'YA'],
            ])->assertSessionHasErrors('answers');

        $this->assertSame('UDD-ABCDEF123456', $booking->fresh()->kode_checkin);
        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
    }

    public function test_questionnaire_exact_end_is_allowed_and_after_end_is_rejected_without_erasing_history(): void
    {
        $schedule = $this->schedule('2026-09-15', '17:00');
        $question = $this->question();
        $atEnd = $this->booking($schedule);
        $afterEnd = $this->booking($schedule);

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00', 'UTC'));
        $this->actingAs($atEnd->pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $atEnd), [
                'answers' => [$question->id_pertanyaan => 'YA'],
            ])->assertRedirect(route('pendonor.kode-checkin.show', $atEnd));
        $code = $atEnd->fresh()->kode_checkin;
        $this->assertMatchesRegularExpression('/\AUDD-[0-9A-F]{12}\z/', $code);
        $this->assertNull($atEnd->fresh()->waktu_checkin);

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:01', 'UTC'));
        $this->actingAs($afterEnd->pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $afterEnd))
            ->assertOk()
            ->assertDontSee('Kirim Kuesioner');
        $this->actingAs($afterEnd->pendonor->akun)
            ->post(route('pendonor.kuesioner.store', $afterEnd), [
                'answers' => [$question->id_pertanyaan => 'YA'],
            ])->assertSessionHasErrors('answers');
        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 1);
        $this->assertNull($afterEnd->fresh()->kode_checkin);

        $this->actingAs($atEnd->pendonor->akun)
            ->get(route('pendonor.kuesioner.show', $atEnd))
            ->assertOk()
            ->assertSee('YA');
        $this->actingAs($atEnd->pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $atEnd))
            ->assertOk()
            ->assertSee($code);
        $this->assertSame($code, $atEnd->fresh()->kode_checkin);
    }

    public function test_donor_cancellation_is_allowed_at_exact_end_but_rejected_after_end(): void
    {
        $schedule = $this->schedule('2026-09-15', '17:00');
        $atEnd = $this->booking($schedule);
        $afterEnd = $this->booking($schedule);

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00', 'UTC'));
        $this->actingAs($atEnd->pendonor->akun)
            ->get(route('pendonor.pemesanan.index'))
            ->assertSee(route('pendonor.pemesanan.cancel', $atEnd), false);
        $this->actingAs($atEnd->pendonor->akun)
            ->patch(route('pendonor.pemesanan.cancel', $atEnd))
            ->assertRedirect(route('pendonor.pemesanan.index'));
        $this->assertSame('DIBATALKAN', $atEnd->fresh()->status_pemesanan);

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:01', 'UTC'));
        $this->actingAs($afterEnd->pendonor->akun)
            ->get(route('pendonor.pemesanan.index'))
            ->assertDontSee(route('pendonor.pemesanan.cancel', $afterEnd), false);
        $this->actingAs($afterEnd->pendonor->akun)
            ->patch(route('pendonor.pemesanan.cancel', $afterEnd))
            ->assertSessionHasErrors('pemesanan');
        $this->assertSame('TERJADWAL', $afterEnd->fresh()->status_pemesanan);
        $this->assertDatabaseCount('pemesanan_donor', 2);

        $future = $this->booking($this->schedule('2026-09-16', '09:00'));
        $this->actingAs($future->pendonor->akun)
            ->patch(route('pendonor.pemesanan.cancel', $future))
            ->assertRedirect(route('pendonor.pemesanan.index'));
        $this->assertSame('DIBATALKAN', $future->fresh()->status_pemesanan);
    }

    public function test_explicit_no_show_requires_elapsed_end_and_preserves_existing_data(): void
    {
        $petugas = $this->petugas();
        $otherPetugas = $this->petugas();
        $schedule = $this->schedule('2026-09-15', '17:00');
        $withoutQuestionnaire = $this->booking($schedule);
        $withHistory = $this->booking($schedule);
        $question = $this->question();
        $this->historicalQuestionnaire($withHistory, $question);
        $code = $withHistory->fresh()->kode_checkin;

        $this->actingAs($petugas->akun)
            ->get(route('petugas.check-in.index'))
            ->assertOk()
            ->assertDontSee(route('petugas.pemesanan.tidak-hadir', $withoutQuestionnaire), false);
        $this->actingAs($petugas->akun)
            ->patch(route('petugas.pemesanan.tidak-hadir', $withoutQuestionnaire))
            ->assertSessionHasErrors('pemesanan');

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00', 'UTC'));
        $this->actingAs($petugas->akun)
            ->patch(route('petugas.pemesanan.tidak-hadir', $withoutQuestionnaire))
            ->assertSessionHasErrors('pemesanan');
        $this->assertSame('TERJADWAL', $withoutQuestionnaire->fresh()->status_pemesanan);

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:01', 'UTC'));
        $this->actingAs($otherPetugas->akun)
            ->get(route('petugas.check-in.index'))
            ->assertOk()
            ->assertSee(route('petugas.pemesanan.tidak-hadir', $withoutQuestionnaire), false)
            ->assertSee(route('petugas.pemesanan.tidak-hadir', $withHistory), false);
        foreach ([$withoutQuestionnaire, $withHistory] as $booking) {
            $this->actingAs($otherPetugas->akun)
                ->patch(route('petugas.pemesanan.tidak-hadir', $booking))
                ->assertRedirect(route('petugas.check-in.index'));
            $this->assertSame('TIDAK_HADIR', $booking->fresh()->status_pemesanan);
        }

        $this->assertNull($withoutQuestionnaire->fresh()->kode_checkin);
        $this->assertSame($code, $withHistory->fresh()->kode_checkin);
        $this->assertDatabaseCount('pemesanan_donor', 2);
        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 1);
        $this->actingAs($withHistory->pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $withHistory))
            ->assertOk()->assertSee($code);
    }

    public function test_past_booking_can_be_marked_no_show_but_other_statuses_and_checkin_time_cannot(): void
    {
        $petugas = $this->petugas();
        $schedule = $this->schedule('2026-09-14', '17:00');
        $eligible = $this->booking($schedule);
        $this->actingAs($petugas->akun)
            ->patch(route('petugas.pemesanan.tidak-hadir', $eligible))
            ->assertRedirect(route('petugas.check-in.index'));
        $this->assertSame('TIDAK_HADIR', $eligible->fresh()->status_pemesanan);

        foreach (['CHECK_IN', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            $booking = $this->booking($schedule, $status);
            $this->actingAs($petugas->akun)
                ->patch(route('petugas.pemesanan.tidak-hadir', $booking))
                ->assertSessionHasErrors('pemesanan');
            $this->assertSame($status, $booking->fresh()->status_pemesanan);
        }

        $inconsistent = $this->booking($schedule);
        $inconsistent->update(['waktu_checkin' => now()]);
        $this->actingAs($petugas->akun)
            ->patch(route('petugas.pemesanan.tidak-hadir', $inconsistent))
            ->assertSessionHasErrors('pemesanan');
        $this->assertSame('TERJADWAL', $inconsistent->fresh()->status_pemesanan);

        $this->actingAs($eligible->pendonor->akun)
            ->patch(route('petugas.pemesanan.tidak-hadir', $inconsistent))
            ->assertForbidden();
        $this->assertDatabaseCount('pemesanan_donor', 6);
    }

    public function test_admin_cancellation_changes_only_scheduled_bookings_and_reopening_does_not_revive_them(): void
    {
        $admin = $this->account('ADMIN');
        $schedule = $this->schedule();
        $bookings = [];

        foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            $bookings[$status] = $this->booking($schedule, $status);
        }
        $question = $this->question();
        $this->historicalQuestionnaire($bookings['TERJADWAL'], $question);
        $historicalCode = $bookings['TERJADWAL']->fresh()->kode_checkin;
        $unrelated = $this->booking($this->schedule('2026-10-16'));

        $this->actingAs($admin)
            ->put(route('admin.jadwal.update', $schedule), $this->schedulePayload('DIBATALKAN'))
            ->assertRedirect(route('admin.jadwal.index'));

        $this->assertSame('DIBATALKAN', $schedule->fresh()->status_jadwal);
        foreach ($bookings as $status => $booking) {
            $expected = $status === 'TERJADWAL' ? 'DIBATALKAN' : $status;
            $this->assertSame($expected, $booking->fresh()->status_pemesanan);
        }
        $this->assertSame('TERJADWAL', $unrelated->fresh()->status_pemesanan);
        $this->assertSame($historicalCode, $bookings['TERJADWAL']->fresh()->kode_checkin);
        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 1);
        $this->assertDatabaseCount('pemesanan_donor', 6);

        $this->actingAs($admin)
            ->put(route('admin.jadwal.update', $schedule), $this->schedulePayload('DIBUKA'))
            ->assertRedirect(route('admin.jadwal.index'));
        $this->assertSame('DIBATALKAN', $bookings['TERJADWAL']->fresh()->status_pemesanan);
        $this->actingAs($admin)
            ->delete(route('admin.jadwal.destroy', $schedule))
            ->assertSessionHasErrors('jadwal');
        $this->assertDatabaseHas('jadwal_pelayanan', ['id_jadwal' => $schedule->id_jadwal]);
    }

    private function account(string $role): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "batchb-{$number}@example.test",
            'password_hash' => 'test-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function petugas(): Petugas
    {
        $account = $this->account('PETUGAS');

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'PTG-'.str_pad((string) $account->id_akun, 6, '0', STR_PAD_LEFT),
            'nama_petugas' => 'Petugas '.$account->id_akun,
        ]);
    }

    private function schedule(string $date = '2026-10-15', string $end = '17:00'): JadwalPelayanan
    {
        return JadwalPelayanan::create([
            'tanggal' => $date,
            'jam_mulai' => '08:00',
            'jam_selesai' => $end,
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ]);
    }

    private function schedulePayload(string $status): array
    {
        return [
            'tanggal' => '2026-10-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '17:00',
            'kapasitas' => 10,
            'status_jadwal' => $status,
        ];
    }

    private function booking(JadwalPelayanan $schedule, string $status = 'TERJADWAL'): PemesananDonor
    {
        $account = $this->account('PENDONOR');
        $donor = Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $account->id_akun, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => 'Pendonor '.$account->id_akun,
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat',
            'nomor_telepon' => '081234567890',
        ]);

        return PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => now(),
            'kode_checkin' => null,
            'waktu_checkin' => null,
            'status_pemesanan' => $status,
        ]);
    }

    private function question(): PertanyaanKuesioner
    {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => 'Pertanyaan '.$this->sequence,
            'kategori' => null,
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => ++$this->sequence,
            'status_aktif' => true,
        ]);
    }

    private function historicalQuestionnaire(PemesananDonor $booking, PertanyaanKuesioner $question): void
    {
        $questionnaire = KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => now(),
        ]);
        JawabanKuesioner::create([
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);
        $booking->update([
            'kode_checkin' => 'UDD-'.strtoupper(bin2hex(random_bytes(6))),
        ]);
    }
}

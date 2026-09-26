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

    public function test_code_page_requires_pendonor_and_ownership(): void
    {
        [$pendonor, $booking] = $this->newBooking();
        $booking->update(['kode_checkin' => 'UDD-A84C21EF07B9']);

        $this->get(route('pendonor.kode-checkin.show', $booking))
            ->assertRedirect(route('login'));
        $this->actingAs($this->account('ADMIN'))
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertForbidden();
        $other = $this->account('PENDONOR');
        $this->donor($other);
        $this->actingAs($other)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertNotFound()
            ->assertDontSee($booking->kode_checkin);
        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee($booking->kode_checkin);
    }

    public function test_old_post_generate_route_and_form_are_gone(): void
    {
        [$pendonor, $booking] = $this->newBooking();
        $path = '/pendonor/pemesanan/'.$booking->id_pemesanan.'/kode-checkin';

        $this->actingAs($pendonor->akun)->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee('Kode check-in belum tersedia')
            ->assertDontSee('Buat Kode Check-in');
        $this->actingAs($pendonor->akun)->post($path)->assertStatus(405);
        $this->assertNull($booking->fresh()->kode_checkin);
    }

    public function test_questionnaire_submit_issues_unique_code_and_get_never_rotates_it(): void
    {
        [$firstDonor, $firstBooking, $firstQuestion] = $this->newBooking();
        [$secondDonor, $secondBooking] = $this->newBooking(false);
        $secondQuestion = $firstQuestion;

        foreach ([[$firstDonor, $firstBooking, $firstQuestion], [$secondDonor, $secondBooking, $secondQuestion]] as [$donor, $booking, $question]) {
            $this->actingAs($donor->akun)
                ->post(route('pendonor.kuesioner.store', $booking), [
                    'answers' => [$question->id_pertanyaan => 'YA'],
                ])->assertRedirect(route('pendonor.kode-checkin.show', $booking));

            $booking->refresh();
            $this->assertMatchesRegularExpression('/\AUDD-[0-9A-F]{12}\z/', $booking->kode_checkin);
            $this->assertSame(16, strlen($booking->kode_checkin));
            $this->assertSame('TERJADWAL', $booking->status_pemesanan);
            $this->assertNull($booking->waktu_checkin);
            $this->assertDatabaseHas('pemesanan_donor', [
                'id_pemesanan' => $booking->id_pemesanan,
                'kode_checkin' => $booking->kode_checkin,
            ]);
            $this->assertDatabaseCount('seleksi_donor', 0);
        }

        $this->assertNotSame($firstBooking->kode_checkin, $secondBooking->kode_checkin);
        $code = $firstBooking->kode_checkin;
        $this->actingAs($firstDonor->akun)
            ->get(route('pendonor.kode-checkin.show', $firstBooking))
            ->assertOk()->assertSee($code);
        $this->actingAs($firstDonor->akun)
            ->get(route('pendonor.kode-checkin.show', $firstBooking))
            ->assertOk()->assertSee($code);
        $this->assertSame($code, $firstBooking->fresh()->kode_checkin);
        $this->assertDatabaseCount('kuesioner_pradonasi', 2);
        $this->assertDatabaseCount('jawaban_kuesioner', 2);

        $this->actingAs($firstDonor->akun)
            ->post(route('pendonor.kuesioner.store', $firstBooking), [
                'answers' => [$firstQuestion->id_pertanyaan => 'TIDAK'],
            ])->assertSessionHasErrors('answers');
        $this->assertSame($code, $firstBooking->fresh()->kode_checkin);
        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_pertanyaan' => $firstQuestion->id_pertanyaan,
            'jawaban' => 'YA',
        ]);
        $this->assertDatabaseMissing('jawaban_kuesioner', [
            'id_pertanyaan' => $firstQuestion->id_pertanyaan,
            'jawaban' => 'TIDAK',
        ]);
    }

    public function test_existing_code_remains_visible_after_status_and_date_change(): void
    {
        foreach (['DIBATALKAN', 'TIDAK_HADIR', 'CHECK_IN', 'SELESAI'] as $status) {
            [$donor, $booking] = $this->newBooking();
            $code = 'UDD-'.strtoupper(bin2hex(random_bytes(6)));
            $booking->update(['kode_checkin' => $code, 'status_pemesanan' => $status]);
            $booking->jadwalPelayanan->update(['tanggal' => '2026-09-14']);

            $this->actingAs($donor->akun)
                ->get(route('pendonor.kode-checkin.show', $booking))
                ->assertOk()
                ->assertSee($code)
                ->assertDontSee('Buat Kode Check-in');
            $this->assertSame($code, $booking->fresh()->kode_checkin);
        }
    }

    public function test_historical_questionnaire_without_code_is_not_retroactively_generated_by_get(): void
    {
        [$donor, $booking, $question] = $this->newBooking();
        $questionnaire = KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => now(),
        ]);
        JawabanKuesioner::create([
            'id_kuesioner' => $questionnaire->id_kuesioner,
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);

        $this->actingAs($donor->akun)
            ->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee('Kode check-in belum tersedia');
        $this->assertNull($booking->fresh()->kode_checkin);
        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 1);
    }

    public function test_booking_list_links_to_read_only_code_page_without_qr_or_checkin_action(): void
    {
        [$donor, $booking] = $this->newBooking();

        $this->actingAs($donor->akun)->get(route('pendonor.pemesanan.index'))
            ->assertOk()
            ->assertSee(route('pendonor.kode-checkin.show', $booking), false);
        $this->actingAs($donor->akun)->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertDontSee('QR code')
            ->assertDontSee('barcode')
            ->assertDontSee('Petugas Check-in');
    }

    private function newBooking(bool $createQuestion = true): array
    {
        $donor = $this->donor($this->account('PENDONOR'));
        $schedule = JadwalPelayanan::create([
            'tanggal' => '2026-10-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 5,
            'status_jadwal' => 'DIBUKA',
        ]);
        $booking = PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => now(),
            'kode_checkin' => null,
            'waktu_checkin' => null,
            'status_pemesanan' => 'TERJADWAL',
        ]);
        $question = $createQuestion
            ? PertanyaanKuesioner::create([
                'teks_pertanyaan' => 'Apakah Anda sehat?',
                'kategori' => null,
                'jenis_jawaban' => 'YA_TIDAK',
                'urutan' => ++$this->sequence,
                'status_aktif' => true,
            ])
            : null;

        return [$donor, $booking, $question];
    }

    private function account(string $role): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7f-{$number}@example.test",
            'password_hash' => 'test-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function donor(Akun $account): Pendonor
    {
        return Pendonor::create([
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
    }
}

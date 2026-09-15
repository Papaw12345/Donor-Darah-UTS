<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7DPemesananTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private ?Petugas $petugas = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_guest_cannot_use_booking_routes(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule();
        $pemesanan = $this->createBooking($pendonor, $jadwal);

        $this->get(route('pendonor.pemesanan.index'))
            ->assertRedirect(route('login'));

        $this->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('login'));

        $this->patch(route('pendonor.pemesanan.cancel', $pemesanan))
            ->assertRedirect(route('login'));
    }

    public function test_non_pendonor_cannot_use_booking_routes(): void
    {
        $admin = $this->createAccount('ADMIN');
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule();
        $pemesanan = $this->createBooking($pendonor, $jadwal);

        $this->actingAs($admin)
            ->get(route('pendonor.pemesanan.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('pendonor.pemesanan.cancel', $pemesanan))
            ->assertForbidden();
    }

    public function test_index_only_displays_authenticated_pendonor_bookings(): void
    {
        $pemilik = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $jadwalPemilik = $this->createSchedule(['tanggal' => '2026-10-11']);
        $jadwalLain = $this->createSchedule(['tanggal' => '2026-11-22']);

        $this->createBooking($pemilik, $jadwalPemilik);
        $this->createBooking($pendonorLain, $jadwalLain);

        $this->actingAs($pemilik->akun)
            ->get(route('pendonor.pemesanan.index'))
            ->assertOk()
            ->assertSee('11-10-2026')
            ->assertDontSee('22-11-2026');
    }

    public function test_pendonor_cannot_cancel_another_pendonor_booking(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $pemesananLain = $this->createBooking($pendonorLain, $this->createSchedule());

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemesanan.cancel', $pemesananLain))
            ->assertNotFound();

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $pemesananLain->id_pemesanan,
            'status_pemesanan' => 'TERJADWAL',
        ]);
    }

    public function test_open_schedule_for_today_can_be_booked_with_initial_state(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule([
            'tanggal' => '2026-09-15',
            'jam_mulai' => '06:00',
            'jam_selesai' => '07:00',
        ]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'))
            ->assertSessionHas('success');

        $pemesanan = PemesananDonor::query()->sole();

        $this->assertSame($pendonor->id_pendonor, $pemesanan->id_pendonor);
        $this->assertSame($jadwal->id_jadwal, $pemesanan->id_jadwal);
        $this->assertSame('TERJADWAL', $pemesanan->status_pemesanan);
        $this->assertNotNull($pemesanan->waktu_pemesanan);
        $this->assertNull($pemesanan->kode_checkin);
        $this->assertNull($pemesanan->waktu_checkin);
    }

    public function test_past_schedule_is_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule(['tanggal' => '2026-09-14']);

        $this->actingAs($pendonor->akun)
            ->from(route('pendonor.jadwal.index'))
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.jadwal.index'))
            ->assertSessionHasErrors('pemesanan');

        $this->assertDatabaseCount('pemesanan_donor', 0);
    }

    public function test_closed_schedule_is_rejected(): void
    {
        $this->assertScheduleStatusIsRejected('DITUTUP');
    }

    public function test_cancelled_schedule_is_rejected(): void
    {
        $this->assertScheduleStatusIsRejected('DIBATALKAN');
    }

    public function test_full_schedule_is_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule(['kapasitas' => 1]);
        $this->createBooking($this->createPendonor(), $jadwal, 'TERJADWAL');

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertSessionHasErrors('pemesanan');

        $this->assertDatabaseCount('pemesanan_donor', 1);
    }

    public function test_capacity_counts_all_locked_statuses_and_excludes_cancelled(): void
    {
        foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'TIDAK_HADIR'] as $status) {
            $jadwal = $this->createSchedule(['kapasitas' => 1]);
            $this->createBooking($this->createPendonor(), $jadwal, $status);
            $pendonor = $this->createPendonor();

            $this->actingAs($pendonor->akun)
                ->post(route('pendonor.pemesanan.store', $jadwal))
                ->assertSessionHasErrors('pemesanan');
        }

        $jadwal = $this->createSchedule(['kapasitas' => 1]);
        $this->createBooking($this->createPendonor(), $jadwal, 'DIBATALKAN');
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertSame(
            2,
            PemesananDonor::query()->where('id_jadwal', $jadwal->id_jadwal)->count()
        );
    }

    public function test_same_schedule_rebooking_is_blocked_by_each_locked_status(): void
    {
        foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'TIDAK_HADIR'] as $status) {
            $pendonor = $this->createPendonor();
            $jadwal = $this->createSchedule(['kapasitas' => 2]);
            $this->createBooking($pendonor, $jadwal, $status);

            $this->actingAs($pendonor->akun)
                ->post(route('pendonor.pemesanan.store', $jadwal))
                ->assertSessionHasErrors('pemesanan');

            $this->assertSame(
                1,
                PemesananDonor::query()
                    ->where('id_pendonor', $pendonor->id_pendonor)
                    ->where('id_jadwal', $jadwal->id_jadwal)
                    ->count()
            );
        }
    }

    public function test_cancelled_same_schedule_booking_allows_rebooking(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule(['kapasitas' => 1]);
        $this->createBooking($pendonor, $jadwal, 'DIBATALKAN');

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'status_pemesanan' => 'TERJADWAL',
        ]);
        $this->assertDatabaseCount('pemesanan_donor', 2);
    }

    public function test_booking_a_different_schedule_is_not_automatically_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $jadwalPertama = $this->createSchedule(['tanggal' => '2026-10-01']);
        $jadwalKedua = $this->createSchedule(['tanggal' => '2026-10-01']);
        $this->createBooking($pendonor, $jadwalPertama);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwalKedua))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertDatabaseCount('pemesanan_donor', 2);
    }

    public function test_first_time_donor_has_no_repeat_donor_history_restriction(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule(['tanggal' => '2026-09-20']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'status_pemesanan' => 'TERJADWAL',
        ]);
    }

    public function test_booking_before_two_calendar_month_interval_is_rejected(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonation($pendonor, '2026-07-31 09:00:00', 'BERHASIL');
        $jadwal = $this->createSchedule(['tanggal' => '2026-09-29']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertSessionHasErrors('pemesanan');

        $this->assertDatabaseMissing('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
        ]);
    }

    public function test_booking_at_two_calendar_month_boundary_is_allowed(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonation($pendonor, '2026-07-31 09:00:00', 'BERHASIL');
        $jadwal = $this->createSchedule(['tanggal' => '2026-09-30']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'));
    }

    public function test_only_successful_donations_count_for_repeat_donor_rules(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonation($pendonor, '2026-05-01 09:00:00', 'BERHASIL');
        $this->createDonation($pendonor, '2026-09-01 09:00:00', 'GAGAL');
        $jadwal = $this->createSchedule(['tanggal' => '2026-09-20']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'status_pemesanan' => 'TERJADWAL',
        ]);
    }

    public function test_male_donor_at_six_successful_donations_in_calendar_year_is_rejected(): void
    {
        $pendonor = $this->createPendonor('LAKI_LAKI');

        for ($day = 1; $day <= 6; $day++) {
            $this->createDonation($pendonor, sprintf('2026-01-%02d 09:00:00', $day), 'BERHASIL');
        }

        $jadwal = $this->createSchedule(['tanggal' => '2026-09-20']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertSessionHasErrors('pemesanan');
    }

    public function test_female_donor_at_four_successful_donations_in_calendar_year_is_rejected(): void
    {
        $pendonor = $this->createPendonor('PEREMPUAN');

        for ($day = 1; $day <= 4; $day++) {
            $this->createDonation($pendonor, sprintf('2026-01-%02d 09:00:00', $day), 'BERHASIL');
        }

        $jadwal = $this->createSchedule(['tanggal' => '2026-09-20']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertSessionHasErrors('pemesanan');
    }

    public function test_repeat_donor_uses_selected_schedule_date_and_its_calendar_year(): void
    {
        $pendonor = $this->createPendonor('LAKI_LAKI');

        for ($month = 1; $month <= 6; $month++) {
            $this->createDonation(
                $pendonor,
                sprintf('2026-%02d-01 09:00:00', $month),
                'BERHASIL'
            );
        }

        $jadwal = $this->createSchedule(['tanggal' => '2027-03-01']);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertRedirect(route('pendonor.pemesanan.index'));

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
        ]);
    }

    public function test_own_today_booking_can_be_cancelled_without_deleting_row(): void
    {
        $pendonor = $this->createPendonor();
        $pemesanan = $this->createBooking(
            $pendonor,
            $this->createSchedule([
                'tanggal' => '2026-09-15',
                'jam_mulai' => '06:00',
                'jam_selesai' => '07:00',
            ])
        );

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemesanan.cancel', $pemesanan))
            ->assertRedirect(route('pendonor.pemesanan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('pemesanan_donor', 1);
        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'status_pemesanan' => 'DIBATALKAN',
        ]);
    }

    public function test_non_scheduled_booking_statuses_cannot_be_cancelled(): void
    {
        foreach (['CHECK_IN', 'SELESAI', 'TIDAK_HADIR', 'DIBATALKAN'] as $status) {
            $pendonor = $this->createPendonor();
            $pemesanan = $this->createBooking($pendonor, $this->createSchedule(), $status);

            $this->actingAs($pendonor->akun)
                ->from(route('pendonor.pemesanan.index'))
                ->patch(route('pendonor.pemesanan.cancel', $pemesanan))
                ->assertRedirect(route('pendonor.pemesanan.index'))
                ->assertSessionHasErrors('pemesanan');

            $this->assertDatabaseHas('pemesanan_donor', [
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'status_pemesanan' => $status,
            ]);
        }
    }

    public function test_past_scheduled_booking_cannot_be_cancelled(): void
    {
        $pendonor = $this->createPendonor();
        $pemesanan = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-14'])
        );

        $this->actingAs($pendonor->akun)
            ->from(route('pendonor.pemesanan.index'))
            ->patch(route('pendonor.pemesanan.cancel', $pemesanan))
            ->assertRedirect(route('pendonor.pemesanan.index'))
            ->assertSessionHasErrors('pemesanan');

        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'status_pemesanan' => 'TERJADWAL',
        ]);
    }

    public function test_jadwal_page_contains_real_booking_form(): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertSee(route('pendonor.pemesanan.store', $jadwal), false)
            ->assertSee('Buat Pemesanan')
            ->assertSee('name="_token"', false);
    }

    private function assertScheduleStatusIsRejected(string $status): void
    {
        $pendonor = $this->createPendonor();
        $jadwal = $this->createSchedule(['status_jadwal' => $status]);

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertSessionHasErrors('pemesanan');

        $this->assertDatabaseCount('pemesanan_donor', 0);
    }

    private function createAccount(string $role = 'PENDONOR'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function createPendonor(string $jenisKelamin = 'LAKI_LAKI'): Pendonor
    {
        $akun = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $akun->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0812'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
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
        JadwalPelayanan $jadwal,
        string $status = 'TERJADWAL'
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'waktu_pemesanan' => now(),
            'kode_checkin' => null,
            'waktu_checkin' => null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createDonation(
        Pendonor $pendonor,
        string $waktuPengambilan,
        string $hasil
    ): Penyumbangan {
        $tanggal = CarbonImmutable::parse($waktuPengambilan, 'Asia/Jakarta')->toDateString();
        $pemesanan = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => $tanggal]),
            'SELESAI'
        );
        $petugas = $this->getPetugas();

        $seleksi = SeleksiDonor::create([
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'waktu_seleksi' => $waktuPengambilan,
            'berat_badan' => 60,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 75,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ]);

        return Penyumbangan::create([
            'id_seleksi' => $seleksi->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'waktu_pengambilan' => $waktuPengambilan,
            'volume_ml' => $hasil === 'BERHASIL' ? 350 : null,
            'hasil_penyumbangan' => $hasil,
            'alasan_gagal' => $hasil === 'GAGAL' ? 'Fixture pengujian' : null,
        ]);
    }

    private function getPetugas(): Petugas
    {
        if ($this->petugas !== null) {
            return $this->petugas;
        }

        $akun = $this->createAccount('PETUGAS');

        return $this->petugas = Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => 'PTG-TEST',
            'nama_petugas' => 'Petugas Pengujian',
        ]);
    }
}

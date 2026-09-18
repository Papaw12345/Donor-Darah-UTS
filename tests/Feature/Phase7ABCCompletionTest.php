<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\Pemberitahuan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Petugas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7ABCCompletionTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_dashboard_access_requires_active_pendonor(): void
    {
        $this->get(route('pendonor.home'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->createAccount('ADMIN'))
            ->get(route('pendonor.home'))
            ->assertForbidden();

        $pendonorNonaktif = $this->createPendonor('NONAKTIF');

        $this->actingAs($pendonorNonaktif->akun)
            ->get(route('pendonor.home'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_only_owned_active_statuses(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $terjadwal = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20']),
            'TERJADWAL'
        );
        $checkIn = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-21']),
            'CHECK_IN'
        );

        foreach (['SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $index => $status) {
            $this->createBooking(
                $pendonor,
                $this->createSchedule(['tanggal' => '2026-10-'.sprintf('%02d', $index + 1)]),
                $status
            );
        }

        $this->createBooking(
            $pendonorLain,
            $this->createSchedule(['tanggal' => '2026-09-19']),
            'TERJADWAL'
        );

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('20-09-2026')
            ->assertSee('21-09-2026')
            ->assertSee('TERJADWAL')
            ->assertSee('CHECK_IN')
            ->assertDontSee('19-09-2026')
            ->assertDontSee('01-10-2026')
            ->assertDontSee('02-10-2026')
            ->assertDontSee('03-10-2026');

        $this->assertSame(
            [$terjadwal->id_pemesanan, $checkIn->id_pemesanan],
            $response->viewData('pemesananAktif')->modelKeys()
        );
    }

    public function test_dashboard_displays_all_active_bookings_including_past_dates(): void
    {
        $pendonor = $this->createPendonor();
        $pastTerjadwal = $this->createBooking(
            $pendonor,
            $this->createSchedule([
                'tanggal' => '2026-09-13',
                'jam_mulai' => '06:00',
                'jam_selesai' => '07:00',
            ]),
            'TERJADWAL'
        );
        $pastCheckIn = $this->createBooking(
            $pendonor,
            $this->createSchedule([
                'tanggal' => '2026-09-14',
                'jam_mulai' => '08:00',
                'jam_selesai' => '09:00',
            ]),
            'CHECK_IN'
        );
        $future = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20']),
            'TERJADWAL'
        );

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('13-09-2026')
            ->assertSee('14-09-2026')
            ->assertSee('20-09-2026');

        $this->assertSame(
            [$pastTerjadwal->id_pemesanan, $pastCheckIn->id_pemesanan, $future->id_pemesanan],
            $response->viewData('pemesananAktif')->modelKeys()
        );
    }

    public function test_active_booking_order_uses_all_four_deterministic_keys(): void
    {
        $pendonor = $this->createPendonor();
        $earliestDate = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-18', 'jam_mulai' => '10:00']),
            waktuPemesanan: '2026-09-10 10:00:00'
        );
        $earliestTime = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20', 'jam_mulai' => '07:00']),
            waktuPemesanan: '2026-09-10 10:00:00'
        );
        $laterBookingTime = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20', 'jam_mulai' => '08:00']),
            waktuPemesanan: '2026-09-11 10:00:00'
        );
        $smallerId = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20', 'jam_mulai' => '08:00']),
            waktuPemesanan: '2026-09-10 10:00:00'
        );
        $largerId = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20', 'jam_mulai' => '08:00']),
            waktuPemesanan: '2026-09-10 10:00:00'
        );

        $this->assertGreaterThan($smallerId->id_pemesanan, $largerId->id_pemesanan);

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk();

        $this->assertSame([
            $earliestDate->id_pemesanan,
            $earliestTime->id_pemesanan,
            $smallerId->id_pemesanan,
            $largerId->id_pemesanan,
            $laterBookingTime->id_pemesanan,
        ], $response->viewData('pemesananAktif')->modelKeys());
    }

    public function test_dashboard_active_summary_has_empty_state_real_link_and_no_mutation_action(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('Pemesanan')
            ->assertSee('Belum ada pemesanan aktif.')
            ->assertSee('Lihat Pemesanan')
            ->assertSee(route('pendonor.pemesanan.index'), false)
            ->assertDontSee('Buat Pemesanan')
            ->assertDontSee('Batalkan Pemesanan')
            ->assertDontSee('Isi Kuesioner')
            ->assertDontSee('Buat Kode Check-in')
            ->assertDontSee('/kuesioner', false)
            ->assertDontSee('/kode-checkin', false);
    }

    public function test_dashboard_get_is_read_only(): void
    {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $this->createSchedule(),
            'TERJADWAL',
            '2026-09-10 08:00:00'
        );
        $before = $booking->getAttributes();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk();
        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk();

        $this->assertEquals($before, $booking->fresh()->getAttributes());
        $this->assertDatabaseCount('pemesanan_donor', 1);
        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('penyumbangan', 0);
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_client_supplied_donor_id_cannot_switch_dashboard_ownership(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $own = $this->createBooking(
            $pendonor,
            $this->createSchedule(['tanggal' => '2026-09-20'])
        );
        $this->createBooking(
            $pendonorLain,
            $this->createSchedule(['tanggal' => '2026-09-19'])
        );

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home', ['id_pendonor' => $pendonorLain->id_pendonor]))
            ->assertOk()
            ->assertSee('20-09-2026')
            ->assertDontSee('19-09-2026');

        $this->assertSame([$own->id_pemesanan], $response->viewData('pemesananAktif')->modelKeys());
    }

    public function test_authenticated_pendonor_can_view_and_update_only_editable_profile_fields(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.profil.show'))
            ->assertOk()
            ->assertSee($pendonor->nama_lengkap)
            ->assertSee($pendonor->nik);

        $this->actingAs($pendonor->akun)
            ->put(route('pendonor.profil.update'), [
                'nama_lengkap' => 'Nama Profil Baru',
                'tempat_lahir' => 'Bandung',
                'alamat' => 'Alamat profil baru',
                'nomor_telepon' => '081234567890',
                'pekerjaan' => 'Analis',
                'alamat_kantor' => 'Kantor baru',
            ])
            ->assertRedirect(route('pendonor.profil.show'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pendonor', [
            'id_pendonor' => $pendonor->id_pendonor,
            'nama_lengkap' => 'Nama Profil Baru',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Alamat profil baru',
            'nomor_telepon' => '081234567890',
            'pekerjaan' => 'Analis',
            'alamat_kantor' => 'Kantor baru',
        ]);
    }

    public function test_hostile_profile_update_cannot_change_protected_identity_or_account_fields(): void
    {
        $golonganAwal = $this->createBloodGroup('A', 'POSITIF');
        $golonganLain = $this->createBloodGroup('B', 'NEGATIF');
        $pendonor = $this->createPendonor(overrides: [
            'id_golongan_darah' => $golonganAwal->id_golongan_darah,
            'nik' => '3173000000000001',
            'nomor_donor' => 'DONOR-ASLI',
            'jenis_kelamin' => 'PEREMPUAN',
            'tanggal_lahir' => '1990-02-03',
        ]);
        $emailAwal = $pendonor->akun->email;

        $this->actingAs($pendonor->akun)
            ->put(route('pendonor.profil.update'), [
                'nama_lengkap' => 'Nama Tetap Boleh Berubah',
                'tempat_lahir' => 'Surabaya',
                'alamat' => 'Alamat baru',
                'nomor_telepon' => '081299999999',
                'pekerjaan' => null,
                'alamat_kantor' => null,
                'nik' => '9999999999999999',
                'nomor_donor' => 'DONOR-DIUBAH',
                'jenis_kelamin' => 'LAKI_LAKI',
                'tanggal_lahir' => '2000-01-01',
                'id_golongan_darah' => $golonganLain->id_golongan_darah,
                'email' => 'hostile@example.test',
                'id_pendonor' => 999999,
            ])
            ->assertRedirect(route('pendonor.profil.show'));

        $fresh = $pendonor->fresh();
        $this->assertSame('3173000000000001', $fresh->nik);
        $this->assertSame('DONOR-ASLI', $fresh->nomor_donor);
        $this->assertSame('PEREMPUAN', $fresh->jenis_kelamin);
        $this->assertSame('1990-02-03', $fresh->tanggal_lahir->toDateString());
        $this->assertSame($golonganAwal->id_golongan_darah, $fresh->id_golongan_darah);
        $this->assertSame($emailAwal, $fresh->akun->email);
    }

    public function test_profile_displays_protected_fields_without_writable_inputs(): void
    {
        $golongan = $this->createBloodGroup('AB', 'NEGATIF');
        $pendonor = $this->createPendonor(overrides: [
            'id_golongan_darah' => $golongan->id_golongan_darah,
            'nomor_donor' => 'ND-READ-ONLY',
        ]);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.profil.show'))
            ->assertOk()
            ->assertSee($pendonor->akun->email)
            ->assertSee($pendonor->nik)
            ->assertSee('ND-READ-ONLY')
            ->assertSee('AB')
            ->assertDontSee('name="nik"', false)
            ->assertDontSee('name="nomor_donor"', false)
            ->assertDontSee('name="jenis_kelamin"', false)
            ->assertDontSee('name="tanggal_lahir"', false)
            ->assertDontSee('name="id_golongan_darah"', false)
            ->assertDontSee('name="email"', false);
    }

    public function test_profile_identifier_input_cannot_update_another_pendonor(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $namaPendonorLain = $pendonorLain->nama_lengkap;

        $this->actingAs($pendonor->akun)
            ->put(route('pendonor.profil.update'), [
                'id_pendonor' => $pendonorLain->id_pendonor,
                'nama_lengkap' => 'Hanya Profil Login',
                'tempat_lahir' => 'Bogor',
                'alamat' => 'Alamat sendiri',
                'nomor_telepon' => '081288888888',
                'pekerjaan' => null,
                'alamat_kantor' => null,
            ])
            ->assertRedirect(route('pendonor.profil.show'));

        $this->assertSame('Hanya Profil Login', $pendonor->fresh()->nama_lengkap);
        $this->assertSame($namaPendonorLain, $pendonorLain->fresh()->nama_lengkap);
    }

    public function test_schedule_page_only_contains_open_nonpast_nonfull_schedules(): void
    {
        $pendonor = $this->createPendonor();
        $available = $this->createSchedule(['tanggal' => '2026-09-20']);
        $this->createSchedule(['tanggal' => '2026-09-14']);
        $this->createSchedule(['tanggal' => '2026-09-21', 'status_jadwal' => 'DITUTUP']);
        $this->createSchedule(['tanggal' => '2026-09-22', 'status_jadwal' => 'DIBATALKAN']);
        $full = $this->createSchedule(['tanggal' => '2026-09-23', 'kapasitas' => 1]);
        $this->createBooking($this->createPendonor(), $full, 'TERJADWAL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertSee('20-09-2026')
            ->assertDontSee('14-09-2026')
            ->assertDontSee('21-09-2026')
            ->assertDontSee('22-09-2026')
            ->assertDontSee('23-09-2026');

        $this->assertSame([$available->id_jadwal], $response->viewData('jadwal')->modelKeys());
    }

    public function test_today_schedule_after_end_is_hidden_while_future_schedule_remains_available(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 11:30:00', 'UTC'));

        $pendonor = $this->createPendonor();
        $today = $this->createSchedule([
            'tanggal' => '2026-09-15',
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:00',
        ]);
        $future = $this->createSchedule([
            'tanggal' => '2026-09-16',
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:00',
        ]);

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertDontSee('15-09-2026')
            ->assertSee('16-09-2026');

        $this->assertSame([$future->id_jadwal], $response->viewData('jadwal')->modelKeys());
        $this->assertSame('DIBUKA', $today->fresh()->status_jadwal);
    }

    public function test_today_schedule_is_available_before_start_and_at_exact_end_boundary(): void
    {
        $pendonor = $this->createPendonor();
        $schedule = $this->createSchedule([
            'tanggal' => '2026-09-15',
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:00',
        ]);

        foreach (['2026-09-15 01:00:00', '2026-09-15 10:00:00'] as $utcTime) {
            $this->travelTo(CarbonImmutable::parse($utcTime, 'UTC'));

            $response = $this->actingAs($pendonor->akun)
                ->get(route('pendonor.jadwal.index'))
                ->assertOk()
                ->assertSee('15-09-2026');

            $this->assertSame([$schedule->id_jadwal], $response->viewData('jadwal')->modelKeys());
        }
    }

    public function test_all_capacity_consuming_statuses_make_capacity_one_schedule_full(): void
    {
        $pendonor = $this->createPendonor();
        $fullScheduleIds = [];

        foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'TIDAK_HADIR'] as $index => $status) {
            $schedule = $this->createSchedule([
                'tanggal' => '2026-10-'.sprintf('%02d', $index + 1),
                'kapasitas' => 1,
            ]);
            $this->createBooking($this->createPendonor(), $schedule, $status);
            $fullScheduleIds[] = $schedule->id_jadwal;
        }

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertSee('Belum ada jadwal yang tersedia.');

        $this->assertSame([], $response->viewData('jadwal')->modelKeys());
        $this->assertCount(4, $fullScheduleIds);
    }

    public function test_cancelled_booking_does_not_consume_capacity_and_remaining_capacity_is_derived(): void
    {
        $pendonor = $this->createPendonor();
        $schedule = $this->createSchedule(['tanggal' => '2026-09-20', 'kapasitas' => 3]);
        $this->createBooking($this->createPendonor(), $schedule, 'TERJADWAL');
        $this->createBooking($this->createPendonor(), $schedule, 'SELESAI');
        $this->createBooking($this->createPendonor(), $schedule, 'DIBATALKAN');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertSee('20-09-2026');

        $displayedSchedule = $response->viewData('jadwal')->sole();
        $this->assertSame($schedule->id_jadwal, $displayedSchedule->id_jadwal);
        $this->assertSame(2, $displayedSchedule->jumlah_pemesanan_berlaku);
        $this->assertSame(1, $displayedSchedule->kapasitas - $displayedSchedule->jumlah_pemesanan_berlaku);
    }

    public function test_available_schedule_has_real_booking_form_and_empty_state_works(): void
    {
        $pendonor = $this->createPendonor();
        $schedule = $this->createSchedule(['tanggal' => '2026-09-20']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertSee('Buat Pemesanan')
            ->assertSee(route('pendonor.pemesanan.store', $schedule), false)
            ->assertSee('method="POST"', false);

        $schedule->update(['status_jadwal' => 'DITUTUP']);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertSee('Belum ada jadwal yang tersedia.')
            ->assertDontSee(route('pendonor.pemesanan.store', $schedule), false);
    }

    public function test_existing_donor_next_and_notification_dashboard_content_still_renders(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $notification = Pemberitahuan::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_petugas_pengirim' => $petugas->id_petugas,
            'isi_pesan' => 'Pemberitahuan Phase 7I tetap tampil',
            'waktu_dibuat' => '2026-09-14 08:00:00',
            'waktu_dibaca' => null,
        ]);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('Donor Berikutnya')
            ->assertSee('Anda dapat mencoba donor pertama.')
            ->assertSee(route('pendonor.donor-berikutnya.index'), false)
            ->assertSee('Pemberitahuan Phase 7I tetap tampil')
            ->assertSee('1 belum dibaca.')
            ->assertSee(route('pendonor.pemberitahuan.show', $notification), false)
            ->assertSee(route('pendonor.pemberitahuan.index'), false);
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7abc-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPendonor(
        string $accountStatus = 'AKTIF',
        array $overrides = []
    ): Pendonor {
        $akun = $this->createAccount('PENDONOR', $accountStatus);
        $number = $this->sequence;

        return Pendonor::create(array_merge([
            'id_akun' => $akun->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0818'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ], $overrides));
    }

    private function createBloodGroup(string $abo, string $rhesus): GolonganDarah
    {
        return GolonganDarah::create([
            'abo' => $abo,
            'rhesus' => $rhesus,
        ]);
    }

    private function createSchedule(array $overrides = []): JadwalPelayanan
    {
        return JadwalPelayanan::create(array_merge([
            'tanggal' => '2026-09-20',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ], $overrides));
    }

    private function createBooking(
        Pendonor $pendonor,
        JadwalPelayanan $schedule,
        string $status = 'TERJADWAL',
        string $waktuPemesanan = '2026-09-10 08:00:00'
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => $waktuPemesanan,
            'kode_checkin' => null,
            'waktu_checkin' => $status === 'CHECK_IN' ? '2026-09-10 09:00:00' : null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createPetugas(): Petugas
    {
        $akun = $this->createAccount('PETUGAS');
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => "P7ABC-{$number}",
            'nama_petugas' => "Petugas {$number}",
        ]);
    }
}

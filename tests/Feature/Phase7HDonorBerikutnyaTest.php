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

class Phase7HDonorBerikutnyaTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private ?Petugas $petugas = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-14 18:00:00', 'UTC'));
    }

    public function test_guest_cannot_access_donor_next_page(): void
    {
        $this->get(route('pendonor.donor-berikutnya.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_pendonor_cannot_access_donor_next_page(): void
    {
        $admin = $this->createAccount('ADMIN');

        $this->actingAs($admin)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertForbidden();
    }

    public function test_inactive_pendonor_is_blocked_by_active_middleware(): void
    {
        $pendonor = $this->createPendonor('LAKI_LAKI', 'NONAKTIF');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_pendonor_result_uses_only_own_history(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();

        for ($day = 1; $day <= 6; $day++) {
            $this->createDonationFor(
                $pendonorLain,
                sprintf('2026-01-%02d', $day),
                'BERHASIL'
            );
        }

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Anda belum memiliki riwayat donor berhasil');

        $informasi = $response->viewData('informasiDonorBerikutnya');

        $this->assertTrue($informasi['donor_pertama']);
        $this->assertSame(0, $informasi['jumlah_donor_tahun_ini']);
        $this->assertSame('2026-09-15', $informasi['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_client_supplied_donor_id_cannot_switch_ownership(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();

        for ($day = 1; $day <= 6; $day++) {
            $this->createDonationFor(
                $pendonorLain,
                sprintf('2026-01-%02d', $day),
                'BERHASIL'
            );
        }

        $queryResponse = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index', [
                'id_pendonor' => $pendonorLain->id_pendonor,
            ]))
            ->assertOk();

        $bodyResponse = $this->actingAs($pendonor->akun)
            ->json('GET', route('pendonor.donor-berikutnya.index'), [
                'id_pendonor' => $pendonorLain->id_pendonor,
            ])
            ->assertOk();

        foreach ([$queryResponse, $bodyResponse] as $response) {
            $informasi = $response->viewData('informasiDonorBerikutnya');
            $this->assertTrue($informasi['donor_pertama']);
            $this->assertSame(0, $informasi['jumlah_donor_tahun_ini']);
            $this->assertSame('2026-09-15', $informasi['tanggal_donor_berikutnya']->toDateString());
        }
    }

    public function test_first_time_donor_can_try_first_donation_now_based_on_history(): void
    {
        $pendonor = $this->createPendonor();

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Anda dapat mencoba donor pertama sekarang')
            ->assertSee('15-09-2026');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertTrue($informasi['donor_pertama']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
        $this->assertNull($informasi['tanggal_donor_terakhir']);
    }

    public function test_failed_donations_alone_behave_like_no_successful_history(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-09-10', 'GAGAL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Anda belum memiliki riwayat donor berhasil');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertTrue($informasi['donor_pertama']);
        $this->assertSame(0, $informasi['jumlah_donor_tahun_ini']);
        $this->assertSame('2026-09-15', $informasi['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_failed_donation_after_success_does_not_replace_last_success(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-05-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-09-10', 'GAGAL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('01-05-2026');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame('2026-05-01', $informasi['tanggal_donor_terakhir']->toDateString());
        $this->assertSame(1, $informasi['jumlah_donor_tahun_ini']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
    }

    public function test_before_two_month_boundary_donor_cannot_try_yet(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-07-31', 'BERHASIL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Anda belum dapat mencoba donor kembali')
            ->assertSee('30-09-2026');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertFalse($informasi['interval_terpenuhi']);
        $this->assertFalse($informasi['dapat_mencoba_sekarang']);
        $this->assertSame('2026-09-30', $informasi['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_exactly_at_two_calendar_month_boundary_interval_is_satisfied(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-07-15', 'BERHASIL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Anda sudah dapat mencoba donor kembali');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertTrue($informasi['interval_terpenuhi']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
        $this->assertSame('2026-09-15', $informasi['tanggal_interval_terpenuhi']->toDateString());
    }

    public function test_month_end_interval_uses_no_overflow_behavior(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-29 18:00:00', 'UTC'));
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-07-31', 'BERHASIL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Anda sudah dapat mencoba donor kembali');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame('2026-09-30', $informasi['tanggal_acuan']->toDateString());
        $this->assertSame('2026-09-30', $informasi['tanggal_interval_terpenuhi']->toDateString());
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
    }

    public function test_previous_calendar_year_donations_do_not_count_this_year(): void
    {
        $pendonor = $this->createPendonor();

        for ($month = 1; $month <= 6; $month++) {
            $this->createDonationFor(
                $pendonor,
                sprintf('2025-%02d-01', $month),
                'BERHASIL'
            );
        }

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('0 dari batas 6');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame(0, $informasi['jumlah_donor_tahun_ini']);
        $this->assertTrue($informasi['frekuensi_terpenuhi']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
    }

    public function test_future_successful_donation_is_ignored_for_current_information(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-05-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-10-01', 'BERHASIL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('01-05-2026')
            ->assertDontSee('01-10-2026');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame('2026-05-01', $informasi['tanggal_donor_terakhir']->toDateString());
        $this->assertSame(1, $informasi['jumlah_donor_tahun_ini']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
    }

    public function test_male_below_six_successes_is_not_frequency_blocked(): void
    {
        $pendonor = $this->createPendonor('LAKI_LAKI');

        for ($day = 1; $day <= 5; $day++) {
            $this->createDonationFor($pendonor, sprintf('2026-01-%02d', $day), 'BERHASIL');
        }

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('5 dari batas 6');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame(5, $informasi['jumlah_donor_tahun_ini']);
        $this->assertTrue($informasi['frekuensi_terpenuhi']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
    }

    public function test_male_at_six_successes_is_frequency_blocked_until_next_year(): void
    {
        $pendonor = $this->createPendonor('LAKI_LAKI');

        for ($day = 1; $day <= 6; $day++) {
            $this->createDonationFor($pendonor, sprintf('2026-01-%02d', $day), 'BERHASIL');
        }

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('6 dari batas 6')
            ->assertSee('01-01-2027');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertFalse($informasi['frekuensi_terpenuhi']);
        $this->assertSame('2027-01-01', $informasi['tanggal_frekuensi_terpenuhi']->toDateString());
        $this->assertSame('2027-01-01', $informasi['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_female_at_four_successes_is_frequency_blocked_until_next_year(): void
    {
        $pendonor = $this->createPendonor('PEREMPUAN');

        for ($day = 1; $day <= 4; $day++) {
            $this->createDonationFor($pendonor, sprintf('2026-01-%02d', $day), 'BERHASIL');
        }

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('4 dari batas 4')
            ->assertSee('01-01-2027');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame(4, $informasi['batas_tahunan']);
        $this->assertFalse($informasi['frekuensi_terpenuhi']);
        $this->assertSame('2027-01-01', $informasi['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_estimated_date_uses_later_of_interval_and_frequency_constraints(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-12-19 18:00:00', 'UTC'));
        $pendonor = $this->createPendonor('LAKI_LAKI');

        foreach (['2026-01-01', '2026-02-01', '2026-03-01', '2026-04-01', '2026-05-01'] as $date) {
            $this->createDonationFor($pendonor, $date, 'BERHASIL');
        }
        $this->createDonationFor($pendonor, '2026-12-15', 'BERHASIL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('15-02-2027');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertSame('2027-02-15', $informasi['tanggal_interval_terpenuhi']->toDateString());
        $this->assertSame('2027-01-01', $informasi['tanggal_frekuensi_terpenuhi']->toDateString());
        $this->assertSame('2027-02-15', $informasi['tanggal_donor_berikutnya']->toDateString());
    }

    public function test_satisfied_interval_and_frequency_are_shown_as_able_to_try_now(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-01-15', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-06-15', 'BERHASIL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Berdasarkan riwayat donor berhasil, Anda sudah dapat mencoba donor kembali.');

        $informasi = $response->viewData('informasiDonorBerikutnya');
        $this->assertTrue($informasi['interval_terpenuhi']);
        $this->assertTrue($informasi['frekuensi_terpenuhi']);
        $this->assertTrue($informasi['dapat_mencoba_sekarang']);
    }

    public function test_page_displays_correct_current_year_success_count(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2025-12-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-01-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-03-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-05-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-08-01', 'GAGAL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('3 dari batas 6');

        $this->assertSame(
            3,
            $response->viewData('informasiDonorBerikutnya')['jumlah_donor_tahun_ini']
        );
    }

    public function test_page_displays_correct_last_successful_donation(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-01-01', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-08-20', 'BERHASIL');
        $this->createDonationFor($pendonor, '2026-09-10', 'GAGAL');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('20-08-2026')
            ->assertDontSee('10-09-2026');

        $this->assertSame(
            '2026-08-20',
            $response->viewData('informasiDonorBerikutnya')['tanggal_donor_terakhir']->toDateString()
        );
    }

    public function test_dashboard_contains_real_link_to_donor_next_page(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee(route('pendonor.donor-berikutnya.index'), false)
            ->assertSee('Lihat Informasi Donor Berikutnya');
    }

    public function test_dashboard_summary_uses_same_calculation_as_detailed_page(): void
    {
        $pendonor = $this->createPendonor();
        $this->createDonationFor($pendonor, '2026-07-31', 'BERHASIL');

        $detail = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('30-09-2026');

        $dashboard = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('30-09-2026');

        $this->assertEquals(
            $detail->viewData('informasiDonorBerikutnya'),
            $dashboard->viewData('informasiDonorBerikutnya')
        );
    }

    public function test_phase_7h_get_requests_do_not_create_or_mutate_workflow_records(): void
    {
        $pendonor = $this->createPendonor();
        $donation = $this->createDonationFor($pendonor, '2026-05-01', 'BERHASIL');
        $selection = $donation->seleksiDonor;
        $booking = $selection->pemesananDonor;
        $bookingBefore = $booking->getAttributes();
        $selectionBefore = $selection->getAttributes();
        $donationBefore = $donation->getAttributes();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk();

        $this->assertEquals($bookingBefore, $booking->fresh()->getAttributes());
        $this->assertEquals($selectionBefore, $selection->fresh()->getAttributes());
        $this->assertEquals($donationBefore, $donation->fresh()->getAttributes());
        $this->assertDatabaseCount('pemesanan_donor', 1);
        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('seleksi_donor', 1);
        $this->assertDatabaseCount('penyumbangan', 1);
        $this->assertDatabaseCount('pemberitahuan', 0);
        $this->assertDatabaseCount('unit_komponen_darah', 0);
    }

    public function test_page_states_result_is_not_final_medical_eligibility(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('Informasi ini bukan keputusan kelayakan medis akhir.')
            ->assertSee('Kuesioner pradonasi')
            ->assertSee('pemeriksaan')
            ->assertSee('seleksi Petugas');
    }

    public function test_page_contains_no_notification_booking_selection_or_petugas_control(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertDontSee('<form', false)
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Buat Pemesanan')
            ->assertDontSee('Lakukan Seleksi')
            ->assertDontSee('Check-in Petugas');
    }

    private function createAccount(
        string $role = 'PENDONOR',
        string $status = 'AKTIF'
    ): Akun {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7h-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPendonor(
        string $jenisKelamin = 'LAKI_LAKI',
        string $accountStatus = 'AKTIF'
    ): Pendonor {
        $akun = $this->createAccount('PENDONOR', $accountStatus);
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
            'nomor_telepon' => '0816'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createDonationFor(
        Pendonor $pendonor,
        string $tanggal,
        string $hasil
    ): Penyumbangan {
        $jadwal = JadwalPelayanan::create([
            'tanggal' => $tanggal,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DITUTUP',
        ]);
        $pemesanan = PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'waktu_pemesanan' => $tanggal.' 07:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => $tanggal.' 08:00:00',
            'status_pemesanan' => 'SELESAI',
        ]);
        $petugas = $this->getPetugas();
        $seleksi = SeleksiDonor::create([
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'waktu_seleksi' => $tanggal.' 08:30:00',
            'berat_badan' => 60,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
        ]);

        return Penyumbangan::create([
            'id_seleksi' => $seleksi->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'waktu_pengambilan' => $tanggal.' 09:00:00',
            'volume_ml' => $hasil === 'BERHASIL' ? 350 : null,
            'hasil_penyumbangan' => $hasil,
            'alasan_gagal' => $hasil === 'GAGAL' ? 'Fixture gagal' : null,
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
            'nomor_petugas' => 'P7H-TEST',
            'nama_petugas' => 'Petugas Phase 7H',
        ]);
    }
}

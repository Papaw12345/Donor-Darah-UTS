<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7GRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guest_cannot_access_history(): void
    {
        $this->get(route('pendonor.riwayat.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_pendonor_cannot_access_history(): void
    {
        $admin = $this->createAccount('ADMIN');

        $this->actingAs($admin)
            ->get(route('pendonor.riwayat.index'))
            ->assertForbidden();
    }

    public function test_inactive_pendonor_is_blocked_by_active_middleware(): void
    {
        $pendonor = $this->createPendonor('NONAKTIF');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_pendonor_sees_only_own_donation_rows(): void
    {
        $pemilik = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createDonationFor($pemilik, $petugas, [
            'waktu_pengambilan' => '2026-08-01 09:00:00',
            'alasan_gagal' => 'RIWAYAT-MILIK-SENDIRI',
        ]);
        $this->createDonationFor($pendonorLain, $petugas, [
            'waktu_pengambilan' => '2026-08-02 09:00:00',
            'alasan_gagal' => 'RIWAYAT-PENDONOR-LAIN',
        ]);

        $this->actingAs($pemilik->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSee('RIWAYAT-MILIK-SENDIRI')
            ->assertDontSee('RIWAYAT-PENDONOR-LAIN')
            ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->count() === 1);
    }

    public function test_client_supplied_donor_id_cannot_switch_history_ownership(): void
    {
        $pemilik = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createDonationFor($pemilik, $petugas, [
            'alasan_gagal' => 'DATA-PEMILIK-AKUN',
        ]);
        $this->createDonationFor($pendonorLain, $petugas, [
            'alasan_gagal' => 'DATA-ID-DARI-CLIENT',
        ]);

        $this->actingAs($pemilik->akun)
            ->get(route('pendonor.riwayat.index', [
                'id_pendonor' => $pendonorLain->id_pendonor,
            ]))
            ->assertOk()
            ->assertSee('DATA-PEMILIK-AKUN')
            ->assertDontSee('DATA-ID-DARI-CLIENT');

        $this->actingAs($pemilik->akun)
            ->json('GET', route('pendonor.riwayat.index'), [
                'id_pendonor' => $pendonorLain->id_pendonor,
            ])
            ->assertOk()
            ->assertSee('DATA-PEMILIK-AKUN')
            ->assertDontSee('DATA-ID-DARI-CLIENT');
    }

    public function test_booking_without_donation_does_not_become_history(): void
    {
        $pendonor = $this->createPendonor();
        $this->createBooking($pendonor, 'CHECK_IN');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSee('Belum ada riwayat penyumbangan.')
            ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->isEmpty());
    }

    public function test_selection_without_donation_does_not_become_history(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createSelection($this->createBooking($pendonor), $petugas, 'LAYAK');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->isEmpty());
    }

    public function test_each_selection_decision_without_donation_is_not_history(): void
    {
        foreach (['LAYAK', 'DITUNDA', 'DITOLAK'] as $decision) {
            $pendonor = $this->createPendonor();
            $petugas = $this->createPetugas();
            $this->createSelection($this->createBooking($pendonor), $petugas, $decision);

            $this->actingAs($pendonor->akun)
                ->get(route('pendonor.riwayat.index'))
                ->assertOk()
                ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->isEmpty());
        }
    }

    public function test_successful_and_failed_donations_are_both_displayed(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createDonationFor($pendonor, $petugas, [
            'hasil_penyumbangan' => 'BERHASIL',
            'volume_ml' => 350,
            'alasan_gagal' => null,
        ]);
        $this->createDonationFor($pendonor, $petugas, [
            'hasil_penyumbangan' => 'GAGAL',
            'volume_ml' => null,
            'alasan_gagal' => 'Aliran darah berhenti',
        ]);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSee('BERHASIL')
            ->assertSee('GAGAL')
            ->assertSee('350 mL')
            ->assertSee('Aliran darah berhenti')
            ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->count() === 2);
    }

    public function test_null_volume_is_displayed_neutrally(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createDonationFor($pendonor, $petugas, [
            'hasil_penyumbangan' => 'GAGAL',
            'volume_ml' => null,
            'alasan_gagal' => 'Volume tidak tersedia',
        ]);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSeeInOrder(['Volume tidak tersedia', '</tr>'], false)
            ->assertSee('<td>-</td>', false);
    }

    public function test_history_is_ordered_by_newest_collection_time_first(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createDonationFor($pendonor, $petugas, [
            'waktu_pengambilan' => '2026-01-01 08:00:00',
            'alasan_gagal' => 'DONASI-LAMA',
        ]);
        $this->createDonationFor($pendonor, $petugas, [
            'waktu_pengambilan' => '2026-09-01 08:00:00',
            'alasan_gagal' => 'DONASI-TERBARU',
        ]);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSeeInOrder(['DONASI-TERBARU', 'DONASI-LAMA']);
    }

    public function test_equal_collection_times_use_descending_donation_id_as_tie_breaker(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $first = $this->createDonationFor($pendonor, $petugas, [
            'waktu_pengambilan' => '2026-08-01 08:00:00',
            'alasan_gagal' => 'ID-LEBIH-KECIL',
        ]);
        $second = $this->createDonationFor($pendonor, $petugas, [
            'waktu_pengambilan' => '2026-08-01 08:00:00',
            'alasan_gagal' => 'ID-LEBIH-BESAR',
        ]);

        $this->assertGreaterThan($first->id_penyumbangan, $second->id_penyumbangan);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSeeInOrder(['ID-LEBIH-BESAR', 'ID-LEBIH-KECIL']);
    }

    public function test_donor_without_actual_donations_sees_empty_state(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSee('Belum ada riwayat penyumbangan.')
            ->assertDontSee('<table>', false);
    }

    public function test_history_get_leaves_workflow_data_unchanged_and_creates_no_rows(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $booking = $this->createBooking($pendonor, 'SELESAI');
        $selection = $this->createSelection($booking, $petugas);
        $donation = $this->createDonation($selection, $petugas, [
            'hasil_penyumbangan' => 'BERHASIL',
            'volume_ml' => 350,
            'alasan_gagal' => null,
        ]);
        $bookingBefore = $booking->getAttributes();
        $selectionBefore = $selection->getAttributes();
        $donationBefore = $donation->getAttributes();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk();

        $this->assertEquals($bookingBefore, $booking->fresh()->getAttributes());
        $this->assertEquals($selectionBefore, $selection->fresh()->getAttributes());
        $this->assertEquals($donationBefore, $donation->fresh()->getAttributes());
        $this->assertDatabaseCount('pemesanan_donor', 1);
        $this->assertDatabaseCount('seleksi_donor', 1);
        $this->assertDatabaseCount('penyumbangan', 1);
        $this->assertDatabaseCount('unit_komponen_darah', 0);
    }

    public function test_history_page_has_no_mutation_form_or_action(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createDonationFor($pendonor, $petugas);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertDontSee('Edit Penyumbangan')
            ->assertDontSee('Hapus Penyumbangan')
            ->assertDontSee('Buat Penyumbangan');
    }

    public function test_pendonor_dashboard_contains_real_history_link(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee(route('pendonor.riwayat.index'), false)
            ->assertSee('Riwayat Donor');
    }

    public function test_history_page_has_no_phase_7h_donor_next_calculation_or_control(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertDontSee('Donor Berikutnya')
            ->assertDontSee('dapat donor kembali')
            ->assertDontSee('Sisa kuota')
            ->assertDontSee('Hitung Kelayakan');
    }

    private function createAccount(
        string $role = 'PENDONOR',
        string $status = 'AKTIF'
    ): Akun {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7g-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPendonor(string $accountStatus = 'AKTIF'): Pendonor
    {
        $akun = $this->createAccount('PENDONOR', $accountStatus);
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
            'nomor_telepon' => '0815'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createPetugas(): Petugas
    {
        $akun = $this->createAccount('PETUGAS');
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => "P7G-{$number}",
            'nama_petugas' => "Petugas {$number}",
        ]);
    }

    private function createBooking(
        Pendonor $pendonor,
        string $status = 'SELESAI'
    ): PemesananDonor {
        $schedule = JadwalPelayanan::create([
            'tanggal' => '2026-08-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DITUTUP',
        ]);

        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2026-07-01 08:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createSelection(
        PemesananDonor $booking,
        Petugas $petugas,
        string $decision = 'LAYAK'
    ): SeleksiDonor {
        return SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'waktu_seleksi' => '2026-08-01 08:30:00',
            'berat_badan' => 60,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => null,
            'keputusan_seleksi' => $decision,
            'alasan_keputusan' => null,
        ]);
    }

    private function createDonationFor(
        Pendonor $pendonor,
        Petugas $petugas,
        array $overrides = []
    ): Penyumbangan {
        return $this->createDonation(
            $this->createSelection($this->createBooking($pendonor), $petugas),
            $petugas,
            $overrides
        );
    }

    private function createDonation(
        SeleksiDonor $selection,
        Petugas $petugas,
        array $overrides = []
    ): Penyumbangan {
        return Penyumbangan::create(array_merge([
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'waktu_pengambilan' => '2026-08-01 09:00:00',
            'volume_ml' => null,
            'hasil_penyumbangan' => 'GAGAL',
            'alasan_gagal' => 'Alasan pengujian',
        ], $overrides));
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasDashboardController;
use App\Models\Akun;
use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JenisKomponenDarah;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use App\Models\UnitKomponenDarah;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8ADashboardPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_route_and_access_are_limited_to_active_petugas_with_profile(): void
    {
        $route = Route::getRoutes()->getByName('petugas.home');

        $this->assertNotNull($route);
        $this->assertSame('petugas', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(
            PetugasDashboardController::class.'@index',
            $route->getActionName()
        );
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('active', $route->gatherMiddleware());
        $this->assertContains('role:PETUGAS', $route->gatherMiddleware());

        $this->get(route('petugas.home'))
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $this->actingAs($this->createAccount($role))
                ->get(route('petugas.home'))
                ->assertForbidden();
        }

        $petugasNonaktif = $this->createPetugas('NONAKTIF');
        $this->actingAs($petugasNonaktif->akun)
            ->get(route('petugas.home'))
            ->assertRedirect(route('login'));

        $petugasAktif = $this->createPetugas();
        $this->actingAs($petugasAktif->akun)
            ->get(route('petugas.home'))
            ->assertOk();

        $akunTanpaProfil = $this->createAccount('PETUGAS');
        $jumlahPetugas = Petugas::query()->count();

        $this->actingAs($akunTanpaProfil)
            ->get(route('petugas.home'))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');

        $this->assertSame($jumlahPetugas, Petugas::query()->count());
        $this->assertNull($akunTanpaProfil->petugas()->first());
    }

    public function test_identity_comes_from_authenticated_account_and_ignores_client_petugas_id(): void
    {
        $petugas = $this->createPetugas(name: 'Petugas Login');
        $petugasLain = $this->createPetugas(name: 'Petugas Lain');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home', [
                'id_petugas' => $petugasLain->id_petugas,
            ]))
            ->assertOk()
            ->assertSee('Petugas Login')
            ->assertSee($petugas->nomor_petugas)
            ->assertDontSee('Petugas Lain')
            ->assertDontSee($petugasLain->nomor_petugas);

        $this->assertSame(
            $petugas->id_petugas,
            $response->viewData('petugas')->id_petugas
        );
    }

    public function test_today_activity_uses_wib_date_counts_rows_and_renders_missing_status_as_zero(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 17:30:00', 'UTC'));

        $petugas = $this->createPetugas();
        $pendonor = $this->createPendonor();

        foreach (['TERJADWAL', 'CHECK_IN', 'SELESAI', 'TIDAK_HADIR', 'DIBATALKAN'] as $status) {
            $this->createBooking($pendonor, '2026-09-16', $status);
        }

        $this->createBooking($pendonor, '2026-09-15', 'TERJADWAL');
        $this->createBooking($pendonor, '2026-09-17', 'CHECK_IN');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertSee('Tanggal operasional WIB: 2026-09-16');

        $this->assertSame([
            'TERJADWAL' => 1,
            'CHECK_IN' => 1,
            'SELESAI' => 1,
            'TIDAK_HADIR' => 1,
        ], $response->viewData('kegiatanHariIni'));

        PemesananDonor::query()
            ->where('status_pemesanan', 'SELESAI')
            ->delete();

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk();

        $this->assertSame(0, $response->viewData('kegiatanHariIni')['SELESAI']);
        $response->assertSeeInOrder(['SELESAI', '<dd>0</dd>'], false);
    }

    public function test_processed_donors_are_udd_wide_distinct_checkins_without_date_filter(): void
    {
        $petugas = $this->createPetugas();
        $petugasLain = $this->createPetugas();
        $pendonorSatu = $this->createPendonor();
        $pendonorDua = $this->createPendonor();
        $bukanDiproses = $this->createPendonor();

        $this->createBooking($pendonorSatu, '2026-09-01', 'CHECK_IN');
        $this->createBooking($pendonorSatu, '2026-10-01', 'CHECK_IN');
        $this->createBooking($pendonorDua, '2026-09-15', 'CHECK_IN');

        foreach (['TERJADWAL', 'SELESAI', 'TIDAK_HADIR', 'DIBATALKAN'] as $status) {
            $this->createBooking($bukanDiproses, '2026-09-15', $status);
        }

        foreach ([$petugas, $petugasLain] as $viewer) {
            $response = $this->actingAs($viewer->akun)
                ->get(route('petugas.home', [
                    'id_petugas' => 999999,
                    'id_pendonor' => $bukanDiproses->id_pendonor,
                ]))
                ->assertOk();

            $this->assertSame(2, $response->viewData('jumlahPendonorDiproses'));
        }
    }

    public function test_total_inventory_uses_status_and_inclusive_wib_expiry_udd_wide(): void
    {
        $petugas = $this->createPetugas();
        $petugasLain = $this->createPetugas();
        $komponen = $this->createComponent('WB', 'Whole Blood');
        $golongan = $this->createBloodGroup('A', 'POSITIF');

        $this->createUnit($petugas, $komponen, $golongan, 'TERSEDIA', '2026-09-16');
        $this->createUnit($petugasLain, $komponen, $golongan, 'TERSEDIA', '2026-09-15');
        $this->createUnit($petugas, $komponen, $golongan, 'TERSEDIA', '2026-09-14');

        foreach (['MENUNGGU_PELULUSAN', 'DITOLAK', 'DIDISTRIBUSIKAN'] as $status) {
            $this->createUnit($petugasLain, $komponen, $golongan, $status, '2026-09-16');
        }

        foreach ([$petugas, $petugasLain] as $viewer) {
            $response = $this->actingAs($viewer->akun)
                ->get(route('petugas.home'))
                ->assertOk()
                ->assertSee('Total unit tersedia: 2');

            $this->assertSame(2, $response->viewData('totalPersediaanTersedia'));
        }
    }

    public function test_low_stock_handles_below_equal_above_zero_and_unconfigured_combinations(): void
    {
        $petugas = $this->createPetugas();
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $aPos = $this->createBloodGroup('A', 'POSITIF');
        $bPos = $this->createBloodGroup('B', 'POSITIF');
        $abNeg = $this->createBloodGroup('AB', 'NEGATIF');

        $below = $this->createThreshold($wb, $aPos, 2);
        $equal = $this->createThreshold($wb, $bPos, 1);
        $above = $this->createThreshold($prc, $aPos, 0);
        $zero = $this->createThreshold($prc, $bPos, 1);

        $this->createUnit($petugas, $wb, $aPos, 'TERSEDIA', '2026-09-20');
        $this->createUnit($petugas, $wb, $bPos, 'TERSEDIA', '2026-09-15');
        $this->createUnit($petugas, $prc, $aPos, 'TERSEDIA', '2026-09-20');
        $this->createUnit($petugas, $prc, $abNeg, 'TERSEDIA', '2026-09-20');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk();

        $lowStock = $response->viewData('persediaanRendah');

        $this->assertSame(
            [$below->id_ambang, $equal->id_ambang, $zero->id_ambang],
            $lowStock->pluck('id_ambang')->sort()->values()->all()
        );
        $this->assertSame(
            [1, 1, 0],
            $lowStock->sortBy('id_ambang')->pluck('jumlah_persediaan')->values()->all()
        );
        $this->assertFalse($lowStock->contains('id_ambang', $above->id_ambang));
        $this->assertSame(3, $lowStock->count());
        $this->assertSame(4, $response->viewData('totalPersediaanTersedia'));
    }

    public function test_low_stock_count_excludes_expired_nonavailable_and_mismatched_units(): void
    {
        $petugas = $this->createPetugas();
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $aPos = $this->createBloodGroup('A', 'POSITIF');
        $bPos = $this->createBloodGroup('B', 'POSITIF');
        $threshold = $this->createThreshold($wb, $aPos, 1);

        $this->createUnit($petugas, $wb, $aPos, 'TERSEDIA', '2026-09-15');
        $this->createUnit($petugas, $wb, $aPos, 'TERSEDIA', '2026-09-14');

        foreach (['MENUNGGU_PELULUSAN', 'DITOLAK', 'DIDISTRIBUSIKAN'] as $status) {
            $this->createUnit($petugas, $wb, $aPos, $status, '2026-09-20');
        }

        $this->createUnit($petugas, $prc, $aPos, 'TERSEDIA', '2026-09-20');
        $this->createUnit($petugas, $wb, $bPos, 'TERSEDIA', '2026-09-20');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk();

        $item = $response->viewData('persediaanRendah')->sole();
        $this->assertSame($threshold->id_ambang, $item->id_ambang);
        $this->assertSame(1, $item->jumlah_persediaan);
    }

    public function test_all_low_stock_rows_and_required_details_are_rendered_without_top_three_limit(): void
    {
        $petugas = $this->createPetugas();
        $golongan = $this->createBloodGroup('O', 'NEGATIF');
        $components = [
            $this->createComponent('WB', 'Whole Blood'),
            $this->createComponent('PRC', 'Packed Red Cell'),
            $this->createComponent('TC', 'Thrombocyte Concentrate'),
            $this->createComponent('FFP', 'Fresh Frozen Plasma'),
        ];

        foreach ($components as $component) {
            $this->createThreshold($component, $golongan, 0);
        }

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertSee('Jumlah kombinasi persediaan rendah: 4')
            ->assertSee('Jumlah Persediaan')
            ->assertSee('Jumlah Minimum');

        foreach ($components as $component) {
            $response
                ->assertSee($component->kode_komponen)
                ->assertSee($component->nama_komponen);
        }

        $response->assertSee('O')->assertSee('NEGATIF');
        $this->assertCount(4, $response->viewData('persediaanRendah'));
    }

    public function test_low_stock_empty_states_distinguish_missing_thresholds_from_no_low_stock(): void
    {
        $petugas = $this->createPetugas();

        $emptyResponse = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertSee('Belum ada konfigurasi ambang persediaan.')
            ->assertDontSee('Tidak ada persediaan yang berada pada atau di bawah ambang.');

        $this->assertSame([
            'TERJADWAL' => 0,
            'CHECK_IN' => 0,
            'SELESAI' => 0,
            'TIDAK_HADIR' => 0,
        ], $emptyResponse->viewData('kegiatanHariIni'));
        $this->assertSame(0, $emptyResponse->viewData('jumlahPendonorDiproses'));

        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('AB', 'POSITIF');
        $this->createThreshold($component, $bloodGroup, 0);
        $this->createUnit($petugas, $component, $bloodGroup, 'TERSEDIA', '2026-09-20');

        $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertSee('Tidak ada persediaan yang berada pada atau di bawah ambang.')
            ->assertDontSee('Belum ada konfigurasi ambang persediaan.');
    }

    public function test_dashboard_get_is_read_only_and_exposes_only_real_checkin_navigation(): void
    {
        $petugas = $this->createPetugas();
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, '2026-09-15', 'CHECK_IN');
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('A', 'NEGATIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 2);

        $bookingBefore = $booking->getAttributes();
        $thresholdBefore = $threshold->getAttributes();
        $tableCounts = collect([
            'pemesanan_donor',
            'kuesioner_pradonasi',
            'seleksi_donor',
            'penyumbangan',
            'unit_komponen_darah',
            'pemberitahuan',
            'ambang_persediaan',
        ])->mapWithKeys(fn (string $table): array => [
            $table => $this->getConnection()->table($table)->count(),
        ]);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk();
        $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk();

        $this->assertEquals($bookingBefore, $booking->fresh()->getAttributes());
        $this->assertEquals($thresholdBefore, $threshold->fresh()->getAttributes());

        foreach ($tableCounts as $table => $count) {
            $this->assertSame($count, $this->getConnection()->table($table)->count());
        }

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '<form'));
        $this->assertSame(1, substr_count($html, '<button'));
        $this->assertSame(2, substr_count($html, '<a '));
        $response
            ->assertSee(route('logout'), false)
            ->assertSee(
                '<a href="'.route('petugas.check-in.index').'">Check-in Pendonor</a>',
                false
            )
            ->assertSee(
                '<a href="'.route('petugas.pelulusan.index').'">Pelulusan</a>',
                false
            )
            ->assertDontSee('name="kode_checkin"', false)
            ->assertDontSee('action="/petugas/', false)
            ->assertDontSee('Review Kuesioner')
            ->assertDontSee('Kuesioner Pradonasi')
            ->assertDontSee('Seleksi Donor')
            ->assertDontSee('Penyumbangan')
            ->assertDontSee('Buat Unit')
            ->assertDontSee('Distribusi')
            ->assertDontSee('Pemanggilan Pendonor')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Buat Pemberitahuan');
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8a-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(
        string $accountStatus = 'AKTIF',
        ?string $name = null
    ): Petugas {
        $akun = $this->createAccount('PETUGAS', $accountStatus);
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => "P8A-{$number}",
            'nama_petugas' => $name ?? "Petugas {$number}",
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
            'nomor_telepon' => '0816'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createSchedule(string $date): JadwalPelayanan
    {
        return JadwalPelayanan::create([
            'tanggal' => $date,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 100,
            'status_jadwal' => 'DIBUKA',
        ]);
    }

    private function createBooking(
        Pendonor $pendonor,
        string $scheduleDate,
        string $status = 'TERJADWAL'
    ): PemesananDonor {
        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $this->createSchedule($scheduleDate)->id_jadwal,
            'waktu_pemesanan' => '2026-09-01 08:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => $status === 'CHECK_IN' ? '2026-09-01 09:00:00' : null,
            'status_pemesanan' => $status,
        ]);
    }

    private function createBloodGroup(string $abo, string $rhesus): GolonganDarah
    {
        return GolonganDarah::create([
            'abo' => $abo,
            'rhesus' => $rhesus,
        ]);
    }

    private function createComponent(string $code, string $name): JenisKomponenDarah
    {
        return JenisKomponenDarah::create([
            'kode_komponen' => $code,
            'nama_komponen' => $name,
        ]);
    }

    private function createThreshold(
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        int $minimum
    ): AmbangPersediaan {
        return AmbangPersediaan::create([
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'jumlah_minimum' => $minimum,
        ]);
    }

    private function createUnit(
        Petugas $petugas,
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        string $status,
        string $expiryDate
    ): UnitKomponenDarah {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking($pendonor, '2026-08-01', 'SELESAI');
        $selection = SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'waktu_seleksi' => '2026-08-01 08:00:00',
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
        $donation = Penyumbangan::create([
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'waktu_pengambilan' => '2026-08-01 09:00:00',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ]);
        $number = ++$this->sequence;

        return UnitKomponenDarah::create([
            'nomor_unit' => "UNIT-P8A-{$number}",
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'id_petugas_pelulus' => $status === 'TERSEDIA' ? $petugas->id_petugas : null,
            'tanggal_pembuatan' => '2026-08-01',
            'tanggal_kedaluwarsa' => $expiryDate,
            'waktu_pelulusan' => $status === 'TERSEDIA' ? '2026-08-01 10:00:00' : null,
            'status_unit' => $status,
            'catatan_pelulusan' => null,
            'waktu_distribusi' => $status === 'DIDISTRIBUSIKAN'
                ? '2026-08-02 10:00:00'
                : null,
        ]);
    }
}

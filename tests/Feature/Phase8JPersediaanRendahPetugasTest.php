<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasPersediaanRendahController;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8JPersediaanRendahPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-16 03:00:00', 'UTC'));
    }

    public function test_exact_route_and_access_are_limited_to_active_petugas_with_profile(): void
    {
        $route = Route::getRoutes()->getByName('petugas.persediaan-rendah.index');

        $this->assertNotNull($route);
        $this->assertSame('petugas/persediaan-rendah', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(
            PetugasPersediaanRendahController::class.'@index',
            $route->getActionName()
        );

        foreach (['web', 'auth', 'active', 'role:PETUGAS'] as $middleware) {
            $this->assertContains($middleware, $route->gatherMiddleware());
        }

        $matchingRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($candidate): bool => $candidate->uri() === 'petugas/persediaan-rendah');
        $this->assertCount(1, $matchingRoutes);

        $this->get(route('petugas.persediaan-rendah.index'))
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $this->actingAs($this->createAccount($role))
                ->get(route('petugas.persediaan-rendah.index'))
                ->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertRedirect(route('login'));

        $withoutProfile = $this->createAccount('PETUGAS');
        $profileCount = Petugas::query()->count();
        $this->actingAs($withoutProfile)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');
        $this->assertSame($profileCount, Petugas::query()->count());
        $this->assertNull($withoutProfile->petugas()->first());

        $active = $this->createPetugas(name: 'Petugas Login');
        $other = $this->createPetugas(name: 'Petugas Lain');
        $response = $this->actingAs($active->akun)
            ->get(route('petugas.persediaan-rendah.index', [
                'id_petugas' => $other->id_petugas,
            ]))
            ->assertOk()
            ->assertSee('Petugas Login')
            ->assertDontSee('Petugas Lain');

        $this->assertSame(
            $active->id_petugas,
            $response->viewData('petugas')->id_petugas
        );
    }

    public function test_low_stock_classification_starts_from_configured_thresholds_and_includes_zero_stock(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $tc = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $bPositive = $this->createBloodGroup('B', 'POSITIF');
        $oNegative = $this->createBloodGroup('O', 'NEGATIF');

        $below = $this->createThreshold($wb, $aPositive, 2);
        $equal = $this->createThreshold($wb, $bPositive, 1);
        $above = $this->createThreshold($prc, $aPositive, 0);
        $zero = $this->createThreshold($prc, $bPositive, 1);

        $this->createUnit($workflow, $wb, $aPositive);
        $this->createUnit($workflow, $wb, $bPositive);
        $this->createUnit($workflow, $prc, $aPositive);
        $this->createUnit($workflow, $tc, $oNegative);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk();

        $rows = $response->viewData('persediaanRendah')->keyBy('id_ambang');
        $this->assertCount(3, $rows);
        $this->assertSame(1, (int) $rows[$below->id_ambang]->jumlah_persediaan);
        $this->assertSame(1, (int) $rows[$equal->id_ambang]->jumlah_persediaan);
        $this->assertSame(0, (int) $rows[$zero->id_ambang]->jumlah_persediaan);
        $this->assertFalse($rows->has($above->id_ambang));
        $this->assertFalse(
            $rows->contains(
                fn (object $row): bool => $row->id_jenis_komponen === $tc->id_jenis_komponen
                    && $row->id_golongan_darah === $oNegative->id_golongan_darah
            )
        );
    }

    public function test_only_the_exact_available_nonexpired_pair_contributes_to_stock(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $bNegative = $this->createBloodGroup('B', 'NEGATIF');
        $this->createThreshold($wb, $aPositive, 10);

        $eligible = $this->createUnit($workflow, $wb, $aPositive, 'TERSEDIA', '2026-09-16');
        $expired = $this->createUnit($workflow, $wb, $aPositive, 'TERSEDIA', '2026-09-15');
        $pending = $this->createUnit($workflow, $wb, $aPositive, 'MENUNGGU_PELULUSAN', '2026-09-20');
        $rejected = $this->createUnit($workflow, $wb, $aPositive, 'DITOLAK', '2026-09-20');
        $distributed = $this->createUnit($workflow, $wb, $aPositive, 'DIDISTRIBUSIKAN', '2026-09-20');
        $differentComponent = $this->createUnit($workflow, $prc, $aPositive, 'TERSEDIA', '2026-09-20');
        $differentBloodGroup = $this->createUnit($workflow, $wb, $bNegative, 'TERSEDIA', '2026-09-20');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk();

        $row = $response->viewData('persediaanRendah')->sole();
        $this->assertSame(1, (int) $row->jumlah_persediaan);

        foreach ([
            [$eligible, 'TERSEDIA'],
            [$expired, 'TERSEDIA'],
            [$pending, 'MENUNGGU_PELULUSAN'],
            [$rejected, 'DITOLAK'],
            [$distributed, 'DIDISTRIBUSIKAN'],
            [$differentComponent, 'TERSEDIA'],
            [$differentBloodGroup, 'TERSEDIA'],
        ] as [$unit, $status]) {
            $this->assertSame($status, $unit->fresh()->status_unit);
        }

        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
    }

    public function test_expiry_boundary_uses_the_real_current_wib_calendar_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 18:00:00', 'UTC'));

        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $component = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $bloodGroup = $this->createBloodGroup('AB', 'NEGATIF');
        $this->createThreshold($component, $bloodGroup, 2);

        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-17');
        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-16');
        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-15');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk()
            ->assertSee('Tanggal acuan WIB: 2026-09-16');

        $this->assertSame('2026-09-15', now('UTC')->toDateString());
        $this->assertSame('2026-09-16', now('Asia/Jakarta')->toDateString());
        $this->assertSame('2026-09-16', $response->viewData('tanggalAcuan'));
        $this->assertSame(
            2,
            (int) $response->viewData('persediaanRendah')->sole()->jumlah_persediaan
        );
    }

    public function test_low_stock_rows_use_the_locked_database_ascending_order(): void
    {
        $petugas = $this->createPetugas();
        $tc = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $oPositive = $this->createBloodGroup('O', 'POSITIF');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $aNegative = $this->createBloodGroup('A', 'NEGATIF');

        $this->createThreshold($tc, $oPositive, 0);
        $this->createThreshold($wb, $aPositive, 0);
        $this->createThreshold($prc, $oPositive, 0);
        $this->createThreshold($prc, $aPositive, 0);
        $this->createThreshold($prc, $aNegative, 0);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'PRC - Packed Red Cell',
                'TC - Thrombocyte Concentrate',
                'WB - Whole Blood',
            ]);

        $orderedRows = $response->viewData('persediaanRendah')
            ->map(fn (object $row): string => implode('|', [
                $row->kode_komponen,
                $row->abo,
                $row->rhesus,
            ]))
            ->all();

        $this->assertSame([
            'PRC|A|NEGATIF',
            'PRC|A|POSITIF',
            'PRC|O|POSITIF',
            'TC|O|POSITIF',
            'WB|A|POSITIF',
        ], $orderedRows);
    }

    public function test_no_threshold_configuration_uses_the_first_empty_state(): void
    {
        $petugas = $this->createPetugas();

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk()
            ->assertSee('Belum ada konfigurasi ambang persediaan.')
            ->assertDontSee('Tidak ada persediaan yang berada pada atau di bawah ambang.');

        $this->assertSame(0, $response->viewData('jumlahAmbangPersediaan'));
        $this->assertTrue($response->viewData('persediaanRendah')->isEmpty());
    }

    public function test_configured_stock_above_threshold_uses_the_second_empty_state(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $component = $this->createComponent('FFP', 'Fresh Frozen Plasma');
        $bloodGroup = $this->createBloodGroup('B', 'POSITIF');
        $this->createThreshold($component, $bloodGroup, 0);
        $this->createUnit($workflow, $component, $bloodGroup);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk()
            ->assertSee('Tidak ada persediaan yang berada pada atau di bawah ambang.')
            ->assertDontSee('Belum ada konfigurasi ambang persediaan.');

        $this->assertSame(1, $response->viewData('jumlahAmbangPersediaan'));
        $this->assertTrue($response->viewData('persediaanRendah')->isEmpty());
    }

    public function test_repeated_get_is_read_only_and_exposes_no_phase_8k_or_8l_controls(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('A', 'NEGATIF');
        $eligible = $this->createUnit($workflow, $component, $bloodGroup);
        $expired = $this->createUnit(
            $workflow,
            $component,
            $bloodGroup,
            'TERSEDIA',
            '2026-09-15'
        );
        $threshold = $this->createThreshold($component, $bloodGroup, 2);

        DB::table('pemberitahuan')->insert([
            'id_pendonor' => $workflow['donor']->id_pendonor,
            'id_petugas_pengirim' => $petugas->id_petugas,
            'isi_pesan' => 'Pesan existing',
            'waktu_dibuat' => '2026-09-16 08:00:00',
            'waktu_dibaca' => null,
        ]);

        $unitsBefore = UnitKomponenDarah::query()
            ->orderBy('id_unit')
            ->get()
            ->map->getAttributes()
            ->all();
        $thresholdBefore = $threshold->getAttributes();
        $notificationsBefore = DB::table('pemberitahuan')
            ->orderBy('id_pemberitahuan')
            ->get()
            ->all();
        $tableCounts = collect([
            'unit_komponen_darah',
            'ambang_persediaan',
            'pemberitahuan',
        ])->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->count(),
        ]);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk()
            ->assertSee(route('petugas.home'), false)
            ->assertDontSee('Pemanggilan Pendonor')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Buat Pemberitahuan')
            ->assertDontSee('KEDALUWARSA');
        $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan-rendah.index'))
            ->assertOk();

        $this->assertEquals(
            $unitsBefore,
            UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all()
        );
        $this->assertEquals($thresholdBefore, $threshold->fresh()->getAttributes());
        $this->assertEquals(
            $notificationsBefore,
            DB::table('pemberitahuan')->orderBy('id_pemberitahuan')->get()->all()
        );

        foreach ($tableCounts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count());
        }

        $this->assertSame('TERSEDIA', $eligible->fresh()->status_unit);
        $this->assertSame('2026-09-16', $eligible->fresh()->tanggal_kedaluwarsa->toDateString());
        $this->assertSame('TERSEDIA', $expired->fresh()->status_unit);
        $this->assertSame('2026-09-15', $expired->fresh()->tanggal_kedaluwarsa->toDateString());
        $this->assertSame(2, $threshold->fresh()->jumlah_minimum);
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
        $response
            ->assertDontSee('Lihat Kandidat')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Buat Pemberitahuan')
            ->assertDontSee('name="id_ambang"', false);
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8j-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(
        string $status = 'AKTIF',
        ?string $name = null
    ): Petugas {
        $account = $this->createAccount('PETUGAS', $status);
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => "P8J-{$number}",
            'nama_petugas' => $name ?? "Petugas {$number}",
        ]);
    }

    private function createWorkflow(Petugas $recorder): array
    {
        $account = $this->createAccount();
        $number = $this->sequence;
        $donor = Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => "DNR-J-{$number}",
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat',
            'nomor_telepon' => '081'.str_pad((string) $number, 9, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
        $schedule = JadwalPelayanan::create([
            'tanggal' => '2026-09-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 20,
            'status_jadwal' => 'DITUTUP',
        ]);
        $booking = PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2026-09-01 07:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => '2026-09-01 08:00:00',
            'status_pemesanan' => 'SELESAI',
        ]);
        $selection = SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $recorder->id_petugas,
            'waktu_seleksi' => '2026-09-01 08:30:00',
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
            'id_petugas_pencatat' => $recorder->id_petugas,
            'waktu_pengambilan' => '2026-09-01 09:00:00',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ]);

        return compact('donor', 'schedule', 'booking', 'selection', 'donation', 'recorder');
    }

    private function createComponent(string $code, string $name): JenisKomponenDarah
    {
        return JenisKomponenDarah::create([
            'kode_komponen' => $code,
            'nama_komponen' => $name,
        ]);
    }

    private function createBloodGroup(string $abo, string $rhesus): GolonganDarah
    {
        return GolonganDarah::create([
            'abo' => $abo,
            'rhesus' => $rhesus,
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
        array $workflow,
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        string $status = 'TERSEDIA',
        string $expiryDate = '2026-09-16'
    ): UnitKomponenDarah {
        $number = ++$this->sequence;
        $released = in_array($status, ['TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'], true);

        return UnitKomponenDarah::create([
            'nomor_unit' => "UNIT-P8J-{$number}",
            'id_penyumbangan' => $workflow['donation']->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $workflow['recorder']->id_petugas,
            'id_petugas_pelulus' => $released ? $workflow['recorder']->id_petugas : null,
            'tanggal_pembuatan' => '2026-09-01',
            'tanggal_kedaluwarsa' => $expiryDate,
            'waktu_pelulusan' => $released ? '2026-09-02 10:00:00' : null,
            'status_unit' => $status,
            'catatan_pelulusan' => $released ? 'Lulus' : null,
            'waktu_distribusi' => $status === 'DIDISTRIBUSIKAN'
                ? '2026-09-10 10:00:00'
                : null,
        ]);
    }
}

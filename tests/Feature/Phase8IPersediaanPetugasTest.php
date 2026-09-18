<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasPersediaanController;
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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase8IPersediaanPetugasTest extends TestCase
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
        $route = Route::getRoutes()->getByName('petugas.persediaan.index');

        $this->assertNotNull($route);
        $this->assertSame('petugas/persediaan', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(
            PetugasPersediaanController::class.'@index',
            $route->getActionName()
        );

        foreach (['web', 'auth', 'active', 'role:PETUGAS'] as $middleware) {
            $this->assertContains($middleware, $route->gatherMiddleware());
        }

        $inventoryRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($candidate): bool => $candidate->uri() === 'petugas/persediaan');
        $this->assertCount(1, $inventoryRoutes);

        $this->get(route('petugas.persediaan.index'))
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $this->actingAs($this->createAccount($role))
                ->get(route('petugas.persediaan.index'))
                ->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertRedirect(route('login'));

        $withoutProfile = $this->createAccount('PETUGAS');
        $profileCount = Petugas::query()->count();
        $this->actingAs($withoutProfile)
            ->get(route('petugas.persediaan.index'))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');
        $this->assertSame($profileCount, Petugas::query()->count());
        $this->assertNull($withoutProfile->petugas()->first());

        $active = $this->createPetugas(name: 'Petugas Login');
        $other = $this->createPetugas(name: 'Petugas Lain');
        $response = $this->actingAs($active->akun)
            ->get(route('petugas.persediaan.index', [
                'id_petugas' => $other->id_petugas,
            ]))
            ->assertOk()
            ->assertDontSee('Petugas Login')
            ->assertDontSee('Petugas Lain');

        $this->assertSame(
            $active->id_petugas,
            $response->viewData('petugas')->id_petugas
        );
    }

    public function test_inventory_is_grouped_by_exact_component_and_blood_group_and_is_udd_wide(): void
    {
        $viewer = $this->createPetugas(name: 'Petugas Viewer');
        $otherPetugas = $this->createPetugas(name: 'Petugas Pencatat Lain');
        $viewerWorkflow = $this->createWorkflow($viewer);
        $otherWorkflow = $this->createWorkflow($otherPetugas);
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $bNegative = $this->createBloodGroup('B', 'NEGATIF');

        $this->createUnit($viewerWorkflow, $wb, $aPositive);
        $this->createUnit($otherWorkflow, $wb, $aPositive);
        $this->createUnit($otherWorkflow, $prc, $aPositive);
        $this->createUnit($viewerWorkflow, $wb, $bNegative);

        $response = $this->actingAs($viewer->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertSee('Persediaan Darah')
            ->assertSee('WB - Whole Blood')
            ->assertSee('PRC - Packed Red Cell')
            ->assertSee('A')
            ->assertSee('B')
            ->assertSee('POSITIF')
            ->assertSee('NEGATIF')
            ->assertSee('Jumlah Persediaan');

        $persediaan = $response->viewData('persediaan');
        $this->assertCount(3, $persediaan);

        $counts = $persediaan->mapWithKeys(
            fn (object $item): array => [
                $item->id_jenis_komponen.'|'.$item->id_golongan_darah
                    => (int) $item->jumlah_persediaan,
            ]
        );

        $this->assertSame(2, $counts[$wb->id_jenis_komponen.'|'.$aPositive->id_golongan_darah]);
        $this->assertSame(1, $counts[$prc->id_jenis_komponen.'|'.$aPositive->id_golongan_darah]);
        $this->assertSame(1, $counts[$wb->id_jenis_komponen.'|'.$bNegative->id_golongan_darah]);
    }

    public function test_only_available_nonexpired_units_are_counted(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('O', 'POSITIF');

        $eligible = $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-16');
        $pending = $this->createUnit($workflow, $component, $bloodGroup, 'MENUNGGU_PELULUSAN', '2026-09-20');
        $rejected = $this->createUnit($workflow, $component, $bloodGroup, 'DITOLAK', '2026-09-20');
        $distributed = $this->createUnit($workflow, $component, $bloodGroup, 'DIDISTRIBUSIKAN', '2026-09-20');
        $expired = $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-15');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk();

        $item = $response->viewData('persediaan')->sole();
        $this->assertSame(1, (int) $item->jumlah_persediaan);
        $this->assertSame('TERSEDIA', $eligible->fresh()->status_unit);
        $this->assertSame('MENUNGGU_PELULUSAN', $pending->fresh()->status_unit);
        $this->assertSame('DITOLAK', $rejected->fresh()->status_unit);
        $this->assertSame('DIDISTRIBUSIKAN', $distributed->fresh()->status_unit);
        $this->assertSame('TERSEDIA', $expired->fresh()->status_unit);
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
    }

    public function test_expiry_boundary_uses_the_real_current_wib_calendar_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 18:00:00', 'UTC'));

        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $component = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $bloodGroup = $this->createBloodGroup('AB', 'NEGATIF');

        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-17');
        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-16');
        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', '2026-09-15');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertSee('Tanggal acuan: 16-09-2026');

        $this->assertSame('2026-09-15', now('UTC')->toDateString());
        $this->assertSame('2026-09-16', now('Asia/Jakarta')->toDateString());
        $this->assertSame('2026-09-16', $response->viewData('tanggalAcuan'));
        $this->assertSame(
            2,
            (int) $response->viewData('persediaan')->sole()->jumlah_persediaan
        );
    }

    public function test_groups_use_the_locked_database_ascending_order(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $tc = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $wb = $this->createComponent('WB', 'Whole Blood');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $oPositive = $this->createBloodGroup('O', 'POSITIF');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $aNegative = $this->createBloodGroup('A', 'NEGATIF');

        $this->createUnit($workflow, $tc, $oPositive);
        $this->createUnit($workflow, $wb, $aPositive);
        $this->createUnit($workflow, $prc, $oPositive);
        $this->createUnit($workflow, $prc, $aPositive);
        $this->createUnit($workflow, $prc, $aNegative);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'PRC - Packed Red Cell',
                'TC - Thrombocyte Concentrate',
                'WB - Whole Blood',
            ]);

        $orderedGroups = $response->viewData('persediaan')
            ->map(fn (object $item): string => implode('|', [
                $item->kode_komponen,
                $item->abo,
                $item->rhesus,
            ]))
            ->all();

        $this->assertSame([
            'PRC|A|NEGATIF',
            'PRC|A|POSITIF',
            'PRC|O|POSITIF',
            'TC|O|POSITIF',
            'WB|A|POSITIF',
        ], $orderedGroups);
    }

    public function test_zero_stock_master_and_threshold_rows_are_not_synthesized(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('ZERO', 'Komponen Tanpa Stok');
        $bloodGroup = $this->createBloodGroup('AB', 'NEGATIF');
        $this->createThreshold($component, $bloodGroup, 5);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertSee('Belum ada unit yang termasuk persediaan tersedia.')
            ->assertDontSee('ZERO')
            ->assertDontSee('Komponen Tanpa Stok')
            ->assertDontSee('Jumlah Minimum');

        $this->assertTrue($response->viewData('persediaan')->isEmpty());
    }

    public function test_phase_8j_threshold_and_actions_are_absent_from_inventory_page(): void
    {
        $petugas = $this->createPetugas();
        $workflow = $this->createWorkflow($petugas);
        $component = $this->createComponent('FFP', 'Fresh Frozen Plasma');
        $bloodGroup = $this->createBloodGroup('B', 'POSITIF');
        $this->createUnit($workflow, $component, $bloodGroup);
        $this->createThreshold($component, $bloodGroup, 10);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertDontSee('Jumlah Minimum')
            ->assertDontSee('Persediaan Rendah')
            ->assertDontSee('Pemanggilan Pendonor')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Buat Pemberitahuan')
            ->assertDontSee('Tambah Stok')
            ->assertDontSee('Edit Stok')
            ->assertDontSee('Hapus Stok')
            ->assertDontSee('Distribusikan Unit');

        $item = $response->viewData('persediaan')->sole();
        $this->assertFalse(property_exists($item, 'jumlah_minimum'));
    }

    public function test_repeated_get_is_read_only_and_exposes_no_inventory_mutation_controls(): void
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
        $notificationBefore = DB::table('pemberitahuan')->orderBy('id_pemberitahuan')->get()->all();
        $tableCounts = collect([
            'unit_komponen_darah',
            'jenis_komponen_darah',
            'golongan_darah',
            'ambang_persediaan',
            'pemberitahuan',
        ])->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->count(),
        ]);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertSee(route('petugas.home'), false);
        $this->actingAs($petugas->akun)
            ->get(route('petugas.persediaan.index'))
            ->assertOk();

        $this->assertEquals(
            $unitsBefore,
            UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all()
        );
        $this->assertEquals($thresholdBefore, $threshold->fresh()->getAttributes());
        $this->assertEquals(
            $notificationBefore,
            DB::table('pemberitahuan')->orderBy('id_pemberitahuan')->get()->all()
        );

        foreach ($tableCounts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count());
        }

        $this->assertSame('TERSEDIA', $eligible->fresh()->status_unit);
        $this->assertSame('2026-09-16', $eligible->fresh()->tanggal_kedaluwarsa->toDateString());
        $this->assertSame('TERSEDIA', $expired->fresh()->status_unit);
        $this->assertSame('2026-09-15', $expired->fresh()->tanggal_kedaluwarsa->toDateString());
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
        $this->assertFalse(Schema::hasTable('persediaan'));
        $this->assertFalse(Schema::hasTable('transaksi_persediaan'));
        $response
            ->assertDontSee('Tambah Stok')
            ->assertDontSee('Edit Stok')
            ->assertDontSee('Hapus Stok')
            ->assertDontSee('Distribusikan Unit');
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8i-user{$number}@example.test",
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
            'nomor_petugas' => "P8I-{$number}",
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
            'nomor_donor' => "DNR-I-{$number}",
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
            'nomor_unit' => "UNIT-P8I-{$number}",
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

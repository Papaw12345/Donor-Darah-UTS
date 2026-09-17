<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasDistribusiController;
use App\Models\Akun;
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

class Phase8HDistribusiPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-16 03:00:00', 'UTC'));
    }

    public function test_exact_routes_and_access_are_limited_to_active_petugas_with_profile(): void
    {
        $index = Route::getRoutes()->getByName('petugas.distribusi.index');
        $show = Route::getRoutes()->getByName('petugas.distribusi.show');
        $store = Route::getRoutes()->getByName('petugas.distribusi.store');

        $this->assertSame('petugas/distribusi', $index->uri());
        $this->assertSame(['GET', 'HEAD'], $index->methods());
        $this->assertSame(PetugasDistribusiController::class.'@index', $index->getActionName());
        $this->assertSame('petugas/distribusi/{unit}', $show->uri());
        $this->assertSame(['GET', 'HEAD'], $show->methods());
        $this->assertSame(PetugasDistribusiController::class.'@show', $show->getActionName());
        $this->assertSame('petugas/distribusi/{unit}', $store->uri());
        $this->assertSame(['POST'], $store->methods());
        $this->assertSame(PetugasDistribusiController::class.'@store', $store->getActionName());

        foreach ([$index, $show, $store] as $route) {
            foreach (['web', 'auth', 'active', 'role:PETUGAS'] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware());
            }
        }

        $unit = $this->createFixture()['unit'];
        $this->get(route('petugas.distribusi.index'))->assertRedirect(route('login'));
        $this->get(route('petugas.distribusi.show', $unit))->assertRedirect(route('login'));
        $this->post(route('petugas.distribusi.store', $unit))->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $account = $this->createAccount($role);
            $this->actingAs($account)->get(route('petugas.distribusi.index'))->assertForbidden();
            $this->actingAs($account)->get(route('petugas.distribusi.show', $unit))->assertForbidden();
            $this->actingAs($account)->post(route('petugas.distribusi.store', $unit))->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)->get(route('petugas.distribusi.index'))->assertRedirect(route('login'));
        $this->actingAs($inactive->akun)->get(route('petugas.distribusi.show', $unit))->assertRedirect(route('login'));
        $this->actingAs($inactive->akun)->post(route('petugas.distribusi.store', $unit))->assertRedirect(route('login'));

        $withoutProfile = $this->createAccount('PETUGAS');
        $profileCount = Petugas::query()->count();
        $this->actingAs($withoutProfile)->get(route('petugas.distribusi.index'))->assertStatus(409);
        $this->actingAs($withoutProfile)->get(route('petugas.distribusi.show', $unit))->assertStatus(409);
        $this->actingAs($withoutProfile)->post(route('petugas.distribusi.store', $unit))->assertStatus(409);
        $this->assertSame($profileCount, Petugas::query()->count());

        $active = $this->createPetugas();
        $this->actingAs($active->akun)->get(route('petugas.distribusi.index'))->assertOk();
        $this->actingAs($active->akun)->get(route('petugas.distribusi.show', $unit))->assertOk();
        $this->actingAs($active->akun)->get(route('petugas.distribusi.show', ['unit' => 999999999]))->assertNotFound();
        $this->actingAs($active->akun)->post(route('petugas.distribusi.store', ['unit' => 999999999]))->assertNotFound();
    }

    public function test_index_lists_only_nonexpired_available_units_in_id_order_and_is_read_only(): void
    {
        $viewer = $this->createPetugas();
        $workflow = $this->createWorkflow();
        [$component, $bloodGroup] = $this->createMasters();

        $future = $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', [
            'nomor_unit' => 'FUTURE-AVAILABLE',
            'tanggal_kedaluwarsa' => '2026-09-17',
        ]);
        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', [
            'nomor_unit' => 'EXPIRED-HIDDEN',
            'tanggal_kedaluwarsa' => '2026-09-15',
        ]);
        $this->createUnit($workflow, $component, $bloodGroup, 'MENUNGGU_PELULUSAN', ['nomor_unit' => 'PENDING-HIDDEN']);
        $this->createUnit($workflow, $component, $bloodGroup, 'DITOLAK', ['nomor_unit' => 'REJECTED-HIDDEN']);
        $this->createUnit($workflow, $component, $bloodGroup, 'DIDISTRIBUSIKAN', [
            'nomor_unit' => 'DISTRIBUTED-HIDDEN',
            'waktu_distribusi' => '2026-09-15 10:00:00',
        ]);
        $today = $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', [
            'nomor_unit' => 'TODAY-AVAILABLE',
            'tanggal_kedaluwarsa' => '2026-09-16',
        ]);
        $before = UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all();

        $response = $this->actingAs($viewer->akun)
            ->get(route('petugas.distribusi.index'))
            ->assertOk()
            ->assertSee('Distribusi Unit')
            ->assertSeeInOrder(['FUTURE-AVAILABLE', 'TODAY-AVAILABLE'])
            ->assertSee('WB - Whole Blood')
            ->assertSee('A POSITIF')
            ->assertSee('2026-09-01')
            ->assertSee('2026-09-17')
            ->assertSee('TERSEDIA')
            ->assertSee(route('petugas.distribusi.show', $future), false)
            ->assertSee(route('petugas.distribusi.show', $today), false)
            ->assertSee(route('petugas.home'), false)
            ->assertDontSee('EXPIRED-HIDDEN')
            ->assertDontSee('PENDING-HIDDEN')
            ->assertDontSee('REJECTED-HIDDEN')
            ->assertDontSee('DISTRIBUTED-HIDDEN');

        $this->assertSame(
            [$future->id_unit, $today->id_unit],
            $response->viewData('units')->pluck('id_unit')->all()
        );
        $this->assertEquals(
            $before,
            UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all()
        );
    }

    public function test_detail_exposes_action_only_for_eligible_unit_and_all_gets_are_read_only(): void
    {
        $viewer = $this->createPetugas();
        $workflow = $this->createWorkflow();
        [$component, $bloodGroup] = $this->createMasters();
        $eligible = $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', [
            'nomor_unit' => 'ELIGIBLE-DETAIL',
            'tanggal_kedaluwarsa' => '2026-09-17',
        ]);
        $expired = $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', [
            'nomor_unit' => 'EXPIRED-DETAIL',
            'tanggal_kedaluwarsa' => '2026-09-15',
        ]);
        $distributed = $this->createUnit($workflow, $component, $bloodGroup, 'DIDISTRIBUSIKAN', [
            'nomor_unit' => 'DISTRIBUTED-DETAIL',
            'waktu_distribusi' => '2026-09-15 10:11:12',
        ]);
        $pending = $this->createUnit($workflow, $component, $bloodGroup, 'MENUNGGU_PELULUSAN');
        $rejected = $this->createUnit($workflow, $component, $bloodGroup, 'DITOLAK');
        $before = UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all();

        $eligibleResponse = $this->actingAs($viewer->akun)
            ->get(route('petugas.distribusi.show', $eligible))
            ->assertOk()
            ->assertSee('ELIGIBLE-DETAIL')
            ->assertSee('WB - Whole Blood')
            ->assertSee('A POSITIF')
            ->assertSee('2026-09-01')
            ->assertSee('2026-09-17')
            ->assertSee('TERSEDIA')
            ->assertSee('Distribusikan Unit')
            ->assertSee('action="'.route('petugas.distribusi.store', $eligible).'"', false)
            ->assertDontSee('name="status_unit"', false)
            ->assertDontSee('name="waktu_distribusi"', false);
        $this->assertSame(1, substr_count($eligibleResponse->getContent(), '<form'));

        foreach ([$expired, $pending, $rejected] as $readOnlyUnit) {
            $response = $this->actingAs($viewer->akun)
                ->get(route('petugas.distribusi.show', $readOnlyUnit))
                ->assertOk()
                ->assertSee('Riwayat distribusi hanya-baca.')
                ->assertDontSee('Distribusikan Unit');
            $this->assertSame(0, substr_count($response->getContent(), '<form'));
        }

        $distributedResponse = $this->actingAs($viewer->akun)
            ->get(route('petugas.distribusi.show', $distributed))
            ->assertOk()
            ->assertSee('DIDISTRIBUSIKAN')
            ->assertSee('Waktu Distribusi')
            ->assertSee('2026-09-15 10:11:12')
            ->assertSee('Riwayat distribusi hanya-baca.')
            ->assertDontSee('Distribusikan Unit');
        $this->assertSame(0, substr_count($distributedResponse->getContent(), '<form'));
        $this->assertEquals(
            $before,
            UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all()
        );
    }

    public function test_success_uses_route_and_server_authority_and_preserves_all_other_state(): void
    {
        $petugas = $this->createPetugas();
        $hostilePetugas = $this->createPetugas();
        $target = $this->createFixture(['nomor_unit' => 'TARGET-DISTRIBUSI']);
        $other = $this->createFixture(['nomor_unit' => 'OTHER-DISTRIBUSI']);
        $unit = $target['unit'];
        $unitBefore = $unit->getAttributes();
        $relatedBefore = $this->relatedAttributes($target);
        $otherBefore = $other['unit']->getAttributes();
        $unitCount = UnitKomponenDarah::query()->count();
        $notificationCount = DB::table('pemberitahuan')->count();

        $response = $this->actingAs($petugas->akun)->post(
            route('petugas.distribusi.store', $unit),
            [
                'id_unit' => $other['unit']->id_unit,
                'status_unit' => 'TERSEDIA',
                'waktu_distribusi' => '2000-01-01 00:00:00',
                'id_petugas_distributor' => $hostilePetugas->id_petugas,
                'id_penyumbangan' => $other['donation']->id_penyumbangan,
                'id_jenis_komponen' => $other['component']->id_jenis_komponen,
                'id_golongan_darah' => $other['bloodGroup']->id_golongan_darah,
                'id_petugas_pencatat' => $hostilePetugas->id_petugas,
                'id_petugas_pelulus' => $hostilePetugas->id_petugas,
                'tanggal_kedaluwarsa' => '2020-01-01',
            ]
        )->assertRedirect(route('petugas.distribusi.show', $unit));

        $stored = $unit->fresh();
        $this->assertSame('DIDISTRIBUSIKAN', $stored->status_unit);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $stored->getRawOriginal('waktu_distribusi'));
        foreach ([
            'nomor_unit',
            'id_penyumbangan',
            'id_jenis_komponen',
            'id_golongan_darah',
            'id_petugas_pencatat',
            'id_petugas_pelulus',
            'tanggal_pembuatan',
            'tanggal_kedaluwarsa',
            'waktu_pelulusan',
            'catatan_pelulusan',
        ] as $field) {
            $this->assertSame($unitBefore[$field], $stored->getAttributes()[$field], $field);
        }
        $this->assertEquals($relatedBefore, $this->relatedAttributes($target));
        $this->assertSame($unitCount, UnitKomponenDarah::query()->count());
        $this->assertSame($notificationCount, DB::table('pemberitahuan')->count());
        $this->assertEquals($otherBefore, $other['unit']->fresh()->getAttributes());
        $this->assertFalse(Schema::hasTable('persediaan'));
        $this->assertFalse(Schema::hasTable('transaksi_persediaan'));
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
        $response->assertSessionHas('success', 'Distribusi unit berhasil dicatat.');
    }

    public function test_expiry_boundary_uses_current_wib_calendar_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 18:00:00', 'UTC'));

        $petugas = $this->createPetugas();
        $future = $this->createFixture(['tanggal_kedaluwarsa' => '2026-09-17'])['unit'];
        $today = $this->createFixture(['tanggal_kedaluwarsa' => '2026-09-16'])['unit'];
        $expired = $this->createFixture(['tanggal_kedaluwarsa' => '2026-09-15'])['unit'];

        $this->actingAs($petugas->akun)
            ->post(route('petugas.distribusi.store', $future))
            ->assertRedirect(route('petugas.distribusi.show', $future));
        $this->actingAs($petugas->akun)
            ->post(route('petugas.distribusi.store', $today))
            ->assertRedirect(route('petugas.distribusi.show', $today));
        $this->actingAs($petugas->akun)
            ->post(route('petugas.distribusi.store', $expired))
            ->assertStatus(409);

        $this->assertSame('DIDISTRIBUSIKAN', $future->fresh()->status_unit);
        $this->assertNotNull($future->fresh()->waktu_distribusi);
        $this->assertSame('DIDISTRIBUSIKAN', $today->fresh()->status_unit);
        $this->assertNotNull($today->fresh()->waktu_distribusi);
        $this->assertSame('TERSEDIA', $expired->fresh()->status_unit);
        $this->assertNull($expired->fresh()->waktu_distribusi);
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
    }

    public function test_invalid_states_and_double_submit_cannot_repair_or_overwrite_state(): void
    {
        $firstPetugas = $this->createPetugas();
        $secondPetugas = $this->createPetugas();

        foreach (['MENUNGGU_PELULUSAN', 'DITOLAK', 'DIDISTRIBUSIKAN'] as $status) {
            $overrides = $status === 'DIDISTRIBUSIKAN'
                ? ['waktu_distribusi' => '2026-09-15 12:00:00']
                : [];
            $unit = $this->createFixture($overrides, $status)['unit'];
            $before = $unit->getAttributes();

            $this->actingAs($firstPetugas->akun)
                ->post(route('petugas.distribusi.store', $unit))
                ->assertStatus(409);
            $this->assertEquals($before, $unit->fresh()->getAttributes());
        }

        $unit = $this->createFixture()['unit'];
        $this->actingAs($firstPetugas->akun)
            ->post(route('petugas.distribusi.store', $unit))
            ->assertRedirect(route('petugas.distribusi.show', $unit));
        $firstTimestamp = $unit->fresh()->getRawOriginal('waktu_distribusi');

        $this->travel(10)->minutes();
        $this->actingAs($secondPetugas->akun)
            ->post(route('petugas.distribusi.store', $unit), [
                'status_unit' => 'TERSEDIA',
                'waktu_distribusi' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertStatus(409);

        $after = $unit->fresh();
        $this->assertSame('DIDISTRIBUSIKAN', $after->status_unit);
        $this->assertSame($firstTimestamp, $after->getRawOriginal('waktu_distribusi'));
        $this->assertDatabaseCount('unit_komponen_darah', 4);
    }

    public function test_distribution_naturally_removes_unit_from_existing_inventory_query(): void
    {
        $petugas = $this->createPetugas();
        $unit = $this->createFixture(['tanggal_kedaluwarsa' => '2026-09-17'])['unit'];
        $unitCount = UnitKomponenDarah::query()->count();

        $before = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertViewHas('totalPersediaanTersedia', 1);

        $this->actingAs($petugas->akun)
            ->post(route('petugas.distribusi.store', $unit))
            ->assertRedirect(route('petugas.distribusi.show', $unit));

        $after = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertViewHas('totalPersediaanTersedia', 0);

        $this->assertSame($unitCount, UnitKomponenDarah::query()->count());
        $this->assertStringContainsString('Total unit tersedia: 1', $before->getContent());
        $this->assertStringContainsString('Total unit tersedia: 0', $after->getContent());
    }

    public function test_dashboard_exposes_exactly_the_three_current_real_petugas_links(): void
    {
        $petugas = $this->createPetugas();
        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.home'))
            ->assertOk()
            ->assertSee('<a href="'.route('petugas.check-in.index').'">Check-in Pendonor</a>', false)
            ->assertSee('<a href="'.route('petugas.pelulusan.index').'">Pelulusan</a>', false)
            ->assertSee('<a href="'.route('petugas.distribusi.index').'">Distribusi</a>', false)
            ->assertDontSee('>Persediaan</a>', false)
            ->assertDontSee('>Persediaan Rendah</a>', false)
            ->assertDontSee('Pemanggilan Pendonor');

        $this->assertSame(3, substr_count($response->getContent(), '<a '));
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8h-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(string $status = 'AKTIF'): Petugas
    {
        $account = $this->createAccount('PETUGAS', $status);

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'P8H-'.$this->sequence,
            'nama_petugas' => 'Petugas '.$this->sequence,
        ]);
    }

    private function createWorkflow(): array
    {
        $account = $this->createAccount();
        $donor = Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $this->sequence, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => 'DNR-H-'.$this->sequence,
            'nama_lengkap' => 'Pendonor '.$this->sequence,
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat',
            'nomor_telepon' => '081'.str_pad((string) $this->sequence, 9, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
        $schedule = JadwalPelayanan::create([
            'tanggal' => '2026-09-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
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
        $recorder = $this->createPetugas();
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

        return compact('donor', 'schedule', 'booking', 'recorder', 'selection', 'donation');
    }

    private function createMasters(): array
    {
        return [
            JenisKomponenDarah::firstOrCreate(
                ['kode_komponen' => 'WB'],
                ['nama_komponen' => 'Whole Blood']
            ),
            GolonganDarah::firstOrCreate(['abo' => 'A', 'rhesus' => 'POSITIF']),
        ];
    }

    private function createUnit(
        array $workflow,
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        string $status = 'TERSEDIA',
        array $overrides = []
    ): UnitKomponenDarah {
        $number = ++$this->sequence;
        $released = in_array($status, ['TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'], true);

        return UnitKomponenDarah::create(array_merge([
            'nomor_unit' => 'UNIT-P8H-'.$number,
            'id_penyumbangan' => $workflow['donation']->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $workflow['recorder']->id_petugas,
            'id_petugas_pelulus' => $released ? $workflow['recorder']->id_petugas : null,
            'tanggal_pembuatan' => '2026-09-01',
            'tanggal_kedaluwarsa' => '2026-09-17',
            'waktu_pelulusan' => $released ? '2026-09-02 10:00:00' : null,
            'status_unit' => $status,
            'catatan_pelulusan' => $released ? 'Lulus' : null,
            'waktu_distribusi' => null,
        ], $overrides));
    }

    private function createFixture(array $unitOverrides = [], string $status = 'TERSEDIA'): array
    {
        $workflow = $this->createWorkflow();
        [$component, $bloodGroup] = $this->createMasters();
        $unit = $this->createUnit($workflow, $component, $bloodGroup, $status, $unitOverrides);

        return array_merge($workflow, compact('component', 'bloodGroup', 'unit'));
    }

    private function relatedAttributes(array $fixture): array
    {
        return [
            'donation' => $fixture['donation']->fresh()->getAttributes(),
            'selection' => $fixture['selection']->fresh()->getAttributes(),
            'booking' => $fixture['booking']->fresh()->getAttributes(),
            'donor' => $fixture['donor']->fresh()->getAttributes(),
            'schedule' => $fixture['schedule']->fresh()->getAttributes(),
        ];
    }
}

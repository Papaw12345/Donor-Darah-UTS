<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasPelulusanController;
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
use Tests\TestCase;

class Phase8GPelulusanPetugasTest extends TestCase
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
        $index = Route::getRoutes()->getByName('petugas.pelulusan.index');
        $show = Route::getRoutes()->getByName('petugas.pelulusan.show');
        $store = Route::getRoutes()->getByName('petugas.pelulusan.store');

        $this->assertSame('petugas/pelulusan', $index->uri());
        $this->assertSame(['GET', 'HEAD'], $index->methods());
        $this->assertSame(PetugasPelulusanController::class.'@index', $index->getActionName());
        $this->assertSame('petugas/pelulusan/{unit}', $show->uri());
        $this->assertSame(['GET', 'HEAD'], $show->methods());
        $this->assertSame(PetugasPelulusanController::class.'@show', $show->getActionName());
        $this->assertSame('petugas/pelulusan/{unit}', $store->uri());
        $this->assertSame(['POST'], $store->methods());
        $this->assertSame(PetugasPelulusanController::class.'@store', $store->getActionName());

        foreach ([$index, $show, $store] as $route) {
            foreach (['web', 'auth', 'active', 'role:PETUGAS'] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware());
            }
        }

        $unit = $this->createFixture()['unit'];
        $this->get(route('petugas.pelulusan.index'))->assertRedirect(route('login'));
        $this->get(route('petugas.pelulusan.show', $unit))->assertRedirect(route('login'));
        $this->post(route('petugas.pelulusan.store', $unit), $this->payload())->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $account = $this->createAccount($role);
            $this->actingAs($account)->get(route('petugas.pelulusan.index'))->assertForbidden();
            $this->actingAs($account)->get(route('petugas.pelulusan.show', $unit))->assertForbidden();
            $this->actingAs($account)->post(route('petugas.pelulusan.store', $unit), $this->payload())->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)->get(route('petugas.pelulusan.index'))->assertRedirect(route('login'));

        $withoutProfile = $this->createAccount('PETUGAS');
        $countBefore = Petugas::query()->count();
        $this->actingAs($withoutProfile)->get(route('petugas.pelulusan.index'))->assertStatus(409);
        $this->actingAs($withoutProfile)->get(route('petugas.pelulusan.show', $unit))->assertStatus(409);
        $this->actingAs($withoutProfile)->post(route('petugas.pelulusan.store', $unit), $this->payload())->assertStatus(409);
        $this->assertSame($countBefore, Petugas::query()->count());

        $active = $this->createPetugas();
        $this->actingAs($active->akun)->get(route('petugas.pelulusan.index'))->assertOk();
        $this->actingAs($active->akun)->get(route('petugas.pelulusan.show', ['unit' => 999999999]))->assertNotFound();
        $this->actingAs($active->akun)->post(route('petugas.pelulusan.store', ['unit' => 999999999]), $this->payload())->assertNotFound();
    }

    public function test_index_lists_only_pending_units_in_id_order_and_is_read_only(): void
    {
        $viewer = $this->createPetugas();
        $workflow = $this->createWorkflow();
        [$component, $bloodGroup] = $this->createMasters();
        $pendingOne = $this->createUnit($workflow, $component, $bloodGroup, 'MENUNGGU_PELULUSAN', ['nomor_unit' => 'PENDING-ONE']);
        $this->createUnit($workflow, $component, $bloodGroup, 'TERSEDIA', ['nomor_unit' => 'AVAILABLE-HIDDEN']);
        $this->createUnit($workflow, $component, $bloodGroup, 'DITOLAK', ['nomor_unit' => 'REJECTED-HIDDEN']);
        $this->createUnit($workflow, $component, $bloodGroup, 'DIDISTRIBUSIKAN', ['nomor_unit' => 'DISTRIBUTED-HIDDEN']);
        $pendingTwo = $this->createUnit($workflow, $component, $bloodGroup, 'MENUNGGU_PELULUSAN', ['nomor_unit' => 'PENDING-TWO']);
        $before = UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all();

        $response = $this->actingAs($viewer->akun)->get(route('petugas.pelulusan.index'))
            ->assertOk()->assertSeeInOrder(['PENDING-ONE', 'PENDING-TWO'])
            ->assertSee('WB - Whole Blood')->assertSee('A POSITIF')
            ->assertSee('2026-09-01')->assertSee('2026-10-01')
            ->assertSee(route('petugas.pelulusan.show', $pendingOne), false)
            ->assertSee(route('petugas.pelulusan.show', $pendingTwo), false)
            ->assertDontSee('AVAILABLE-HIDDEN')->assertDontSee('REJECTED-HIDDEN')->assertDontSee('DISTRIBUTED-HIDDEN');

        $this->assertSame([$pendingOne->id_unit, $pendingTwo->id_unit], $response->viewData('units')->pluck('id_unit')->all());
        $this->assertEquals($before, UnitKomponenDarah::query()->orderBy('id_unit')->get()->map->getAttributes()->all());
    }

    public function test_pending_detail_has_form_while_all_historical_states_are_read_only(): void
    {
        $viewer = $this->createPetugas();
        $releaser = $this->createPetugas();
        $workflow = $this->createWorkflow();
        [$component, $bloodGroup] = $this->createMasters();
        $pending = $this->createUnit($workflow, $component, $bloodGroup);

        $this->actingAs($viewer->akun)->get(route('petugas.pelulusan.show', $pending))
            ->assertOk()->assertSee('name="hasil_pelulusan"', false)
            ->assertSee('name="catatan_pelulusan"', false)->assertSee('Simpan Pelulusan');

        foreach (['TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'] as $status) {
            $unit = $this->createUnit($workflow, $component, $bloodGroup, $status, [
                'id_petugas_pelulus' => $releaser->id_petugas,
                'waktu_pelulusan' => '2026-09-15 10:00:00',
                'catatan_pelulusan' => 'Catatan historis',
                'waktu_distribusi' => $status === 'DIDISTRIBUSIKAN' ? '2026-09-16 10:00:00' : null,
            ]);
            $response = $this->actingAs($viewer->akun)->get(route('petugas.pelulusan.show', $unit))
                ->assertOk()->assertSee($status)->assertSee('Petugas Pelulus')
                ->assertSee($releaser->nama_petugas)->assertSee('2026-09-15 10:00:00')
                ->assertSee('Catatan historis')->assertSee('Riwayat pelulusan hanya-baca.')
                ->assertDontSee('name="hasil_pelulusan"', false)->assertDontSee('Simpan Pelulusan');
            $this->assertSame(0, substr_count($response->getContent(), '<form'));
        }
    }

    public function test_validation_accepts_only_two_results_and_normalizes_optional_note(): void
    {
        $petugas = $this->createPetugas();
        $missing = $this->createFixture()['unit'];
        $this->actingAs($petugas->akun)->post(route('petugas.pelulusan.store', $missing), [])->assertSessionHasErrors('hasil_pelulusan');
        $this->assertSame('MENUNGGU_PELULUSAN', $missing->fresh()->status_unit);

        $invalid = $this->createFixture()['unit'];
        $this->actingAs($petugas->akun)->post(route('petugas.pelulusan.store', $invalid), ['hasil_pelulusan' => 'DIDISTRIBUSIKAN'])->assertSessionHasErrors('hasil_pelulusan');
        $this->assertSame('MENUNGGU_PELULUSAN', $invalid->fresh()->status_unit);

        $trimmed = $this->createFixture()['unit'];
        $this->actingAs($petugas->akun)->post(route('petugas.pelulusan.store', $trimmed), $this->payload('TERSEDIA', '  Lulus pemeriksaan  '))->assertSessionHasNoErrors();
        $this->assertSame('Lulus pemeriksaan', $trimmed->fresh()->catatan_pelulusan);

        $blank = $this->createFixture()['unit'];
        $this->actingAs($petugas->akun)->post(route('petugas.pelulusan.store', $blank), $this->payload('DITOLAK', '   '))->assertSessionHasNoErrors();
        $this->assertNull($blank->fresh()->catatan_pelulusan);

        $nullable = $this->createFixture()['unit'];
        $this->actingAs($petugas->akun)->post(route('petugas.pelulusan.store', $nullable), ['hasil_pelulusan' => 'DITOLAK', 'catatan_pelulusan' => null])->assertSessionHasNoErrors();
        $this->assertNull($nullable->fresh()->catatan_pelulusan);
    }

    public function test_available_release_uses_server_authority_and_preserves_all_other_state(): void
    {
        $releaser = $this->createPetugas();
        $hostilePetugas = $this->createPetugas();
        $target = $this->createFixture(['tanggal_kedaluwarsa' => '2020-01-01', 'waktu_distribusi' => '2026-01-02 03:04:05']);
        $other = $this->createFixture();
        $unit = $target['unit'];
        $unitBefore = $unit->getAttributes();
        $relatedBefore = $this->relatedAttributes($target);
        $unitCount = UnitKomponenDarah::query()->count();
        $notificationCount = DB::table('pemberitahuan')->count();

        $response = $this->actingAs($releaser->akun)->post(route('petugas.pelulusan.store', $unit), array_merge(
            $this->payload('TERSEDIA', '  Aman dirilis  '),
            ['id_unit' => $other['unit']->id_unit, 'id_petugas_pelulus' => $hostilePetugas->id_petugas,
                'id_petugas_pencatat' => $hostilePetugas->id_petugas, 'id_penyumbangan' => $other['donation']->id_penyumbangan,
                'status_unit' => 'DIDISTRIBUSIKAN', 'waktu_pelulusan' => '2000-01-01 00:00:00',
                'waktu_distribusi' => '2000-01-01 00:00:00']
        ))->assertRedirect(route('petugas.pelulusan.show', $unit));

        $stored = $unit->fresh();
        $this->assertSame('TERSEDIA', $stored->status_unit);
        $this->assertSame($releaser->id_petugas, $stored->id_petugas_pelulus);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $stored->getRawOriginal('waktu_pelulusan'));
        $this->assertSame('Aman dirilis', $stored->catatan_pelulusan);
        foreach (['nomor_unit', 'id_penyumbangan', 'id_jenis_komponen', 'id_golongan_darah', 'id_petugas_pencatat', 'tanggal_pembuatan', 'tanggal_kedaluwarsa', 'waktu_distribusi'] as $field) {
            $this->assertSame($unitBefore[$field], $stored->getAttributes()[$field], $field);
        }
        $this->assertEquals($relatedBefore, $this->relatedAttributes($target));
        $this->assertSame($unitCount, UnitKomponenDarah::query()->count());
        $this->assertSame($notificationCount, DB::table('pemberitahuan')->count());
        $this->assertSame('MENUNGGU_PELULUSAN', $other['unit']->fresh()->status_unit);
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'KEDALUWARSA']);
        $response->assertSessionHas('success', 'Hasil pelulusan unit berhasil disimpan.');
    }

    public function test_rejected_release_and_double_submit_preserve_first_writer(): void
    {
        $firstPetugas = $this->createPetugas();
        $secondPetugas = $this->createPetugas();
        $unit = $this->createFixture()['unit'];
        $unchanged = $unit->getAttributes();

        $this->actingAs($firstPetugas->akun)->post(route('petugas.pelulusan.store', $unit), $this->payload('DITOLAK', '  Hasil pertama  '))
            ->assertRedirect(route('petugas.pelulusan.show', $unit));
        $first = $unit->fresh();
        $firstTimestamp = $first->getRawOriginal('waktu_pelulusan');
        $this->assertSame('DITOLAK', $first->status_unit);
        $this->assertSame($firstPetugas->id_petugas, $first->id_petugas_pelulus);
        $this->assertSame('Hasil pertama', $first->catatan_pelulusan);

        $this->travel(10)->minutes();
        $this->actingAs($secondPetugas->akun)->post(route('petugas.pelulusan.store', $unit), $this->payload('TERSEDIA', 'Upaya menimpa'))->assertStatus(409);
        $after = $unit->fresh();
        $this->assertSame('DITOLAK', $after->status_unit);
        $this->assertSame($firstPetugas->id_petugas, $after->id_petugas_pelulus);
        $this->assertSame($firstTimestamp, $after->getRawOriginal('waktu_pelulusan'));
        $this->assertSame('Hasil pertama', $after->catatan_pelulusan);
        foreach (['nomor_unit', 'id_penyumbangan', 'id_jenis_komponen', 'id_golongan_darah', 'id_petugas_pencatat', 'tanggal_pembuatan', 'tanggal_kedaluwarsa', 'waktu_distribusi'] as $field) {
            $this->assertSame($unchanged[$field], $after->getAttributes()[$field], $field);
        }
        $this->assertDatabaseCount('unit_komponen_darah', 1);
        $this->assertDatabaseMissing('unit_komponen_darah', ['status_unit' => 'DIDISTRIBUSIKAN']);
    }

    public function test_dashboard_exposes_only_current_real_petugas_navigation(): void
    {
        $petugas = $this->createPetugas();
        $response = $this->actingAs($petugas->akun)->get(route('petugas.home'))
            ->assertOk()->assertSee(route('petugas.check-in.index'), false)->assertSee('Check-in Pendonor')
            ->assertSee(route('petugas.pelulusan.index'), false)->assertSee('Pelulusan')
            ->assertSee(route('petugas.distribusi.index'), false)->assertSee('Distribusi')
            ->assertSee(route('petugas.persediaan.index'), false)->assertSee('Persediaan')
            ->assertSee(route('petugas.persediaan-rendah.index'), false)->assertSee('Persediaan Rendah')
            ->assertSee(route('petugas.pemanggilan.index'), false)->assertSee('Pemanggilan Pendonor')
            ->assertDontSee('Pemberitahuan Petugas')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertDontSee('Buat Pemberitahuan');
        $this->assertSame(6, substr_count($response->getContent(), '<a '));
    }

    private function payload(string $result = 'TERSEDIA', ?string $note = null): array
    {
        return ['hasil_pelulusan' => $result, 'catatan_pelulusan' => $note];
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;
        return Akun::create(['email' => "phase8g-user{$number}@example.test", 'password_hash' => 'test-password-hash', 'peran' => $role, 'status_akun' => $status]);
    }

    private function createPetugas(string $status = 'AKTIF'): Petugas
    {
        $account = $this->createAccount('PETUGAS', $status);
        return Petugas::create(['id_akun' => $account->id_akun, 'nomor_petugas' => 'P8G-'.$this->sequence, 'nama_petugas' => 'Petugas '.$this->sequence]);
    }

    private function createWorkflow(): array
    {
        $account = $this->createAccount();
        $donor = Pendonor::create(['id_akun' => $account->id_akun, 'id_golongan_darah' => null, 'nik' => str_pad((string) $this->sequence, 16, '0', STR_PAD_LEFT), 'nomor_donor' => 'DNR-'.$this->sequence, 'nama_lengkap' => 'Pendonor '.$this->sequence, 'jenis_kelamin' => 'LAKI_LAKI', 'tanggal_lahir' => '1990-01-01', 'tempat_lahir' => 'Jakarta', 'alamat' => 'Alamat', 'nomor_telepon' => '081'.str_pad((string) $this->sequence, 9, '0', STR_PAD_LEFT), 'pekerjaan' => null, 'alamat_kantor' => null]);
        $schedule = JadwalPelayanan::create(['tanggal' => '2026-09-01', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'kapasitas' => 10, 'status_jadwal' => 'DITUTUP']);
        $booking = PemesananDonor::create(['id_pendonor' => $donor->id_pendonor, 'id_jadwal' => $schedule->id_jadwal, 'waktu_pemesanan' => '2026-09-01 07:00:00', 'kode_checkin' => null, 'waktu_checkin' => '2026-09-01 08:00:00', 'status_pemesanan' => 'SELESAI']);
        $recorder = $this->createPetugas();
        $selection = SeleksiDonor::create(['id_pemesanan' => $booking->id_pemesanan, 'id_petugas' => $recorder->id_petugas, 'waktu_seleksi' => '2026-09-01 08:30:00', 'berat_badan' => 60, 'tekanan_sistolik' => 120, 'tekanan_diastolik' => 80, 'denyut_nadi' => 72, 'suhu_tubuh' => 36.5, 'kadar_hb' => 13.5, 'hasil_pemeriksaan_kesehatan' => null, 'keputusan_seleksi' => 'LAYAK', 'alasan_keputusan' => null]);
        $donation = Penyumbangan::create(['id_seleksi' => $selection->id_seleksi, 'id_petugas_pencatat' => $recorder->id_petugas, 'waktu_pengambilan' => '2026-09-01 09:00:00', 'volume_ml' => 350, 'hasil_penyumbangan' => 'BERHASIL', 'alasan_gagal' => null]);
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

    private function createUnit(array $workflow, JenisKomponenDarah $component, GolonganDarah $bloodGroup, string $status = 'MENUNGGU_PELULUSAN', array $overrides = []): UnitKomponenDarah
    {
        $number = ++$this->sequence;
        return UnitKomponenDarah::create(array_merge([
            'nomor_unit' => 'UNIT-P8G-'.$number,
            'id_penyumbangan' => $workflow['donation']->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $workflow['recorder']->id_petugas,
            'id_petugas_pelulus' => null,
            'tanggal_pembuatan' => '2026-09-01',
            'tanggal_kedaluwarsa' => '2026-10-01',
            'waktu_pelulusan' => null,
            'status_unit' => $status,
            'catatan_pelulusan' => null,
            'waktu_distribusi' => null,
        ], $overrides));
    }

    private function createFixture(array $unitOverrides = []): array
    {
        $workflow = $this->createWorkflow();
        [$component, $bloodGroup] = $this->createMasters();
        $unit = $this->createUnit($workflow, $component, $bloodGroup, 'MENUNGGU_PELULUSAN', $unitOverrides);
        return array_merge($workflow, compact('component', 'bloodGroup', 'unit'));
    }

    private function relatedAttributes(array $fixture): array
    {
        return [
            'donation' => $fixture['donation']->fresh()->getAttributes(),
            'selection' => $fixture['selection']->fresh()->getAttributes(),
            'booking' => $fixture['booking']->fresh()->getAttributes(),
            'donor' => $fixture['donor']->fresh()->getAttributes(),
        ];
    }
}

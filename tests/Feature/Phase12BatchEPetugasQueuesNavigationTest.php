<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\JenisKomponenDarah;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use App\Models\Penyumbangan;
use App\Models\SeleksiDonor;
use App\Models\UnitKomponenDarah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase12BatchEPetugasQueuesNavigationTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_all_queue_routes_require_an_active_petugas_with_a_profile(): void
    {
        $routes = [
            'petugas.seleksi.index' => 'petugas/seleksi',
            'petugas.penyumbangan.index' => 'petugas/penyumbangan',
            'petugas.unit-komponen.index' => 'petugas/unit-komponen',
            'petugas.pelulusan.index' => 'petugas/pelulusan',
        ];
        $admin = $this->account('ADMIN');
        $donor = $this->account('PENDONOR');
        $inactive = $this->staff('NONAKTIF');
        $withoutProfile = $this->account('PETUGAS');
        $active = $this->staff();

        foreach ($routes as $name => $uri) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertSame($uri, $route->uri());
            $this->assertSame(['GET', 'HEAD'], $route->methods());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('active', $route->gatherMiddleware());
            $this->assertContains('role:PETUGAS', $route->gatherMiddleware());

            Auth::logout();
            $this->get(route($name))->assertRedirect(route('login'));
            $this->actingAs($admin)->get(route($name))->assertForbidden();
            $this->actingAs($donor)->get(route($name))->assertForbidden();
            $this->actingAs($inactive->akun)->get(route($name))->assertRedirect(route('login'));
            $this->actingAs($withoutProfile)->get(route($name))->assertStatus(409);
            $this->actingAs($active->akun)->get(route($name))->assertOk();
        }
    }

    public function test_selection_queue_requires_all_prerequisites_and_orders_by_checkin_then_booking_id(): void
    {
        $recorder = $this->staff();
        $viewer = $this->staff();
        $late = $this->booking(checkin: '2026-09-16 09:00:00');
        $first = $this->booking(checkin: '2026-09-16 08:00:00', scheduleDate: '2020-01-01', scheduleStatus: 'DIBATALKAN');
        $second = $this->booking(checkin: '2026-09-16 08:00:00');
        foreach ([$late, $first, $second] as $booking) {
            $this->questionnaire($booking);
        }

        $missingTimestamp = $this->booking(checkin: null);
        $this->questionnaire($missingTimestamp);
        $this->booking(); // no questionnaire
        $noAnswer = $this->booking();
        $this->questionnaire($noAnswer, withAnswer: false);
        $selected = $this->booking();
        $this->questionnaire($selected);
        $this->selection($selected, $recorder);
        foreach (['TERJADWAL', 'SELESAI', 'DIBATALKAN', 'TIDAK_HADIR'] as $status) {
            $excluded = $this->booking(status: $status);
            $this->questionnaire($excluded);
        }

        $before = $this->businessRows();
        $response = $this->actingAs($viewer->akun)->get(route('petugas.seleksi.index'))->assertOk();
        $this->assertSame(
            [$first->id_pemesanan, $second->id_pemesanan, $late->id_pemesanan],
            $response->viewData('pemesananMenunggu')->pluck('id_pemesanan')->all()
        );
        foreach ([$first, $second, $late] as $booking) {
            $response->assertSee('href="'.route('petugas.seleksi.show', $booking).'"', false);
        }
        $this->actingAs($viewer->akun)->get(route('petugas.seleksi.show', $first))->assertOk();
        $this->assertSame($before, $this->businessRows());
    }

    public function test_donation_queue_requires_layak_checked_in_without_donation_and_orders_deterministically(): void
    {
        $recorder = $this->staff();
        $viewer = $this->staff();
        $late = $this->selection($this->booking(), $recorder, at: '2026-09-16 09:00:00');
        $first = $this->selection($this->booking(scheduleDate: '2020-01-01', scheduleStatus: 'DIBATALKAN'), $recorder, at: '2026-09-16 08:00:00');
        $second = $this->selection($this->booking(), $recorder, at: '2026-09-16 08:00:00');
        $this->selection($this->booking(), $recorder, decision: 'DITUNDA');
        $this->selection($this->booking(), $recorder, decision: 'DITOLAK');
        $done = $this->selection($this->booking(), $recorder);
        $this->donation($done, $recorder);
        $this->selection($this->booking(status: 'SELESAI'), $recorder);
        $this->selection($this->booking(checkin: null), $recorder);

        $before = $this->businessRows();
        $response = $this->actingAs($viewer->akun)->get(route('petugas.penyumbangan.index'))->assertOk();
        $this->assertSame(
            [$first->id_seleksi, $second->id_seleksi, $late->id_seleksi],
            $response->viewData('seleksiMenunggu')->pluck('id_seleksi')->all()
        );
        foreach ([$first, $second, $late] as $selection) {
            $response->assertSee('href="'.route('petugas.penyumbangan.show', $selection).'"', false);
        }
        $this->actingAs($viewer->akun)->get(route('petugas.penyumbangan.show', $first))->assertOk();
        $this->assertSame($before, $this->businessRows());
    }

    public function test_unit_queue_keeps_successful_sources_after_units_exist_and_orders_deterministically(): void
    {
        $recorder = $this->staff();
        $viewer = $this->staff();
        $late = $this->donation($this->selection($this->booking(), $recorder), $recorder, at: '2026-09-16 09:00:00');
        $first = $this->donation($this->selection($this->booking(scheduleDate: '2020-01-01', scheduleStatus: 'DIBATALKAN'), $recorder), $recorder, at: '2026-09-16 08:00:00');
        $second = $this->donation($this->selection($this->booking(), $recorder), $recorder, at: '2026-09-16 08:00:00');
        $failed = $this->donation($this->selection($this->booking(), $recorder), $recorder, result: 'GAGAL');
        $blood = GolonganDarah::create(['abo' => 'A', 'rhesus' => 'POSITIF']);
        $component = JenisKomponenDarah::create(['kode_komponen' => 'PRC', 'nama_komponen' => 'Packed Red Cells']);
        $this->unit($first, $recorder, $component, $blood);
        $this->unit($first, $recorder, $component, $blood);

        $before = $this->businessRows();
        $response = $this->actingAs($viewer->akun)->get(route('petugas.unit-komponen.index'))->assertOk();
        $this->assertSame(
            [$first->id_penyumbangan, $second->id_penyumbangan, $late->id_penyumbangan],
            $response->viewData('penyumbanganBerhasil')->pluck('id_penyumbangan')->all()
        );
        $this->assertSame(2, $response->viewData('penyumbanganBerhasil')->first()->unit_komponen_darah_count);
        $response->assertSee('Kelola Unit')->assertSee('href="'.route('petugas.unit-komponen.show', $first).'"', false);
        $response->assertDontSee('href="'.route('petugas.unit-komponen.show', $failed).'"', false);
        $this->actingAs($viewer->akun)->get(route('petugas.unit-komponen.show', $first))->assertOk();
        $this->assertSame($before, $this->businessRows());
    }

    public function test_release_queue_remains_pending_only_in_unit_id_order(): void
    {
        $staff = $this->staff();
        $donation = $this->donation($this->selection($this->booking(), $staff), $staff);
        $blood = GolonganDarah::create(['abo' => 'O', 'rhesus' => 'NEGATIF']);
        $component = JenisKomponenDarah::create(['kode_komponen' => 'WB', 'nama_komponen' => 'Whole Blood']);
        $pendingFirst = $this->unit($donation, $staff, $component, $blood);
        foreach (['TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'] as $status) {
            $this->unit($donation, $staff, $component, $blood, $status);
        }
        $pendingSecond = $this->unit($donation, $staff, $component, $blood);
        $before = $this->businessRows();

        $response = $this->actingAs($staff->akun)->get(route('petugas.pelulusan.index'))->assertOk();
        $this->assertSame(
            [$pendingFirst->id_unit, $pendingSecond->id_unit],
            $response->viewData('units')->pluck('id_unit')->all()
        );
        $this->assertSame($before, $this->businessRows());
    }

    public function test_navigation_and_empty_states_link_to_real_workflows_without_extra_top_level_menus(): void
    {
        $staff = $this->staff();
        $response = $this->actingAs($staff->akun)->get(route('petugas.seleksi.index'))->assertOk();
        $response->assertSee('Tidak ada pemesanan yang menunggu seleksi.');
        $content = $response->getContent();
        $this->assertStringContainsString('aria-label="Operasional"', $content);
        $this->assertStringContainsString('aria-label="Monitoring"', $content);
        $links = [
            'petugas.home' => 'Dashboard',
            'petugas.check-in.index' => 'Check-in',
            'petugas.seleksi.index' => 'Seleksi Donor',
            'petugas.penyumbangan.index' => 'Penyumbangan',
            'petugas.unit-komponen.index' => 'Unit Komponen',
            'petugas.pelulusan.index' => 'Pelulusan',
            'petugas.distribusi.index' => 'Distribusi',
            'petugas.jadwal.index' => 'Jadwal Pelayanan',
            'petugas.riwayat-pelayanan.index' => 'Riwayat Pelayanan',
            'petugas.persediaan.index' => 'Persediaan',
            'petugas.pemanggilan.index' => 'Pemanggilan Pendonor',
        ];
        foreach ($links as $name => $label) {
            $this->assertNotNull(Route::getRoutes()->getByName($name));
            $response->assertSee('href="'.route($name).'"', false)->assertSee($label);
            $this->actingAs($staff->akun)->get(route($name))->assertOk();
        }
        $response->assertSee('action="'.route('logout').'"', false);
        $this->assertSame(11, substr_count($content, 'class="nav-link'));
        $this->assertStringNotContainsString('>Kuesioner</a>', $content);
        $this->assertStringNotContainsString('>Persediaan Rendah</a>', $content);

        $this->actingAs($staff->akun)->get(route('petugas.penyumbangan.index'))
            ->assertOk()->assertSee('Tidak ada seleksi yang menunggu penyumbangan.');
        $this->actingAs($staff->akun)->get(route('petugas.unit-komponen.index'))
            ->assertOk()->assertSee('Belum ada penyumbangan berhasil untuk dikelola.');
    }

    private function account(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "batch-e-{$number}@example.test",
            'password_hash' => 'hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function staff(string $status = 'AKTIF'): Petugas
    {
        $account = $this->account('PETUGAS', $status);

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'PTG-'.str_pad((string) $account->id_akun, 6, '0', STR_PAD_LEFT),
            'nama_petugas' => "Petugas {$account->id_akun}",
        ]);
    }

    private function booking(
        string $status = 'CHECK_IN',
        ?string $checkin = '2026-09-16 08:30:00',
        string $scheduleDate = '2026-09-16',
        string $scheduleStatus = 'DIBUKA'
    ): PemesananDonor {
        $account = $this->account();
        $donor = Pendonor::create([
            'id_akun' => $account->id_akun,
            'nik' => str_pad((string) $account->id_akun, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => 'DNR-'.str_pad((string) $account->id_akun, 6, '0', STR_PAD_LEFT),
            'nama_lengkap' => "Pendonor {$account->id_akun}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0819'.str_pad((string) $account->id_akun, 8, '0', STR_PAD_LEFT),
        ]);
        $schedule = JadwalPelayanan::create([
            'tanggal' => $scheduleDate,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => $scheduleStatus,
        ]);

        return PemesananDonor::create([
            'id_pendonor' => $donor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2026-09-15 09:00:00',
            'kode_checkin' => 'UDD-'.strtoupper(str_pad(dechex($account->id_akun), 12, '0', STR_PAD_LEFT)),
            'waktu_checkin' => $checkin,
            'status_pemesanan' => $status,
        ]);
    }

    private function questionnaire(PemesananDonor $booking, bool $withAnswer = true): void
    {
        $question = PertanyaanKuesioner::create([
            'teks_pertanyaan' => "Pertanyaan {$booking->id_pemesanan}",
            'kategori' => null,
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ]);
        $questionnaire = KuesionerPradonasi::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'waktu_pengisian' => '2026-09-16 07:30:00',
        ]);
        if ($withAnswer) {
            JawabanKuesioner::create([
                'id_kuesioner' => $questionnaire->id_kuesioner,
                'id_pertanyaan' => $question->id_pertanyaan,
                'jawaban' => 'TIDAK',
            ]);
        }
    }

    private function selection(
        PemesananDonor $booking,
        Petugas $staff,
        string $decision = 'LAYAK',
        string $at = '2026-09-16 08:45:00'
    ): SeleksiDonor {
        return SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $staff->id_petugas,
            'waktu_seleksi' => $at,
            'berat_badan' => '60.00',
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => '36.7',
            'kadar_hb' => '13.5',
            'hasil_pemeriksaan_kesehatan' => 'Sehat',
            'keputusan_seleksi' => $decision,
            'alasan_keputusan' => $decision === 'LAYAK' ? null : 'Alasan pengujian',
        ]);
    }

    private function donation(
        SeleksiDonor $selection,
        Petugas $staff,
        string $result = 'BERHASIL',
        string $at = '2026-09-16 09:15:00'
    ): Penyumbangan {
        return Penyumbangan::create([
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $staff->id_petugas,
            'waktu_pengambilan' => $at,
            'volume_ml' => 350,
            'hasil_penyumbangan' => $result,
            'alasan_gagal' => $result === 'GAGAL' ? 'Alasan pengujian' : null,
        ]);
    }

    private function unit(
        Penyumbangan $donation,
        Petugas $staff,
        JenisKomponenDarah $component,
        GolonganDarah $blood,
        string $status = 'MENUNGGU_PELULUSAN'
    ): UnitKomponenDarah {
        $number = ++$this->sequence;

        return UnitKomponenDarah::create([
            'nomor_unit' => 'TEST-UNIT-'.$number,
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $blood->id_golongan_darah,
            'id_petugas_pencatat' => $staff->id_petugas,
            'tanggal_pembuatan' => '2026-09-16',
            'tanggal_kedaluwarsa' => '2026-10-16',
            'status_unit' => $status,
        ]);
    }

    private function businessRows(): array
    {
        return collect([
            'akun', 'golongan_darah', 'pendonor', 'petugas', 'jadwal_pelayanan',
            'pemesanan_donor', 'kuesioner_pradonasi', 'pertanyaan_kuesioner',
            'jawaban_kuesioner', 'seleksi_donor', 'penyumbangan',
            'jenis_komponen_darah', 'unit_komponen_darah', 'ambang_persediaan',
            'pemberitahuan',
        ])->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
    }
}

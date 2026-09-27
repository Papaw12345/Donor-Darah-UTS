<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JenisKomponenDarah;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase12BatchFAdminSortingDashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_dashboard_access_four_derived_counts_wib_boundary_and_read_only_get(): void
    {
        // UTC masih 26 September, tetapi hari ini di Jakarta sudah 27 September.
        $this->travelTo(CarbonImmutable::parse('2026-09-26 18:30:00', 'UTC'));
        $admin = $this->account('ADMIN');
        $donor = $this->account('PENDONOR');
        $otherStaff = $this->staff();
        $this->staff();
        $this->staff('NONAKTIF');
        $this->staffForAccount($this->account('ADMIN')); // profil tidak menjadikan akun ADMIN sebagai Petugas aktif

        $this->schedule('2026-09-26', 'DIBUKA');
        $this->schedule('2026-09-27', 'DIBUKA');
        $this->schedule('2026-09-28', 'DIBUKA');
        $this->schedule('2026-09-28', 'DITUTUP');
        $this->schedule('2026-09-29', 'DIBATALKAN');
        $this->question(1, true);
        $this->question(2, true);
        $this->question(3, false);

        $components = [
            $this->createComponent('WB'), $this->createComponent('PRC'),
            $this->createComponent('TC'), $this->createComponent('FFP'),
        ];
        $groups = [];
        foreach (['A', 'B', 'AB', 'O'] as $abo) {
            foreach (['POSITIF', 'NEGATIF'] as $rhesus) {
                $groups[] = $this->blood($abo, $rhesus);
            }
        }
        $this->threshold($components[0], $groups[0], 2);
        $this->threshold($components[1], $groups[1], 3);
        $this->threshold($components[2], $groups[2], 4);

        $this->get(route('admin.home'))->assertRedirect(route('login'));
        $this->actingAs($donor)->get(route('admin.home'))->assertForbidden();
        $this->actingAs($otherStaff->akun)->get(route('admin.home'))->assertForbidden();
        $inactiveAdmin = $this->account('ADMIN', 'NONAKTIF');
        $this->actingAs($inactiveAdmin)->get(route('admin.home'))->assertRedirect(route('login'));

        $before = $this->configurationRows();
        $response = $this->actingAs($admin)->get(route('admin.home'))->assertOk();
        $response
            ->assertSee('<h1 class="page-title">Ringkasan Konfigurasi Sistem</h1>', false)
            ->assertSee('Petugas Aktif')
            ->assertSee('Jadwal Dibuka')
            ->assertSee('Pertanyaan Aktif')
            ->assertSee('Konfigurasi Ambang')
            ->assertSee('3/32 terisi, 29 belum')
            ->assertDontSee('Donor hari ini')
            ->assertDontSee('Pendonor sedang diproses')
            ->assertDontSee('Total Unit Darah');
        $this->assertSame(4, substr_count($response->getContent(), 'class="dashboard-summary-item"'));
        $this->assertSame(2, $response->viewData('activePetugasCount'));
        $this->assertSame(2, $response->viewData('openUpcomingScheduleCount'));
        $this->assertSame(2, $response->viewData('activeQuestionCount'));
        $this->assertSame(3, $response->viewData('configuredThresholdCount'));
        $this->assertSame(32, $response->viewData('expectedThresholdCount'));
        $this->assertSame(29, $response->viewData('missingThresholdCount'));
        $this->assertSame($before, $this->configurationRows());

        // Total kombinasi selalu dihitung ulang ketika master bertambah.
        $this->createComponent('OTHER');
        $this->actingAs($admin)->get(route('admin.home'))
            ->assertOk()
            ->assertSee('3/40 terisi, 37 belum');
    }

    public function test_petugas_list_places_active_first_then_descending_petugas_id(): void
    {
        $admin = $this->account('ADMIN');
        $activeOld = $this->staff();
        $inactiveOld = $this->staff('NONAKTIF');
        $activeNew = $this->staff();
        $inactiveNew = $this->staff('NONAKTIF');
        $foreignRole = $this->staffForAccount($this->account('ADMIN'));

        $response = $this->actingAs($admin)->get(route('admin.petugas.index'))->assertOk();
        $this->assertSame(
            [$activeNew->id_petugas, $activeOld->id_petugas, $inactiveNew->id_petugas, $inactiveOld->id_petugas],
            $response->viewData('petugas')->modelKeys()
        );
        $response->assertDontSee($foreignRole->nama_petugas);
        $response->assertSee(route('admin.petugas.edit', $activeNew), false);
    }

    public function test_question_list_places_active_first_then_urutan_and_question_id(): void
    {
        $admin = $this->account('ADMIN');
        $activeLater = $this->question(3, true);
        $inactiveLater = $this->question(1, false);
        $activeFirst = $this->question(1, true);
        $activeTie = $this->question(1, true);
        $inactiveFirst = $this->question(0, false);

        $response = $this->actingAs($admin)->get(route('admin.pertanyaan.index'))->assertOk();
        $this->assertSame(
            [
                $activeFirst->id_pertanyaan, $activeTie->id_pertanyaan, $activeLater->id_pertanyaan,
                $inactiveFirst->id_pertanyaan, $inactiveLater->id_pertanyaan,
            ],
            $response->viewData('pertanyaan')->modelKeys()
        );
        $response->assertSee($inactiveFirst->teks_pertanyaan);
        $response->assertSee(route('admin.pertanyaan.edit', $activeFirst), false);
    }

    public function test_schedule_list_groups_today_and_future_before_newest_history_regardless_of_status(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-26 18:30:00', 'UTC'));
        $admin = $this->account('ADMIN');
        $pastOld = $this->schedule('2026-09-20', 'DIBUKA');
        $futureFar = $this->schedule('2026-10-02', 'DIBATALKAN');
        $today = $this->schedule('2026-09-27', 'DITUTUP');
        $pastNew = $this->schedule('2026-09-26', 'DIBATALKAN');
        $futureNear = $this->schedule('2026-09-28', 'DIBUKA');

        $response = $this->actingAs($admin)->get(route('admin.jadwal.index'))->assertOk();
        $this->assertSame(
            [$today->id_jadwal, $futureNear->id_jadwal, $futureFar->id_jadwal],
            $response->viewData('jadwalMendatang')->modelKeys()
        );
        $this->assertSame(
            [$pastNew->id_jadwal, $pastOld->id_jadwal],
            $response->viewData('jadwalRiwayat')->modelKeys()
        );
        $response
            ->assertSee('Hari Ini &amp; Mendatang', false)
            ->assertSee('Riwayat')
            ->assertSeeInOrder(['27-09-2026', '28-09-2026', '02-10-2026', '26-09-2026', '20-09-2026'])
            ->assertSee('Ditutup')
            ->assertSee('Dibatalkan')
            ->assertSee(route('admin.jadwal.edit', $pastNew), false);
    }

    public function test_threshold_list_orders_by_component_code_then_blood_id_then_threshold_id(): void
    {
        $admin = $this->account('ADMIN');
        $wholeBlood = $this->createComponent('WB'); // ID lebih kecil, tetapi kode lebih akhir
        $frozenPlasma = $this->createComponent('FFP');
        $redCells = $this->createComponent('PRC');
        $o = $this->blood('O', 'NEGATIF'); // ID lebih kecil, tetapi ABO lexical lebih akhir
        $ab = $this->blood('AB', 'POSITIF');
        $a = $this->blood('A', 'POSITIF');

        $wbO = $this->threshold($wholeBlood, $o, 1);
        $ffpA = $this->threshold($frozenPlasma, $a, 2);
        $prcAb = $this->threshold($redCells, $ab, 3);
        $ffpAb = $this->threshold($frozenPlasma, $ab, 4);
        $ffpO = $this->threshold($frozenPlasma, $o, 5);

        DB::enableQueryLog();
        $response = $this->actingAs($admin)->get(route('admin.ambang.index'))->assertOk();
        $orderedQuery = collect(DB::getQueryLog())->pluck('query')->first(
            fn (string $query): bool => str_contains($query, 'join "jenis_komponen_darah"')
        );
        DB::disableQueryLog();
        $this->assertNotNull($orderedQuery);
        $normalizedQuery = str_replace(['"', '`'], '', strtolower($orderedQuery));
        $this->assertStringContainsString(
            'order by jenis_komponen_darah.kode_komponen asc, golongan_darah.id_golongan_darah asc, ambang_persediaan.id_ambang asc',
            $normalizedQuery
        );
        $this->assertSame(
            [$ffpO->id_ambang, $ffpAb->id_ambang, $ffpA->id_ambang, $prcAb->id_ambang, $wbO->id_ambang],
            $response->viewData('ambang')->modelKeys()
        );
        $response->assertSee('FFP')->assertSee('O Negatif')->assertSee('5');
        $response->assertSee(route('admin.ambang.edit', $ffpO), false);
    }

    private function account(string $role, string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "batch-f-{$number}@example.test",
            'password_hash' => 'test-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function staff(string $status = 'AKTIF'): Petugas
    {
        return $this->staffForAccount($this->account('PETUGAS', $status));
    }

    private function staffForAccount(Akun $account): Petugas
    {
        $number = ++$this->sequence;

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => "TEST-PTG-{$number}",
            'nama_petugas' => "Petugas {$number}",
        ]);
    }

    private function schedule(string $date, string $status): JadwalPelayanan
    {
        return JadwalPelayanan::create([
            'tanggal' => $date,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 10,
            'status_jadwal' => $status,
        ]);
    }

    private function question(int $order, bool $active): PertanyaanKuesioner
    {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => 'Pertanyaan batch F '.(++$this->sequence),
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => $order,
            'status_aktif' => $active,
        ]);
    }

    private function createComponent(string $code): JenisKomponenDarah
    {
        return JenisKomponenDarah::create([
            'kode_komponen' => $code,
            'nama_komponen' => "Komponen {$code}",
        ]);
    }

    private function blood(string $abo, string $rhesus): GolonganDarah
    {
        return GolonganDarah::create(['abo' => $abo, 'rhesus' => $rhesus]);
    }

    private function threshold(JenisKomponenDarah $component, GolonganDarah $blood, int $minimum): AmbangPersediaan
    {
        return AmbangPersediaan::create([
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $blood->id_golongan_darah,
            'jumlah_minimum' => $minimum,
        ]);
    }

    private function configurationRows(): array
    {
        $tables = [
            'akun' => 'id_akun',
            'petugas' => 'id_petugas',
            'jadwal_pelayanan' => 'id_jadwal',
            'pertanyaan_kuesioner' => 'id_pertanyaan',
            'jenis_komponen_darah' => 'id_jenis_komponen',
            'golongan_darah' => 'id_golongan_darah',
            'ambang_persediaan' => 'id_ambang',
        ];

        return collect($tables)->mapWithKeys(fn (string $key, string $table): array => [
            $table => DB::table($table)->orderBy($key)->get()
                ->map(fn (object $row): array => (array) $row)->all(),
        ])->all();
    }
}

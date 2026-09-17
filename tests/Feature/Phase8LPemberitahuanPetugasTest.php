<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasPemberitahuanController;
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

class Phase8LPemberitahuanPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private ?Petugas $recorder = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 18:00:00', 'UTC'));
    }

    public function test_exact_routes_and_access_are_limited_to_active_petugas_with_profile(): void
    {
        $uri = 'petugas/pemanggilan/{ambang}/pendonor/{pendonor}/pemberitahuan';
        $routes = [
            'petugas.pemberitahuan.create' => [
                ['GET', 'HEAD'],
                PetugasPemberitahuanController::class.'@create',
            ],
            'petugas.pemberitahuan.store' => [
                ['POST'],
                PetugasPemberitahuanController::class.'@store',
            ],
        ];

        foreach ($routes as $name => [$methods, $action]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertSame($uri, $route->uri());
            $this->assertSame($methods, $route->methods());
            $this->assertSame($action, $route->getActionName());

            foreach (['web', 'auth', 'active', 'role:PETUGAS'] as $middleware) {
                $this->assertContains($middleware, $route->gatherMiddleware());
            }
        }

        $matching = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => $route->uri() === $uri);
        $this->assertCount(2, $matching);

        [$threshold, $candidate] = $this->createValidContext();
        $createUrl = route('petugas.pemberitahuan.create', [$threshold, $candidate]);
        $storeUrl = route('petugas.pemberitahuan.store', [$threshold, $candidate]);

        $this->get($createUrl)->assertRedirect(route('login'));
        $this->post($storeUrl, ['isi_pesan' => 'Pesan'])->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $account = $this->createAccount($role);
            $this->actingAs($account)->get($createUrl)->assertForbidden();
            $this->actingAs($account)->post($storeUrl, ['isi_pesan' => 'Pesan'])->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)->get($createUrl)->assertRedirect(route('login'));
        $this->actingAs($inactive->akun)
            ->post($storeUrl, ['isi_pesan' => 'Pesan'])
            ->assertRedirect(route('login'));

        $withoutProfile = $this->createAccount('PETUGAS');
        $this->actingAs($withoutProfile)
            ->get($createUrl)
            ->assertStatus(409)
            ->assertContent('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');
        $this->actingAs($withoutProfile)
            ->post($storeUrl, ['isi_pesan' => 'Pesan'])
            ->assertStatus(409)
            ->assertContent('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');

        $active = $this->createPetugas();
        $this->actingAs($active->akun)->get($createUrl)->assertOk();
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_valid_get_shows_only_allowed_context_and_is_read_only(): void
    {
        [$threshold, $candidate, $component, $bloodGroup] = $this->createValidContext();
        $petugas = $this->createPetugas(name: 'Petugas Form');
        $before = $this->businessSnapshots();

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemberitahuan.create', [$threshold, $candidate]))
            ->assertOk()
            ->assertSee('Buat Pemberitahuan')
            ->assertSee('Petugas Form')
            ->assertSee($component->kode_komponen)
            ->assertSee($component->nama_komponen)
            ->assertSee($bloodGroup->abo)
            ->assertSee($bloodGroup->rhesus)
            ->assertSee('Jumlah persediaan saat ini: 0')
            ->assertSee('Jumlah minimum: 1')
            ->assertSee($candidate->nomor_donor)
            ->assertSee($candidate->nama_lengkap)
            ->assertSee('name="isi_pesan"', false)
            ->assertSee('method="POST"', false)
            ->assertSee(route('petugas.pemberitahuan.store', [$threshold, $candidate]), false)
            ->assertSee(route('petugas.pemanggilan.index', [
                'id_ambang' => $threshold->id_ambang,
            ]), false)
            ->assertDontSee($candidate->nik)
            ->assertDontSee($candidate->alamat)
            ->assertDontSee($candidate->nomor_telepon)
            ->assertDontSee($candidate->akun->email)
            ->assertDontSee($candidate->tanggal_lahir->format('Y-m-d'))
            ->assertDontSee($candidate->tempat_lahir)
            ->assertDontSee($candidate->pekerjaan)
            ->assertDontSee($candidate->alamat_kantor)
            ->assertDontSee($candidate->akun->password_hash);

        $html = $response->getContent();
        $this->assertStringContainsString('name="_token"', $html);
        foreach (['id_pendonor', 'id_petugas_pengirim', 'id_ambang', 'waktu_dibuat', 'waktu_dibaca'] as $field) {
            $this->assertStringNotContainsString('name="'.$field.'"', $html);
        }
        $this->assertEquals($before, $this->businessSnapshots());
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_nonexistent_route_resources_return_404_without_insert(): void
    {
        [$threshold, $candidate] = $this->createValidContext();
        $petugas = $this->createPetugas();

        foreach ([
            route('petugas.pemberitahuan.create', [999999, $candidate]),
            route('petugas.pemberitahuan.create', [$threshold, 999999]),
        ] as $url) {
            $this->actingAs($petugas->akun)->get($url)->assertNotFound();
        }

        foreach ([
            route('petugas.pemberitahuan.store', [999999, $candidate]),
            route('petugas.pemberitahuan.store', [$threshold, 999999]),
        ] as $url) {
            $this->actingAs($petugas->akun)
                ->post($url, ['isi_pesan' => 'Tidak tersimpan'])
                ->assertNotFound();
        }

        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_stale_low_stock_context_is_rejected_on_get_and_post(): void
    {
        [$threshold, $candidate, $component, $bloodGroup] = $this->createValidContext(minimum: 0);
        $petugas = $this->createPetugas();
        $this->createStockUnit($component, $bloodGroup, 'TERSEDIA', '2026-09-16');
        $message = 'Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.';

        $this->actingAs($petugas->akun)
            ->get(route('petugas.pemberitahuan.create', [$threshold, $candidate]))
            ->assertStatus(409)
            ->assertContent($message);
        $this->actingAs($petugas->akun)
            ->post(route('petugas.pemberitahuan.store', [$threshold, $candidate]), [
                'isi_pesan' => 'Tidak tersimpan',
            ])
            ->assertStatus(409)
            ->assertContent($message);

        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_stale_candidates_are_rejected_on_get_and_post(): void
    {
        [$threshold, $valid, , $bloodGroup] = $this->createValidContext();
        $otherGroup = $this->createBloodGroup('O', 'NEGATIF');
        $wrongGroup = $this->createPendonor($otherGroup, 'Golongan Salah');
        $inactive = $this->createPendonor($bloodGroup, 'Akun Nonaktif', status: 'NONAKTIF');
        $wrongRole = $this->createPendonor($bloodGroup, 'Peran Salah', role: 'ADMIN');
        $historyBlocked = $this->createPendonor($bloodGroup, 'Interval Belum Terpenuhi');
        $this->createDonationFor($historyBlocked, '2026-08-01', 'BERHASIL');
        $petugas = $this->createPetugas();
        $message = 'Pendonor tidak lagi memenuhi kriteria pemanggilan untuk kondisi persediaan ini.';

        foreach ([$wrongGroup, $inactive, $wrongRole, $historyBlocked] as $candidate) {
            $this->actingAs($petugas->akun)
                ->get(route('petugas.pemberitahuan.create', [$threshold, $candidate]))
                ->assertStatus(409)
                ->assertContent($message);
            $this->actingAs($petugas->akun)
                ->post(route('petugas.pemberitahuan.store', [$threshold, $candidate]), [
                    'isi_pesan' => 'Tidak tersimpan',
                ])
                ->assertStatus(409)
                ->assertContent($message);
        }

        $this->actingAs($petugas->akun)
            ->get(route('petugas.pemberitahuan.create', [$threshold, $valid]))
            ->assertOk();
        $this->assertDatabaseCount('pemberitahuan', 0);
    }

    public function test_wib_expiry_status_and_zero_stock_semantics_are_used(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $bloodGroup = $this->createBloodGroup('AB', 'NEGATIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 1);
        $candidate = $this->createPendonor($bloodGroup, 'Kandidat Batas WIB');

        $this->createStockUnit($component, $bloodGroup, 'TERSEDIA', '2026-09-16');
        $this->createStockUnit($component, $bloodGroup, 'TERSEDIA', '2026-09-15');
        $this->createStockUnit($component, $bloodGroup, 'MENUNGGU_PELULUSAN', '2026-09-20');
        $this->createStockUnit($component, $bloodGroup, 'DITOLAK', '2026-09-20');
        $this->createStockUnit($component, $bloodGroup, 'DIDISTRIBUSIKAN', '2026-09-20');

        $this->assertSame('2026-09-15', now('UTC')->toDateString());
        $this->assertSame('2026-09-16', now('Asia/Jakarta')->toDateString());
        $this->actingAs($petugas->akun)
            ->get(route('petugas.pemberitahuan.create', [$threshold, $candidate]))
            ->assertOk()
            ->assertSee('Jumlah persediaan saat ini: 1');

        $zeroComponent = $this->createComponent('FFP', 'Fresh Frozen Plasma');
        $zeroThreshold = $this->createThreshold($zeroComponent, $bloodGroup, 0);
        $this->actingAs($petugas->akun)
            ->get(route('petugas.pemberitahuan.create', [$zeroThreshold, $candidate]))
            ->assertOk()
            ->assertSee('Jumlah persediaan saat ini: 0');
    }

    public function test_message_validation_rejects_missing_empty_whitespace_and_non_string_values(): void
    {
        [$threshold, $candidate] = $this->createValidContext();
        $petugas = $this->createPetugas();
        $url = route('petugas.pemberitahuan.store', [$threshold, $candidate]);

        foreach ([[], ['isi_pesan' => ''], ['isi_pesan' => "  \r\n  "], ['isi_pesan' => ['array']]] as $payload) {
            $this->actingAs($petugas->akun)
                ->post($url, $payload)
                ->assertSessionHasErrors('isi_pesan');
            $this->assertDatabaseCount('pemberitahuan', 0);
        }
    }

    public function test_successful_insert_uses_route_and_authenticated_authority_and_server_timestamps(): void
    {
        [$threshold, $candidate, , $bloodGroup] = $this->createValidContext();
        $otherCandidate = $this->createPendonor($bloodGroup, 'Target Hostile');
        $petugas = $this->createPetugas(name: 'Pengirim Benar');
        $otherPetugas = $this->createPetugas(name: 'Pengirim Hostile');

        $this->actingAs($petugas->akun)
            ->post(route('petugas.pemberitahuan.store', [$threshold, $candidate]), [
                'isi_pesan' => '  Silakan datang untuk donor.  ',
                'id_pendonor' => $otherCandidate->id_pendonor,
                'id_petugas_pengirim' => $otherPetugas->id_petugas,
                'id_ambang' => 999999,
                'waktu_dibuat' => '2030-01-01 00:00:00',
                'waktu_dibaca' => '2030-01-01 00:00:00',
            ])
            ->assertRedirect(route('petugas.pemanggilan.index', [
                'id_ambang' => $threshold->id_ambang,
            ]))
            ->assertSessionHas('success', 'Pemberitahuan berhasil dikirim kepada Pendonor.');

        $row = DB::table('pemberitahuan')->sole();
        $this->assertSame($candidate->id_pendonor, $row->id_pendonor);
        $this->assertSame($petugas->id_petugas, $row->id_petugas_pengirim);
        $this->assertSame('Silakan datang untuk donor.', $row->isi_pesan);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $row->waktu_dibuat);
        $this->assertNull($row->waktu_dibaca);
        $this->assertDatabaseCount('pemberitahuan', 1);
    }

    public function test_successful_store_mutates_only_notifications_and_repeated_posts_are_allowed(): void
    {
        [$threshold, $candidate] = $this->createValidContext();
        $petugas = $this->createPetugas();
        $before = $this->protectedBusinessSnapshots();
        $url = route('petugas.pemberitahuan.store', [$threshold, $candidate]);

        $this->actingAs($petugas->akun)->post($url, ['isi_pesan' => 'Pesan pertama'])->assertRedirect();
        $this->actingAs($petugas->akun)->post($url, ['isi_pesan' => 'Pesan kedua'])->assertRedirect();

        $this->assertEquals($before, $this->protectedBusinessSnapshots());
        $this->assertDatabaseCount('pemberitahuan', 2);
        $this->assertSame(
            ['Pesan pertama', 'Pesan kedua'],
            DB::table('pemberitahuan')->orderBy('id_pemberitahuan')->pluck('isi_pesan')->all()
        );
    }

    public function test_created_notification_is_visible_only_to_target_receiver_and_starts_unread(): void
    {
        [$threshold, $candidate, , $bloodGroup] = $this->createValidContext();
        $otherCandidate = $this->createPendonor($bloodGroup, 'Pendonor Lain');
        $petugas = $this->createPetugas();
        $message = 'PESAN-PHASE-8L-UNTUK-TARGET';

        $this->actingAs($petugas->akun)
            ->post(route('petugas.pemberitahuan.store', [$threshold, $candidate]), [
                'isi_pesan' => $message,
            ])
            ->assertRedirect();

        $notification = DB::table('pemberitahuan')->sole();
        $this->assertNull($notification->waktu_dibaca);
        $this->actingAs($candidate->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSee($message);
        $this->actingAs($otherCandidate->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertDontSee($message);
    }

    public function test_phase_8k_exposes_real_links_and_both_get_pages_remain_read_only(): void
    {
        [$threshold, $candidate] = $this->createValidContext();
        $petugas = $this->createPetugas();
        $before = $this->businessSnapshots();
        $link = route('petugas.pemberitahuan.create', [$threshold, $candidate]);

        $phase8k = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]))
            ->assertOk()
            ->assertSee('Buat Pemberitahuan')
            ->assertSee($link, false);
        $form = $this->actingAs($petugas->akun)->get($link)->assertOk();

        $this->assertSame(0, substr_count($phase8k->getContent(), '<form'));
        $this->assertSame(0, substr_count($phase8k->getContent(), '<button'));
        $this->assertSame(0, substr_count($phase8k->getContent(), 'type="checkbox"'));
        $this->assertSame(0, substr_count($phase8k->getContent(), 'method="POST"'));
        $this->assertEquals($before, $this->businessSnapshots());
        $this->assertStringNotContainsString('type="checkbox"', $form->getContent());

        foreach (['Bulk', 'Riwayat Pemberitahuan', 'Kirim Ulang', 'Edit', 'Hapus', 'SMS', 'WhatsApp', 'Email'] as $forbidden) {
            $form->assertDontSee($forbidden);
        }
    }

    private function createValidContext(int $minimum = 1): array
    {
        $component = $this->createComponent('PRC', 'Packed Red Cell');
        $bloodGroup = $this->createBloodGroup('A', 'POSITIF');
        $threshold = $this->createThreshold($component, $bloodGroup, $minimum);
        $candidate = $this->createPendonor($bloodGroup, 'Kandidat Valid');

        return [$threshold, $candidate, $component, $bloodGroup];
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8l-user{$number}@example.test",
            'password_hash' => "phase8l-secret-hash-{$number}",
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(string $status = 'AKTIF', ?string $name = null): Petugas
    {
        $account = $this->createAccount('PETUGAS', $status);
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => "P8L-{$number}",
            'nama_petugas' => $name ?? "Petugas {$number}",
        ]);
    }

    private function createPendonor(
        ?GolonganDarah $bloodGroup,
        string $name,
        string $jenisKelamin = 'LAKI_LAKI',
        string $status = 'AKTIF',
        string $role = 'PENDONOR'
    ): Pendonor {
        $account = $this->createAccount($role, $status);
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $bloodGroup?->id_golongan_darah,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => "DNR-L-{$number}",
            'nama_lengkap' => $name,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => '1995-03-04',
            'tempat_lahir' => "Tempat Rahasia {$number}",
            'alamat' => "Alamat Rahasia {$number}",
            'nomor_telepon' => '0817'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => "Pekerjaan Rahasia {$number}",
            'alamat_kantor' => "Kantor Rahasia {$number}",
        ]);
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
        return GolonganDarah::create(['abo' => $abo, 'rhesus' => $rhesus]);
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

    private function createDonationFor(Pendonor $pendonor, string $date, string $result): Penyumbangan
    {
        $schedule = JadwalPelayanan::create([
            'tanggal' => $date,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 20,
            'status_jadwal' => 'DITUTUP',
        ]);
        $booking = PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => $date.' 07:00:00',
            'kode_checkin' => null,
            'waktu_checkin' => $date.' 08:00:00',
            'status_pemesanan' => 'SELESAI',
        ]);
        $recorder = $this->getRecorder();
        $selection = SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $recorder->id_petugas,
            'waktu_seleksi' => $date.' 08:30:00',
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
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $recorder->id_petugas,
            'waktu_pengambilan' => $date.' 09:00:00',
            'volume_ml' => $result === 'BERHASIL' ? 350 : null,
            'hasil_penyumbangan' => $result,
            'alasan_gagal' => $result === 'GAGAL' ? 'Fixture gagal' : null,
        ]);
    }

    private function getRecorder(): Petugas
    {
        return $this->recorder ??= $this->createPetugas(name: 'Petugas Pencatat Fixture');
    }

    private function createStockUnit(
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        string $status,
        string $expiryDate
    ): UnitKomponenDarah {
        $source = $this->createPendonor($bloodGroup, 'Sumber Unit '.$this->sequence, status: 'NONAKTIF');
        $donation = $this->createDonationFor($source, '2026-01-01', 'BERHASIL');
        $recorder = $this->getRecorder();
        $number = ++$this->sequence;
        $released = in_array($status, ['TERSEDIA', 'DITOLAK', 'DIDISTRIBUSIKAN'], true);

        return UnitKomponenDarah::create([
            'nomor_unit' => "UNIT-P8L-{$number}",
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $recorder->id_petugas,
            'id_petugas_pelulus' => $released ? $recorder->id_petugas : null,
            'tanggal_pembuatan' => '2026-01-01',
            'tanggal_kedaluwarsa' => $expiryDate,
            'waktu_pelulusan' => $released ? '2026-01-02 10:00:00' : null,
            'status_unit' => $status,
            'catatan_pelulusan' => $released ? 'Lulus' : null,
            'waktu_distribusi' => $status === 'DIDISTRIBUSIKAN'
                ? '2026-09-10 10:00:00'
                : null,
        ]);
    }

    private function protectedBusinessSnapshots(): array
    {
        return $this->snapshots([
            'ambang_persediaan' => 'id_ambang',
            'unit_komponen_darah' => 'id_unit',
            'pendonor' => 'id_pendonor',
            'akun' => 'id_akun',
            'pemesanan_donor' => 'id_pemesanan',
            'seleksi_donor' => 'id_seleksi',
            'penyumbangan' => 'id_penyumbangan',
        ]);
    }

    private function businessSnapshots(): array
    {
        return $this->snapshots([
            'ambang_persediaan' => 'id_ambang',
            'unit_komponen_darah' => 'id_unit',
            'pendonor' => 'id_pendonor',
            'akun' => 'id_akun',
            'pemesanan_donor' => 'id_pemesanan',
            'seleksi_donor' => 'id_seleksi',
            'penyumbangan' => 'id_penyumbangan',
            'pemberitahuan' => 'id_pemberitahuan',
        ]);
    }

    private function snapshots(array $primaryKeys): array
    {
        return collect($primaryKeys)->mapWithKeys(
            fn (string $primaryKey, string $table): array => [
                $table => DB::table($table)
                    ->orderBy($primaryKey)
                    ->get()
                    ->map(fn (object $row): array => (array) $row)
                    ->all(),
            ]
        )->all();
    }
}

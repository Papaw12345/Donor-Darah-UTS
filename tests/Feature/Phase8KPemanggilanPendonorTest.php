<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasPemanggilanController;
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

class Phase8KPemanggilanPendonorTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private ?Petugas $recorder = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 18:00:00', 'UTC'));
    }

    public function test_exact_route_and_access_are_limited_to_active_petugas_with_profile(): void
    {
        $route = Route::getRoutes()->getByName('petugas.pemanggilan.index');

        $this->assertNotNull($route);
        $this->assertSame('petugas/pemanggilan', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(PetugasPemanggilanController::class.'@index', $route->getActionName());

        foreach (['web', 'auth', 'active', 'role:PETUGAS'] as $middleware) {
            $this->assertContains($middleware, $route->gatherMiddleware());
        }

        $matchingRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($candidate): bool => $candidate->uri() === 'petugas/pemanggilan');
        $this->assertCount(1, $matchingRoutes);

        $this->get(route('petugas.pemanggilan.index'))
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $this->actingAs($this->createAccount($role))
                ->get(route('petugas.pemanggilan.index'))
                ->assertForbidden();
        }

        $inactive = $this->createPetugas('NONAKTIF');
        $this->actingAs($inactive->akun)
            ->get(route('petugas.pemanggilan.index'))
            ->assertRedirect(route('login'));

        $withoutProfile = $this->createAccount('PETUGAS');
        $profileCount = Petugas::query()->count();
        $this->actingAs($withoutProfile)
            ->get(route('petugas.pemanggilan.index'))
            ->assertStatus(409)
            ->assertSee('Relasi akun PETUGAS dengan profil Petugas tidak konsisten.');
        $this->assertSame($profileCount, Petugas::query()->count());
        $this->assertNull($withoutProfile->petugas()->first());

        $active = $this->createPetugas(name: 'Petugas Login');
        $other = $this->createPetugas(name: 'Petugas Lain');
        $response = $this->actingAs($active->akun)
            ->get(route('petugas.pemanggilan.index', [
                'id_petugas' => $other->id_petugas,
            ]))
            ->assertOk()
            ->assertSee('Petugas Login')
            ->assertDontSee('Petugas Lain');

        $this->assertSame($active->id_petugas, $response->viewData('petugas')->id_petugas);
    }

    public function test_no_current_low_stock_condition_shows_the_controlled_state_without_candidates(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('A', 'POSITIF');
        $this->createThreshold($component, $bloodGroup, 0);
        $this->createStockUnit($component, $bloodGroup, '2026-09-16');
        $secret = $this->createPendonor($bloodGroup, 'Kandidat Tidak Boleh Tampil');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index'))
            ->assertOk()
            ->assertSee('Tidak ada kondisi persediaan rendah yang memerlukan pemanggilan Pendonor.')
            ->assertDontSee($secret->nama_lengkap)
            ->assertDontSee('Nomor Donor');

        $this->assertTrue($response->viewData('persediaanRendah')->isEmpty());
        $this->assertTrue($response->viewData('kandidatPendonor')->isEmpty());
    }

    public function test_current_low_stock_contexts_are_ordered_and_not_automatically_selected(): void
    {
        $petugas = $this->createPetugas();
        $tc = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $prc = $this->createComponent('PRC', 'Packed Red Cell');
        $oPositive = $this->createBloodGroup('O', 'POSITIF');
        $aNegative = $this->createBloodGroup('A', 'NEGATIF');

        $tcO = $this->createThreshold($tc, $oPositive, 0);
        $prcO = $this->createThreshold($prc, $oPositive, 0);
        $prcA = $this->createThreshold($prc, $aNegative, 0);
        $secret = $this->createPendonor($aNegative, 'Kandidat Belum Dipilih');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index'))
            ->assertOk()
            ->assertSee('Pilih kondisi persediaan rendah untuk melihat kandidat Pendonor.')
            ->assertDontSee($secret->nama_lengkap)
            ->assertDontSee('Nomor Donor');

        foreach ([$tcO, $prcO, $prcA] as $threshold) {
            $response->assertSee(
                route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]),
                false
            );
        }

        $ordered = $response->viewData('persediaanRendah')
            ->map(fn (object $row): string => implode('|', [
                $row->kode_komponen,
                $row->abo,
                $row->rhesus,
                $row->id_ambang,
            ]))
            ->all();

        $this->assertSame([
            "PRC|A|NEGATIF|{$prcA->id_ambang}",
            "PRC|O|POSITIF|{$prcO->id_ambang}",
            "TC|O|POSITIF|{$tcO->id_ambang}",
        ], $ordered);
        $this->assertNull($response->viewData('ambangTerpilih'));
        $this->assertTrue($response->viewData('kandidatPendonor')->isEmpty());
    }

    public function test_selected_context_uses_exact_blood_group_and_active_pendonor_account(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('PRC', 'Packed Red Cell');
        $aPositive = $this->createBloodGroup('A', 'POSITIF');
        $oPositive = $this->createBloodGroup('O', 'POSITIF');
        $threshold = $this->createThreshold($component, $aPositive, 1);

        $included = $this->createPendonor($aPositive, 'Aktif Exact');
        $differentGroup = $this->createPendonor($oPositive, 'Aktif Beda Golongan');
        $inactive = $this->createPendonor($aPositive, 'Nonaktif Exact', status: 'NONAKTIF');
        $wrongRole = $this->createPendonor($aPositive, 'Admin Dengan Profil', role: 'ADMIN');
        $withoutGroup = $this->createPendonor(null, 'Golongan Belum Terkonfirmasi');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]))
            ->assertOk()
            ->assertSee('Aktif Exact')
            ->assertDontSee($differentGroup->nama_lengkap)
            ->assertDontSee($inactive->nama_lengkap)
            ->assertDontSee($wrongRole->nama_lengkap)
            ->assertDontSee($withoutGroup->nama_lengkap)
            ->assertDontSee($included->nik)
            ->assertDontSee($included->alamat)
            ->assertDontSee($included->nomor_telepon)
            ->assertDontSee($included->akun->email);

        $candidate = $response->viewData('kandidatPendonor')->sole();
        $this->assertSame($included->nomor_donor, $candidate->nomor_donor);
        $this->assertSame('A', $candidate->abo);
        $this->assertSame('POSITIF', $candidate->rhesus);
        $this->assertNull($candidate->tanggal_donor_terakhir);
        $this->assertSame(0, $candidate->jumlah_donor_tahun_ini);
    }

    public function test_historical_eligibility_reuses_first_time_interval_and_annual_limits(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('B', 'NEGATIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 1);

        $firstTime = $this->createPendonor($bloodGroup, 'First Time');
        $atBoundary = $this->createPendonor($bloodGroup, 'Interval Terpenuhi');
        $beforeBoundary = $this->createPendonor($bloodGroup, 'Interval Belum Terpenuhi');
        $maleLimit = $this->createPendonor($bloodGroup, 'Laki Laki Limit');
        $femaleLimit = $this->createPendonor(
            $bloodGroup,
            'Perempuan Limit',
            jenisKelamin: 'PEREMPUAN'
        );

        $this->createDonationFor($atBoundary, '2026-07-16', 'BERHASIL');
        $this->createDonationFor($beforeBoundary, '2026-08-01', 'BERHASIL');
        for ($day = 1; $day <= 6; $day++) {
            $this->createDonationFor($maleLimit, sprintf('2026-01-%02d', $day), 'BERHASIL');
        }
        for ($day = 1; $day <= 4; $day++) {
            $this->createDonationFor($femaleLimit, sprintf('2026-02-%02d', $day), 'BERHASIL');
        }

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]))
            ->assertOk()
            ->assertSee($firstTime->nama_lengkap)
            ->assertSee($atBoundary->nama_lengkap)
            ->assertDontSee($beforeBoundary->nama_lengkap)
            ->assertDontSee($maleLimit->nama_lengkap)
            ->assertDontSee($femaleLimit->nama_lengkap);

        $candidateNames = $response->viewData('kandidatPendonor')->pluck('nama_lengkap')->all();
        $this->assertSame([$firstTime->nama_lengkap, $atBoundary->nama_lengkap], $candidateNames);
    }

    public function test_failed_and_future_history_do_not_block_or_replace_current_success_history(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('FFP', 'Fresh Frozen Plasma');
        $bloodGroup = $this->createBloodGroup('AB', 'POSITIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 1);

        $failedOnly = $this->createPendonor($bloodGroup, 'Gagal Saja');
        $oldSuccessFailed = $this->createPendonor($bloodGroup, 'Berhasil Lama Lalu Gagal');
        $futureSuccess = $this->createPendonor($bloodGroup, 'Berhasil Masa Depan');

        $this->createDonationFor($failedOnly, '2026-09-10', 'GAGAL');
        $this->createDonationFor($oldSuccessFailed, '2026-05-01', 'BERHASIL');
        $this->createDonationFor($oldSuccessFailed, '2026-09-10', 'GAGAL');
        $this->createDonationFor($futureSuccess, '2026-05-02', 'BERHASIL');
        $this->createDonationFor($futureSuccess, '2026-10-01', 'BERHASIL');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]))
            ->assertOk();

        $candidates = $response->viewData('kandidatPendonor')->keyBy('nama_lengkap');
        $this->assertCount(3, $candidates);
        $this->assertNull($candidates[$failedOnly->nama_lengkap]->tanggal_donor_terakhir);
        $this->assertSame(0, $candidates[$failedOnly->nama_lengkap]->jumlah_donor_tahun_ini);
        $this->assertSame(
            '2026-05-01',
            $candidates[$oldSuccessFailed->nama_lengkap]->tanggal_donor_terakhir->toDateString()
        );
        $this->assertSame(1, $candidates[$oldSuccessFailed->nama_lengkap]->jumlah_donor_tahun_ini);
        $this->assertSame(
            '2026-05-02',
            $candidates[$futureSuccess->nama_lengkap]->tanggal_donor_terakhir->toDateString()
        );
        $this->assertSame(1, $candidates[$futureSuccess->nama_lengkap]->jumlah_donor_tahun_ini);
        $response->assertSee('01-05-2026')->assertSee('02-05-2026')->assertDontSee('01-10-2026');
    }

    public function test_no_overflow_and_inventory_boundary_use_the_real_current_wib_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-29 18:00:00', 'UTC'));

        $petugas = $this->createPetugas();
        $component = $this->createComponent('TC', 'Thrombocyte Concentrate');
        $bloodGroup = $this->createBloodGroup('AB', 'NEGATIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 1);
        $candidate = $this->createPendonor($bloodGroup, 'Batas No Overflow');
        $this->createDonationFor($candidate, '2026-07-31', 'BERHASIL');
        $this->createStockUnit($component, $bloodGroup, '2026-09-30');
        $this->createStockUnit($component, $bloodGroup, '2026-09-29');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]))
            ->assertOk()
            ->assertSee('Tanggal acuan WIB: 2026-09-30')
            ->assertSee($candidate->nama_lengkap);

        $this->assertSame('2026-09-29', now('UTC')->toDateString());
        $this->assertSame('2026-09-30', now('Asia/Jakarta')->toDateString());
        $this->assertSame('2026-09-30', $response->viewData('tanggalAcuan'));
        $this->assertSame(1, (int) $response->viewData('ambangTerpilih')->jumlah_persediaan);
        $this->assertSame(
            '2026-07-31',
            $response->viewData('kandidatPendonor')->sole()->tanggal_donor_terakhir->toDateString()
        );
    }

    public function test_existing_threshold_that_is_no_longer_low_returns_controlled_http_200_state(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('WB', 'Whole Blood');
        $bloodGroup = $this->createBloodGroup('O', 'POSITIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 0);
        $this->createStockUnit($component, $bloodGroup, '2026-09-20');
        $secret = $this->createPendonor($bloodGroup, 'Kandidat State Lama');

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]))
            ->assertOk()
            ->assertSee('Kondisi persediaan yang dipilih tidak sedang berada pada atau di bawah ambang.')
            ->assertDontSee($secret->nama_lengkap)
            ->assertDontSee('Nomor Donor');

        $this->assertTrue($response->viewData('ambangTerpilihTidakRendah'));
        $this->assertNull($response->viewData('ambangTerpilih'));
        $this->assertTrue($response->viewData('kandidatPendonor')->isEmpty());
    }

    public function test_nonexistent_and_malformed_threshold_identifiers_return_controlled_404(): void
    {
        $petugas = $this->createPetugas();
        $bloodGroup = $this->createBloodGroup('A', 'NEGATIF');
        $secret = $this->createPendonor($bloodGroup, 'Data Pendonor Rahasia');

        foreach (['999999', '1abc'] as $idAmbang) {
            $this->actingAs($petugas->akun)
                ->get(route('petugas.pemanggilan.index', ['id_ambang' => $idAmbang]))
                ->assertNotFound()
                ->assertSee('Kondisi persediaan tidak ditemukan.')
                ->assertDontSee($secret->nama_lengkap);
        }
    }

    public function test_candidate_order_display_and_repeated_get_are_read_only_with_phase_8l_links(): void
    {
        $petugas = $this->createPetugas();
        $component = $this->createComponent('PRC', 'Packed Red Cell');
        $bloodGroup = $this->createBloodGroup('B', 'POSITIF');
        $threshold = $this->createThreshold($component, $bloodGroup, 1);
        $zeta = $this->createPendonor($bloodGroup, 'Zeta Pendonor');
        $alphaFirst = $this->createPendonor($bloodGroup, 'Alpha Pendonor');
        $alphaSecond = $this->createPendonor($bloodGroup, 'Alpha Pendonor');
        $this->createDonationFor($alphaFirst, '2026-05-01', 'BERHASIL');

        $before = $this->businessSnapshots();
        $url = route('petugas.pemanggilan.index', ['id_ambang' => $threshold->id_ambang]);
        $response = $this->actingAs($petugas->akun)
            ->get($url)
            ->assertOk()
            ->assertSee($zeta->nomor_donor)
            ->assertSee($alphaFirst->nomor_donor)
            ->assertSee($alphaSecond->nomor_donor)
            ->assertSee('01-05-2026')
            ->assertSee('Daftar ini berdasarkan eligibility historis dan bukan keputusan kelayakan medis akhir.')
            ->assertDontSee('Kirim Pemberitahuan')
            ->assertSee('Buat Pemberitahuan')
            ->assertDontSee('Pemberitahuan Petugas');
        $this->actingAs($petugas->akun)->get($url)->assertOk();

        $orderedDonorNumbers = $response->viewData('kandidatPendonor')->pluck('nomor_donor')->all();
        $this->assertSame([
            $alphaFirst->nomor_donor,
            $alphaSecond->nomor_donor,
            $zeta->nomor_donor,
        ], $orderedDonorNumbers);
        $this->assertSame(1, $response->viewData('kandidatPendonor')->first()->jumlah_donor_tahun_ini);
        $this->assertNull($response->viewData('kandidatPendonor')[1]->tanggal_donor_terakhir);
        $this->assertSame('B', $response->viewData('kandidatPendonor')->first()->abo);
        $this->assertSame('POSITIF', $response->viewData('kandidatPendonor')->first()->rhesus);
        foreach ([$alphaFirst, $alphaSecond, $zeta] as $candidate) {
            $response->assertSee(route('petugas.pemberitahuan.create', [
                'ambang' => $threshold->id_ambang,
                'pendonor' => $candidate->id_pendonor,
            ]), false);
        }
        $this->assertEquals($before, $this->businessSnapshots());

        $html = $response->getContent();
        $this->assertSame(0, substr_count($html, '<form'));
        $this->assertSame(0, substr_count($html, '<button'));
        $this->assertSame(0, substr_count($html, 'type="checkbox"'));
        $this->assertSame(0, substr_count($html, 'method="POST"'));
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8k-user{$number}@example.test",
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
            'nomor_petugas' => "P8K-{$number}",
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
            'nomor_donor' => "DNR-K-{$number}",
            'nama_lengkap' => $name,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => "Alamat rahasia {$number}",
            'nomor_telepon' => '0817'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => 'Pekerjaan rahasia',
            'alamat_kantor' => 'Kantor rahasia',
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

    private function createDonationFor(
        Pendonor $pendonor,
        string $date,
        string $result
    ): Penyumbangan {
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
        if ($this->recorder !== null) {
            return $this->recorder;
        }

        return $this->recorder = $this->createPetugas(name: 'Petugas Pencatat Fixture');
    }

    private function createStockUnit(
        JenisKomponenDarah $component,
        GolonganDarah $bloodGroup,
        string $expiryDate
    ): UnitKomponenDarah {
        $source = $this->createPendonor(
            $bloodGroup,
            'Sumber Unit '.$this->sequence,
            status: 'NONAKTIF'
        );
        $donation = $this->createDonationFor($source, '2026-01-01', 'BERHASIL');
        $number = ++$this->sequence;
        $recorder = $this->getRecorder();

        return UnitKomponenDarah::create([
            'nomor_unit' => "UNIT-P8K-{$number}",
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'id_petugas_pencatat' => $recorder->id_petugas,
            'id_petugas_pelulus' => $recorder->id_petugas,
            'tanggal_pembuatan' => '2026-01-01',
            'tanggal_kedaluwarsa' => $expiryDate,
            'waktu_pelulusan' => '2026-01-02 10:00:00',
            'status_unit' => 'TERSEDIA',
            'catatan_pelulusan' => 'Lulus',
            'waktu_distribusi' => null,
        ]);
    }

    private function businessSnapshots(): array
    {
        $primaryKeys = [
            'unit_komponen_darah' => 'id_unit',
            'ambang_persediaan' => 'id_ambang',
            'pendonor' => 'id_pendonor',
            'akun' => 'id_akun',
            'pemesanan_donor' => 'id_pemesanan',
            'seleksi_donor' => 'id_seleksi',
            'penyumbangan' => 'id_penyumbangan',
            'pemberitahuan' => 'id_pemberitahuan',
        ];

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

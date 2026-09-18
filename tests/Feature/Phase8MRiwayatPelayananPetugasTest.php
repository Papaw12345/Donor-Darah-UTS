<?php

namespace Tests\Feature;

use App\Http\Controllers\PetugasDashboardController;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8MRiwayatPelayananPetugasTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_route_and_access_are_limited_to_active_petugas_with_profile(): void
    {
        $route = Route::getRoutes()->getByName(
            'petugas.riwayat-pelayanan.index'
        );

        $this->assertNotNull($route);
        $this->assertSame(
            'petugas/riwayat-pelayanan',
            $route->uri()
        );
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(
            PetugasDashboardController::class.'@riwayatPelayanan',
            $route->getActionName()
        );
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('active', $route->gatherMiddleware());
        $this->assertContains(
            'role:PETUGAS',
            $route->gatherMiddleware()
        );

        $this->get(route('petugas.riwayat-pelayanan.index'))
            ->assertRedirect(route('login'));

        foreach (['PENDONOR', 'ADMIN'] as $role) {
            $this->actingAs($this->createAccount($role))
                ->get(route('petugas.riwayat-pelayanan.index'))
                ->assertForbidden();
        }

        $petugasNonaktif = $this->createPetugas('NONAKTIF');

        $this->actingAs($petugasNonaktif->akun)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertRedirect(route('login'));

        $akunTanpaProfil = $this->createAccount('PETUGAS');

        $this->actingAs($akunTanpaProfil)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertStatus(409)
            ->assertSee(
                'Relasi akun PETUGAS dengan profil Petugas tidak konsisten.'
            );

        $petugasAktif = $this->createPetugas();

        $this->actingAs($petugasAktif->akun)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertOk()
            ->assertSee('Riwayat Pelayanan Donor');
    }

    public function test_history_contains_final_selection_and_actual_donation_outcomes(): void
    {
        $petugas = $this->createPetugas();

        $ditunda = $this->createSelection(
            $petugas,
            'DITUNDA',
            '2026-09-15 09:00:00',
            'SELESAI'
        );

        $ditolak = $this->createSelection(
            $petugas,
            'DITOLAK',
            '2026-09-15 10:00:00',
            'SELESAI'
        );

        $layakAktif = $this->createSelection(
            $petugas,
            'LAYAK',
            '2026-09-15 11:00:00',
            'CHECK_IN'
        );

        $gagal = $this->createSelection(
            $petugas,
            'LAYAK',
            '2026-09-15 12:00:00',
            'SELESAI'
        );

        $penyumbanganGagal = $this->createDonation(
            $petugas,
            $gagal,
            'GAGAL',
            '2026-09-15 12:30:00',
            null
        );

        $berhasil = $this->createSelection(
            $petugas,
            'LAYAK',
            '2026-09-15 13:00:00',
            'SELESAI'
        );

        $penyumbanganBerhasil = $this->createDonation(
            $petugas,
            $berhasil,
            'BERHASIL',
            '2026-09-15 13:30:00',
            350
        );

        $this->createUnit($petugas, $penyumbanganBerhasil);
        $this->createUnit($petugas, $penyumbanganBerhasil);

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertOk()
            ->assertSee('Ditunda')
            ->assertSee('Ditolak')
            ->assertSee('Gagal')
            ->assertSee('Berhasil')
            ->assertSee('350 mL')
            ->assertSee(
                route(
                    'petugas.unit-komponen.show',
                    ['penyumbangan' => $penyumbanganBerhasil->id_penyumbangan]
                ),
                false
            )
            ->assertSee(
                route(
                    'petugas.penyumbangan.show',
                    ['seleksi' => $gagal->id_seleksi]
                ),
                false
            )
            ->assertSee(
                route(
                    'petugas.seleksi.show',
                    ['pemesanan' => $ditunda->id_pemesanan]
                ),
                false
            )
            ->assertSee(
                route(
                    'petugas.seleksi.show',
                    ['pemesanan' => $ditolak->id_pemesanan]
                ),
                false
            );

        $riwayat = $response->viewData('riwayatPelayanan');

        $this->assertSame(4, $riwayat->count());

        $this->assertSame(
            [
                $berhasil->id_seleksi,
                $gagal->id_seleksi,
                $ditolak->id_seleksi,
                $ditunda->id_seleksi,
            ],
            $riwayat->pluck('id_seleksi')->all()
        );

        $this->assertFalse(
            $riwayat->contains(
                'id_seleksi',
                $layakAktif->id_seleksi
            )
        );

        $rowBerhasil = $riwayat->firstWhere(
            'id_seleksi',
            $berhasil->id_seleksi
        );

        $this->assertSame(
            'BERHASIL',
            $rowBerhasil->hasil_pelayanan
        );
        $this->assertSame(
            2,
            (int) $rowBerhasil->jumlah_unit
        );

        $rowGagal = $riwayat->firstWhere(
            'id_seleksi',
            $gagal->id_seleksi
        );

        $this->assertSame(
            $penyumbanganGagal->id_penyumbangan,
            $rowGagal->id_penyumbangan
        );
        $this->assertSame(
            'GAGAL',
            $rowGagal->hasil_pelayanan
        );
    }

    public function test_empty_state_and_get_are_read_only(): void
    {
        $petugas = $this->createPetugas();

        $this->actingAs($petugas->akun)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertOk()
            ->assertSee('Belum ada riwayat pelayanan donor.');

        $selection = $this->createSelection(
            $petugas,
            'DITUNDA',
            '2026-09-15 09:00:00',
            'SELESAI'
        );

        $before = collect([
            'pemesanan_donor',
            'seleksi_donor',
            'penyumbangan',
            'unit_komponen_darah',
        ])->mapWithKeys(fn (string $table): array => [
            $table => $this->getConnection()
                ->table($table)
                ->count(),
        ]);

        $selectionBefore = $selection->getAttributes();

        $response = $this->actingAs($petugas->akun)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertOk()
            ->assertSee('Riwayat', false)
            ->assertSee(
                route('petugas.riwayat-pelayanan.index'),
                false
            );

        $this->actingAs($petugas->akun)
            ->get(route('petugas.riwayat-pelayanan.index'))
            ->assertOk();

        $this->assertEquals(
            $selectionBefore,
            $selection->fresh()->getAttributes()
        );

        foreach ($before as $table => $count) {
            $this->assertSame(
                $count,
                $this->getConnection()->table($table)->count()
            );
        }

        $response
            ->assertDontSee('Tambah Riwayat')
            ->assertDontSee('Hapus Riwayat')
            ->assertDontSee('Edit Riwayat');
    }

    private function createAccount(
        string $role = 'PENDONOR',
        string $status = 'AKTIF'
    ): Akun {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase8m-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPetugas(
        string $status = 'AKTIF'
    ): Petugas {
        $akun = $this->createAccount('PETUGAS', $status);
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => "P8M-{$number}",
            'nama_petugas' => "Petugas {$number}",
        ]);
    }

    private function createPendonor(): Pendonor
    {
        $akun = $this->createAccount();
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $akun->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad(
                (string) $number,
                16,
                '0',
                STR_PAD_LEFT
            ),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Surabaya',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0816'.str_pad(
                (string) $number,
                8,
                '0',
                STR_PAD_LEFT
            ),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createBooking(
        Pendonor $pendonor,
        string $status
    ): PemesananDonor {
        $schedule = JadwalPelayanan::create([
            'tanggal' => '2026-09-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '16:00',
            'kapasitas' => 100,
            'status_jadwal' => 'DIBUKA',
        ]);

        return PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $schedule->id_jadwal,
            'waktu_pemesanan' => '2026-09-15 07:00:00',
            'kode_checkin' => 'UDD-'.str_pad(
                strtoupper(dechex(++$this->sequence)),
                12,
                '0',
                STR_PAD_LEFT
            ),
            'waktu_checkin' => '2026-09-15 08:00:00',
            'status_pemesanan' => $status,
        ]);
    }

    private function createSelection(
        Petugas $petugas,
        string $decision,
        string $selectionTime,
        string $bookingStatus
    ): SeleksiDonor {
        $pendonor = $this->createPendonor();
        $booking = $this->createBooking(
            $pendonor,
            $bookingStatus
        );

        return SeleksiDonor::create([
            'id_pemesanan' => $booking->id_pemesanan,
            'id_petugas' => $petugas->id_petugas,
            'waktu_seleksi' => $selectionTime,
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

    private function createDonation(
        Petugas $petugas,
        SeleksiDonor $selection,
        string $result,
        string $time,
        ?int $volume
    ): Penyumbangan {
        return Penyumbangan::create([
            'id_seleksi' => $selection->id_seleksi,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'waktu_pengambilan' => $time,
            'volume_ml' => $volume,
            'hasil_penyumbangan' => $result,
            'alasan_gagal' => $result === 'GAGAL'
                ? 'Simulasi gagal'
                : null,
        ]);
    }

    private function createUnit(
        Petugas $petugas,
        Penyumbangan $donation
    ): UnitKomponenDarah {
        $component = JenisKomponenDarah::firstOrCreate(
            ['kode_komponen' => 'PRC'],
            ['nama_komponen' => 'Packed Red Cell']
        );

        $blood = GolonganDarah::firstOrCreate([
            'abo' => 'O',
            'rhesus' => 'POSITIF',
        ]);

        $number = ++$this->sequence;

        return UnitKomponenDarah::create([
            'nomor_unit' => "UNIT-P8M-{$number}",
            'id_penyumbangan' => $donation->id_penyumbangan,
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $blood->id_golongan_darah,
            'id_petugas_pencatat' => $petugas->id_petugas,
            'id_petugas_pelulus' => null,
            'tanggal_pembuatan' => '2026-09-15',
            'tanggal_kedaluwarsa' => '2026-09-25',
            'waktu_pelulusan' => null,
            'status_unit' => 'MENUNGGU_PELULUSAN',
            'catatan_pelulusan' => null,
            'waktu_distribusi' => null,
        ]);
    }
}
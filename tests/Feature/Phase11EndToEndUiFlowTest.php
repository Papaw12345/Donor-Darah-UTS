<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JenisKomponenDarah;
use App\Models\Pemberitahuan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\Penyumbangan;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use App\Models\SeleksiDonor;
use App\Models\UnitKomponenDarah;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class Phase11EndToEndUiFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'phase11-password';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 03:00:00', 'UTC'));
    }

    public function test_phase_11_ui_contract_supports_the_complete_cross_role_workflow(): void
    {
        $bloodGroup = GolonganDarah::create([
            'abo' => 'A',
            'rhesus' => 'POSITIF',
        ]);
        $component = JenisKomponenDarah::create([
            'kode_komponen' => 'WB',
            'nama_komponen' => 'Whole Blood',
        ]);
        $admin = $this->createAccount('admin.phase11@example.test', 'ADMIN');
        $candidate = $this->createCallingCandidate($bloodGroup);

        $this->assertGuestEntryUiContracts();

        $registration = $this->registrationPayload();
        $this->post(route('register.store'), $registration)
            ->assertRedirect(route('login'));

        $mainAccount = Akun::query()->where('email', $registration['email'])->sole();
        $mainDonor = Pendonor::query()->where('id_akun', $mainAccount->id_akun)->sole();
        $this->assertSame('PENDONOR', $mainAccount->peran);
        $this->assertSame('AKTIF', $mainAccount->status_akun);
        $this->assertNull($mainDonor->id_golongan_darah);

        $this->post(route('login.authenticate'), [
            'email' => $mainAccount->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('pendonor.home'));
        $this->assertAuthenticatedAs($mainAccount);
        $this->assertPendonorNavigation($this->get(route('pendonor.home'))->assertOk());
        $this->get(route('admin.home'))->assertForbidden();
        $this->get(route('petugas.home'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.authenticate'), [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($admin);
        $this->assertAdminNavigation($this->get(route('admin.home'))->assertOk());
        $this->get(route('pendonor.home'))->assertForbidden();
        $this->get(route('petugas.home'))->assertForbidden();

        $petugasCreate = $this->get(route('admin.petugas.create'))->assertOk();
        $this->assertBusinessForm(
            $petugasCreate,
            route('admin.petugas.store'),
            ['email', 'password', 'password_confirmation', 'nomor_petugas', 'nama_petugas']
        );
        $this->post(route('admin.petugas.store'), [
            'email' => 'petugas.phase11@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'nomor_petugas' => 'P11-001',
            'nama_petugas' => 'Petugas Phase 11',
        ])->assertRedirect(route('admin.petugas.index'));
        $petugasAccount = Akun::query()->where('email', 'petugas.phase11@example.test')->sole();
        $petugas = Petugas::query()->where('id_akun', $petugasAccount->id_akun)->sole();
        $this->assertSame('PETUGAS', $petugasAccount->peran);
        $this->assertSame('AKTIF', $petugasAccount->status_akun);

        $scheduleCreate = $this->get(route('admin.jadwal.create'))->assertOk();
        $this->assertBusinessForm(
            $scheduleCreate,
            route('admin.jadwal.store'),
            ['tanggal', 'jam_mulai', 'jam_selesai', 'kapasitas', 'status_jadwal']
        );
        $this->post(route('admin.jadwal.store'), [
            'tanggal' => '2026-10-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
            'kapasitas' => 5,
            'status_jadwal' => 'DIBUKA',
        ])->assertRedirect(route('admin.jadwal.index'));
        $schedule = JadwalPelayanan::query()->sole();

        $questionCreate = $this->get(route('admin.pertanyaan.create'))->assertOk();
        $this->assertBusinessForm(
            $questionCreate,
            route('admin.pertanyaan.store'),
            ['teks_pertanyaan', 'kategori', 'jenis_jawaban', 'urutan', 'status_aktif']
        );
        $this->post(route('admin.pertanyaan.store'), [
            'teks_pertanyaan' => 'Apakah Anda sedang merasa sehat?',
            'kategori' => 'Kesehatan umum',
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ])->assertRedirect(route('admin.pertanyaan.index'));
        $question = PertanyaanKuesioner::query()->sole();

        $thresholdCreate = $this->get(route('admin.ambang.create'))->assertOk();
        $this->assertBusinessForm(
            $thresholdCreate,
            route('admin.ambang.store'),
            ['id_jenis_komponen', 'id_golongan_darah', 'jumlah_minimum']
        );
        $this->post(route('admin.ambang.store'), [
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'jumlah_minimum' => 1,
        ])->assertRedirect(route('admin.ambang.index'));
        $threshold = AmbangPersediaan::query()->sole();

        $this->get(route('admin.petugas.index'))->assertOk()->assertSee($petugas->nama_petugas);
        $this->get(route('admin.jadwal.index'))->assertOk()->assertSee('DIBUKA');
        $this->get(route('admin.pertanyaan.index'))->assertOk()->assertSee($question->teks_pertanyaan);
        $this->get(route('admin.ambang.index'))->assertOk()->assertSee('Whole Blood');
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.authenticate'), [
            'email' => $mainAccount->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('pendonor.home'));

        $this->assertPendonorNavigation($this->get(route('pendonor.home'))->assertOk());
        $this->get(route('pendonor.profil.show'))
            ->assertOk()
            ->assertSee('action="'.route('pendonor.profil.update').'"', false)
            ->assertSee('name="nama_lengkap"', false);
        $schedulePage = $this->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertViewHas('jadwal', fn ($rows): bool => $rows->contains('id_jadwal', $schedule->id_jadwal));
        $this->assertBusinessForm(
            $schedulePage,
            route('pendonor.pemesanan.store', $schedule),
            []
        );

        $this->post(route('pendonor.pemesanan.store', $schedule))
            ->assertRedirect(route('pendonor.pemesanan.index'));
        $booking = PemesananDonor::query()
            ->where('id_pendonor', $mainDonor->id_pendonor)
            ->sole();
        $this->assertSame('TERJADWAL', $booking->status_pemesanan);
        $this->assertNull($booking->kode_checkin);
        $this->assertNull($booking->waktu_checkin);

        $this->get(route('pendonor.pemesanan.index'))
            ->assertOk()
            ->assertSee(route('pendonor.kuesioner.show', $booking), false)
            ->assertSee(route('pendonor.kode-checkin.show', $booking), false);
        $questionnairePage = $this->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan);
        $this->assertBusinessForm(
            $questionnairePage,
            route('pendonor.kuesioner.store', $booking),
            ["answers[{$question->id_pertanyaan}]"]
        );
        $this->post(route('pendonor.kuesioner.store', $booking), [
            'answers' => [$question->id_pertanyaan => 'YA'],
        ])->assertRedirect(route('pendonor.kuesioner.show', $booking));
        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_pertanyaan' => $question->id_pertanyaan,
            'jawaban' => 'YA',
        ]);

        $codePage = $this->get(route('pendonor.kode-checkin.show', $booking))->assertOk();
        $this->assertBusinessForm(
            $codePage,
            route('pendonor.kode-checkin.generate', $booking),
            []
        );
        $this->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));
        $booking->refresh();
        $this->assertMatchesRegularExpression('/\AUDD-[0-9A-F]{12}\z/', $booking->kode_checkin);
        $this->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee($booking->kode_checkin)
            ->assertDontSee('action="'.route('pendonor.kode-checkin.generate', $booking).'"', false);
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.authenticate'), [
            'email' => $petugasAccount->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('petugas.home'));
        $this->assertAuthenticatedAs($petugasAccount);
        $this->assertPetugasNavigation($this->get(route('petugas.home'))->assertOk());
        $this->get(route('admin.home'))->assertForbidden();
        $this->get(route('pendonor.home'))->assertForbidden();

        $checkinPage = $this->get(route('petugas.check-in.index', [
            'kode_checkin' => $booking->kode_checkin,
        ]))->assertOk()->assertSee($mainDonor->nama_lengkap);
        $this->assertBusinessForm(
            $checkinPage,
            route('petugas.check-in.store'),
            ['kode_checkin']
        );
        $this->post(route('petugas.check-in.store'), [
            'kode_checkin' => $booking->kode_checkin,
        ])->assertRedirect(route('petugas.check-in.index', [
            'kode_checkin' => $booking->kode_checkin,
        ]));
        $booking->refresh();
        $firstCheckinTime = $booking->getRawOriginal('waktu_checkin');
        $this->assertSame('CHECK_IN', $booking->status_pemesanan);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $firstCheckinTime);

        $this->post(route('petugas.check-in.store'), [
            'kode_checkin' => $booking->kode_checkin,
        ])->assertRedirect(route('petugas.check-in.index', [
            'kode_checkin' => $booking->kode_checkin,
        ]));
        $this->assertSame($firstCheckinTime, $booking->fresh()->getRawOriginal('waktu_checkin'));

        $this->get(route('petugas.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan)
            ->assertSee('YA')
            ->assertSee(route('petugas.seleksi.show', $booking), false);
        $selectionPage = $this->get(route('petugas.seleksi.show', $booking))->assertOk();
        $this->assertBusinessForm(
            $selectionPage,
            route('petugas.seleksi.store', $booking),
            ['berat_badan', 'keputusan_seleksi', 'id_golongan_darah']
        );
        $selectionPayload = [
            'berat_badan' => 60,
            'tekanan_sistolik' => 120,
            'tekanan_diastolik' => 80,
            'denyut_nadi' => 72,
            'suhu_tubuh' => 36.5,
            'kadar_hb' => 13.5,
            'hasil_pemeriksaan_kesehatan' => 'Tidak ada kontraindikasi.',
            'keputusan_seleksi' => 'LAYAK',
            'alasan_keputusan' => null,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
        ];
        $this->post(route('petugas.seleksi.store', $booking), $selectionPayload)
            ->assertRedirect(route('petugas.seleksi.show', $booking));
        $selection = SeleksiDonor::query()->where('id_pemesanan', $booking->id_pemesanan)->sole();
        $this->assertSame('LAYAK', $selection->keputusan_seleksi);
        $this->assertSame('CHECK_IN', $booking->fresh()->status_pemesanan);
        $this->assertSame($bloodGroup->id_golongan_darah, $mainDonor->fresh()->id_golongan_darah);
        $this->post(route('petugas.seleksi.store', $booking), array_merge($selectionPayload, [
            'keputusan_seleksi' => 'DITOLAK',
        ]))->assertStatus(409);
        $this->assertSame('LAYAK', $selection->fresh()->keputusan_seleksi);
        $this->assertDatabaseCount('seleksi_donor', 1);

        $donationPage = $this->get(route('petugas.penyumbangan.show', $selection))->assertOk();
        $this->assertBusinessForm(
            $donationPage,
            route('petugas.penyumbangan.store', $selection),
            ['waktu_pengambilan', 'volume_ml', 'hasil_penyumbangan', 'alasan_gagal']
        );
        $donationPayload = [
            'waktu_pengambilan' => '2026-10-15T10:30',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ];
        $this->post(route('petugas.penyumbangan.store', $selection), $donationPayload)
            ->assertRedirect(route('petugas.penyumbangan.show', $selection));
        $donation = Penyumbangan::query()->where('id_seleksi', $selection->id_seleksi)->sole();
        $this->assertSame('BERHASIL', $donation->hasil_penyumbangan);
        $this->assertSame(350, $donation->volume_ml);
        $this->assertSame('SELESAI', $booking->fresh()->status_pemesanan);
        $this->post(route('petugas.penyumbangan.store', $selection), array_merge($donationPayload, [
            'hasil_penyumbangan' => 'GAGAL',
            'volume_ml' => null,
            'alasan_gagal' => 'Upaya kedua',
        ]))->assertStatus(409);
        $this->assertSame('BERHASIL', $donation->fresh()->hasil_penyumbangan);
        $this->assertDatabaseCount('penyumbangan', 1);

        $unitPage = $this->get(route('petugas.unit-komponen.show', $donation))->assertOk();
        $this->assertBusinessForm(
            $unitPage,
            route('petugas.unit-komponen.store', $donation),
            ['nomor_unit', 'id_jenis_komponen', 'id_golongan_darah', 'tanggal_pembuatan', 'tanggal_kedaluwarsa']
        );
        $this->post(route('petugas.unit-komponen.store', $donation), [
            'nomor_unit' => 'UNIT-P11-0001',
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'tanggal_pembuatan' => '2026-10-15',
            'tanggal_kedaluwarsa' => '2026-10-20',
        ])->assertRedirect(route('petugas.unit-komponen.show', $donation));
        $unit = UnitKomponenDarah::query()->where('nomor_unit', 'UNIT-P11-0001')->sole();
        $this->assertSame('MENUNGGU_PELULUSAN', $unit->status_unit);
        $this->assertNull($unit->id_petugas_pelulus);
        $this->assertNull($unit->waktu_pelulusan);
        $this->assertNull($unit->waktu_distribusi);

        $this->get(route('petugas.pelulusan.index'))
            ->assertOk()
            ->assertSee($unit->nomor_unit)
            ->assertSee(route('petugas.pelulusan.show', $unit), false);
        $releasePage = $this->get(route('petugas.pelulusan.show', $unit))->assertOk();
        $this->assertBusinessForm(
            $releasePage,
            route('petugas.pelulusan.store', $unit),
            ['hasil_pelulusan', 'catatan_pelulusan']
        );
        $this->post(route('petugas.pelulusan.store', $unit), [
            'hasil_pelulusan' => 'TERSEDIA',
            'catatan_pelulusan' => 'Lulus pemeriksaan mutu.',
        ])->assertRedirect(route('petugas.pelulusan.show', $unit));
        $unit->refresh();
        $firstReleaseTime = $unit->getRawOriginal('waktu_pelulusan');
        $this->assertSame('TERSEDIA', $unit->status_unit);
        $this->assertSame($petugas->id_petugas, $unit->id_petugas_pelulus);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $firstReleaseTime);
        $this->post(route('petugas.pelulusan.store', $unit), [
            'hasil_pelulusan' => 'DITOLAK',
            'catatan_pelulusan' => 'Upaya kedua',
        ])->assertStatus(409);
        $this->assertSame('TERSEDIA', $unit->fresh()->status_unit);
        $this->assertSame($firstReleaseTime, $unit->fresh()->getRawOriginal('waktu_pelulusan'));

        $this->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertViewHas('persediaan', function ($rows) use ($component, $bloodGroup): bool {
                $stock = $rows->first();

                return $rows->count() === 1
                    && (int) $stock->id_jenis_komponen === $component->id_jenis_komponen
                    && (int) $stock->id_golongan_darah === $bloodGroup->id_golongan_darah
                    && (int) $stock->jumlah_persediaan === 1;
            });
        $this->get(route('petugas.persediaan-rendah.index'))
            ->assertOk()
            ->assertViewHas('persediaanRendah', function ($rows) use ($threshold): bool {
                $lowStock = $rows->firstWhere('id_ambang', $threshold->id_ambang);

                return $lowStock !== null
                    && (int) $lowStock->jumlah_persediaan === 1
                    && (int) $lowStock->jumlah_minimum === 1;
            });

        $callingPage = $this->get(route('petugas.pemanggilan.index', [
            'id_ambang' => $threshold->id_ambang,
        ]))->assertOk()
            ->assertViewHas(
                'kandidatPendonor',
                fn ($rows): bool => $rows->contains(
                    fn ($row): bool => (int) $row->id_pendonor === $candidate->id_pendonor
                )
            )
            ->assertSee($candidate->nama_lengkap)
            ->assertDontSee($mainDonor->nama_lengkap)
            ->assertSee(route('petugas.pemberitahuan.create', [$threshold, $candidate]), false);
        $this->assertPetugasNavigation($callingPage);

        $message = 'Persediaan A positif sedang rendah. Silakan donor kembali.';
        $notificationPage = $this->get(route('petugas.pemberitahuan.create', [$threshold, $candidate]))
            ->assertOk();
        $this->assertBusinessForm(
            $notificationPage,
            route('petugas.pemberitahuan.store', [$threshold, $candidate]),
            ['isi_pesan']
        );
        $this->post(route('petugas.pemberitahuan.store', [$threshold, $candidate]), [
            'isi_pesan' => $message,
        ])->assertRedirect(route('petugas.pemanggilan.index', [
            'id_ambang' => $threshold->id_ambang,
        ]));
        $notification = Pemberitahuan::query()->sole();
        $this->assertSame($candidate->id_pendonor, $notification->id_pendonor);
        $this->assertSame($petugas->id_petugas, $notification->id_petugas_pengirim);

        $this->get(route('petugas.distribusi.index'))
            ->assertOk()
            ->assertSee($unit->nomor_unit)
            ->assertSee(route('petugas.distribusi.show', $unit), false);
        $distributionPage = $this->get(route('petugas.distribusi.show', $unit))->assertOk();
        $this->assertBusinessForm(
            $distributionPage,
            route('petugas.distribusi.store', $unit),
            []
        );
        $this->post(route('petugas.distribusi.store', $unit))
            ->assertRedirect(route('petugas.distribusi.show', $unit));
        $unit->refresh();
        $firstDistributionTime = $unit->getRawOriginal('waktu_distribusi');
        $this->assertSame('DIDISTRIBUSIKAN', $unit->status_unit);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $firstDistributionTime);
        $this->post(route('petugas.distribusi.store', $unit))->assertStatus(409);
        $this->assertSame($firstDistributionTime, $unit->fresh()->getRawOriginal('waktu_distribusi'));
        $this->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertViewHas('persediaan', fn ($rows): bool => $rows->isEmpty());
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.authenticate'), [
            'email' => $mainAccount->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('pendonor.home'));
        $this->get(route('pendonor.riwayat.index'))
            ->assertOk()
            ->assertSee('BERHASIL')
            ->assertSee('350');
        $this->get(route('pendonor.donor-berikutnya.index'))
            ->assertOk()
            ->assertSee('15-12-2026')
            ->assertSee('bukan keputusan kelayakan medis akhir');
        $this->get(route('pendonor.pemberitahuan.show', $notification))->assertNotFound();
        $this->get(route('pendonor.kuesioner.show', [
            'pemesanan' => $booking->id_pemesanan + 999999,
        ]))->assertNotFound();
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.authenticate'), [
            'email' => $candidate->akun->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('pendonor.home'));
        $this->assertPendonorNavigation(
            $this->get(route('pendonor.pemberitahuan.index'))
                ->assertOk()
                ->assertSee($message)
        );
        $this->get(route('pendonor.kuesioner.show', $booking))->assertNotFound();
        $this->get(route('pendonor.kode-checkin.show', $booking))->assertNotFound();

        $notificationDetail = $this->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertOk()
            ->assertSee($message)
            ->assertSee('action="'.route('pendonor.pemberitahuan.read', $notification).'"', false)
            ->assertSee('name="_method" value="PATCH"', false);
        $this->assertSharedLayout($notificationDetail);
        $this->assertNull($notification->fresh()->waktu_dibaca);
        $this->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification));
        $firstReadTime = $notification->fresh()->getRawOriginal('waktu_dibaca');
        $this->assertSame(now()->format('Y-m-d H:i:s'), $firstReadTime);
        $this->travel(5)->minutes();
        $this->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification));
        $this->assertSame($firstReadTime, $notification->fresh()->getRawOriginal('waktu_dibaca'));

        $this->assertTrue($mainAccount->fresh()->pendonor->is($mainDonor));
        $this->assertTrue($booking->fresh()->pendonor->is($mainDonor));
        $this->assertTrue($selection->fresh()->pemesananDonor->is($booking));
        $this->assertTrue($donation->fresh()->seleksiDonor->is($selection));
        $this->assertTrue($unit->fresh()->penyumbangan->is($donation));
        $this->assertTrue($unit->fresh()->jenisKomponenDarah->is($component));
        $this->assertTrue($unit->fresh()->golonganDarah->is($bloodGroup));
        $this->assertTrue($notification->fresh()->pendonor->is($candidate));
        $this->assertTrue($notification->fresh()->petugasPengirim->is($petugas));
    }

    public function test_inactive_petugas_cannot_log_in_or_use_the_shared_petugas_area(): void
    {
        $account = $this->createAccount(
            'inactive.petugas.phase11@example.test',
            'PETUGAS',
            'NONAKTIF'
        );
        Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => 'P11-NONAKTIF',
            'nama_petugas' => 'Petugas Nonaktif Phase 11',
        ]);

        $this->post(route('login.authenticate'), [
            'email' => $account->email,
            'password' => self::PASSWORD,
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($account)
            ->get(route('petugas.home'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function assertGuestEntryUiContracts(): void
    {
        $landing = $this->get('/')->assertOk();
        $this->assertSharedLayout($landing);
        $landing
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Daftar sebagai Pendonor');

        $login = $this->get(route('login'))->assertOk();
        $this->assertSharedLayout($login);
        $this->assertBusinessForm(
            $login,
            route('login.authenticate'),
            ['email', 'password']
        );

        $register = $this->get(route('register'))->assertOk();
        $this->assertSharedLayout($register);
        $this->assertBusinessForm(
            $register,
            route('register.store'),
            [
                'email',
                'password',
                'password_confirmation',
                'nik',
                'nomor_donor',
                'nama_lengkap',
                'jenis_kelamin',
                'tanggal_lahir',
                'tempat_lahir',
                'alamat',
                'nomor_telepon',
                'pekerjaan',
                'alamat_kantor',
            ]
        );
    }

    private function assertAdminNavigation(TestResponse $response): void
    {
        $this->assertSharedLayout($response);
        $this->assertNavigationRoutes($response, [
            'admin.home',
            'admin.petugas.index',
            'admin.jadwal.index',
            'admin.pertanyaan.index',
            'admin.ambang.index',
        ]);
    }

    private function assertPendonorNavigation(TestResponse $response): void
    {
        $this->assertSharedLayout($response);
        $this->assertNavigationRoutes($response, [
            'pendonor.home',
            'pendonor.jadwal.index',
            'pendonor.pemesanan.index',
            'pendonor.riwayat.index',
            'pendonor.pemberitahuan.index',
            'pendonor.profil.show',
        ]);
    }

    private function assertPetugasNavigation(TestResponse $response): void
    {
        $this->assertSharedLayout($response);
        $this->assertNavigationRoutes($response, [
            'petugas.home',
            'petugas.check-in.index',
            'petugas.pelulusan.index',
            'petugas.distribusi.index',
            'petugas.persediaan.index',
            'petugas.pemanggilan.index',
        ]);
    }

    private function assertNavigationRoutes(TestResponse $response, array $routeNames): void
    {
        foreach ($routeNames as $routeName) {
            $response->assertSee('href="'.route($routeName).'"', false);
        }

        $response
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false);
    }

    private function assertSharedLayout(TestResponse $response): void
    {
        $response
            ->assertSee('<html lang="id">', false)
            ->assertSee('href="'.asset('css/app.css').'"', false)
            ->assertSee('class="site-header"', false)
            ->assertSee('class="site-footer"', false);
    }

    private function assertBusinessForm(
        TestResponse $response,
        string $action,
        array $fieldNames
    ): void {
        $response->assertSee('action="'.$action.'"', false);

        foreach ($fieldNames as $fieldName) {
            $response->assertSee('name="'.$fieldName.'"', false);
        }
    }

    private function createAccount(
        string $email,
        string $role,
        string $status = 'AKTIF'
    ): Akun {
        return Akun::create([
            'email' => $email,
            'password_hash' => Hash::make(self::PASSWORD),
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createCallingCandidate(GolonganDarah $bloodGroup): Pendonor
    {
        $account = $this->createAccount('candidate.phase11@example.test', 'PENDONOR');

        return Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'nik' => '3273010101880011',
            'nomor_donor' => 'DNR-P11-CANDIDATE',
            'nama_lengkap' => 'Kandidat Phase 11',
            'jenis_kelamin' => 'PEREMPUAN',
            'tanggal_lahir' => '1988-01-01',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jalan Kandidat Phase 11',
            'nomor_telepon' => '081200000011',
            'pekerjaan' => 'Guru',
            'alamat_kantor' => 'Bandung',
        ]);
    }

    private function registrationPayload(): array
    {
        return [
            'email' => 'main.donor.phase11@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'nik' => '3273010101900011',
            'nomor_donor' => null,
            'nama_lengkap' => 'Pendonor Utama Phase 11',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jalan Utama Phase 11',
            'nomor_telepon' => '081200000021',
            'pekerjaan' => 'Dosen',
            'alamat_kantor' => 'Bandung',
        ];
    }
}

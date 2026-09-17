<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\JenisKomponenDarah;
use App\Models\KuesionerPradonasi;
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
use Tests\TestCase;

class Phase10EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'phase10-password';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 03:00:00', 'UTC'));
    }

    public function test_main_demo_flow_runs_from_admin_setup_to_donor_notification(): void
    {
        $bloodGroup = GolonganDarah::create([
            'abo' => 'A',
            'rhesus' => 'POSITIF',
        ]);
        $component = JenisKomponenDarah::create([
            'kode_komponen' => 'WB',
            'nama_komponen' => 'Whole Blood',
        ]);
        $admin = $this->createAccount('admin.phase10@example.test', 'ADMIN');
        $petugas = $this->createPetugas('petugas.phase10@example.test', 'P10-001');
        $candidate = $this->createExistingCandidate($bloodGroup);

        $this->post(route('login.authenticate'), [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($admin);

        $this->post(route('admin.jadwal.store'), [
            'tanggal' => '2026-10-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
            'kapasitas' => 5,
            'status_jadwal' => 'DIBUKA',
        ])->assertRedirect(route('admin.jadwal.index'));
        $schedule = JadwalPelayanan::query()->sole();

        $this->post(route('admin.pertanyaan.store'), [
            'teks_pertanyaan' => 'Apakah Anda sedang merasa sehat?',
            'kategori' => 'Kesehatan umum',
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ])->assertRedirect(route('admin.pertanyaan.index'));
        $question = PertanyaanKuesioner::query()->sole();

        $this->post(route('admin.ambang.store'), [
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'jumlah_minimum' => 1,
        ])->assertRedirect(route('admin.ambang.index'));
        $threshold = AmbangPersediaan::query()->sole();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $registration = $this->registrationPayload();
        $this->post(route('register.store'), $registration)
            ->assertRedirect(route('login'));

        $mainAccount = Akun::query()
            ->where('email', $registration['email'])
            ->sole();
        $mainDonor = Pendonor::query()
            ->where('id_akun', $mainAccount->id_akun)
            ->sole();

        $this->post(route('login.authenticate'), [
            'email' => $registration['email'],
            'password' => $registration['password'],
        ])->assertRedirect(route('pendonor.home'));
        $this->assertAuthenticatedAs($mainAccount);

        $this->get(route('pendonor.jadwal.index'))
            ->assertOk()
            ->assertViewHas(
                'jadwal',
                fn ($rows): bool => $rows->contains('id_jadwal', $schedule->id_jadwal)
            );

        $this->post(route('pendonor.pemesanan.store', $schedule))
            ->assertRedirect(route('pendonor.pemesanan.index'));
        $booking = PemesananDonor::query()
            ->where('id_pendonor', $mainDonor->id_pendonor)
            ->sole();

        $this->get(route('pendonor.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan);
        $this->post(route('pendonor.kuesioner.store', $booking), [
            'answers' => [$question->id_pertanyaan => 'YA'],
        ])->assertRedirect(route('pendonor.kuesioner.show', $booking));

        $questionnaire = KuesionerPradonasi::query()->sole();
        $answer = JawabanKuesioner::query()->sole();

        $this->post(route('pendonor.kode-checkin.generate', $booking))
            ->assertRedirect(route('pendonor.kode-checkin.show', $booking));
        $booking->refresh();
        $this->assertMatchesRegularExpression('/\AUDD-[0-9A-F]{12}\z/', $booking->kode_checkin);
        $this->get(route('pendonor.kode-checkin.show', $booking))
            ->assertOk()
            ->assertSee($booking->kode_checkin);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.authenticate'), [
            'email' => $petugas->akun->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('petugas.home'));
        $this->assertAuthenticatedAs($petugas->akun);

        $this->get(route('petugas.check-in.index', [
            'kode_checkin' => $booking->kode_checkin,
        ]))->assertOk()->assertSee($mainDonor->nama_lengkap);
        $this->post(route('petugas.check-in.store'), [
            'kode_checkin' => $booking->kode_checkin,
        ])->assertRedirect(route('petugas.check-in.index', [
            'kode_checkin' => $booking->kode_checkin,
        ]));

        $this->get(route('petugas.kuesioner.show', $booking))
            ->assertOk()
            ->assertSee($question->teks_pertanyaan)
            ->assertSee('YA');

        $this->get(route('petugas.seleksi.show', $booking))->assertOk();
        $this->post(route('petugas.seleksi.store', $booking), [
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
        ])->assertRedirect(route('petugas.seleksi.show', $booking));
        $selection = SeleksiDonor::query()->sole();

        $this->get(route('petugas.penyumbangan.show', $selection))->assertOk();
        $this->post(route('petugas.penyumbangan.store', $selection), [
            'waktu_pengambilan' => '2026-10-15T10:30',
            'volume_ml' => 350,
            'hasil_penyumbangan' => 'BERHASIL',
            'alasan_gagal' => null,
        ])->assertRedirect(route('petugas.penyumbangan.show', $selection));
        $donation = Penyumbangan::query()->sole();

        $this->get(route('petugas.unit-komponen.show', $donation))->assertOk();
        $this->post(route('petugas.unit-komponen.store', $donation), [
            'nomor_unit' => 'UNIT-P10-0001',
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'tanggal_pembuatan' => '2026-10-15',
            'tanggal_kedaluwarsa' => '2026-10-20',
        ])->assertRedirect(route('petugas.unit-komponen.show', $donation));
        $unit = UnitKomponenDarah::query()->sole();
        $this->assertSame('MENUNGGU_PELULUSAN', $unit->status_unit);

        $this->get(route('petugas.persediaan.index'))
            ->assertOk()
            ->assertViewHas('persediaan', fn ($rows): bool => $rows->isEmpty());
        $this->get(route('petugas.pelulusan.show', $unit))->assertOk();
        $this->post(route('petugas.pelulusan.store', $unit), [
            'hasil_pelulusan' => 'TERSEDIA',
            'catatan_pelulusan' => 'Lulus pemeriksaan mutu.',
        ])->assertRedirect(route('petugas.pelulusan.show', $unit));
        $unit->refresh();
        $this->assertSame('TERSEDIA', $unit->status_unit);

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

        $this->get(route('petugas.pemanggilan.index', [
            'id_ambang' => $threshold->id_ambang,
        ]))->assertOk()
            ->assertViewHas(
                'kandidatPendonor',
                fn ($rows): bool => $rows->contains(
                    fn ($row): bool => (int) $row->id_pendonor === $candidate->id_pendonor
                )
            )
            ->assertSee($candidate->nama_lengkap)
            ->assertDontSee($mainDonor->nama_lengkap);

        $message = 'Persediaan A positif sedang rendah. Silakan donor kembali.';
        $this->get(route('petugas.pemberitahuan.create', [$threshold, $candidate]))
            ->assertOk();
        $this->post(route('petugas.pemberitahuan.store', [$threshold, $candidate]), [
            'isi_pesan' => $message,
        ])->assertRedirect(route('petugas.pemanggilan.index', [
            'id_ambang' => $threshold->id_ambang,
        ]));
        $notification = Pemberitahuan::query()->sole();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.authenticate'), [
            'email' => $candidate->akun->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('pendonor.home'));
        $this->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSee($message);
        $this->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertOk()
            ->assertSee($message);
        $this->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification));
        $this->assertNotNull($notification->fresh()->waktu_dibaca);

        $this->assertTrue($mainAccount->fresh()->pendonor->is($mainDonor));
        $this->assertTrue($booking->fresh()->pendonor->is($mainDonor));
        $this->assertTrue($questionnaire->fresh()->pemesananDonor->is($booking));
        $this->assertTrue($answer->fresh()->kuesionerPradonasi->is($questionnaire));
        $this->assertTrue($selection->fresh()->pemesananDonor->is($booking));
        $this->assertTrue($donation->fresh()->seleksiDonor->is($selection));
        $this->assertTrue($unit->fresh()->penyumbangan->is($donation));
        $this->assertTrue($unit->fresh()->jenisKomponenDarah->is($component));
        $this->assertTrue($unit->fresh()->golonganDarah->is($bloodGroup));
        $this->assertTrue($notification->fresh()->pendonor->is($candidate));
        $this->assertTrue($notification->fresh()->petugasPengirim->is($petugas));
    }

    public function test_registration_rejects_duplicate_email_and_nik_without_partial_rows(): void
    {
        $account = $this->createAccount('existing.phase10@example.test', 'PENDONOR');
        Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => null,
            'nik' => '3273010101900001',
            'nomor_donor' => null,
            'nama_lengkap' => 'Pendonor Existing',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jalan Existing',
            'nomor_telepon' => '081200000001',
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => $account->email,
            'nik' => '3273010101900002',
        ]))->assertSessionHasErrors('email');

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'new.phase10@example.test',
            'nik' => '3273010101900001',
        ]))->assertSessionHasErrors('nik');

        $this->assertDatabaseCount('akun', 1);
        $this->assertDatabaseCount('pendonor', 1);
    }

    public function test_inactive_petugas_cannot_log_in(): void
    {
        $petugas = $this->createPetugas(
            'inactive.petugas.phase10@example.test',
            'P10-NONAKTIF',
            'NONAKTIF'
        );

        $this->post(route('login.authenticate'), [
            'email' => $petugas->akun->email,
            'password' => self::PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_rejects_duplicate_threshold_combination(): void
    {
        $admin = $this->createAccount('admin.duplicate.phase10@example.test', 'ADMIN');
        $component = JenisKomponenDarah::create([
            'kode_komponen' => 'PRC',
            'nama_komponen' => 'Packed Red Cells',
        ]);
        $bloodGroup = GolonganDarah::create([
            'abo' => 'O',
            'rhesus' => 'NEGATIF',
        ]);
        $payload = [
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'jumlah_minimum' => 2,
        ];

        $this->post(route('login.authenticate'), [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('admin.home'));
        $this->post(route('admin.ambang.store'), $payload)
            ->assertRedirect(route('admin.ambang.index'));
        $this->post(route('admin.ambang.store'), $payload)
            ->assertSessionHasErrors('id_golongan_darah');

        $this->assertDatabaseCount('ambang_persediaan', 1);
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

    private function createPetugas(
        string $email,
        string $number,
        string $status = 'AKTIF'
    ): Petugas {
        $account = $this->createAccount($email, 'PETUGAS', $status);

        return Petugas::create([
            'id_akun' => $account->id_akun,
            'nomor_petugas' => $number,
            'nama_petugas' => 'Petugas Phase 10',
        ]);
    }

    private function createExistingCandidate(GolonganDarah $bloodGroup): Pendonor
    {
        $account = $this->createAccount('candidate.phase10@example.test', 'PENDONOR');

        return Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'nik' => '3273010101880010',
            'nomor_donor' => 'DNR-P10-CANDIDATE',
            'nama_lengkap' => 'Kandidat Phase 10',
            'jenis_kelamin' => 'PEREMPUAN',
            'tanggal_lahir' => '1988-01-01',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jalan Kandidat',
            'nomor_telepon' => '081200000010',
            'pekerjaan' => 'Guru',
            'alamat_kantor' => 'Bandung',
        ]);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'main.donor.phase10@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'nik' => '3273010101900010',
            'nomor_donor' => null,
            'nama_lengkap' => 'Pendonor Utama Phase 10',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jalan Utama Phase 10',
            'nomor_telepon' => '081200000020',
            'pekerjaan' => 'Dosen',
            'alamat_kantor' => 'Bandung',
        ], $overrides);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\AmbangPersediaan;
use App\Models\GolonganDarah;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\JenisKomponenDarah;
use App\Models\Pemberitahuan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use App\Models\Petugas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class F04MySqlRepresentabilityTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_kapasitas_signed_int_maximum_on_create_and_update(): void
    {
        $this->actingAs($this->account('ADMIN'));
        $payload = $this->schedulePayload(['kapasitas' => '2147483647']);

        $this->post(route('admin.jadwal.store'), $payload)
            ->assertRedirect(route('admin.jadwal.index'))->assertSessionHasNoErrors();
        $jadwal = JadwalPelayanan::query()->sole();
        $this->assertSame(2147483647, $jadwal->kapasitas);

        $invalid = array_replace($payload, ['kapasitas' => '2147483648', 'status_jadwal' => 'DIBATALKAN']);
        $before = $jadwal->getAttributes();
        $this->post(route('admin.jadwal.store'), $invalid)->assertSessionHasErrors('kapasitas');
        $this->put(route('admin.jadwal.update', $jadwal), $invalid)->assertSessionHasErrors('kapasitas');
        $this->assertDatabaseCount('jadwal_pelayanan', 1);
        $this->assertSame($before, $jadwal->fresh()->getAttributes());

        $jadwal->update(['kapasitas' => 1]);
        $this->put(route('admin.jadwal.update', $jadwal), $payload)
            ->assertRedirect(route('admin.jadwal.index'))->assertSessionHasNoErrors();
        $this->assertSame(2147483647, $jadwal->fresh()->kapasitas);
    }

    public function test_jumlah_minimum_signed_int_maximum_on_create_and_update(): void
    {
        $this->actingAs($this->account('ADMIN'));
        $component = $this->createComponent();
        $bloodGroup = $this->bloodGroup();
        $payload = [
            'id_jenis_komponen' => $component->id_jenis_komponen,
            'id_golongan_darah' => $bloodGroup->id_golongan_darah,
            'jumlah_minimum' => '2147483648',
        ];

        $this->post(route('admin.ambang.store'), $payload)->assertSessionHasErrors('jumlah_minimum');
        $this->assertDatabaseCount('ambang_persediaan', 0);

        $payload['jumlah_minimum'] = '2147483647';
        $this->post(route('admin.ambang.store'), $payload)
            ->assertRedirect(route('admin.ambang.index'))->assertSessionHasNoErrors();
        $ambang = AmbangPersediaan::query()->sole();
        $this->assertSame(2147483647, $ambang->jumlah_minimum);
        $before = $ambang->getAttributes();

        $this->put(route('admin.ambang.update', $ambang), ['jumlah_minimum' => '2147483648'])
            ->assertSessionHasErrors('jumlah_minimum');
        $this->assertSame($before, $ambang->fresh()->getAttributes());

        $ambang->update(['jumlah_minimum' => 0]);
        $this->put(route('admin.ambang.update', $ambang), ['jumlah_minimum' => '2147483647'])
            ->assertRedirect(route('admin.ambang.index'))->assertSessionHasNoErrors();
        $this->assertSame(2147483647, $ambang->fresh()->jumlah_minimum);
        $this->assertDatabaseCount('ambang_persediaan', 1);
    }

    #[DataProvider('signedIntBoundaries')]
    public function test_urutan_signed_int_range_on_create_and_update(string $urutan, bool $accepted): void
    {
        $this->actingAs($this->account('ADMIN'));
        $payload = $this->questionPayload(['urutan' => $urutan]);
        $response = $this->post(route('admin.pertanyaan.store'), $payload);

        if ($accepted) {
            $response->assertRedirect(route('admin.pertanyaan.index'))->assertSessionHasNoErrors();
            $pertanyaan = PertanyaanKuesioner::query()->sole();
            $this->assertSame((int) $urutan, $pertanyaan->urutan);
            $pertanyaan->update(['urutan' => 0]);
            $this->put(route('admin.pertanyaan.update', $pertanyaan), $payload)
                ->assertRedirect(route('admin.pertanyaan.index'))->assertSessionHasNoErrors();
            $this->assertSame((int) $urutan, $pertanyaan->fresh()->urutan);
        } else {
            $response->assertSessionHasErrors('urutan');
            $this->assertDatabaseCount('pertanyaan_kuesioner', 0);
            $pertanyaan = PertanyaanKuesioner::create($this->questionPayload());
            $before = $pertanyaan->fresh()->getAttributes();
            $this->put(route('admin.pertanyaan.update', $pertanyaan), array_replace($payload, [
                'teks_pertanyaan' => 'Tidak boleh berubah',
            ]))->assertSessionHasErrors('urutan');
            $this->assertSame($before, $pertanyaan->fresh()->getAttributes());
        }

        $this->assertDatabaseCount('pertanyaan_kuesioner', 1);
    }

    public static function signedIntBoundaries(): array
    {
        return [
            'signed minimum' => ['-2147483648', true],
            'signed maximum' => ['2147483647', true],
            'below signed minimum' => ['-2147483649', false],
            'above signed maximum' => ['2147483648', false],
        ];
    }

    #[DataProvider('addressFields')]
    public function test_registration_rejects_oversized_text_without_partial_account(string $field): void
    {
        $payload = array_replace($this->profilePayload(), [
            'email' => 'f04-register@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nik' => '0012345678901234',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            $field => $this->oversizedUtf8(),
        ]);

        $this->post(route('register.store'), $payload)->assertSessionHasErrors($field);
        $this->assertDatabaseCount('akun', 0);
        $this->assertDatabaseCount('pendonor', 0);
    }

    #[DataProvider('addressFields')]
    public function test_profile_rejects_oversized_text_without_changing_identity_or_other_fields(string $field): void
    {
        $pendonor = $this->donor();
        $before = $pendonor->fresh()->getAttributes();
        $accountBefore = $pendonor->akun->getAttributes();

        $this->actingAs($pendonor->akun)->put(route('pendonor.profil.update'), $this->profilePayload([
            'nama_lengkap' => 'Tidak boleh berubah',
            $field => $this->oversizedUtf8(),
        ]))->assertSessionHasErrors($field);

        $this->assertSame($before, $pendonor->fresh()->getAttributes());
        $this->assertSame($accountBefore, $pendonor->akun->fresh()->getAttributes());
    }

    public static function addressFields(): array
    {
        return ['alamat' => ['alamat'], 'alamat kantor' => ['alamat_kantor']];
    }

    public function test_admin_question_rejects_oversized_text_without_create_or_partial_update(): void
    {
        $this->actingAs($this->account('ADMIN'));
        $payload = $this->questionPayload(['teks_pertanyaan' => $this->oversizedUtf8()]);
        $this->post(route('admin.pertanyaan.store'), $payload)->assertSessionHasErrors('teks_pertanyaan');
        $this->assertDatabaseCount('pertanyaan_kuesioner', 0);

        $pertanyaan = PertanyaanKuesioner::create($this->questionPayload());
        $before = $pertanyaan->fresh()->getAttributes();
        $this->put(route('admin.pertanyaan.update', $pertanyaan), array_replace($payload, [
            'jenis_jawaban' => 'YA_TIDAK', 'status_aktif' => false, 'urutan' => -1,
        ]))->assertSessionHasErrors('teks_pertanyaan');
        $this->assertSame($before, $pertanyaan->fresh()->getAttributes());
        $this->assertDatabaseCount('pertanyaan_kuesioner', 1);
    }

    public function test_questionnaire_checks_trimmed_bytes_and_rejects_without_answers_or_code(): void
    {
        $pendonor = $this->donor();
        $jadwal = JadwalPelayanan::create($this->schedulePayload());
        $booking = PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'waktu_pemesanan' => now(),
            'status_pemesanan' => 'TERJADWAL',
        ]);
        $yesNo = PertanyaanKuesioner::create($this->questionPayload(['jenis_jawaban' => 'YA_TIDAK']));
        $text = PertanyaanKuesioner::create($this->questionPayload(['urutan' => 2]));
        $before = $booking->fresh()->getAttributes();
        $url = route('pendonor.kuesioner.store', $booking);

        $this->actingAs($pendonor->akun)->post($url, ['answers' => [
            $yesNo->id_pertanyaan => 'YA', $text->id_pertanyaan => $this->oversizedUtf8(),
        ]])->assertSessionHasErrors('answers.'.$text->id_pertanyaan);
        $this->assertDatabaseCount('kuesioner_pradonasi', 0);
        $this->assertDatabaseCount('jawaban_kuesioner', 0);
        $this->assertSame($before, $booking->fresh()->getAttributes());

        $boundary = str_repeat('a', 65535);
        $this->actingAs($pendonor->akun)->post($url, ['answers' => [
            $yesNo->id_pertanyaan => 'YA', $text->id_pertanyaan => '  '.$boundary.'  ',
        ]])->assertRedirect(route('pendonor.kode-checkin.show', $booking))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('kuesioner_pradonasi', 1);
        $this->assertDatabaseCount('jawaban_kuesioner', 2);
        $this->assertSame($boundary, JawabanKuesioner::query()->where('id_pertanyaan', $text->id_pertanyaan)->sole()->jawaban);
        $this->assertNotNull($booking->fresh()->kode_checkin);
        $this->assertSame('TERJADWAL', $booking->fresh()->status_pemesanan);
    }

    public function test_notification_checks_trimmed_bytes_without_mutating_candidate_or_threshold(): void
    {
        $petugas = Petugas::create([
            'id_akun' => $this->account('PETUGAS')->id_akun,
            'nomor_petugas' => 'PTG-000001',
            'nama_petugas' => 'Petugas F04',
        ]);
        $pendonor = $this->donor($this->bloodGroup());
        $ambang = AmbangPersediaan::create([
            'id_jenis_komponen' => $this->createComponent()->id_jenis_komponen,
            'id_golongan_darah' => $pendonor->id_golongan_darah,
            'jumlah_minimum' => 0,
        ]);
        $before = [$pendonor->fresh()->getAttributes(), $ambang->fresh()->getAttributes()];
        $url = route('petugas.pemberitahuan.store', [$ambang, $pendonor]);

        $this->actingAs($petugas->akun)->post($url, ['isi_pesan' => $this->oversizedUtf8()])
            ->assertSessionHasErrors('isi_pesan');
        $this->assertDatabaseCount('pemberitahuan', 0);
        $this->assertSame($before, [$pendonor->fresh()->getAttributes(), $ambang->fresh()->getAttributes()]);

        $boundary = str_repeat('a', 65535);
        $this->actingAs($petugas->akun)->post($url, ['isi_pesan' => '  '.$boundary.'  '])
            ->assertRedirect(route('petugas.pemanggilan.index', ['id_ambang' => $ambang->id_ambang]))
            ->assertSessionHasNoErrors();
        $message = Pemberitahuan::query()->sole();
        $this->assertSame($boundary, $message->isi_pesan);
        $this->assertSame($petugas->id_petugas, $message->id_petugas_pengirim);
        $this->assertSame($pendonor->id_pendonor, $message->id_pendonor);
        $this->assertNull($message->waktu_dibaca);
        $this->assertSame($before, [$pendonor->fresh()->getAttributes(), $ambang->fresh()->getAttributes()]);
    }

    private function oversizedUtf8(): string
    {
        return str_repeat("\u{1F600}", 16384);
    }

    private function account(string $role): Akun
    {
        return Akun::create([
            'email' => 'f04-'.(++$this->sequence).'@example.test',
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function donor(?GolonganDarah $bloodGroup = null): Pendonor
    {
        $account = $this->account('PENDONOR');

        return Pendonor::create(array_replace($this->profilePayload(), [
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => $bloodGroup?->id_golongan_darah,
            'nik' => str_pad((string) $account->id_akun, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => 'DNR-'.str_pad((string) $account->id_akun, 6, '0', STR_PAD_LEFT),
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
        ]));
    }

    private function bloodGroup(): GolonganDarah
    {
        return GolonganDarah::create(['abo' => 'A', 'rhesus' => 'POSITIF']);
    }

    private function createComponent(): JenisKomponenDarah
    {
        return JenisKomponenDarah::create(['kode_komponen' => 'PRC', 'nama_komponen' => 'Packed Red Cell']);
    }

    private function schedulePayload(array $overrides = []): array
    {
        return array_replace([
            'tanggal' => '2026-10-15', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00',
            'kapasitas' => 5, 'status_jadwal' => 'DIBUKA',
        ], $overrides);
    }

    private function questionPayload(array $overrides = []): array
    {
        return array_replace([
            'teks_pertanyaan' => 'Jelaskan kondisi Anda', 'kategori' => null,
            'jenis_jawaban' => 'TEKS', 'urutan' => 1, 'status_aktif' => true,
        ], $overrides);
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_replace([
            'nama_lengkap' => 'Pendonor F04', 'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat rumah', 'nomor_telepon' => '081234567890',
            'pekerjaan' => null, 'alamat_kantor' => 'Alamat kantor',
        ], $overrides);
    }
}

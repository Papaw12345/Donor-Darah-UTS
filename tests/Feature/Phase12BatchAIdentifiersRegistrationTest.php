<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\Pendonor;
use App\Models\Petugas;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12BatchAIdentifiersRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_registration_preserves_leading_zero_and_issues_server_owned_number_without_blood_group(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('name="nomor_donor"', false)
            ->assertDontSee('name="id_golongan_darah"', false);

        $this->post(route('register.store'), $this->registrationPayload([
            'nik' => '0012345678901234',
            'tanggal_lahir' => '2009-09-15',
            'nomor_donor' => 'DNR-CLIENT',
            'id_golongan_darah' => 999,
        ]))->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $pendonor = Pendonor::query()->sole();
        $this->assertSame('0012345678901234', $pendonor->nik);
        $this->assertSame($this->number('DNR', $pendonor->id_pendonor), $pendonor->nomor_donor);
        $this->assertNull($pendonor->id_golongan_darah);
        $this->assertSame('PENDONOR', $pendonor->akun->peran);
    }

    public function test_registration_rejects_invalid_nik_duplicate_future_birth_and_underage_without_partial_rows(): void
    {
        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('login'));

        foreach (['123', '123456789012345', '12345678901234567', '12345678901234AB', '3273010101900001'] as $nik) {
            $this->post(route('register.store'), $this->registrationPayload([
                'email' => 'invalid-'.$nik.'@example.test',
                'nik' => $nik,
            ]))->assertSessionHasErrors('nik');
        }

        foreach (['2026-09-16', '2009-09-16'] as $tanggalLahir) {
            $this->post(route('register.store'), $this->registrationPayload([
                'email' => 'invalid-'.$tanggalLahir.'@example.test',
                'nik' => '0012345678901234',
                'tanggal_lahir' => $tanggalLahir,
            ]))->assertSessionHasErrors('tanggal_lahir');
        }

        $this->assertDatabaseCount('akun', 1);
        $this->assertDatabaseCount('pendonor', 1);
    }

    public function test_registration_rolls_back_account_if_final_donor_number_collides(): void
    {
        $account = $this->account('existing@example.test', 'PENDONOR');
        $existing = $this->donor($account, '1990-01-01');
        $existing->update([
            'nomor_donor' => $this->number('DNR', $existing->id_pendonor + 1),
        ]);
        $this->withoutExceptionHandling();

        try {
            $this->post(route('register.store'), $this->registrationPayload());
            $this->fail('Penerbitan nomor donor seharusnya gagal karena UNIQUE.');
        } catch (QueryException $exception) {
            $this->assertDatabaseMissing('akun', ['email' => 'new@example.test']);
            $this->assertDatabaseCount('akun', 1);
            $this->assertDatabaseCount('pendonor', 1);
        }
    }

    public function test_booking_checks_age_on_schedule_date_including_exact_seventeenth_birthday(): void
    {
        $jadwal = JadwalPelayanan::create([
            'tanggal' => '2026-10-15',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'kapasitas' => 3,
            'status_jadwal' => 'DIBUKA',
        ]);

        $terlaluMuda = $this->donor($this->account('young@example.test', 'PENDONOR'), '2009-10-16');
        $tepat17 = $this->donor($this->account('boundary@example.test', 'PENDONOR'), '2009-10-15');
        $lebihTua = $this->donor($this->account('older@example.test', 'PENDONOR'), '2009-10-14');

        $this->actingAs($terlaluMuda->akun)
            ->post(route('pendonor.pemesanan.store', $jadwal))
            ->assertSessionHasErrors('pemesanan');
        $this->assertDatabaseCount('pemesanan_donor', 0);

        foreach ([$tepat17, $lebihTua] as $pendonor) {
            $this->actingAs($pendonor->akun)
                ->post(route('pendonor.pemesanan.store', $jadwal))
                ->assertRedirect(route('pendonor.pemesanan.index'))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('pemesanan_donor', 2);
        $this->assertDatabaseMissing('pemesanan_donor', ['id_pendonor' => $terlaluMuda->id_pendonor]);
    }

    public function test_admin_create_and_update_keep_petugas_number_server_owned_and_immutable(): void
    {
        $admin = $this->account('admin@example.test', 'ADMIN');

        $this->actingAs($admin)->get(route('admin.petugas.create'))
            ->assertOk()
            ->assertDontSee('name="nomor_petugas"', false);

        $this->actingAs($admin)->post(route('admin.petugas.store'), $this->petugasPayload(
            'first@example.test',
            ['nomor_petugas' => 'PTG-CLIENT']
        ))->assertRedirect(route('admin.petugas.index'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.petugas.store'), $this->petugasPayload(
            'second@example.test'
        ))->assertRedirect(route('admin.petugas.index'))->assertSessionHasNoErrors();

        $first = Petugas::query()->whereHas('akun', fn ($query) => $query->where('email', 'first@example.test'))->sole();
        $second = Petugas::query()->whereHas('akun', fn ($query) => $query->where('email', 'second@example.test'))->sole();
        foreach ([$first, $second] as $petugas) {
            $this->assertSame($this->number('PTG', $petugas->id_petugas), $petugas->nomor_petugas);
            $this->assertStringNotContainsString('TMP-', $petugas->nomor_petugas);
        }
        $this->assertNotSame($first->nomor_petugas, $second->nomor_petugas);

        $this->actingAs($admin)->get(route('admin.petugas.edit', $first))
            ->assertOk()
            ->assertSee($first->nomor_petugas)
            ->assertDontSee('name="nomor_petugas"', false);

        $this->actingAs($admin)->put(route('admin.petugas.update', $first), [
            'email' => 'first-updated@example.test',
            'nama_petugas' => 'Nama Diperbarui',
            'nomor_petugas' => 'PTG-HOSTILE',
        ])->assertRedirect(route('admin.petugas.index'))->assertSessionHasNoErrors();

        $this->assertSame($this->number('PTG', $first->id_petugas), $first->fresh()->nomor_petugas);
        $this->assertSame('Nama Diperbarui', $first->fresh()->nama_petugas);
        $this->assertSame('first-updated@example.test', $first->akun->fresh()->email);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'new@example.test',
            'password' => 'test-password',
            'password_confirmation' => 'test-password',
            'nik' => '3273010101900001',
            'nama_lengkap' => 'Pendonor Baru',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat',
            'nomor_telepon' => '081234567890',
        ], $overrides);
    }

    private function petugasPayload(string $email, array $overrides = []): array
    {
        return array_merge([
            'email' => $email,
            'password' => 'test-password',
            'password_confirmation' => 'test-password',
            'nama_petugas' => 'Petugas Baru',
        ], $overrides);
    }

    private function account(string $email, string $role): Akun
    {
        return Akun::create([
            'email' => $email,
            'password_hash' => 'test-hash',
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function donor(Akun $account, string $birthDate, ?string $number = null): Pendonor
    {
        return Pendonor::create([
            'id_akun' => $account->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $account->id_akun, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => $number,
            'nama_lengkap' => 'Pendonor Fixture',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => $birthDate,
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat',
            'nomor_telepon' => '081234567890',
        ]);
    }

    private function number(string $prefix, int $key): string
    {
        return $prefix.'-'.str_pad((string) $key, 6, '0', STR_PAD_LEFT);
    }
}

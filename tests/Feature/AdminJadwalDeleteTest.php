<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminJadwalDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_schedule_without_any_booking_even_when_open(): void
    {
        $admin = $this->createAccount('admin.delete.schedule@example.test', 'ADMIN');
        $jadwal = $this->createSchedule('DIBUKA');

        $this->actingAs($admin)
            ->delete(route('admin.jadwal.destroy', $jadwal))
            ->assertRedirect(route('admin.jadwal.index'))
            ->assertSessionHas('success', 'Jadwal pelayanan berhasil dihapus.');

        $this->assertDatabaseMissing('jadwal_pelayanan', [
            'id_jadwal' => $jadwal->id_jadwal,
        ]);
    }

    public function test_admin_cannot_delete_schedule_with_historical_cancelled_booking(): void
    {
        $admin = $this->createAccount('admin.delete.schedule@example.test', 'ADMIN');
        $donorAccount = $this->createAccount('donor.delete.schedule@example.test', 'PENDONOR');
        $pendonor = Pendonor::create([
            'id_akun' => $donorAccount->id_akun,
            'nik' => '3578000000000001',
            'nama_lengkap' => 'Pendonor Delete Test',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1998-01-01',
            'tempat_lahir' => 'Surabaya',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '081234567890',
        ]);
        $jadwal = $this->createSchedule('DIBATALKAN');
        $pemesanan = PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'waktu_pemesanan' => '2026-09-30 10:00:00',
            'status_pemesanan' => 'DIBATALKAN',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.jadwal.destroy', $jadwal))
            ->assertRedirect(route('admin.jadwal.index'))
            ->assertSessionHasErrors('jadwal');

        $this->assertDatabaseHas('jadwal_pelayanan', [
            'id_jadwal' => $jadwal->id_jadwal,
        ]);
        $this->assertDatabaseHas('pemesanan_donor', [
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'id_jadwal' => $jadwal->id_jadwal,
            'status_pemesanan' => 'DIBATALKAN',
        ]);
    }

    public function test_admin_schedule_list_exposes_delete_form(): void
    {
        $admin = $this->createAccount('admin.delete.schedule@example.test', 'ADMIN');
        $jadwal = $this->createSchedule('DIBUKA');

        $this->actingAs($admin)
            ->get(route('admin.jadwal.index'))
            ->assertOk()
            ->assertSee(route('admin.jadwal.destroy', $jadwal), false)
            ->assertSee('name="_token"', false)
            ->assertSee('value="DELETE"', false)
            ->assertSee('Hapus');
    }

    public function test_guest_and_non_admin_cannot_delete_schedule(): void
    {
        $jadwal = $this->createSchedule('DIBUKA');

        $this->delete(route('admin.jadwal.destroy', $jadwal))
            ->assertRedirect(route('login'));

        $donor = $this->createAccount('donor.delete.schedule@example.test', 'PENDONOR');
        $this->actingAs($donor)
            ->delete(route('admin.jadwal.destroy', $jadwal))
            ->assertForbidden();

        $this->assertDatabaseHas('jadwal_pelayanan', [
            'id_jadwal' => $jadwal->id_jadwal,
        ]);
    }

    private function createAccount(string $email, string $role): Akun
    {
        return Akun::create([
            'email' => $email,
            'password_hash' => Hash::make('password'),
            'peran' => $role,
            'status_akun' => 'AKTIF',
        ]);
    }

    private function createSchedule(string $status): JadwalPelayanan
    {
        return JadwalPelayanan::create([
            'tanggal' => '2026-10-01',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'kapasitas' => 10,
            'status_jadwal' => $status,
        ]);
    }
}

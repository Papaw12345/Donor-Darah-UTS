<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminJadwalValidationMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_shows_indonesian_end_time_error_without_creating_schedule(): void
    {
        $this->actingAs($this->createAdmin())
            ->followingRedirects()
            ->from(route('admin.jadwal.create'))
            ->post(route('admin.jadwal.store'), $this->invalidSchedulePayload())
            ->assertOk()
            ->assertSeeText('Jam selesai harus setelah jam mulai.');

        $this->assertDatabaseCount('jadwal_pelayanan', 0);
    }

    public function test_update_shows_indonesian_end_time_error_without_mutating_schedule(): void
    {
        $jadwal = JadwalPelayanan::create([
            'tanggal' => '2026-10-10',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ]);
        $before = $jadwal->fresh()->getAttributes();

        $this->actingAs($this->createAdmin())
            ->followingRedirects()
            ->from(route('admin.jadwal.edit', $jadwal))
            ->put(route('admin.jadwal.update', $jadwal), [
                ...$this->invalidSchedulePayload(),
                'status_jadwal' => 'DIBATALKAN',
            ])
            ->assertOk()
            ->assertSeeText('Jam selesai harus setelah jam mulai.');

        $this->assertSame($before, $jadwal->fresh()->getAttributes());
        $this->assertDatabaseCount('jadwal_pelayanan', 1);
    }

    private function createAdmin(): Akun
    {
        return Akun::create([
            'email' => 'admin.validation.schedule@example.test',
            'password_hash' => Hash::make('password'),
            'peran' => 'ADMIN',
            'status_akun' => 'AKTIF',
        ]);
    }

    private function invalidSchedulePayload(): array
    {
        return [
            'tanggal' => '2026-10-15',
            'jam_mulai' => '10:00',
            'jam_selesai' => '09:00',
            'kapasitas' => 30,
            'status_jadwal' => 'DIBUKA',
        ];
    }
}

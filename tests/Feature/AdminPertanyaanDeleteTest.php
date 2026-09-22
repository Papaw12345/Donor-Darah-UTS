<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JadwalPelayanan;
use App\Models\JawabanKuesioner;
use App\Models\KuesionerPradonasi;
use App\Models\PemesananDonor;
use App\Models\Pendonor;
use App\Models\PertanyaanKuesioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPertanyaanDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_question_that_has_never_been_answered(): void
    {
        $admin = $this->createAdmin();
        $pertanyaan = $this->createQuestion();

        $this->actingAs($admin)
            ->delete(route('admin.pertanyaan.destroy', $pertanyaan))
            ->assertRedirect(route('admin.pertanyaan.index'))
            ->assertSessionHas('success', 'Pertanyaan kuesioner berhasil dihapus.');

        $this->assertDatabaseMissing('pertanyaan_kuesioner', [
            'id_pertanyaan' => $pertanyaan->id_pertanyaan,
        ]);
    }

    public function test_admin_cannot_delete_question_that_has_historical_answer(): void
    {
        $admin = $this->createAdmin();
        $pertanyaan = $this->createQuestion();
        $jawaban = $this->createHistoricalAnswer($pertanyaan);

        $this->actingAs($admin)
            ->delete(route('admin.pertanyaan.destroy', $pertanyaan))
            ->assertRedirect(route('admin.pertanyaan.index'))
            ->assertSessionHasErrors('pertanyaan');

        $this->assertDatabaseHas('pertanyaan_kuesioner', [
            'id_pertanyaan' => $pertanyaan->id_pertanyaan,
        ]);

        $this->assertDatabaseHas('jawaban_kuesioner', [
            'id_jawaban' => $jawaban->id_jawaban,
            'id_pertanyaan' => $pertanyaan->id_pertanyaan,
        ]);
    }

    public function test_admin_question_list_exposes_working_delete_action(): void
    {
        $admin = $this->createAdmin();
        $pertanyaan = $this->createQuestion();

        $this->actingAs($admin)
            ->get(route('admin.pertanyaan.index'))
            ->assertOk()
            ->assertSee(route('admin.pertanyaan.destroy', $pertanyaan), false)
            ->assertSee('value="DELETE"', false)
            ->assertSee('Hapus');
    }

    private function createAdmin(): Akun
    {
        return Akun::create([
            'email' => 'admin.delete.question@example.test',
            'password_hash' => Hash::make('password'),
            'peran' => 'ADMIN',
            'status_akun' => 'AKTIF',
        ]);
    }

    private function createQuestion(): PertanyaanKuesioner
    {
        return PertanyaanKuesioner::create([
            'teks_pertanyaan' => 'Pertanyaan untuk pengujian delete?',
            'kategori' => 'Pengujian',
            'jenis_jawaban' => 'YA_TIDAK',
            'urutan' => 1,
            'status_aktif' => true,
        ]);
    }

    private function createHistoricalAnswer(PertanyaanKuesioner $pertanyaan): JawabanKuesioner
    {
        $akunPendonor = Akun::create([
            'email' => 'pendonor.delete.question@example.test',
            'password_hash' => Hash::make('password'),
            'peran' => 'PENDONOR',
            'status_akun' => 'AKTIF',
        ]);

        $pendonor = Pendonor::create([
            'id_akun' => $akunPendonor->id_akun,
            'nik' => '3578000000000001',
            'nama_lengkap' => 'Pendonor Delete Test',
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1998-01-01',
            'tempat_lahir' => 'Surabaya',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '081234567890',
        ]);

        $jadwal = JadwalPelayanan::create([
            'tanggal' => '2026-10-01',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
            'kapasitas' => 10,
            'status_jadwal' => 'DIBUKA',
        ]);

        $pemesanan = PemesananDonor::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_jadwal' => $jadwal->id_jadwal,
            'waktu_pemesanan' => '2026-09-30 10:00:00',
            'status_pemesanan' => 'TERJADWAL',
        ]);

        $kuesioner = KuesionerPradonasi::create([
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'waktu_pengisian' => '2026-09-30 10:05:00',
        ]);

        return JawabanKuesioner::create([
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'id_pertanyaan' => $pertanyaan->id_pertanyaan,
            'jawaban' => 'YA',
        ]);
    }
}
<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Pemberitahuan;
use App\Models\Pendonor;
use App\Models\Petugas;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase7IPemberitahuanTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
    }

    public function test_guest_cannot_use_notification_routes(): void
    {
        $this->get(route('pendonor.pemberitahuan.index'))
            ->assertRedirect(route('login'));
        $this->get(route('pendonor.pemberitahuan.show', 1))
            ->assertRedirect(route('login'));
        $this->patch(route('pendonor.pemberitahuan.read', 1))
            ->assertRedirect(route('login'));
    }

    public function test_non_pendonor_cannot_use_notification_routes(): void
    {
        $admin = $this->createAccount('ADMIN');

        $this->actingAs($admin)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->get(route('pendonor.pemberitahuan.show', 1))
            ->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('pendonor.pemberitahuan.read', 1))
            ->assertForbidden();
    }

    public function test_inactive_pendonor_is_blocked_on_all_notification_routes(): void
    {
        $pendonor = $this->createPendonor('NONAKTIF');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertRedirect(route('login'));
        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', 1))
            ->assertRedirect(route('login'));
        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', 1))
            ->assertRedirect(route('login'));
    }

    public function test_list_contains_only_authenticated_donors_notifications(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $milikSendiri = $this->createNotification($pendonor, $petugas, 'PESAN-MILIK-SENDIRI');
        $this->createNotification($pendonorLain, $petugas, 'PESAN-PENDONOR-LAIN');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSee('PESAN-MILIK-SENDIRI')
            ->assertDontSee('PESAN-PENDONOR-LAIN')
            ->assertViewHas('pemberitahuan', fn ($items) => $items->modelKeys() === [$milikSendiri->id_pemberitahuan]);
    }

    public function test_client_supplied_donor_id_cannot_switch_list_ownership(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createNotification($pendonor, $petugas, 'DATA-AKUN-TERAUTENTIKASI');
        $this->createNotification($pendonorLain, $petugas, 'DATA-ID-CLIENT');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index', [
                'id_pendonor' => $pendonorLain->id_pendonor,
            ]))
            ->assertOk()
            ->assertSee('DATA-AKUN-TERAUTENTIKASI')
            ->assertDontSee('DATA-ID-CLIENT');

        $this->actingAs($pendonor->akun)
            ->json('GET', route('pendonor.pemberitahuan.index'), [
                'id_pendonor' => $pendonorLain->id_pendonor,
            ])
            ->assertOk()
            ->assertSee('DATA-AKUN-TERAUTENTIKASI')
            ->assertDontSee('DATA-ID-CLIENT');
    }

    public function test_foreign_notification_cannot_be_viewed_or_marked_read(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $notification = $this->createNotification(
            $pendonorLain,
            $this->createPetugas(),
            'RAHASIA-PENDONOR-LAIN'
        );

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertNotFound()
            ->assertDontSee('RAHASIA-PENDONOR-LAIN');

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->waktu_dibaca);
    }

    public function test_nonexistent_notification_returns_not_found_for_detail_and_patch(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', 999999))
            ->assertNotFound();
        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', 999999))
            ->assertNotFound();
    }

    public function test_list_orders_by_created_time_descending(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createNotification($pendonor, $petugas, 'PESAN-LAMA', '2026-09-10 08:00:00');
        $this->createNotification($pendonor, $petugas, 'PESAN-TERBARU', '2026-09-12 08:00:00');

        $response = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSeeInOrder(['PESAN-TERBARU', 'PESAN-LAMA']);

        $this->assertSame(
            ['PESAN-TERBARU', 'PESAN-LAMA'],
            $response->viewData('pemberitahuan')->pluck('isi_pesan')->all()
        );
    }

    public function test_equal_created_times_use_descending_notification_id_tie_breaker(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $first = $this->createNotification($pendonor, $petugas, 'ID-LEBIH-KECIL');
        $second = $this->createNotification($pendonor, $petugas, 'ID-LEBIH-BESAR');

        $this->assertGreaterThan($first->id_pemberitahuan, $second->id_pemberitahuan);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSeeInOrder(['ID-LEBIH-BESAR', 'ID-LEBIH-KECIL']);
    }

    public function test_list_empty_state_read_labels_and_sender_are_displayed(): void
    {
        $emptyPendonor = $this->createPendonor();
        $this->actingAs($emptyPendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSee('Belum ada pemberitahuan.')
            ->assertDontSee('<table>', false);

        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas('Petugas Pengirim Uji');
        $this->createNotification($pendonor, $petugas, 'BELUM-DIBACA');
        $this->createNotification($pendonor, $petugas, 'SUDAH-DIBACA', waktuDibaca: '2026-09-14 09:00:00');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk()
            ->assertSee('Petugas Pengirim Uji')
            ->assertSee('Belum dibaca')
            ->assertSee('Sudah dibaca');
    }

    public function test_get_index_and_detail_are_read_only(): void
    {
        $pendonor = $this->createPendonor();
        $notification = $this->createNotification($pendonor, $this->createPetugas(), 'GET-TETAP-READ-ONLY');
        $before = $notification->getAttributes();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk();
        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertOk()
            ->assertSee('GET-TETAP-READ-ONLY')
            ->assertSee('Belum dibaca')
            ->assertSee('Tandai Sudah Dibaca');

        $this->assertEquals($before, $notification->fresh()->getAttributes());
        $this->assertNull($notification->fresh()->waktu_dibaca);
        $this->assertDatabaseCount('pemberitahuan', 1);
    }

    public function test_first_patch_sets_read_time_and_redirects_to_detail(): void
    {
        $pendonor = $this->createPendonor();
        $notification = $this->createNotification($pendonor, $this->createPetugas());
        $expectedTimestamp = now()->format('Y-m-d H:i:s');

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification))
            ->assertSessionHas('success', 'Pemberitahuan berhasil ditandai sebagai dibaca.');

        $this->assertSame($expectedTimestamp, $notification->fresh()->waktu_dibaca->format('Y-m-d H:i:s'));
    }

    public function test_patch_changes_no_other_notification_field_and_ignores_hostile_input(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $petugasLain = $this->createPetugas();
        $notification = $this->createNotification($pendonor, $petugas, 'PESAN-ASLI', '2026-09-10 08:00:00');

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', $notification), [
                'id_pendonor' => $pendonorLain->id_pendonor,
                'id_petugas_pengirim' => $petugasLain->id_petugas,
                'isi_pesan' => 'PESAN-DIUBAH-CLIENT',
                'waktu_dibuat' => '2030-01-01 00:00:00',
                'waktu_dibaca' => '2030-01-01 00:00:00',
            ])
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification));

        $fresh = $notification->fresh();
        $this->assertSame($pendonor->id_pendonor, $fresh->id_pendonor);
        $this->assertSame($petugas->id_petugas, $fresh->id_petugas_pengirim);
        $this->assertSame('PESAN-ASLI', $fresh->isi_pesan);
        $this->assertSame('2026-09-10 08:00:00', $fresh->waktu_dibuat->format('Y-m-d H:i:s'));
        $this->assertSame(now()->format('Y-m-d H:i:s'), $fresh->waktu_dibaca->format('Y-m-d H:i:s'));
    }

    public function test_repeated_patch_is_idempotent_and_preserves_first_timestamp(): void
    {
        $pendonor = $this->createPendonor();
        $notification = $this->createNotification($pendonor, $this->createPetugas());

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification));
        $firstTimestamp = $notification->fresh()->waktu_dibaca->format('Y-m-d H:i:s');

        $this->travel(2)->hours();

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', $notification))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $notification))
            ->assertSessionHas('success', 'Pemberitahuan sudah ditandai sebagai dibaca.');

        $this->assertSame($firstTimestamp, $notification->fresh()->waktu_dibaca->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('pemberitahuan', 1);
    }

    public function test_already_read_detail_is_safe_and_has_no_redundant_action(): void
    {
        $pendonor = $this->createPendonor();
        $notification = $this->createNotification(
            $pendonor,
            $this->createPetugas(),
            'PESAN-SUDAH-DIBACA',
            waktuDibaca: '2026-09-14 09:00:00'
        );

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertOk()
            ->assertSee('PESAN-SUDAH-DIBACA')
            ->assertSee('Pemberitahuan ini sudah dibaca.')
            ->assertDontSee('Tandai Sudah Dibaca')
            ->assertDontSee(route('pendonor.pemberitahuan.read', $notification), false);

        $this->assertSame('2026-09-14 09:00:00', $notification->fresh()->waktu_dibaca->format('Y-m-d H:i:s'));
    }

    public function test_mark_read_affects_only_targeted_owned_notification(): void
    {
        $pendonor = $this->createPendonor();
        $petugas = $this->createPetugas();
        $target = $this->createNotification($pendonor, $petugas, 'TARGET');
        $other = $this->createNotification($pendonor, $petugas, 'BUKAN-TARGET');

        $this->actingAs($pendonor->akun)
            ->patch(route('pendonor.pemberitahuan.read', $target))
            ->assertRedirect(route('pendonor.pemberitahuan.show', $target));

        $this->assertNotNull($target->fresh()->waktu_dibaca);
        $this->assertNull($other->fresh()->waktu_dibaca);
    }

    public function test_dashboard_unread_count_uses_only_authenticated_donors_rows(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createNotification($pendonor, $petugas, 'OWN-UNREAD-1');
        $this->createNotification($pendonor, $petugas, 'OWN-UNREAD-2');
        $this->createNotification($pendonor, $petugas, 'OWN-READ', waktuDibaca: '2026-09-14 09:00:00');
        $this->createNotification($pendonorLain, $petugas, 'FOREIGN-UNREAD');

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('2 belum dibaca.')
            ->assertViewHas('jumlahPemberitahuanBelumDibaca', 2);
    }

    public function test_dashboard_shows_maximum_three_latest_own_notifications_in_list_order(): void
    {
        $pendonor = $this->createPendonor();
        $pendonorLain = $this->createPendonor();
        $petugas = $this->createPetugas();
        $this->createNotification($pendonor, $petugas, 'OWN-1-OLDEST', '2026-09-10 08:00:00');
        $this->createNotification($pendonor, $petugas, 'OWN-2', '2026-09-11 08:00:00');
        $this->createNotification($pendonor, $petugas, 'OWN-3', '2026-09-12 08:00:00');
        $this->createNotification($pendonor, $petugas, 'OWN-4-NEWEST', '2026-09-13 08:00:00');
        $this->createNotification($pendonorLain, $petugas, 'FOREIGN-NEWEST', '2026-09-14 08:00:00');

        $dashboard = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSeeInOrder(['OWN-4-NEWEST', 'OWN-3', 'OWN-2'])
            ->assertDontSee('OWN-1-OLDEST')
            ->assertDontSee('FOREIGN-NEWEST');

        $list = $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.index'))
            ->assertOk();

        $dashboardIds = $dashboard->viewData('pemberitahuanTerbaru')->modelKeys();
        $listIds = $list->viewData('pemberitahuan')->take(3)->modelKeys();
        $this->assertCount(3, $dashboardIds);
        $this->assertSame($listIds, $dashboardIds);
    }

    public function test_dashboard_empty_state_and_real_notification_links_work(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee('Belum ada pemberitahuan.')
            ->assertSee('Lihat Pemberitahuan')
            ->assertSee(route('pendonor.pemberitahuan.index'), false);

        $notification = $this->createNotification($pendonor, $this->createPetugas());

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.home'))
            ->assertOk()
            ->assertSee(route('pendonor.pemberitahuan.show', $notification), false);

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertOk();
    }

    public function test_pendonor_ui_exposes_only_receiver_actions_and_no_external_channel(): void
    {
        $pendonor = $this->createPendonor();
        $notification = $this->createNotification($pendonor, $this->createPetugas());

        foreach ([
            route('pendonor.pemberitahuan.index'),
            route('pendonor.pemberitahuan.show', $notification),
        ] as $url) {
            $this->actingAs($pendonor->akun)
                ->get($url)
                ->assertOk()
                ->assertDontSee('Kirim Pemberitahuan')
                ->assertDontSee('Edit Pemberitahuan')
                ->assertDontSee('Hapus Pemberitahuan')
                ->assertDontSee('Tandai Belum Dibaca')
                ->assertDontSee('Tandai Semua')
                ->assertDontSee('WhatsApp')
                ->assertDontSee('SMS')
                ->assertDontSee('Email');
        }
    }

    public function test_pendonor_cannot_create_notifications(): void
    {
        $pendonor = $this->createPendonor();

        $this->actingAs($pendonor->akun)
            ->post(route('pendonor.pemberitahuan.index'), [
                'isi_pesan' => 'CLIENT-TIDAK-BOLEH-MEMBUAT',
            ])
            ->assertMethodNotAllowed();

        $this->assertDatabaseCount('pemberitahuan', 0);
        $this->assertFalse(Route::has('pendonor.pemberitahuan.store'));
        $this->assertTrue(Route::has('petugas.pemberitahuan.store'));
    }

    public function test_visible_mark_read_form_uses_existing_patch_route(): void
    {
        $pendonor = $this->createPendonor();
        $notification = $this->createNotification($pendonor, $this->createPetugas());
        $action = route('pendonor.pemberitahuan.read', $notification);

        $this->assertTrue(Route::has('pendonor.pemberitahuan.read'));

        $this->actingAs($pendonor->akun)
            ->get(route('pendonor.pemberitahuan.show', $notification))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee($action, false);
    }

    private function createAccount(string $role = 'PENDONOR', string $status = 'AKTIF'): Akun
    {
        $number = ++$this->sequence;

        return Akun::create([
            'email' => "phase7i-user{$number}@example.test",
            'password_hash' => 'test-password-hash',
            'peran' => $role,
            'status_akun' => $status,
        ]);
    }

    private function createPendonor(string $accountStatus = 'AKTIF'): Pendonor
    {
        $akun = $this->createAccount('PENDONOR', $accountStatus);
        $number = $this->sequence;

        return Pendonor::create([
            'id_akun' => $akun->id_akun,
            'id_golongan_darah' => null,
            'nik' => str_pad((string) $number, 16, '0', STR_PAD_LEFT),
            'nomor_donor' => null,
            'nama_lengkap' => "Pendonor {$number}",
            'jenis_kelamin' => 'LAKI_LAKI',
            'tanggal_lahir' => '1995-01-01',
            'tempat_lahir' => 'Jakarta',
            'alamat' => 'Alamat pengujian',
            'nomor_telepon' => '0817'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'pekerjaan' => null,
            'alamat_kantor' => null,
        ]);
    }

    private function createPetugas(?string $name = null): Petugas
    {
        $akun = $this->createAccount('PETUGAS');
        $number = $this->sequence;

        return Petugas::create([
            'id_akun' => $akun->id_akun,
            'nomor_petugas' => "P7I-{$number}",
            'nama_petugas' => $name ?? "Petugas {$number}",
        ]);
    }

    private function createNotification(
        Pendonor $pendonor,
        Petugas $petugas,
        string $message = 'Pesan pemberitahuan pengujian',
        string $waktuDibuat = '2026-09-12 08:00:00',
        ?string $waktuDibaca = null
    ): Pemberitahuan {
        return Pemberitahuan::create([
            'id_pendonor' => $pendonor->id_pendonor,
            'id_petugas_pengirim' => $petugas->id_petugas,
            'isi_pesan' => $message,
            'waktu_dibuat' => $waktuDibuat,
            'waktu_dibaca' => $waktuDibaca,
        ]);
    }
}

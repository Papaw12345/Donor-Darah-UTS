<?php
namespace Tests\Feature;

use App\Http\Controllers\PetugasUnitKomponenController;
use App\Models\{Akun,GolonganDarah,JadwalPelayanan,JenisKomponenDarah,PemesananDonor,Pendonor,Penyumbangan,Petugas,SeleksiDonor,UnitKomponenDarah};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase8FUnitKomponenPetugasTest extends TestCase
{
    use RefreshDatabase;
    private int $n = 0;

    public function test_routes_access_profile_and_failed_donation_are_guarded(): void
    {
        $get = Route::getRoutes()->getByName('petugas.unit-komponen.show'); $post = Route::getRoutes()->getByName('petugas.unit-komponen.store');
        $this->assertSame('petugas/penyumbangan/{penyumbangan}/unit-komponen', $get->uri()); $this->assertSame(['GET', 'HEAD'], $get->methods());
        $this->assertSame(PetugasUnitKomponenController::class.'@show', $get->getActionName()); $this->assertSame(['POST'], $post->methods());
        foreach ([$get, $post] as $route) foreach (['web','auth','active','role:PETUGAS'] as $middleware) $this->assertContains($middleware, $route->gatherMiddleware());
        $donation = $this->donation(); $this->get(route('petugas.unit-komponen.show', $donation))->assertRedirect(route('login'));
        $noProfile = $this->account('PETUGAS'); $count = Petugas::count(); $this->actingAs($noProfile)->get(route('petugas.unit-komponen.show',$donation))->assertStatus(409); $this->assertSame($count, Petugas::count());
        foreach (['ADMIN','PENDONOR'] as $role) $this->actingAs($this->account($role))->get(route('petugas.unit-komponen.show',$donation))->assertForbidden();
        $petugas=$this->petugas(); $this->masters(); $failed=$this->donation('GAGAL'); $this->actingAs($petugas->akun)->get(route('petugas.unit-komponen.show',$failed))->assertStatus(409);
        $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$failed),$this->payload())->assertStatus(409); $this->assertDatabaseCount('unit_komponen_darah',0);
    }

    public function test_successful_historical_donation_is_read_only_on_get_and_creates_server_owned_unit(): void
    {
        $petugas=$this->petugas(); $donation=$this->donation('BERHASIL', 'SELESAI', ['tanggal'=>'2020-01-01','status_jadwal'=>'DIBATALKAN']); $before=$donation->getAttributes();
        $other=$this->petugas(); $master=$this->masters();
        $this->actingAs($petugas->akun)->get(route('petugas.unit-komponen.show',$donation))->assertOk()->assertSee('Form Unit Komponen')->assertSee('WB')->assertSee('PRC')->assertSee('TC')->assertSee('FFP');
        $this->assertEquals($before,$donation->fresh()->getAttributes());
        $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$donation),array_merge($this->payload($master[0],$master[4],['nomor_unit'=>'  UNIT-001  ','id_penyumbangan'=>999,'id_petugas_pencatat'=>$other->id_petugas,'status_unit'=>'TERSEDIA','id_petugas_pelulus'=>$other->id_petugas,'waktu_pelulusan'=>'2026-01-01 00:00:00','waktu_distribusi'=>'2026-01-01 00:00:00'])))->assertRedirect(route('petugas.unit-komponen.show',$donation));
        $unit=UnitKomponenDarah::sole(); $this->assertSame('UNIT-001',$unit->nomor_unit); $this->assertSame($donation->id_penyumbangan,$unit->id_penyumbangan); $this->assertSame($petugas->id_petugas,$unit->id_petugas_pencatat); $this->assertSame('MENUNGGU_PELULUSAN',$unit->status_unit); $this->assertNull($unit->id_petugas_pelulus); $this->assertNull($unit->waktu_pelulusan); $this->assertNull($unit->catatan_pelulusan); $this->assertNull($unit->waktu_distribusi); $this->assertEquals($before,$donation->fresh()->getAttributes());
        $this->actingAs($petugas->akun)->get(route('petugas.penyumbangan.show',$donation->seleksiDonor))->assertSee('Unit Komponen Darah');
    }

    public function test_shape_master_dates_duplicates_and_multiple_units_are_enforced(): void
    {
        $petugas=$this->petugas(); $donation=$this->donation(); [$wb,$prc,$tc,$ffp,$blood,$outside]=$this->masters(true);
        foreach ([['nomor_unit'=>'   '],['nomor_unit'=>str_repeat('x',51)],['id_jenis_komponen'=>999],['id_jenis_komponen'=>$outside->id_jenis_komponen],['id_golongan_darah'=>999],['tanggal_pembuatan'=>'bad'],['tanggal_kedaluwarsa'=>'bad']] as $bad) $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$donation),$this->payload($wb,$blood,$bad))->assertSessionHasErrors();
        $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$donation),$this->payload($wb,$blood,['nomor_unit'=>'A','tanggal_pembuatan'=>'2026-12-31','tanggal_kedaluwarsa'=>'2026-01-01']))->assertSessionHasNoErrors();
        $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$donation),$this->payload($wb,$blood,['nomor_unit'=>'A']))->assertSessionHasErrors('nomor_unit');
        $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$donation),$this->payload($wb,$blood,['nomor_unit'=>'B']))->assertSessionHasNoErrors();
        $second=$this->donation(); $this->actingAs($petugas->akun)->post(route('petugas.unit-komponen.store',$second),$this->payload($prc,$blood,['nomor_unit'=>'A']))->assertSessionHasErrors('nomor_unit');
        $this->assertDatabaseCount('unit_komponen_darah',2); $this->assertSame(['A','B'],UnitKomponenDarah::orderBy('id_unit')->pluck('nomor_unit')->all());
    }

    private function payload(JenisKomponenDarah $jenis=null, GolonganDarah $golongan=null, array $over=[]): array { [$jenis,$golongan]=[$jenis??JenisKomponenDarah::first(),$golongan??GolonganDarah::first()]; return array_merge(['nomor_unit'=>'UNIT-'.$this->n,'id_jenis_komponen'=>$jenis->id_jenis_komponen,'id_golongan_darah'=>$golongan->id_golongan_darah,'tanggal_pembuatan'=>'2026-01-01','tanggal_kedaluwarsa'=>'2025-01-01'],$over); }
    private function account($role='PENDONOR'): Akun { $i=++$this->n; return Akun::create(['email'=>"p8f{$i}@x.test",'password_hash'=>'x','peran'=>$role,'status_akun'=>'AKTIF']); }
    private function petugas(): Petugas { $a=$this->account('PETUGAS'); return Petugas::create(['id_akun'=>$a->id_akun,'nomor_petugas'=>'P'.$this->n,'nama_petugas'=>'Petugas '.$this->n]); }
    private function donation($hasil='BERHASIL',$status='SELESAI',$schedule=[]): Penyumbangan { $a=$this->account(); $d=Pendonor::create(['id_akun'=>$a->id_akun,'id_golongan_darah'=>null,'nik'=>str_pad((string)$this->n,16,'0',STR_PAD_LEFT),'nomor_donor'=>null,'nama_lengkap'=>'Donor '.$this->n,'jenis_kelamin'=>'LAKI_LAKI','tanggal_lahir'=>'1990-01-01','tempat_lahir'=>'Jakarta','alamat'=>'x','nomor_telepon'=>'081'.str_pad((string)$this->n,9,'0',STR_PAD_LEFT),'pekerjaan'=>null,'alamat_kantor'=>null]); $j=JadwalPelayanan::create(array_merge(['tanggal'=>'2026-01-01','jam_mulai'=>'08:00','jam_selesai'=>'10:00','kapasitas'=>2,'status_jadwal'=>'DIBUKA'],$schedule)); $b=PemesananDonor::create(['id_pendonor'=>$d->id_pendonor,'id_jadwal'=>$j->id_jadwal,'waktu_pemesanan'=>'2026-01-01 07:00:00','kode_checkin'=>null,'waktu_checkin'=>'2026-01-01 08:00:00','status_pemesanan'=>$status]); $p=$this->petugas(); $s=SeleksiDonor::create(['id_pemesanan'=>$b->id_pemesanan,'id_petugas'=>$p->id_petugas,'waktu_seleksi'=>'2026-01-01 08:30:00','berat_badan'=>60,'tekanan_sistolik'=>120,'tekanan_diastolik'=>80,'denyut_nadi'=>72,'suhu_tubuh'=>36.5,'kadar_hb'=>13.5,'hasil_pemeriksaan_kesehatan'=>null,'keputusan_seleksi'=>'LAYAK','alasan_keputusan'=>null]); return Penyumbangan::create(['id_seleksi'=>$s->id_seleksi,'id_petugas_pencatat'=>$p->id_petugas,'waktu_pengambilan'=>'2026-01-01 09:00:00','volume_ml'=>350,'hasil_penyumbangan'=>$hasil,'alasan_gagal'=>null]); }
    private function masters($outside=false): array { $c=[]; foreach ([['WB','Whole Blood'],['PRC','Packed'],['TC','Thrombocyte'],['FFP','Fresh'],['XYZ','Other']] as [$k,$n]) $c[]=JenisKomponenDarah::create(['kode_komponen'=>$k,'nama_komponen'=>$n]); $g=GolonganDarah::create(['abo'=>'A','rhesus'=>'POSITIF']); return $outside?[$c[0],$c[1],$c[2],$c[3],$g,$c[4]]:[$c[0],$c[1],$c[2],$c[3],$g]; }
}

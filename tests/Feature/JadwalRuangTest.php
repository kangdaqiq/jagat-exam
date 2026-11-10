<?php

namespace Tests\Feature;

use App\Livewire\Pengawas\PortalPengawas;
use App\Models\BankSoal;
use App\Models\JadwalRuang;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalRuangTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $guru1 = User::create([
            'name' => 'Guru Pengawas 1',
            'username' => 'guru1',
            'email' => 'guru1@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
        ]);

        $guru2 = User::create([
            'name' => 'Guru Pengawas 2',
            'username' => 'guru2',
            'email' => 'guru2@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
        ]);

        $ruang = RuangUjian::create([
            'kode_ruang' => 'R-01',
            'nama_ruang' => 'Laboratorium Komputer 1',
            'kapasitas' => 30,
            'pengawas_id' => $guru1->id,
            'is_active' => true,
        ]);

        $mapel = Mapel::create([
            'nama_mapel' => 'Matematika Wajib',
            'kode_mapel' => 'MTK',
            'tingkat' => 'X',
        ]);

        $bankSoal = BankSoal::create([
            'user_id' => $guru1->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Bank Soal MTK X',
            'kode_bank' => 'BS-MTK-X',
            'durasi_menit' => 60,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);

        $ujian = Ujian::create([
            'nama_ujian' => 'Penilaian Harian Matematika',
            'mapel_id' => $mapel->id,
            'bank_soal_id' => $bankSoal->id,
            'guru_id' => $guru1->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'durasi_menit' => 60,
            'status' => 'SCHEDULED',
        ]);

        return [$admin, $guru1, $guru2, $ruang, $ujian];
    }

    public function test_dapat_membuat_jadwal_ruang_dan_relasi_terhubung(): void
    {
        [$admin, $guru1, $guru2, $ruang, $ujian] = $this->setupData();

        $jadwal = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id, // Pengawas silang
            'pengawas_pendamping_id' => $guru1->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'status' => 'SCHEDULED',
            'catatan' => 'Sesi pagi pengawas silang.',
        ]);

        $this->assertDatabaseHas('jadwal_ruangs', [
            'id' => $jadwal->id,
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'sesi_ke' => 1,
        ]);

        $this->assertEquals('Laboratorium Komputer 1', $jadwal->ruangUjian->nama_ruang);
        $this->assertEquals('Penilaian Harian Matematika', $jadwal->ujian->nama_ujian);
        $this->assertEquals('Guru Pengawas 2', $jadwal->pengawas->name);
        $this->assertEquals('Guru Pengawas 1', $jadwal->pengawasPendamping->name);
    }

    public function test_rilis_token_jadwal_ruang_berjalan_dan_aktif_selama_10_menit(): void
    {
        [$admin, $guru1, $guru2, $ruang, $ujian] = $this->setupData();

        $jadwal = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'status' => 'SCHEDULED',
        ]);

        $this->assertFalse($jadwal->isTokenActive());

        $token = $jadwal->rilisToken();

        $this->assertEquals(6, strlen($token));
        $this->assertEquals(strtoupper($token), $token);
        $this->assertEquals('ONGOING', $jadwal->status);
        $this->assertTrue($jadwal->isTokenActive());
        $this->assertFalse($jadwal->isTokenExpired());

        // Token juga tersinkron ke RuangUjian
        $ruang->refresh();
        $this->assertEquals($token, $ruang->token_ruang);

        // Simulasi 11 menit kemudian (expired)
        $jadwal->token_generated_at = now()->subMinutes(11);
        $jadwal->save();

        $this->assertFalse($jadwal->isTokenActive());
        $this->assertTrue($jadwal->isTokenExpired());
    }

    public function test_scope_hari_ini_dan_scope_for_user_bekerja_dengan_benar(): void
    {
        [$admin, $guru1, $guru2, $ruang, $ujian] = $this->setupData();

        // Jadwal Hari Ini untuk Guru 2
        $jadwalHariIni = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'status' => 'SCHEDULED',
        ]);

        // Jadwal Kemarin untuk Guru 2
        $jadwalKemarin = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => now()->subDay()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'status' => 'COMPLETED',
        ]);

        // Cek scopeHariIni
        $hariIniCount = JadwalRuang::hariIni()->count();
        $this->assertEquals(1, $hariIniCount);

        // Cek scopeForUser untuk guru2 vs guru1
        $tugasGuru2 = JadwalRuang::hariIni()->forUser($guru2)->get();
        $this->assertCount(1, $tugasGuru2);
        $this->assertEquals($jadwalHariIni->id, $tugasGuru2->first()->id);

        $tugasGuru1 = JadwalRuang::hariIni()->forUser($guru1)->get();
        $this->assertCount(0, $tugasGuru1);

        // Admin dapat melihat semua
        $tugasAdmin = JadwalRuang::hariIni()->forUser($admin)->get();
        $this->assertCount(1, $tugasAdmin);
    }

    public function test_portal_pengawas_menampilkan_tugas_jadwal_dan_dapat_merilis_token(): void
    {
        [$admin, $guru1, $guru2, $ruang, $ujian] = $this->setupData();

        $jadwal = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'status' => 'SCHEDULED',
        ]);

        $this->actingAs($guru2);

        Livewire::test(PortalPengawas::class)
            ->assertSee($ruang->nama_ruang)
            ->call('rilisTokenJadwal', $jadwal->id)
            ->assertSet('selectedRuangId', $ruang->id);

        $jadwal->refresh();
        $this->assertNotNull($jadwal->token_ruang);
        $this->assertEquals('ONGOING', $jadwal->status);
    }

    public function test_portal_pengawas_memfilter_ruangan_sesuai_penugasan_pengawas(): void
    {
        [$admin, $guru1, $guru2, $ruang1, $ujian] = $this->setupData();

        $ruang2 = RuangUjian::create(['nama_ruang' => 'Ruang 2', 'kapasitas' => 30, 'is_active' => true]);
        $ruang3 = RuangUjian::create(['nama_ruang' => 'Ruang 3', 'kapasitas' => 30, 'is_active' => true]);

        // Guru 2 hanya ditugaskan di Ruang 1 dan Ruang 2
        JadwalRuang::create([
            'ruang_ujian_id' => $ruang1->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'status' => 'SCHEDULED',
        ]);

        JadwalRuang::create([
            'ruang_ujian_id' => $ruang2->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '10:00:00',
            'waktu_selesai' => '12:00:00',
            'status' => 'SCHEDULED',
        ]);

        // Ruang 3 ditugaskan ke Guru 1
        JadwalRuang::create([
            'ruang_ujian_id' => $ruang3->id,
            'ujian_id' => $ujian->id,
            'pengawas_id' => $guru1->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '10:00:00',
            'waktu_selesai' => '12:00:00',
            'status' => 'SCHEDULED',
        ]);

        $this->actingAs($guru2);

        // Secara default scopeRuang = 'saya', hanya Ruang 1 dan Ruang 2 yang muncul
        Livewire::test(PortalPengawas::class)
            ->assertSee($ruang1->nama_ruang)
            ->assertSee($ruang2->nama_ruang)
            ->assertDontSee($ruang3->nama_ruang)
            ->assertSee($guru2->name)
            ->assertDontSee('Semua Pengawas')
            ->assertSee('07:30 - 09:30')
            ->assertSee('10:00 - 12:00');
    }
}

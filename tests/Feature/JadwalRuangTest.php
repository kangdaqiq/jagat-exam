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
use Carbon\Carbon;
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

    public function test_satu_jadwal_ruang_bisa_untuk_banyak_agenda_lintas_kelas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 07:45:00'));

        [$admin, $guru1, $guru2, $ruang, $ujianX] = $this->setupData();

        $mapel2 = Mapel::create(['nama_mapel' => 'Bahasa Inggris', 'kode_mapel' => 'BING', 'tingkat' => 'XI']);
        $bankSoal2 = BankSoal::create([
            'user_id' => $guru1->id,
            'mapel_id' => $mapel2->id,
            'judul' => 'Bank Soal BING XI',
            'kode_bank' => 'BS-BING-XI',
            'durasi_menit' => 60,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujianXI = Ujian::create([
            'nama_ujian' => 'TEST H1 S1 K11',
            'mapel_id' => $mapel2->id,
            'bank_soal_id' => $bankSoal2->id,
            'guru_id' => $guru1->id,
            'tanggal' => '2026-11-12',
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'durasi_menit' => 90,
            'status' => 'SCHEDULED',
        ]);

        $jadwal = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujianX->id,
            'pengawas_id' => $guru2->id,
            'tanggal' => '2026-11-12',
            'sesi_ke' => 1,
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'status' => 'SCHEDULED',
        ]);
        $jadwal->ujians()->sync([$ujianX->id, $ujianXI->id]);

        $this->assertTrue($ujianX->fresh()->hasJadwalRuang());
        $this->assertTrue($ujianXI->fresh()->hasJadwalRuang());
        $this->assertStringContainsString('Penilaian Harian Matematika', $jadwal->fresh()->daftar_nama_ujian);
        $this->assertStringContainsString('TEST H1 S1 K11', $jadwal->fresh()->daftar_nama_ujian);

        $jadwal->rilisToken($guru2->id);

        $this->assertEquals('ONGOING', $ujianX->fresh()->status);
        $this->assertEquals('ONGOING', $ujianXI->fresh()->status);
        $this->assertEquals('ONGOING', $jadwal->fresh()->status);
        $this->assertNotNull($jadwal->fresh()->token_ruang);

        Carbon::setTestNow();
    }

    public function test_siswa_subtest_berurutan_lanjut_tanpa_token_ulang(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 12, 8, 0, 0));

        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $pengawas = User::factory()->create(['role' => 'guru', 'name' => 'Pengawas Sesi', 'is_active' => true]);
        $kelasX = Kelas::create(['nama_kelas' => 'X RPL 1', 'tingkat' => '10', 'is_active' => true]);
        $ruang = RuangUjian::create(['nama_ruang' => 'Ruang Subtest', 'kapasitas' => 30, 'is_active' => true]);

        $userSiswa = User::factory()->create(['role' => 'siswa', 'email' => 'subtest1@siswa.cbt', 'is_active' => true]);
        $siswa = \App\Models\Siswa::create([
            'user_id' => $userSiswa->id,
            'kelas_id' => $kelasX->id,
            'ruang_ujian_id' => $ruang->id,
            'nis' => '20001',
            'nama' => 'Siswa Subtest',
            'is_active' => true,
        ]);

        $mapel1 = \App\Models\Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK-10', 'tingkat' => '10']);
        $mapel2 = \App\Models\Mapel::create(['nama_mapel' => 'Bahasa Inggris', 'kode_mapel' => 'ING-10', 'tingkat' => '10']);

        $bank1 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel1->id,
            'judul' => 'Bank MTK 10',
            'kode_bank' => 'BS-MTK-10',
            'durasi_menit' => 60,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        \App\Models\Soal::create([
            'bank_soal_id' => $bank1->id,
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => '1 + 1 = ?',
            'bobot_nilai' => 100,
        ]);

        $bank2 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel2->id,
            'judul' => 'Bank ING 10',
            'kode_bank' => 'BS-ING-10',
            'durasi_menit' => 45,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        \App\Models\Soal::create([
            'bank_soal_id' => $bank2->id,
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Translate: Hello',
            'bobot_nilai' => 100,
        ]);

        $ujian1 = Ujian::create([
            'nama_ujian' => 'Subtest 1 Matematika',
            'mapel_id' => $mapel1->id,
            'bank_soal_id' => $bank1->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'durasi_menit' => 60,
            'status' => 'SCHEDULED',
        ]);
        $ujian1->kelas()->attach($kelasX->id);

        $ujian2 = Ujian::create([
            'nama_ujian' => 'Subtest 2 Bahasa Inggris',
            'mapel_id' => $mapel2->id,
            'bank_soal_id' => $bank2->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'durasi_menit' => 45,
            'status' => 'SCHEDULED',
        ]);
        $ujian2->kelas()->attach($kelasX->id);

        $jadwal = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian1->id,
            'pengawas_id' => $pengawas->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'status' => 'SCHEDULED',
        ]);
        $jadwal->syncOrderedUjians([$ujian1->id, $ujian2->id]);

        $token = $jadwal->rilisToken($pengawas->id);

        // 1. Siswa masuk sekali lewat PortalUjian dengan token ruangan
        $this->actingAs($userSiswa);
        Livewire::test(\App\Livewire\Siswa\PortalUjian::class)
            ->set('token_input', $token)
            ->call('submitToken')
            ->assertHasNoErrors();

        $sesi1 = \App\Models\UjianSesi::where('user_id', $userSiswa->id)
            ->where('ujian_id', $ujian1->id)
            ->first();
        $this->assertNotNull($sesi1);
        $this->assertEquals($jadwal->id, $sesi1->jadwal_ruang_id);

        // 2. Siswa menyelesaikan Subtest 1, muncul laman konfirmasi menuju Subtest 2 tanpa input token
        $runner = Livewire::test(\App\Livewire\ExamRunner::class, ['token_sesi' => $sesi1->token_sesi])
            ->call('submitUjianFinal')
            ->assertSet('is_submitted', true)
            ->assertSee('Subtest 2 Bahasa Inggris')
            ->assertSee('Konfirmasi');

        $nextInfo = $runner->get('nextSubtestInfo');
        $this->assertNotNull($nextInfo);
        $this->assertEquals($ujian2->id, $nextInfo['ujian']->id);

        // 3. Klik lanjut ke subtest berikutnya -> sesi Subtest 2 dibuat & langsung redirect
        $runner->call('mulaiSubtestBerikutnya')
            ->assertRedirect();

        $sesi2 = \App\Models\UjianSesi::where('user_id', $userSiswa->id)
            ->where('ujian_id', $ujian2->id)
            ->first();
        $this->assertNotNull($sesi2);
        $this->assertEquals($jadwal->id, $sesi2->jadwal_ruang_id);
        $this->assertEquals('mengerjakan', $sesi2->status);

        // Rekap per soal tetap terpisah di UjianPeserta
        $this->assertEquals(1, \App\Models\UjianPeserta::where('ujian_id', $ujian1->id)->where('siswa_id', $siswa->id)->where('status', 'SELESAI')->count());
        $this->assertEquals(1, \App\Models\UjianPeserta::where('ujian_id', $ujian2->id)->where('siswa_id', $siswa->id)->where('status', 'SEDANG_MENGERJAKAN')->count());

        Carbon::setTestNow();
    }

    public function test_riwayat_sesi_ruang_dan_bap_persisten_dapat_diakses_kapanpun(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 12, 10, 30, 0));

        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $pengawas = User::factory()->create(['role' => 'guru', 'name' => 'Pengawas Tetap', 'is_active' => true]);
        $kelasX = Kelas::create(['nama_kelas' => 'X TBSM', 'tingkat' => '10', 'is_active' => true]);
        $ruang = RuangUjian::create(['nama_ruang' => 'Ruang Sejarah', 'kode_ruang' => 'R-SJ', 'kapasitas' => 30, 'is_active' => true]);

        $userSiswa1 = User::factory()->create(['role' => 'siswa', 'email' => 'h1@siswa.cbt', 'is_active' => true]);
        $siswa1 = \App\Models\Siswa::create([
            'user_id' => $userSiswa1->id,
            'kelas_id' => $kelasX->id,
            'ruang_ujian_id' => $ruang->id,
            'nis' => '30001',
            'nama' => 'Siswa Hadir Satu',
            'is_active' => true,
        ]);

        $userSiswa2 = User::factory()->create(['role' => 'siswa', 'email' => 'h2@siswa.cbt', 'is_active' => true]);
        $siswa2 = \App\Models\Siswa::create([
            'user_id' => $userSiswa2->id,
            'kelas_id' => $kelasX->id,
            'ruang_ujian_id' => $ruang->id,
            'nis' => '30002',
            'nama' => 'Siswa Absen Dua',
            'is_active' => true,
        ]);

        $mapel = \App\Models\Mapel::create(['nama_mapel' => 'Sejarah Umum', 'kode_mapel' => 'SEJ-10', 'tingkat' => '10']);
        $bank1 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Bank Sesi 1',
            'kode_bank' => 'BS-S1',
            'durasi_menit' => 60,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $bank2 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Bank Sesi 2',
            'kode_bank' => 'BS-S2',
            'durasi_menit' => 60,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);

        $ujian1 = Ujian::create([
            'nama_ujian' => 'Ujian Sesi Pagi',
            'mapel_id' => $mapel->id,
            'bank_soal_id' => $bank1->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'durasi_menit' => 60,
            'status' => 'COMPLETED',
        ]);
        $ujian1->kelas()->attach($kelasX->id);

        $ujian2 = Ujian::create([
            'nama_ujian' => 'Ujian Sesi Siang',
            'mapel_id' => $mapel->id,
            'bank_soal_id' => $bank2->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00:00',
            'waktu_selesai' => '12:00:00',
            'durasi_menit' => 60,
            'status' => 'SCHEDULED',
        ]);
        $ujian2->kelas()->attach($kelasX->id);

        $sesi1 = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian1->id,
            'pengawas_id' => $pengawas->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:30:00',
            'token_ruang' => 'SESI01',
            'status' => 'COMPLETED',
        ]);
        $sesi1->syncOrderedUjians([$ujian1->id]);

        // Siswa 1 hadir di Sesi 1 (UjianPeserta SELESAI + UjianSesi), Siswa 2 tidak hadir
        \App\Models\UjianPeserta::create([
            'ujian_id' => $ujian1->id,
            'siswa_id' => $siswa1->id,
            'user_id' => $userSiswa1->id,
            'status' => 'SELESAI',
            'waktu_mulai' => now()->subHours(3),
            'waktu_selesai' => now()->subHours(2),
            'nilai_total' => 90,
        ]);
        \App\Models\UjianSesi::create([
            'ujian_id' => $ujian1->id,
            'jadwal_ruang_id' => $sesi1->id,
            'ruang_ujian_id' => $ruang->id,
            'bank_soal_id' => $bank1->id,
            'user_id' => $userSiswa1->id,
            'nama_peserta' => $siswa1->nama,
            'nis' => $siswa1->nis,
            'token_sesi' => 'S1-' . $siswa1->id,
            'status' => 'selesai',
            'waktu_mulai' => now()->subHours(3),
            'waktu_selesai' => now()->subHours(2),
            'nilai_akhir' => 90,
        ]);

        $sesi2 = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'ujian_id' => $ujian2->id,
            'pengawas_id' => $pengawas->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 2,
            'waktu_mulai' => '10:00:00',
            'waktu_selesai' => '12:00:00',
            'status' => 'SCHEDULED',
        ]);
        $sesi2->syncOrderedUjians([$ujian2->id]);

        $this->actingAs($pengawas);

        // Portal Pengawas tetap menampilkan Sesi 1 (riwayat selesai) maupun Sesi 2 (aktif/dijadwalkan)
        $portal = Livewire::test(PortalPengawas::class)
            ->assertSee('07:30 - 09:30')
            ->assertSee('10:00 - 12:00')
            ->set('filterSesiRuang', 'selesai')
            ->assertSee('07:30 - 09:30')
            ->assertDontSee('10:00 - 12:00')
            ->call('pilihJadwalRuang', $sesi1->id)
            ->call('bukaModalBap', $sesi1->id)
            ->assertSet('bapForm.jumlah_peserta_seharusnya', 2)
            ->assertSet('bapForm.jumlah_hadir', 1)
            ->assertSet('bapForm.jumlah_tidak_hadir', 1)
            ->set('bapForm.catatan', 'Sesi 1 selesai tertib dan aman.')
            ->call('simpanBap');

        $bap = \App\Models\BeritaAcara::where('jadwal_ruang_id', $sesi1->id)->first();
        $this->assertNotNull($bap);
        $this->assertEquals(1, $bap->jumlah_hadir);
        $this->assertEquals(1, $bap->jumlah_tidak_hadir);
        $this->assertEquals('Sesi 1 selesai tertib dan aman.', $bap->catatan);

        // Daftar Hadir & BAP tetap bisa dicetak kapanpun untuk Sesi 1
        $this->get(route('daftar-hadir.print', [
            'ruang_ujian_id' => $ruang->id,
            'jadwal_ruang_id' => $sesi1->id,
        ]))
            ->assertOk()
            ->assertSee('Siswa Hadir Satu')
            ->assertSee('Siswa Absen Dua');

        $this->get(route('berita-acara.print', [
            'ruang_ujian_id' => $ruang->id,
            'jadwal_ruang_id' => $sesi1->id,
        ]))
            ->assertOk()
            ->assertSee('Sesi 1 selesai tertib dan aman.');

        Carbon::setTestNow();
    }
}

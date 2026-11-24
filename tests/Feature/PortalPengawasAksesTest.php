<?php

namespace Tests\Feature;

use App\Livewire\Pengawas\PortalPengawas;
use App\Models\BankSoal;
use App\Models\JadwalRuang;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\RuangUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPeserta;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalPengawasAksesTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin_cbt',
            'email' => 'admin@cbt.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $guru = User::create([
            'name' => 'Guru Pengawas',
            'username' => 'guru_pengawas',
            'email' => 'guru@cbt.test',
            'password' => bcrypt('password'),
            'role' => 'guru',
        ]);

        $ruang = RuangUjian::create(['nama_ruang' => 'Ruang 1', 'kapasitas' => 30]);
        $kelas = Kelas::create(['nama_kelas' => 'XII TB', 'tingkat' => 'XII', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BIN', 'tingkat' => 'XII']);

        $bank = BankSoal::create([
            'user_id' => $admin->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Bank XII',
            'kode_bank' => 'BS-XII',
            'durasi_menit' => 60,
            'is_active' => true,
        ]);

        $ujian = Ujian::create([
            'nama_ujian' => 'Ujian Bahasa Indonesia',
            'mapel_id' => $mapel->id,
            'bank_soal_id' => $bank->id,
            'guru_id' => $admin->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '23:59:00',
            'durasi_menit' => 60,
            'status' => 'ONGOING',
        ]);
        $ujian->kelas()->attach($kelas->id);

        $jadwalRuang = JadwalRuang::create([
            'ruang_ujian_id' => $ruang->id,
            'pengawas_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'sesi_ke' => 1,
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '23:59:00',
            'status' => 'ACTIVE',
        ]);

        $siswaUser = User::create([
            'name' => 'Budi Santoso',
            'username' => '2401001',
            'email' => 'budi@siswa.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);

        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'kelas_id' => $kelas->id,
            'ruang_ujian_id' => $ruang->id,
            'nama' => 'Budi Santoso',
            'nis' => '2401001',
            'sesi' => 1,
        ]);

        $peserta = UjianPeserta::create([
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswa->id,
            'user_id' => $siswaUser->id,
            'status' => 'SEDANG_MENGERJAKAN',
        ]);

        $sesi = UjianSesi::create([
            'ujian_id' => $ujian->id,
            'jadwal_ruang_id' => $jadwalRuang->id,
            'ruang_ujian_id' => $ruang->id,
            'bank_soal_id' => $bank->id,
            'user_id' => $siswaUser->id,
            'nama_peserta' => $siswa->nama,
            'nis' => $siswa->nis,
            'token_sesi' => 'TOKEN123',
            'ujian_peserta_id' => $peserta->id,
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addMinutes(60),
            'durasi_menit' => 60,
            'status' => 'mengerjakan',
        ]);

        $peserta->update(['ujian_sesi_id' => $sesi->id]);

        return compact('admin', 'guru', 'ruang', 'kelas', 'ujian', 'jadwalRuang', 'siswa', 'peserta', 'sesi');
    }

    public function test_guru_pengawas_hanya_bisa_reset_login_dan_lihat_jawaban(): void
    {
        $data = $this->setupData();

        $this->actingAs($data['guru']);

        $test = Livewire::test(PortalPengawas::class)
            ->call('pilihJadwalRuang', $data['jadwalRuang']->id);

        // Pastikan tombol aksi admin di-hidden untuk guru
        $test->assertDontSee('Tandai Sesi Selesai');
        $test->assertDontSee('Reset Ujian');
        $test->assertDontSee('+10m Waktu');
        $test->assertDontSee('Selesaikan'); // Tombol paksa selesai

        // Pastikan tombol yang diizinkan untuk guru tetap muncul
        $test->assertSee('Reset Login');
        $test->assertSee('Jawaban');

        // Pastikan method admin tidak bisa dipanggil oleh guru
        $test->call('selesaikanSesiRuang');
        $test->assertSet('flashType', 'danger');
        $this->assertEquals('ACTIVE', $data['jadwalRuang']->fresh()->status);

        $test->call('paksaSelesaiSiswa', $data['peserta']->id);
        $test->assertSet('flashType', 'danger');
        $this->assertEquals('SEDANG_MENGERJAKAN', $data['peserta']->fresh()->status);

        $test->call('resetUjianSiswaTotal', $data['peserta']->id);
        $test->assertSet('flashType', 'danger');
        $this->assertEquals('SEDANG_MENGERJAKAN', $data['peserta']->fresh()->status);

        $test->call('tambahWaktuSiswa', $data['peserta']->id, 10);
        $test->assertSet('flashType', 'danger');

        // Pastikan method guru bisa dijalankan oleh guru
        $test->call('bukaLembarJawaban', $data['peserta']->id);
        $test->assertSet('selectedPesertaId', $data['peserta']->id);

        $test->call('resetSesiSiswa', $data['peserta']->id);
        $test->assertSet('flashType', 'success');
    }

    public function test_guru_pengawas_bisa_buka_kunci_ketika_siswa_pindah_tab(): void
    {
        $data = $this->setupData();

        // Simulasikan siswa terkunci karena pindah tab 3 kali
        $data['sesi']->update([
            'jumlah_pelanggaran' => 3,
            'terakhir_pelanggaran_at' => now(),
        ]);

        $this->actingAs($data['guru']);

        $test = Livewire::test(PortalPengawas::class)
            ->call('pilihJadwalRuang', $data['jadwalRuang']->id);

        // Pastikan indikator terkunci dan tombol Buka Kunci terlihat oleh Guru Pengawas
        $test->assertSee('TERKUNCI (3x Pindah Tab)');
        $test->assertSee('Buka Kunci');

        // Guru Pengawas mengeklik tombol Buka Kunci
        $test->call('resetSesiSiswa', $data['peserta']->id);
        $test->assertSet('flashType', 'success');
        $test->assertSee('Kunci pelanggaran pindah tab');

        // Pastikan pelanggaran kembali 0 dan status pengerjaan aktif
        $freshSesi = $data['sesi']->fresh();
        $this->assertEquals(0, $freshSesi->jumlah_pelanggaran);
        $this->assertEquals('mengerjakan', $freshSesi->status);
    }

    public function test_admin_bisa_mengakses_semua_tombol_dan_aksi(): void
    {
        $data = $this->setupData();

        $this->actingAs($data['admin']);

        $test = Livewire::test(PortalPengawas::class)
            ->call('pilihJadwalRuang', $data['jadwalRuang']->id);

        // Pastikan tombol aksi admin terlihat untuk admin
        $test->assertSee('Tandai Sesi Selesai');
        $test->assertSee('Reset Ujian');
        $test->assertSee('+10m Waktu');
        $test->assertSee('Selesaikan');
        $test->assertSee('Reset Login');
        $test->assertSee('Jawaban');

        // Admin bisa menambah waktu
        $test->call('tambahWaktuSiswa', $data['peserta']->id, 10);
        $test->assertSet('flashType', 'success');
        $this->assertEquals(70, $data['sesi']->fresh()->durasi_menit);

        // Admin bisa paksa selesai
        $test->call('paksaSelesaiSiswa', $data['peserta']->id);
        $test->assertSet('flashType', 'info');
        $this->assertEquals('SELESAI', $data['peserta']->fresh()->status);

        // Admin bisa selesaikan sesi ruang
        $test->call('selesaikanSesiRuang');
        $test->assertSet('flashType', 'info');
        $this->assertEquals('COMPLETED', $data['jadwalRuang']->fresh()->status);
    }
}

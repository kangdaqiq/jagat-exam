<?php

namespace Tests\Feature;

use App\Livewire\Siswa\PortalUjian;
use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\RuangUjian;
use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalUjianSesiGandengTest extends TestCase
{
    use RefreshDatabase;

    public function test_sesi_gandeng_dikelompokkan_menjadi_satu_agenda_dan_tombol_didisable_jika_bukan_ongoing(): void
    {
        $guru = User::create([
            'name' => 'Guru Pengawas',
            'username' => 'guru1',
            'email' => 'guru@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru',
        ]);

        $ruang = RuangUjian::create(['nama_ruang' => 'Ruang 2', 'kapasitas' => 20]);
        $kelas = Kelas::create(['nama_kelas' => 'XII TB', 'tingkat' => 'XII', 'is_active' => true]);

        $mapel1 = Mapel::create(['nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BIN', 'tingkat' => 'XII']);
        $mapel2 = Mapel::create(['nama_mapel' => 'Desain Busana', 'kode_mapel' => 'DB', 'tingkat' => 'XII']);

        $bank1 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel1->id,
            'judul' => 'Bank BIN',
            'kode_bank' => 'BS-BIN',
            'durasi_menit' => 60,
            'is_active' => true,
        ]);

        $bank2 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel2->id,
            'judul' => 'Bank DB',
            'kode_bank' => 'BS-DB',
            'durasi_menit' => 60,
            'is_active' => true,
        ]);

        // Ujian Sesi 1: 07:30 - 08:30
        $ujian1 = Ujian::create([
            'nama_ujian' => 'Ujian Bahasa Indonesia',
            'mapel_id' => $mapel1->id,
            'bank_soal_id' => $bank1->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '08:30:00',
            'durasi_menit' => 60,
            'status' => 'SCHEDULED', // BUKAN ONGOING
        ]);
        $ujian1->kelas()->attach($kelas->id);

        // Ujian Sesi 2: 08:30 - 09:30 (Gandeng langsung setelah Ujian 1)
        $ujian2 = Ujian::create([
            'nama_ujian' => 'Ujian Desain Busana',
            'mapel_id' => $mapel2->id,
            'bank_soal_id' => $bank2->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:30:00',
            'waktu_selesai' => '09:30:00',
            'durasi_menit' => 60,
            'status' => 'SCHEDULED', // BUKAN ONGOING
        ]);
        $ujian2->kelas()->attach($kelas->id);

        $siswaUser = User::create([
            'name' => 'Keyla Biyan Ramadhani',
            'username' => '2402017',
            'email' => 'keyla@siswa.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);

        Siswa::create([
            'user_id' => $siswaUser->id,
            'kelas_id' => $kelas->id,
            'ruang_ujian_id' => $ruang->id,
            'nama' => 'Keyla Biyan Ramadhani',
            'nis' => '2402017',
        ]);

        $this->actingAs($siswaUser);

        // 1. Saat status masih SCHEDULED (bukan ongoing)
        $test = Livewire::test(PortalUjian::class);

        // Pastikan hanya muncul 1 agenda (digabung jadi 1 kartu sesi gandeng)
        $test->assertSee('1 Agenda Hari Ini');
        $test->assertSee('Gandeng');
        $test->assertSee('Subtest 1');
        $test->assertSee('Subtest 2');
        $test->assertSee('Bahasa Indonesia');
        $test->assertSee('Desain Busana');

        // Pastikan tombol masuk ruang ditutup / disabled
        $test->assertSee('Tombol Masuk Ditutup (Menunggu Pengawas)');
        $test->assertSee('SESI BELUM DIMULAI');

        // 2. Sekarang aktifkan salah satu ujian menjadi ONGOING
        $ujian1->update(['status' => 'ONGOING']);

        $testOngoing = Livewire::test(PortalUjian::class);

        // Pastikan status menjadi SESI DIBUKA dan tombol masuk menjadi aktif
        $testOngoing->assertSee('SESI DIBUKA');
        $testOngoing->assertSee('Mulai Kerjakan Ujian');
        $testOngoing->assertDontSee('SESI BELUM DIMULAI');
    }
}

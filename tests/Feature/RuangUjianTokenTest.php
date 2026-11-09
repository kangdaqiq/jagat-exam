<?php

namespace Tests\Feature;

use App\Livewire\Siswa\PortalUjian;
use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\RuangUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianSesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RuangUjianTokenTest extends TestCase
{
    use RefreshDatabase;

    private function buatKelasDenganUjian(User $guru, string $namaKelas, string $tingkat): array
    {
        $kelas = Kelas::create(['nama_kelas' => $namaKelas, 'tingkat' => $tingkat, 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => "Mapel {$tingkat}", 'kode_mapel' => "M-{$tingkat}", 'tingkat' => $tingkat]);
        $bank = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'judul' => "Bank {$tingkat}",
            'kode_bank' => "BS-{$tingkat}",
            'durasi_menit' => 45,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        Soal::create([
            'bank_soal_id' => $bank->id,
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => "Soal tingkat {$tingkat}",
            'bobot_nilai' => 100,
        ]);
        $ujian = Ujian::create([
            'nama_ujian' => "Ujian {$tingkat}",
            'mapel_id' => $mapel->id,
            'bank_soal_id' => $bank->id,
            'guru_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '00:00:00',
            'waktu_selesai' => '23:59:00',
            'durasi_menit' => 45,
            'status' => 'ONGOING',
        ]);
        $ujian->kelas()->attach($kelas->id);

        return [$kelas, $bank, $ujian];
    }

    private function buatSiswa(Kelas $kelas, string $nis, RuangUjian $ruang): User
    {
        $user = User::factory()->create(['role' => 'siswa', 'email' => "{$nis}@siswa.cbt", 'is_active' => true]);
        Siswa::create([
            'user_id' => $user->id,
            'kelas_id' => $kelas->id,
            'ruang_ujian_id' => $ruang->id,
            'nis' => $nis,
            'nama' => "Siswa {$nis}",
            'is_active' => true,
        ]);

        return $user;
    }

    public function test_satu_token_ruang_untuk_siswa_beda_kelas_mendapat_soal_sesuai_tingkat(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        [$kelasX, $bankX] = $this->buatKelasDenganUjian($guru, 'X RPL 1', '10');
        [$kelasXI, $bankXI] = $this->buatKelasDenganUjian($guru, 'XI RPL 1', '11');

        $ruang = RuangUjian::create(['nama_ruang' => 'Ruang 1', 'kapasitas' => 36, 'is_active' => true]);
        $userX = $this->buatSiswa($kelasX, '1001', $ruang);
        $userXI = $this->buatSiswa($kelasXI, '1002', $ruang);

        $token = $ruang->rilisToken();

        foreach ([[$userX, $bankX], [$userXI, $bankXI]] as [$user, $bank]) {
            $this->actingAs($user);
            Livewire::test(PortalUjian::class)
                ->set('token_input', $token)
                ->call('submitToken')
                ->assertHasNoErrors();

            $sesi = UjianSesi::where('user_id', $user->id)->first();
            $this->assertNotNull($sesi);
            $this->assertEquals($bank->id, $sesi->bank_soal_id);
            $this->assertEquals($ruang->id, $sesi->ruang_ujian_id);
        }
    }

    public function test_token_ruang_lain_ditolak(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        [$kelasX] = $this->buatKelasDenganUjian($guru, 'X RPL 1', '10');

        $ruang1 = RuangUjian::create(['nama_ruang' => 'Ruang 1', 'kapasitas' => 36, 'is_active' => true]);
        $ruang2 = RuangUjian::create(['nama_ruang' => 'Ruang 2', 'kapasitas' => 36, 'is_active' => true]);
        $user = $this->buatSiswa($kelasX, '1001', $ruang1);

        $token2 = $ruang2->rilisToken();

        $this->actingAs($user);
        Livewire::test(PortalUjian::class)
            ->set('token_input', $token2)
            ->call('submitToken')
            ->assertHasErrors('token_input');
    }
}


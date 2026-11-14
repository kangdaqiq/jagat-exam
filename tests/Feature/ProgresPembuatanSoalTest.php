<?php

namespace Tests\Feature;

use App\Filament\Pages\ProgresPembuatanSoal;
use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\OpsiJawaban;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgresPembuatanSoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_guru_dapat_mengakses_halaman_progres(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $siswa = User::factory()->create(['role' => 'siswa', 'is_active' => true]);

        $this->actingAs($admin);
        $this->assertTrue(ProgresPembuatanSoal::canAccess());
        $this->get('/admin/progres-pembuatan-soal')->assertOk();

        $this->actingAs($guru);
        $this->assertTrue(ProgresPembuatanSoal::canAccess());
        $this->get('/guru/progres-pembuatan-soal')->assertOk();

        $this->actingAs($siswa);
        $this->assertFalse(ProgresPembuatanSoal::canAccess());
    }

    public function test_livewire_renders_stats_and_evaluates_bank_soal_properly(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Informatika', 'kode_mapel' => 'INF-10']);
        $kelas = Kelas::create(['nama_kelas' => 'X RPL 1', 'tingkat' => '10']);

        // Bank 1: Siap Digunakan (10 soal, masing-masing 10 poin, total 100, ada kunci)
        $bank1 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Ulangan Harian 1',
            'kode_bank' => 'SOAL-202611-TEST1',
            'tingkat' => '10',
            'total_bobot' => 100.0,
            'is_active' => true,
        ]);
        $bank1->kelases()->attach($kelas->id);

        for ($i = 1; $i <= 10; $i++) {
            $soal = Soal::create([
                'bank_soal_id' => $bank1->id,
                'nomor_urut' => $i,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "<p>Pertanyaan {$i}</p>",
                'bobot_nilai' => 10.0,
            ]);
            OpsiJawaban::create([
                'soal_id' => $soal->id,
                'label' => 'A',
                'teks_opsi' => 'Opsi A',
                'is_jawaban_benar' => true,
                'nomor_urut' => 1,
            ]);
            OpsiJawaban::create([
                'soal_id' => $soal->id,
                'label' => 'B',
                'teks_opsi' => 'Opsi B',
                'is_jawaban_benar' => false,
                'nomor_urut' => 2,
            ]);
        }

        // Bank 2: Perlu Review (bobot belum 100, total 50)
        $bank2 = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Ulangan Harian 2',
            'kode_bank' => 'SOAL-202611-TEST2',
            'tingkat' => '10',
            'total_bobot' => 50.0,
            'is_active' => true,
        ]);
        $soal2 = Soal::create([
            'bank_soal_id' => $bank2->id,
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => '<p>Soal belum selesai</p>',
            'bobot_nilai' => 50.0,
        ]);
        OpsiJawaban::create([
            'soal_id' => $soal2->id,
            'label' => 'A',
            'teks_opsi' => 'Opsi A',
            'is_jawaban_benar' => true,
            'nomor_urut' => 1,
        ]);

        $this->actingAs($guru);

        Livewire::test(ProgresPembuatanSoal::class)
            ->assertSee('Monitoring Progres Pembuatan Soal')
            ->assertSee('Ulangan Harian 1')
            ->assertSee('Ulangan Harian 2')
            ->assertSee('Siap Digunakan')
            ->assertSee('Perlu Review')
            ->set('filterStatus', 'siap')
            ->assertSee('Ulangan Harian 1')
            ->assertDontSee('Ulangan Harian 2')
            ->set('filterStatus', 'review')
            ->assertSee('Ulangan Harian 2')
            ->assertDontSee('Ulangan Harian 1')
            ->set('filterStatus', 'all')
            ->set('search', 'TEST1')
            ->assertSee('Ulangan Harian 1')
            ->assertDontSee('Ulangan Harian 2');
    }

    public function test_quick_action_auto_bagi_rata_bobot(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK-10']);

        $bank = BankSoal::create([
            'user_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Paket Latihan',
            'kode_bank' => 'SOAL-202611-LTN',
            'tingkat' => '10',
            'total_bobot' => 30.0,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            Soal::create([
                'bank_soal_id' => $bank->id,
                'nomor_urut' => $i,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "<p>Soal {$i}</p>",
                'bobot_nilai' => 5.0, // total 20 belum 100
            ]);
        }

        $this->actingAs($guru);

        Livewire::test(ProgresPembuatanSoal::class)
            ->call('autoBagiRataBobot', $bank->id)
            ->assertNotified('Bobot Berhasil Diratakan');

        $bank->refresh();
        $this->assertEquals(100.0, (float) $bank->soals()->sum('bobot_nilai'));
    }

    public function test_export_csv_berhasil(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BIN-10']);

        BankSoal::create([
            'user_id' => $admin->id,
            'mapel_id' => $mapel->id,
            'judul' => 'Bank Soal Uji Coba',
            'kode_bank' => 'SOAL-202611-CSV',
            'tingkat' => '10',
            'total_bobot' => 100.0,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ProgresPembuatanSoal::class)
            ->call('exportCsv')
            ->assertFileDownloaded();
    }
}

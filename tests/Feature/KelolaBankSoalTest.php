<?php

namespace Tests\Feature;

use App\Filament\Resources\BankSoals\Pages\EditBankSoal;
use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaBankSoalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected BankSoal $bankSoal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $mapel = Mapel::create([
            'nama_mapel' => 'Matematika Peminatan',
            'kode_mapel' => 'MAT-MIN',
        ]);

        $this->bankSoal = BankSoal::create([
            'judul' => 'Ujian Tengah Semester Ganjil',
            'kode_bank' => 'UTS-MAT-2026',
            'mapel_id' => $mapel->id,
            'tingkat' => '11',
            'user_id' => $this->admin->id,
            'total_bobot' => 100,
            'is_active' => true,
        ]);
    }

    public function test_can_load_studio_page_and_auto_create_first_soal(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id])
            ->assertSuccessful()
            ->assertSet('nomor_urut', 1)
            ->assertSet('tipe_soal', 'pilihan_ganda')
            ->assertCount('opsi_jawabans', 4);

        $this->assertDatabaseHas('soals', [
            'bank_soal_id' => $this->bankSoal->id,
            'nomor_urut' => 1,
        ]);
    }

    public function test_single_choice_checkbox_logic_for_pilihan_ganda(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        // Centang opsi ke-2 (index 1 / Opsi B)
        $component->call('pilihKunciJawaban', 1);

        $opsi = $component->get('opsi_jawabans');
        $this->assertFalse($opsi[0]['is_jawaban_benar']);
        $this->assertTrue($opsi[1]['is_jawaban_benar']);
        $this->assertFalse($opsi[2]['is_jawaban_benar']);
        $this->assertFalse($opsi[3]['is_jawaban_benar']);

        // Sekarang klik opsi ke-3 (index 2 / Opsi C) -> Opsi B harus otomatis uncheck!
        $component->call('pilihKunciJawaban', 2);

        $opsi = $component->get('opsi_jawabans');
        $this->assertFalse($opsi[0]['is_jawaban_benar']);
        $this->assertFalse($opsi[1]['is_jawaban_benar']);
        $this->assertTrue($opsi[2]['is_jawaban_benar']);
        $this->assertFalse($opsi[3]['is_jawaban_benar']);
    }

    public function test_can_save_question_with_math_and_options(): void
    {
        $this->actingAs($this->admin);

        $mathText = '<p>Tentukan akar dari persamaan $f(x) = x^2 - 4x + 4 = 0$:</p>';

        Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id])
            ->set('pertanyaan', $mathText)
            ->set('bobot_nilai', 5.0)
            ->set('opsi_jawabans.0.teks_opsi', '$x = 2$')
            ->set('opsi_jawabans.1.teks_opsi', '$x = -2$')
            ->call('pilihKunciJawaban', 0)
            ->call('simpanSoal', false)
            ->assertNotified();

        $this->assertDatabaseHas('soals', [
            'bank_soal_id' => $this->bankSoal->id,
            'nomor_urut' => 1,
            'pertanyaan' => $mathText,
            'bobot_nilai' => 5.0,
        ]);

        $soal = Soal::where('bank_soal_id', $this->bankSoal->id)->first();
        $this->assertDatabaseHas('opsi_jawabans', [
            'soal_id' => $soal->id,
            'label' => 'A',
            'teks_opsi' => '$x = 2$',
            'is_jawaban_benar' => true,
        ]);
    }

    public function test_can_add_duplicate_and_delete_soal(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        // Tambah Soal
        $component->call('tambahSoal')
            ->assertSet('nomor_urut', 2);

        $this->assertCount(2, $this->bankSoal->fresh()->soals);

        // Duplikat Soal No. 2 -> Jadi No. 3
        $component->call('duplikatSoal')
            ->assertSet('nomor_urut', 3);

        $this->assertCount(3, $this->bankSoal->fresh()->soals);

        // Hapus Soal No. 3
        $component->call('hapusSoal');

        $this->assertCount(2, $this->bankSoal->fresh()->soals);
    }

    public function test_ai_agent_can_generate_single_soal_into_active_editor(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->set('ai_mode', 'current')
            ->set('ai_topik', 'Persamaan Kuadrat & Rumus ABC')
            ->set('ai_tipe_soal', 'pilihan_ganda')
            ->set('ai_kesulitan', 'HOTS')
            ->call('generateWithAi')
            ->assertHasNoErrors()
            ->assertDispatched('soal-loaded');

        // Pertanyaan soal 1 diupdate dengan materi topik
        $firstSoal = $this->bankSoal->fresh()->soals()->first();
        $this->assertStringContainsString('Persamaan Kuadrat', $firstSoal->pertanyaan);
        $this->assertCount(4, $firstSoal->opsiJawabans);
        $this->assertTrue((bool) $firstSoal->opsiJawabans->firstWhere('label', 'A')->is_jawaban_benar);
    }

    public function test_ai_agent_can_generate_batch_soals(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        // Awalnya ada 1 soal bawaan
        $this->assertCount(1, $this->bankSoal->fresh()->soals);

        // Generate batch 3 soal baru
        $component->set('ai_mode', 'batch')
            ->set('ai_topik', 'Hukum Termodinamika')
            ->set('ai_tipe_soal', 'pilihan_ganda')
            ->set('ai_jumlah', 3)
            ->call('generateWithAi')
            ->assertHasNoErrors();

        // Total soal sekarang 1 + 3 = 4 soal
        $this->assertCount(4, $this->bankSoal->fresh()->soals);

        // Validasi tidak boleh lebih dari 50
        $component->set('ai_jumlah', 51)
            ->call('generateWithAi')
            ->assertHasErrors(['ai_jumlah']);
    }

    public function test_ai_agent_supports_model_selection_and_available_models(): void
    {
        $this->actingAs($this->admin);

        $models = \App\Services\AiQuestionService::getAvailableGeminiModels();
        $this->assertArrayHasKey('gemini-2.5-flash', $models);
        $this->assertArrayHasKey('gemini-2.5-pro', $models);
        $this->assertArrayHasKey('gemini-2.0-flash', $models);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->call('openAiModal', 'current')
            ->assertSet('ai_model', 'gemini-2.5-flash')
            ->set('ai_model', 'gemini-2.0-flash')
            ->set('ai_topik', 'Fotosintesis dan Klorofil')
            ->call('generateWithAi')
            ->assertHasNoErrors();

        $firstSoal = $this->bankSoal->fresh()->soals()->first();
        $this->assertStringContainsString('Fotosintesis', $firstSoal->pertanyaan);
    }
}



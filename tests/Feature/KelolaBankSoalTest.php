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
        $this->assertArrayHasKey('gemini-3.5-flash', $models);
        $this->assertArrayHasKey('gemini-3.5-flash-lite', $models);
        $this->assertArrayHasKey('gemini-3.5-pro', $models);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->call('openAiModal', 'current')
            ->assertSet('ai_model', 'gemini-3.5-flash')
            ->set('ai_model', 'gemini-3.5-flash-lite')
            ->set('ai_topik', 'Fotosintesis dan Klorofil')
            ->call('generateWithAi')
            ->assertHasNoErrors();

        $firstSoal = $this->bankSoal->fresh()->soals()->first();
        $this->assertStringContainsString('Fotosintesis', $firstSoal->pertanyaan);
    }

    public function test_ai_service_can_parse_raw_copas_text_into_soals(): void
    {
        $rawText = "1. Sebuah benda bermassa 2 kg bergerak dengan percepatan 5 m/s². Berapakah gaya yang bekerja pada benda tersebut?\n" .
            "A. 5 N\n" .
            "B. 10 N\n" .
            "C. 15 N\n" .
            "D. 20 N\n" .
            "Kunci: B\n" .
            "Pembahasan: Berdasarkan Hukum II Newton, F = m * a = 2 kg * 5 m/s² = 10 N.\n\n" .
            "2. Organel sel yang bertanggung jawab menghasilkan energi ATP melalui respirasi seluler adalah...\n" .
            "A. Ribosom\n" .
            "B. Lisosom\n" .
            "C. Mitokondria\n" .
            "D. Badan Golgi\n" .
            "Kunci Jawaban: C\n" .
            "Pembahasan: Mitokondria adalah penghasil energi seluler utama.";

        $service = app(\App\Services\AiQuestionService::class);
        $results = $service->parseRawTextToSoals($rawText, ['bobot_nilai' => 2.5]);

        $this->assertCount(2, $results);

        // Periksa butir soal 1
        $this->assertEquals('pilihan_ganda', $results[0]['tipe_soal']);
        $this->assertEquals(2.5, $results[0]['bobot_nilai']);
        $this->assertStringContainsString('Sebuah benda bermassa 2 kg', $results[0]['pertanyaan']);
        $this->assertCount(4, $results[0]['opsi_jawabans']);
        $this->assertFalse($results[0]['opsi_jawabans'][0]['is_jawaban_benar']); // Opsi A
        $this->assertTrue($results[0]['opsi_jawabans'][1]['is_jawaban_benar']);  // Opsi B (Kunci)
        $this->assertStringContainsString('Hukum II Newton', $results[0]['pembahasan']);

        // Periksa butir soal 2
        $this->assertEquals('pilihan_ganda', $results[1]['tipe_soal']);
        $this->assertTrue($results[1]['opsi_jawabans'][2]['is_jawaban_benar']);  // Opsi C (Kunci)
        $this->assertStringContainsString('respirasi seluler', $results[1]['pertanyaan']);
    }

    public function test_can_open_import_text_modal_and_preview_parsed_soals(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->call('openImportTextModal', 'batch')
            ->assertSet('showImportTextModal', true)
            ->assertSet('import_mode', 'batch')
            ->call('loadSampleText');

        $this->assertNotEmpty($component->get('raw_text_input'));

        $component->call('parseTextWithAi')
            ->assertSet('has_parsed', true)
            ->assertCount('parsed_soals_preview', 2);
    }

    public function test_can_save_parsed_text_in_batch_mode_to_database(): void
    {
        $this->actingAs($this->admin);

        $rawText = "1. Berapakah 5 x 5?\n" .
            "A. 15\n" .
            "B. 20\n" .
            "C. 25\n" .
            "D. 30\n" .
            "Kunci: C\n\n" .
            "2. Ibu kota Indonesia saat ini adalah...\n" .
            "A. Bandung\n" .
            "B. Jakarta\n" .
            "C. Surabaya\n" .
            "D. Medan\n" .
            "Kunci: B";

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->call('openImportTextModal', 'batch')
            ->set('raw_text_input', $rawText)
            ->set('import_default_bobot', 3.0)
            ->call('parseTextWithAi')
            ->assertSet('has_parsed', true)
            ->call('simpanParsedSoals')
            ->assertSet('showImportTextModal', false)
            ->assertNotified();

        // Total soal sekarang 1 (awal) + 2 (baru) = 3 soal
        $soals = $this->bankSoal->fresh()->soals()->with('opsiJawabans')->orderBy('nomor_urut')->get();
        $this->assertCount(3, $soals);

        $soal2 = $soals[1];
        $this->assertEquals(2, $soal2->nomor_urut);
        $this->assertEquals(3.0, (float) $soal2->bobot_nilai);
        $this->assertStringContainsString('5 x 5', $soal2->pertanyaan);

        // Kunci C benar
        $opsiC = $soal2->opsiJawabans->where('label', 'C')->first();
        $this->assertTrue((bool) $opsiC->is_jawaban_benar);

        $soal3 = $soals[2];
        $this->assertEquals(3, $soal3->nomor_urut);
        $opsiB = $soal3->opsiJawabans->where('label', 'B')->first();
        $this->assertTrue((bool) $opsiB->is_jawaban_benar);
    }

    public function test_can_save_parsed_text_in_current_mode_to_active_soal(): void
    {
        $this->actingAs($this->admin);

        $rawText = "1. Berapakah nilai akar kuadrat dari 144?\n" .
            "A. 10\n" .
            "B. 11\n" .
            "C. 12\n" .
            "D. 14\n" .
            "Kunci: C\n" .
            "Pembahasan: 12 x 12 = 144.";

        $component = Livewire::test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->call('openImportTextModal', 'current')
            ->set('raw_text_input', $rawText)
            ->set('import_default_bobot', 5.0)
            ->call('parseTextWithAi')
            ->assertSet('has_parsed', true)
            ->call('simpanParsedSoals')
            ->assertSet('showImportTextModal', false)
            ->assertNotified();

        // Soal No 1 harus diperbarui
        $firstSoal = $this->bankSoal->fresh()->soals()->with('opsiJawabans')->first();
        $this->assertEquals(1, $firstSoal->nomor_urut);
        $this->assertEquals(5.0, (float) $firstSoal->bobot_nilai);
        $this->assertStringContainsString('akar kuadrat dari 144', $firstSoal->pertanyaan);
        $this->assertStringContainsString('12 x 12 = 144', $firstSoal->pembahasan);

        $opsiC = $firstSoal->opsiJawabans->where('label', 'C')->first();
        $this->assertTrue((bool) $opsiC->is_jawaban_benar);
    }
}



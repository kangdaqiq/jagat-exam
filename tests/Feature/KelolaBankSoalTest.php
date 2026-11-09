<?php

namespace Tests\Feature;

use App\Filament\Resources\BankSoals\Pages\EditBankSoal;
use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        // Generate batch 3 soal baru (soal dummy bawaan No. 1 otomatis dibersihkan)
        $component->set('ai_mode', 'batch')
            ->set('ai_topik', 'Hukum Termodinamika')
            ->set('ai_tipe_soal', 'pilihan_ganda')
            ->set('ai_jumlah', 3)
            ->call('generateWithAi')
            ->assertHasNoErrors();

        // Soal dummy No. 1 dihapus, tersisa 3 soal hasil generate bernomor 1, 2, 3
        $this->assertCount(3, $this->bankSoal->fresh()->soals);
        $this->assertEquals(1, $this->bankSoal->fresh()->soals()->first()->nomor_urut);

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
            ->assertSet('ai_model', 'gemini-3.6-flash')
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

        // Soal dummy No. 1 dihapus, tersisa 2 soal hasil parse bernomor 1 dan 2
        $soals = $this->bankSoal->fresh()->soals()->with('opsiJawabans')->orderBy('nomor_urut')->get();
        $this->assertCount(2, $soals);

        $soal1 = $soals[0];
        $this->assertEquals(1, $soal1->nomor_urut);
        $this->assertEquals(3.0, (float) $soal1->bobot_nilai);
        $this->assertStringContainsString('5 x 5', $soal1->pertanyaan);

        // Kunci C benar
        $opsiC = $soal1->opsiJawabans->where('label', 'C')->first();
        $this->assertTrue((bool) $opsiC->is_jawaban_benar);

        $soal2 = $soals[1];
        $this->assertEquals(2, $soal2->nomor_urut);
        $opsiB = $soal2->opsiJawabans->where('label', 'B')->first();
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

    public function test_parser_accurately_handles_markdown_sections_and_mixed_pg_essay(): void
    {
        $rawMarkdown = <<<MD
# SOAL PENDIDIKAN AGAMA ISLAM
Kelas: X SMK\
Petunjuk: Kerjakan dengan baik.

1. Bab 1 – Meraih Kesuksesan
2. Bab 2 – Memahami Hakikat

## A. Pilihan Ganda
1\. Dalam QS. Al-Maidah ayat 48, manusia diperintahkan berlomba dalam kebaikan. Istilah tersebut adalah ....
A. Tawakal\
B. Fastabiqul khairat\
C. Takabur\
D. Tasamuh\
E. Uzlah

2\. Menolong teman tanpa pamrih adalah contoh ....
A. Persaingan tidak sehat\
B. Fastabiqul khairat\
C. Riya\
D. Sum'ah\
E. Hasad

20. Perhatikan pernyataan berikut!
1. Menggunakan uang sesuai kebutuhan.
2. Selalu memamerkan kekayaan.
3. Bersyukur atas rezeki.
Perilaku yang baik ditunjukkan oleh ....
A. 1 dan 3\
B. 2 dan 3\
C. 1, 2, dan 3\
D. 2 saja\
E. 3 saja

## B. Essay
1\. Jelaskan pengertian fastabiqul khairat!
2\. Jelaskan pengertian etos kerja dalam Islam!

## C. Kunci Jawaban
Kunci jawaban guru.
MD;

        $service = app(\App\Services\AiQuestionService::class);
        $results = $service->fallbackLocalParse($rawMarkdown, 2.0);

        // 3 PG + 2 Essay = 5 Soal (Bab 1, Bab 2, dan Kunci Jawaban diabaikan)
        $this->assertCount(5, $results);

        $this->assertEquals('pilihan_ganda', $results[0]['tipe_soal']);
        $this->assertStringContainsString('QS. Al-Maidah ayat 48', $results[0]['pertanyaan']);
        $this->assertCount(5, $results[0]['opsi_jawabans']);

        // Soal 20 tidak boleh pecah oleh 1, 2, 3 di dalamnya
        $this->assertEquals('pilihan_ganda', $results[2]['tipe_soal']);
        $this->assertStringContainsString('Perhatikan pernyataan berikut', $results[2]['pertanyaan']);
        $this->assertStringContainsString('Menggunakan uang sesuai kebutuhan', $results[2]['pertanyaan']);
        $this->assertCount(5, $results[2]['opsi_jawabans']);

        // Essay
        $this->assertEquals('essay', $results[3]['tipe_soal']);
        $this->assertStringContainsString('pengertian fastabiqul khairat', $results[3]['pertanyaan']);
        $this->assertEmpty($results[3]['opsi_jawabans']);

        $this->assertEquals('essay', $results[4]['tipe_soal']);
        $this->assertEquals('essay', $results[4]['tipe_soal']);
    }

    public function test_can_trigger_sesuaikan_bobot_action(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->callAction('sesuaikanBobot')
            ->assertHasNoErrors()
            ->assertNotified('Bobot seluruh butir soal berhasil disesuaikan ke total 100 poin (Rasio LOTS:MOTS:HOTS = 1:2:3)');

        $this->assertEquals(100.0, (float) $this->bankSoal->fresh()->total_bobot);
    }

    public function test_can_parse_questions_with_separate_answer_keys_table_or_list(): void
    {
        $rawText = <<<'MD'
# SOAL PENGETAHUAN UMUM
## A. Pilihan Ganda
1\. Negara Indonesia terletak di antara dua benua, yaitu ....
- A. Asia dan Afrika
- B. Asia dan Australia
- C. Australia dan Eropa
- D. Eropa dan Amerika
- E. Afrika dan Amerika

2\. Planet terbesar dalam tata surya adalah ....
- A. Mars
- B. Venus
- C. Saturnus
- D. Jupiter
- E. Neptunus

3\. Semboyan Bhinneka Tunggal Ika memiliki arti ....
- A. Bersatu kita teguh
- B. Berbeda-beda tetapi tetap satu
- C. Bersama membangun bangsa
- D. Satu bangsa satu bahasa
- E. Persatuan adalah kekuatan

## B. Essay
1\. Jelaskan mengapa Indonesia disebut sebagai negara kepulauan!

## C. Kunci Jawaban Pilihan Ganda
| No. | Jawaban |
| --- | ------- |
| 1   | B       |
| 2   | D       |
| 3   | B       |
MD;

        $service = app(\App\Services\AiQuestionService::class);
        $results = $service->fallbackLocalParse($rawText);

        $this->assertCount(4, $results);

        // Soal 1: Kunci B
        $this->assertEquals('pilihan_ganda', $results[0]['tipe_soal']);
        $optB1 = collect($results[0]['opsi_jawabans'])->firstWhere('label', 'B');
        $optA1 = collect($results[0]['opsi_jawabans'])->firstWhere('label', 'A');
        $this->assertTrue((bool) $optB1['is_jawaban_benar']);
        $this->assertFalse((bool) $optA1['is_jawaban_benar']);

        // Soal 2: Kunci D
        $this->assertEquals('pilihan_ganda', $results[1]['tipe_soal']);
        $optD2 = collect($results[1]['opsi_jawabans'])->firstWhere('label', 'D');
        $optA2 = collect($results[1]['opsi_jawabans'])->firstWhere('label', 'A');
        $this->assertTrue((bool) $optD2['is_jawaban_benar']);
        $this->assertFalse((bool) $optA2['is_jawaban_benar']);

        // Soal 3: Kunci B
        $this->assertEquals('pilihan_ganda', $results[2]['tipe_soal']);
        $optB3 = collect($results[2]['opsi_jawabans'])->firstWhere('label', 'B');
        $this->assertTrue((bool) $optB3['is_jawaban_benar']);

        // Soal 4: Essay
        $this->assertEquals('essay', $results[3]['tipe_soal']);
        $this->assertEmpty($results[3]['opsi_jawabans']);
    }

    public function test_upload_media_gambar_dikompresi_otomatis_ke_webp(): void
    {
        Storage::fake('public');

        $soal = $this->bankSoal->soals()->create([
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Perhatikan gambar berikut ini:',
        ]);

        $uploaded = \Illuminate\Http\UploadedFile::fake()->image('diagram_stimulus.jpg', 1800, 1400);

        $component = Livewire::actingAs($this->admin)
            ->test(EditBankSoal::class, ['record' => $this->bankSoal->id]);

        $component->call('selectSoal', $soal->id)
            ->set('media_type', 'image')
            ->set('media_file_upload', $uploaded)
            ->call('simpanSoal')
            ->assertHasNoErrors();

        $soal->refresh();

        $this->assertNotNull($soal->media_file);
        $this->assertStringEndsWith('.webp', $soal->media_file);
        Storage::disk('public')->assertExists($soal->media_file);
    }
}



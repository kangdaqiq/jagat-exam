<?php

namespace Tests\Feature;

use App\Livewire\ExamRunner;
use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamRunnerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected BankSoal $bankSoal;
    protected Soal $soal1;
    protected Soal $soal2;

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
            'judul' => 'Ujian Tengah Semester',
            'kode_bank' => 'UTS-MAT-2026',
            'mapel_id' => $mapel->id,
            'tingkat' => '11',
            'user_id' => $this->admin->id,
            'total_bobot' => 100,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);

        $this->soal1 = $this->bankSoal->soals()->create([
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'bobot_nilai' => 5.0,
            'pertanyaan' => '<p>Hitunglah $2^3$:</p>',
            'media_type' => 'none',
        ]);

        $this->soal1->opsiJawabans()->createMany([
            ['label' => 'A', 'teks_opsi' => '6', 'is_jawaban_benar' => false, 'nomor_urut' => 1],
            ['label' => 'B', 'teks_opsi' => '8', 'is_jawaban_benar' => true, 'nomor_urut' => 2],
            ['label' => 'C', 'teks_opsi' => '9', 'is_jawaban_benar' => false, 'nomor_urut' => 3],
            ['label' => 'D', 'teks_opsi' => '10', 'is_jawaban_benar' => false, 'nomor_urut' => 4],
        ]);

        $this->soal2 = $this->bankSoal->soals()->create([
            'nomor_urut' => 2,
            'tipe_soal' => 'isian',
            'bobot_nilai' => 5.0,
            'pertanyaan' => '<p>Ibukota negara Indonesia adalah:</p>',
            'media_type' => 'none',
            'kunci_isian' => 'Nusantara',
        ]);
    }

    public function test_can_render_entrance_screen(): void
    {
        Livewire::test(ExamRunner::class, ['bankSoal' => $this->bankSoal])
            ->assertSee('Portal Ujian CBT Siswa')
            ->assertSee($this->bankSoal->judul)
            ->assertSee('Mulai Mengerjakan Ujian');
    }

    public function test_student_can_start_exam_and_answer(): void
    {
        $component = Livewire::test(ExamRunner::class, ['bankSoal' => $this->bankSoal]);

        // Start exam
        $component->set('nama_peserta', 'Ahmad Siswa')
            ->set('nomor_peserta', '2026-SISWA-01')
            ->call('startExam')
            ->assertHasNoErrors()
            ->assertSee('Ahmad Siswa')
            ->assertSee('Hitunglah');

        // Answer Question 1 with Option 'B'
        $component->call('pilihJawabanOpsi', 'B')
            ->assertSet('userAnswers.' . $this->soal1->id . '.pilihan', 'B');

        // Toggle Ragu-Ragu
        $component->call('toggleRagu')
            ->assertSet('userAnswers.' . $this->soal1->id . '.is_ragu', true);

        // Navigate to Question 2
        $component->call('nextSoal')
            ->assertSet('currentIndex', 1)
            ->assertSee('Ibukota negara');

        // Answer Question 2 (Isian)
        $component->call('simpanJawabanTeks', 'nusantara')
            ->assertSet('userAnswers.' . $this->soal2->id . '.teks', 'nusantara');

        // Submit exam
        $component->call('submitUjianFinal')
            ->assertSet('is_submitted', true)
            ->assertDispatched('ujian-selesai-resmi');

        // Check scores: Option B is correct (75.0), Isian "nusantara" matches (25.0) -> Total 100.0 (75:25)
        $sesi = $this->bankSoal->fresh()->sesis()->first();
        $this->assertEquals(100.0, (float) $sesi->skor_total);
        $this->assertEquals('selesai', $sesi->status);
    }

    public function test_can_access_preview_mode_directly(): void
    {
        $this->actingAs($this->admin);

        Livewire::withQueryParams(['preview' => 1])
            ->test(ExamRunner::class, ['bankSoal' => $this->bankSoal])
            ->assertSet('is_preview', true)
            ->assertSee('Hitunglah')
            ->assertSee('Guru');
    }

    public function test_student_route_works(): void
    {
        $response = $this->get(route('exam.run', ['bankSoal' => $this->bankSoal->kode_bank]));
        $response->assertSuccessful();
        $response->assertSee('Portal Ujian CBT Siswa');
    }

    public function test_multiple_essay_questions_keep_independent_answers(): void
    {
        $essay1 = $this->bankSoal->soals()->create([
            'nomor_urut' => 3,
            'tipe_soal' => 'essay',
            'bobot_nilai' => 10.0,
            'pertanyaan' => '<p>Jelaskan konsep fotosintesis:</p>',
        ]);

        $essay2 = $this->bankSoal->soals()->create([
            'nomor_urut' => 4,
            'tipe_soal' => 'essay',
            'bobot_nilai' => 10.0,
            'pertanyaan' => '<p>Jelaskan hukum gravitasi Newton:</p>',
        ]);

        $component = Livewire::test(ExamRunner::class, ['bankSoal' => $this->bankSoal]);
        $component->set('nama_peserta', 'Budi')->call('startExam');

        // Isi jawaban essay 1
        $component->call('selectSoal', 2)
            ->call('simpanJawabanTeks', $essay1->id, 'Jawaban fotosintesis');

        // Pindah ke essay 2
        $component->call('selectSoal', 3)
            ->call('simpanJawabanTeks', $essay2->id, 'Jawaban gravitasi');

        // Verifikasi jawaban essay 1 dan essay 2 independen di state userAnswers
        $component->assertSet('userAnswers.' . $essay1->id . '.teks', 'Jawaban fotosintesis');
        $component->assertSet('userAnswers.' . $essay2->id . '.teks', 'Jawaban gravitasi');

        // Verifikasi di database
        $sesi = $this->bankSoal->fresh()->sesis()->first();
        $this->assertDatabaseHas('ujian_jawabans', [
            'ujian_sesi_id' => $sesi->id,
            'soal_id' => $essay1->id,
            'jawaban_teks' => 'Jawaban fotosintesis',
        ]);
        $this->assertDatabaseHas('ujian_jawabans', [
            'ujian_sesi_id' => $sesi->id,
            'soal_id' => $essay2->id,
            'jawaban_teks' => 'Jawaban gravitasi',
        ]);
    }

    public function test_acak_soal_and_acak_opsi_in_exam_runner(): void
    {
        // Buat bank soal dengan acak_soal = true dan acak_opsi = true
        $bank = BankSoal::create([
            'judul' => 'Bank Soal Acak',
            'kode_bank' => 'BS-ACAK-01',
            'mapel_id' => $this->bankSoal->mapel_id,
            'user_id' => $this->admin->id,
            'tingkat' => '10',
            'acak_soal' => true,
            'acak_opsi' => true,
            'is_active' => true,
        ]);

        // Buat 10 soal pilihan ganda
        for ($i = 1; $i <= 10; $i++) {
            $soal = $bank->soals()->create([
                'nomor_urut' => $i,
                'tipe_soal' => 'pilihan_ganda',
                'bobot_nilai' => 10,
                'pertanyaan' => "<p>Pertanyaan nomor {$i}</p>",
            ]);

            $soal->opsiJawabans()->createMany([
                ['label' => 'A', 'teks_opsi' => "Opsi A Soal {$i}", 'is_jawaban_benar' => true, 'nomor_urut' => 1],
                ['label' => 'B', 'teks_opsi' => "Opsi B Soal {$i}", 'is_jawaban_benar' => false, 'nomor_urut' => 2],
                ['label' => 'C', 'teks_opsi' => "Opsi C Soal {$i}", 'is_jawaban_benar' => false, 'nomor_urut' => 3],
                ['label' => 'D', 'teks_opsi' => "Opsi D Soal {$i}", 'is_jawaban_benar' => false, 'nomor_urut' => 4],
            ]);
        }

        // Siswa 1 mulai ujian
        $component1 = Livewire::test(ExamRunner::class, ['bankSoal' => $bank]);
        $component1->set('nama_peserta', 'Siswa Satu')->call('startExam');
        $sesi1 = $component1->get('sesi');

        // Siswa 2 mulai ujian
        $component2 = Livewire::test(ExamRunner::class, ['bankSoal' => $bank]);
        $component2->set('nama_peserta', 'Siswa Dua')->call('startExam');
        $sesi2 = $component2->get('sesi');

        // Verifikasi urutan opsi tersimpan di ujian_jawabans
        $jawabans1 = $sesi1->jawabans()->orderBy('nomor_urut', 'asc')->get();
        $this->assertCount(10, $jawabans1);
        $this->assertNotNull($jawabans1->first()->urutan_opsi);
        $this->assertIsArray($jawabans1->first()->urutan_opsi);

        // Verifikasi ExamRunner menampilkan soal sesuai urutan siswa
        $soalsSiswa1 = $component1->get('soals');
        $this->assertCount(10, $soalsSiswa1);
        $this->assertEquals($jawabans1->pluck('soal_id')->toArray(), $soalsSiswa1->pluck('id')->toArray());

        // Verifikasi opsi display_label A, B, C, D
        $firstSoal = $soalsSiswa1->first();
        $displayLabels = $firstSoal->opsiJawabans->pluck('display_label')->toArray();
        $this->assertEquals(['A', 'B', 'C', 'D'], $displayLabels);

        // Siswa 1 menjawab soal pertama: temukan label mana yang teksnya adalah teks opsi yang benar
        $kunciAsli = $firstSoal->opsiJawabans->firstWhere('is_jawaban_benar', true);
        $selectedLabel = $kunciAsli->display_label;

        $component1->call('pilihJawabanOpsi', $selectedLabel, $firstSoal->id);
        $component1->call('submitUjianFinal');

        // Verifikasi bahwa jawaban dengan label acak tersebut dinilai BENAR (skor 10)
        $sesi1->refresh();
        $jawaban1_1 = $sesi1->jawabans()->where('soal_id', $firstSoal->id)->first();
        $this->assertTrue((bool) $jawaban1_1->is_benar);
        $this->assertEquals(10.0, (float) $jawaban1_1->skor_didapat);
    }

    public function test_benar_salah_options_are_never_shuffled(): void
    {
        $bank = BankSoal::create([
            'judul' => 'Bank Soal Benar Salah',
            'kode_bank' => 'BS-BS-01',
            'mapel_id' => $this->bankSoal->mapel_id,
            'user_id' => $this->admin->id,
            'tingkat' => '10',
            'acak_soal' => true,
            'acak_opsi' => true,
            'is_active' => true,
        ]);

        $soal = $bank->soals()->create([
            'nomor_urut' => 1,
            'tipe_soal' => 'benar_salah',
            'bobot_nilai' => 10,
            'pertanyaan' => '<p>Bumi mengelilingi matahari.</p>',
        ]);

        $soal->opsiJawabans()->createMany([
            ['label' => 'A', 'teks_opsi' => 'Benar', 'is_jawaban_benar' => true, 'nomor_urut' => 1],
            ['label' => 'B', 'teks_opsi' => 'Salah', 'is_jawaban_benar' => false, 'nomor_urut' => 2],
        ]);

        $component = Livewire::test(ExamRunner::class, ['bankSoal' => $bank]);
        $component->set('nama_peserta', 'Siswa BS')->call('startExam');
        $sesi = $component->get('sesi');

        $jawaban = $sesi->jawabans()->where('soal_id', $soal->id)->first();
        // urutan_opsi harus null untuk benar_salah
        $this->assertNull($jawaban->urutan_opsi);

        $soals = $component->get('soals');
        $bsOpsi = $soals->first()->opsiJawabans;
        $this->assertEquals('Benar', $bsOpsi[0]->teks_opsi);
        $this->assertEquals('Salah', $bsOpsi[1]->teks_opsi);
    }

    public function test_scoring_ratio_75_to_25_and_pure_100_percent(): void
    {
        // 1. Bank Soal Campuran: 20 PG + 5 Essay
        $bankCampuran = BankSoal::create([
            'judul' => 'Ujian Campuran 20 PG 5 Essay',
            'kode_bank' => 'BS-CAMPUR-25',
            'mapel_id' => $this->bankSoal->mapel_id,
            'user_id' => $this->admin->id,
            'tingkat' => '10',
            'acak_soal' => false,
            'acak_opsi' => false,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 20; $i++) {
            $soal = $bankCampuran->soals()->create([
                'nomor_urut' => $i,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "Soal PG {$i}",
            ]);
            $soal->opsiJawabans()->createMany([
                ['label' => 'A', 'teks_opsi' => 'Benar', 'is_jawaban_benar' => true, 'nomor_urut' => 1],
                ['label' => 'B', 'teks_opsi' => 'Salah', 'is_jawaban_benar' => false, 'nomor_urut' => 2],
            ]);
        }

        for ($i = 21; $i <= 25; $i++) {
            $bankCampuran->soals()->create([
                'nomor_urut' => $i,
                'tipe_soal' => 'essay',
                'pertanyaan' => "Soal Essay {$i}",
            ]);
        }

        // Test autoDistributeBobot() pada model
        $bankCampuran->autoDistributeBobot();
        $this->assertEquals(100.0, (float) $bankCampuran->fresh()->total_bobot);
        $this->assertEquals(3.75, (float) $bankCampuran->soals()->first()->bobot_nilai); // 75 / 20 = 3.75
        $this->assertEquals(5.00, (float) $bankCampuran->soals()->where('tipe_soal', 'essay')->first()->bobot_nilai); // 25 / 5 = 5.00

        // Siswa mengerjakan: Menjawab 10 PG benar dari 20 PG
        $runner = Livewire::test(ExamRunner::class, ['bankSoal' => $bankCampuran]);
        $runner->set('nama_peserta', 'Budi 75:25')->call('startExam');

        $soals = $runner->get('soals');
        for ($i = 0; $i < 10; $i++) {
            $runner->call('pilihJawabanOpsi', 'A', $soals[$i]->id); // 10 soal benar
        }

        $runner->call('submitUjianFinal');

        // Verifikasi perolehan skor objektif: 10 * 3.75 = 37.5 poin
        $sesi = $bankCampuran->sesis()->first();
        $this->assertEquals(37.5, (float) $sesi->skor_total);
        $this->assertEquals(75.0, $sesi->getTargetBobotObjektif());
        $this->assertEquals(25.0, $sesi->getTargetBobotSubjektif());

        // 2. Bank Soal Murni Objektif (10 PG, 0 Essay) -> Skala 100%
        $bankMurniPG = BankSoal::create([
            'judul' => 'Ujian Murni 10 PG',
            'kode_bank' => 'BS-MURNI-10',
            'mapel_id' => $this->bankSoal->mapel_id,
            'user_id' => $this->admin->id,
            'tingkat' => '10',
            'acak_soal' => false,
            'acak_opsi' => false,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $s = $bankMurniPG->soals()->create([
                'nomor_urut' => $i,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "Soal {$i}",
            ]);
            $s->opsiJawabans()->createMany([
                ['label' => 'A', 'teks_opsi' => 'Benar', 'is_jawaban_benar' => true, 'nomor_urut' => 1],
                ['label' => 'B', 'teks_opsi' => 'Salah', 'is_jawaban_benar' => false, 'nomor_urut' => 2],
            ]);
        }

        $bankMurniPG->autoDistributeBobot();
        $this->assertEquals(10.0, (float) $bankMurniPG->soals()->first()->bobot_nilai); // 100 / 10 = 10.0

        $runnerMurni = Livewire::test(ExamRunner::class, ['bankSoal' => $bankMurniPG]);
        $runnerMurni->set('nama_peserta', 'Budi 100%')->call('startExam');
        $soalsMurni = $runnerMurni->get('soals');
        for ($i = 0; $i < 8; $i++) {
            $runnerMurni->call('pilihJawabanOpsi', 'A', $soalsMurni[$i]->id); // 8 benar dari 10
        }
        $runnerMurni->call('submitUjianFinal');

        // Verifikasi perolehan skor murni 100%: 8 * 10 = 80.0 poin
        $sesiMurni = $bankMurniPG->sesis()->first();
        $this->assertEquals(80.0, (float) $sesiMurni->skor_total);
        $this->assertEquals(100.0, $sesiMurni->getTargetBobotObjektif());
        $this->assertEquals(0.0, $sesiMurni->getTargetBobotSubjektif());
    }
}


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

        // Check scores: Option B is correct (5.0), Isian "nusantara" matches (5.0) -> Total 10.0
        $sesi = $this->bankSoal->fresh()->sesis()->first();
        $this->assertEquals(10.0, (float) $sesi->skor_total);
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
}


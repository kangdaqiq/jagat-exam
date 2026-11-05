<?php

namespace Tests\Feature;

use App\Livewire\ExamRunner;
use App\Livewire\Siswa\PortalUjian;
use App\Livewire\Siswa\SiswaLogin;
use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPeserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UjianTokenFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $siswaUser;
    protected Siswa $siswa;
    protected Kelas $kelas;
    protected Mapel $mapel;
    protected BankSoal $bankSoal;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Admin & Guru
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::factory()->create([
            'name' => 'Budi Guru',
            'email' => 'guru@sekolah.sch.id',
            'role' => 'guru',
            'is_active' => true,
        ]);

        // 2. Setup Kelas & Siswa
        $this->kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan' => 'RPL',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'name' => 'Ahmad Siswa',
            'email' => '10234@siswa.cbt',
            'password' => Hash::make('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $this->siswaUser->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '10234',
            'nama' => 'Ahmad Siswa',
            'is_active' => true,
        ]);

        // 3. Setup Mapel & Bank Soal dengan 2 butir soal
        $this->mapel = Mapel::create([
            'nama_mapel' => 'Matematika',
            'kode_mapel' => 'MTK-X',
            'tingkat' => '10',
        ]);

        $this->bankSoal = BankSoal::create([
            'user_id' => $this->guru->id,
            'mapel_id' => $this->mapel->id,
            'judul' => 'Ulangan Harian Persamaan Kuadrat',
            'kode_bank' => 'BS-MTK-01',
            'durasi_menit' => 45,
            'is_active' => true,
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);

        $soal1 = Soal::create([
            'bank_soal_id' => $this->bankSoal->id,
            'nomor_urut' => 1,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Akar dari $x^2 - 4 = 0$ adalah?',
            'bobot_nilai' => 50,
        ]);

        $soal1->opsiJawabans()->createMany([
            ['label' => 'A', 'teks_opsi' => '$x = \pm 2$', 'is_jawaban_benar' => true, 'bobot_persen' => 100],
            ['label' => 'B', 'teks_opsi' => '$x = 0$', 'is_jawaban_benar' => false, 'bobot_persen' => 0],
        ]);

        $soal2 = Soal::create([
            'bank_soal_id' => $this->bankSoal->id,
            'nomor_urut' => 2,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Nilai dari $2 + 2$ adalah?',
            'bobot_nilai' => 50,
        ]);

        $soal2->opsiJawabans()->createMany([
            ['label' => 'A', 'teks_opsi' => '4', 'is_jawaban_benar' => true, 'bobot_persen' => 100],
            ['label' => 'B', 'teks_opsi' => '5', 'is_jawaban_benar' => false, 'bobot_persen' => 0],
        ]);
    }

    /**
     * Verifikasi Pemisahan Panel: /admin untuk Admin, /guru untuk Guru
     */
    public function test_panel_separation_guru_and_admin(): void
    {
        // 1. Admin bisa akses /admin dan /guru
        $this->actingAs($this->admin);
        $this->get('/admin')->assertStatus(200);
        $this->get('/guru')->assertStatus(200);

        // 2. Guru BISA akses /guru, tapi DITOLAK (403) di /admin
        $this->actingAs($this->guru);
        $this->get('/guru')->assertStatus(200);
        $this->get('/guru/ujians')->assertStatus(200);
        $this->get('/guru/bank-soals')->assertStatus(200);
        $this->get('/admin')->assertStatus(403);

        // 3. Siswa DITOLAK di /admin dan /guru
        $this->actingAs($this->siswaUser);
        $this->get('/admin')->assertStatus(403);
        $this->get('/guru')->assertStatus(403);
    }

    /**
     * Verifikasi Halaman Form Buat Ujian dapat diakses oleh Admin & Guru
     */
    public function test_admin_and_guru_can_access_ujians_pages(): void
    {
        $this->actingAs($this->admin);

        // Halaman List Ujian
        $response = $this->get('/admin/ujians');
        $response->assertStatus(200);

        // Halaman Create Ujian (UjianForm)
        $response = $this->get('/admin/ujians/create');
        $response->assertStatus(200);
    }

    /**
     * Flow A: Persiapan Ujian oleh Admin
     */
    public function test_flow_a_admin_creates_ujian_and_generates_exam_id_with_scheduled_status(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Penilaian Harian Matematika',
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30:00',
            'waktu_selesai' => '09:00:00',
            'durasi_menit' => 45,
            'status' => 'SCHEDULED',
        ]);

        $ujian->kelas()->attach($this->kelas->id);

        // Verifikasi Exam ID otomatis dibuat dan diawali 'EXAM-'
        $this->assertNotEmpty($ujian->exam_id);
        $this->assertStringStartsWith('EXAM-', $ujian->exam_id);

        // Verifikasi Status awal adalah SCHEDULED dan belum ada token
        $this->assertEquals('SCHEDULED', $ujian->status);
        $this->assertNull($ujian->token_ujian);

        // Sinkronkan peserta dari kelas
        $count = $ujian->syncPesertaFromKelas();
        $this->assertEquals(1, $count);

        $this->assertDatabaseHas('ujian_pesertas', [
            'ujian_id' => $ujian->id,
            'siswa_id' => $this->siswa->id,
            'user_id' => $this->siswaUser->id,
            'status' => 'BELUM_MULAI',
        ]);
    }

    /**
     * Flow B: Guru Membuka Ujian
     */
    public function test_flow_b_guru_starts_exam_status_becomes_ongoing_and_generates_token(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Penilaian Harian Matematika',
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'tanggal' => now()->toDateString(),
            'status' => 'SCHEDULED',
        ]);

        $this->assertEquals('SCHEDULED', $ujian->status);

        // Guru klik "Mulai Ujian"
        $token = $ujian->startExam();

        $ujian->refresh();

        // Verifikasi status menjadi ONGOING
        $this->assertEquals('ONGOING', $ujian->status);

        // Verifikasi kode token adalah 6 karakter alfanumerik uppercase
        $this->assertNotEmpty($ujian->token_ujian);
        $this->assertEquals(6, strlen($ujian->token_ujian));
        $this->assertEquals($token, $ujian->token_ujian);
        $this->assertNotNull($ujian->token_generated_at);
    }

    /**
     * Flow C: Siswa Login, Masukkan Token, dan Masuk ke Lembar Soal
     */
    public function test_flow_c_student_logs_in_enters_token_and_starts_exam(): void
    {
        // 1. Setup ujian dalam status ONGOING
        $ujian = Ujian::create([
            'nama_ujian' => 'Penilaian Harian Matematika',
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'tanggal' => now()->toDateString(),
            'durasi_menit' => 45,
            'status' => 'SCHEDULED',
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujian->kelas()->attach($this->kelas->id);
        $ujian->syncPesertaFromKelas();

        // Guru membuka ujian -> Token digenerate
        $token = $ujian->startExam();

        // 2. Siswa login via form login CBT
        Livewire::test(SiswaLogin::class)
            ->set('login', '10234')
            ->set('password', 'password123')
            ->call('doLogin')
            ->assertRedirect(route('siswa.portal'));

        $this->assertAuthenticatedAs($this->siswaUser);

        // 3. Siswa di portal memasukkan token yang salah -> pesan error
        Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->set('token_input', 'SALAH1')
            ->call('submitToken')
            ->assertHasErrors(['token_input']);

        // 4. Siswa memasukkan token yang benar -> dialihkan ke lembar pengerjaan soal
        $response = Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->set('token_input', $token)
            ->call('submitToken');

        // Verifikasi sesi pengerjaan ujian dibuat di database
        $this->assertDatabaseHas('ujian_sesis', [
            'ujian_id' => $ujian->id,
            'user_id' => $this->siswaUser->id,
            'nama_peserta' => 'Ahmad Siswa',
            'nomor_peserta' => '10234',
            'status' => 'mengerjakan',
        ]);

        // Verifikasi status peserta menjadi SEDANG_MENGERJAKAN
        $this->assertDatabaseHas('ujian_pesertas', [
            'ujian_id' => $ujian->id,
            'siswa_id' => $this->siswa->id,
            'status' => 'SEDANG_MENGERJAKAN',
        ]);

        $sesi = \App\Models\UjianSesi::where('ujian_id', $ujian->id)->where('user_id', $this->siswaUser->id)->first();
        $this->assertNotNull($sesi);

        $response->assertRedirect(route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]));

        // 5. Siswa mengerjakan soal di ExamRunner via token_sesi
        Livewire::actingAs($this->siswaUser)
            ->test(ExamRunner::class, ['token_sesi' => $sesi->token_sesi])
            ->assertSee('Akar dari $x^2 - 4 = 0$ adalah?')
            ->call('pilihJawabanOpsi', 'A')
            ->call('submitUjianFinal');

        // Verifikasi sesi selesai dan skor terhitung
        $sesi->refresh();
        $this->assertEquals('selesai', $sesi->status);
        $this->assertEquals(50, (float) $sesi->skor_total);

        // Verifikasi tabel ujian_pesertas terupdate menjadi SELESAI
        $peserta = UjianPeserta::where('ujian_id', $ujian->id)->where('siswa_id', $this->siswa->id)->first();
        $this->assertEquals('SELESAI', $peserta->status);
        $this->assertEquals(50, (float) $peserta->nilai);
    }

    /**
     * Verifikasi Tipe Ujian & Aturan Hak Akses:
     * - Asesmen Harian: Guru bisa buat, bisa langsung generate token instan dengan/tanpa jadwal
     * - Tengah Semester & Akhir Semester: Hanya Admin yang bisa buat
     */
    public function test_tipe_ujian_rules_and_instant_token_for_asesmen_harian(): void
    {
        // 1. Opsi Tipe untuk Admin (memiliki 3 tipe)
        $adminOptions = Ujian::getTipeOptions($this->admin);
        $this->assertArrayHasKey(Ujian::TIPE_ASESMEN_HARIAN, $adminOptions);
        $this->assertArrayHasKey(Ujian::TIPE_TENGAH_SEMESTER, $adminOptions);
        $this->assertArrayHasKey(Ujian::TIPE_AKHIR_SEMESTER, $adminOptions);

        // 2. Opsi Tipe untuk Guru (HANYA Asesmen Harian)
        $guruOptions = Ujian::getTipeOptions($this->guru);
        $this->assertArrayHasKey(Ujian::TIPE_ASESMEN_HARIAN, $guruOptions);
        $this->assertArrayNotHasKey(Ujian::TIPE_TENGAH_SEMESTER, $guruOptions);
        $this->assertArrayNotHasKey(Ujian::TIPE_AKHIR_SEMESTER, $guruOptions);

        // 3. Admin berhasil membuat ujian Asesmen Akhir Semester
        $ujianSemester = Ujian::create([
            'nama_ujian' => 'Penilaian Akhir Semester Ganjil',
            'tipe_ujian' => Ujian::TIPE_AKHIR_SEMESTER,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'tanggal' => now()->addDays(7)->toDateString(),
            'waktu_mulai' => '08:00:00',
            'durasi_menit' => 90,
            'status' => 'SCHEDULED',
        ]);
        $this->assertEquals(Ujian::TIPE_AKHIR_SEMESTER, $ujianSemester->tipe_ujian);
        $this->assertTrue($ujianSemester->isAsesmenAkhirSemester());

        // 4. Guru membuat Asesmen Harian dan langsung generate token instan (tanpa jadwal ketat)
        $ujianHarian = Ujian::create([
            'nama_ujian' => 'Kuis Harian Aljabar',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 30,
            'status' => 'SCHEDULED',
        ]);
        $this->assertTrue($ujianHarian->isAsesmenHarian());

        // Token langsung digenerate oleh guru
        $token = $ujianHarian->startExam();
        $this->assertNotEmpty($token);
        $this->assertEquals('ONGOING', $ujianHarian->status);
        $this->assertEquals(6, strlen($ujianHarian->token_ujian));
    }

    public function test_guru_and_admin_can_access_hasil_ujian_menu(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Ulangan Harian Matematika',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 45,
            'status' => 'COMPLETED',
        ]);

        // 1. Guru dapat mengakses menu Hasil Ujian di /guru/hasil-ujians
        $this->actingAs($this->guru);
        $resGuru = $this->get('/guru/hasil-ujians');
        $resGuru->assertStatus(200);

        // Guru dapat melihat detail hasil ujian
        $resDetail = $this->get('/guru/hasil-ujians/' . $ujian->id);
        $resDetail->assertStatus(200);

        // 2. Admin juga dapat mengakses menu Hasil Ujian di /admin/hasil-ujians
        $this->actingAs($this->admin);
        $resAdmin = $this->get('/admin/hasil-ujians');
        $resAdmin->assertStatus(200);

        $resAdminDetail = $this->get('/admin/hasil-ujians/' . $ujian->id);
        $resAdminDetail->assertStatus(200);

        // 3. Siswa dilarang mengakses
        $this->actingAs($this->siswaUser);
        $resSiswa = $this->get('/guru/hasil-ujians');
        $resSiswa->assertStatus(403);
    }

    public function test_token_expires_after_10_minutes_and_must_be_regenerated(): void
    {
        // Setup ujian ONGOING
        $ujian = Ujian::create([
            'nama_ujian' => 'Ulangan Harian Timer Token',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 45,
            'status' => 'ONGOING',
            'token_ujian' => 'EXPR99',
            'token_generated_at' => now()->subMinutes(11), // Sudah lewat 11 menit (exp)
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujian->kelas()->attach($this->kelas->id);
        $ujian->syncPesertaFromKelas();

        // 1. Verifikasi model mendeteksi token telah kedaluwarsa
        $this->assertTrue($ujian->isTokenExpired());
        $this->assertFalse($ujian->isTokenActive());
        $this->assertEquals(0, $ujian->sisaDetikToken());

        // 2. Siswa mencoba memasukkan token yang sudah kedaluwarsa
        Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->set('token_input', 'EXPR99')
            ->call('submitToken')
            ->assertHasErrors(['token_input'])
            ->assertSee('KEDALUWARSA');

        // 3. Guru me-refresh / re-generate token baru
        $newToken = $ujian->refreshToken();
        $ujian->refresh();

        $this->assertNotEquals('EXPR99', $newToken);
        $this->assertFalse($ujian->isTokenExpired());
        $this->assertTrue($ujian->isTokenActive());
        $this->assertGreaterThan(500, $ujian->sisaDetikToken());

        // 4. Siswa memasukkan token baru -> berhasil masuk ujian
        $res = Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->set('token_input', $newToken)
            ->call('submitToken')
            ->assertHasNoErrors();

        $sesi = \App\Models\UjianSesi::where('ujian_id', $ujian->id)->where('user_id', $this->siswaUser->id)->first();
        $this->assertNotNull($sesi);
        $res->assertRedirect(route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]));
    }
}


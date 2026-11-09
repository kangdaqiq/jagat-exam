<?php

namespace Tests\Feature;

use App\Filament\Resources\RuangUjians\RuangUjianResource;
use App\Livewire\ExamRunner;
use App\Livewire\Siswa\PortalUjian;
use App\Livewire\Siswa\SiswaLogin;
use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\RuangUjian;
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

    public function test_tailadmin_widgets_render_for_admin_and_guru(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(\App\Filament\Widgets\TailadminQuickActionWidget::class)
            ->assertSuccessful()
            ->assertSee('Selamat datang kembali');

        Livewire::test(\App\Filament\Widgets\TailadminCbtMetricsWidget::class)
            ->assertSuccessful()
            ->assertSee('Total Siswa Terdaftar');

        Livewire::test(\App\Filament\Widgets\TailadminLiveExamsWidget::class)
            ->assertSuccessful()
            ->assertSee('Monitoring Sesi Pelaksanaan Ujian');

        $this->actingAs($this->guru);
        Livewire::test(\App\Filament\Widgets\TailadminQuickActionWidget::class)
            ->assertSuccessful()
            ->assertSee('Guru Pengampu');

        Livewire::test(\App\Filament\Widgets\TailadminCbtMetricsWidget::class)
            ->assertSuccessful()
            ->assertSee('Bank Soal Saya');

        Livewire::test(\App\Filament\Widgets\TailadminLiveExamsWidget::class)
            ->assertSuccessful();
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

        // Verifikasi Exam ID otomatis dibuat dan diawali prefix sesuai tipe ujian (Default: SH untuk Ulangan Harian)
        $this->assertNotEmpty($ujian->exam_id);
        $this->assertStringStartsWith('SH-', $ujian->exam_id);

        // Verifikasi prefix STS untuk Tengah Semester dan SAS untuk Akhir Semester
        $ujianSts = Ujian::create([
            'nama_ujian' => 'PTS Semester Ganjil',
            'tipe_ujian' => Ujian::TIPE_TENGAH_SEMESTER,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'tanggal' => now(),
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '09:00',
            'durasi_menit' => 90,
        ]);
        $this->assertStringStartsWith('STS-', $ujianSts->exam_id);

        $ujianSas = Ujian::create([
            'nama_ujian' => 'PAS Semester Ganjil',
            'tipe_ujian' => Ujian::TIPE_AKHIR_SEMESTER,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'tanggal' => now(),
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '09:00',
            'durasi_menit' => 90,
        ]);
        $this->assertStringStartsWith('SAS-', $ujianSas->exam_id);

        // Verifikasi update tipe ujian otomatis memperbarui prefix exam_id
        $ujian->update(['tipe_ujian' => Ujian::TIPE_TENGAH_SEMESTER]);
        $this->assertStringStartsWith('STS-', $ujian->fresh()->exam_id);

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

        // Uji komponen Livewire ViewHasilUjian (filter, sort, exportCsv)
        $hasilModel = \App\Models\HasilUjian::find($ujian->id);
        Livewire::actingAs($this->guru)
            ->test(\App\Filament\Resources\HasilUjians\Pages\ViewHasilUjian::class, ['record' => $hasilModel->id])
            ->assertSuccessful()
            ->set('search', 'Siswa')
            ->call('toggleSort', 'nilai')
            ->assertSet('sortBy', 'nilai')
            ->call('exportCsv')
            ->assertFileDownloaded();

        // 2. Admin juga dapat mengakses menu Hasil Ujian di /admin/hasil-ujians
        $this->actingAs($this->admin);
        $resAdmin = $this->get('/admin/hasil-ujians');
        $resAdmin->assertStatus(200);

        $resAdminDetail = $this->get('/admin/hasil-ujians/' . $ujian->id);
        $resAdminDetail->assertStatus(200);

        // Verifikasi rute langsung export CSV dan print view
        $this->actingAs($this->guru);
        $resExport = $this->get(route('hasil-ujian.export-csv', $ujian->id));
        $resExport->assertStatus(200);
        $resExport->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $resPrint = $this->get(route('hasil-ujian.print', $ujian->id));
        $resPrint->assertStatus(200);
        $resPrint->assertSee('REKAPITULASI DAFTAR NILAI SISWA CBT');

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

    public function test_guru_and_admin_can_access_pengawasan_ujian_room_and_manage_session(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Ulangan Harian Pengawasan',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 45,
            'status' => 'SCHEDULED',
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujian->kelas()->attach($this->kelas->id);
        $ujian->syncPesertaFromKelas();

        // 1. Guru bisa mengakses halaman Pengawasan Ujian
        $urlGuru = "/guru/ujians/{$ujian->id}/pengawasan";
        $response = $this->actingAs($this->guru)->get($urlGuru);
        $response->assertStatus(200);
        $response->assertSee('Ruang Pengawasan Ujian');
        $response->assertSee('Ulangan Harian Pengawasan');

        // 2. Admin juga bisa mengakses halaman Pengawasan Ujian
        $urlAdmin = "/admin/ujians/{$ujian->id}/pengawasan";
        $responseAdmin = $this->actingAs($this->admin)->get($urlAdmin);
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Ruang Pengawasan Ujian');

        // 3. Proctor actions via Livewire (Mulai Ujian, Rilis Token, Selesaikan)
        Livewire::actingAs($this->guru)
            ->test(\App\Filament\Resources\Ujians\Pages\PengawasanUjian::class, ['record' => $ujian->id])
            ->assertSee('Ulangan Harian Pengawasan')
            ->call('mulaiUjian')
            ->assertHasNoErrors();

        $ujian->refresh();
        $this->assertEquals('ONGOING', $ujian->status);
        $this->assertNotEmpty($ujian->token_ujian);
        $this->assertTrue($ujian->isTokenActive());

        // Rilis token baru
        $oldToken = $ujian->token_ujian;
        Livewire::actingAs($this->guru)
            ->test(\App\Filament\Resources\Ujians\Pages\PengawasanUjian::class, ['record' => $ujian->id])
            ->call('rilisTokenBaru')
            ->assertHasNoErrors();

        $ujian->refresh();
        $this->assertNotEquals($oldToken, $ujian->token_ujian);

        // Selesaikan ujian
        Livewire::actingAs($this->guru)
            ->test(\App\Filament\Resources\Ujians\Pages\PengawasanUjian::class, ['record' => $ujian->id])
            ->call('selesaikanUjian')
            ->assertHasNoErrors();

        $ujian->refresh();
        $this->assertEquals('COMPLETED', $ujian->status);
    }

    public function test_security_persistent_violation_and_resume_exam_flow(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Ujian Keamanan & Integritas',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 45,
            'status' => 'SCHEDULED',
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujian->kelas()->attach($this->kelas->id);
        $ujian->syncPesertaFromKelas();
        $token = $ujian->startExam();

        // 1. Siswa masuk sesi ujian
        Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->set('token_input', $token)
            ->call('submitToken')
            ->assertHasNoErrors();

        $sesi = \App\Models\UjianSesi::where('ujian_id', $ujian->id)->where('user_id', $this->siswaUser->id)->first();
        $this->assertNotNull($sesi);

        // 2. Pelanggaran pindah tab tercatat secara persisten di database
        Livewire::actingAs($this->siswaUser)
            ->test(ExamRunner::class, ['token_sesi' => $sesi->token_sesi])
            ->call('triggerViolation')
            ->assertSet('violationCount', 1)
            ->call('triggerViolation')
            ->assertSet('violationCount', 2);

        $sesi->refresh();
        $this->assertEquals(2, $sesi->jumlah_pelanggaran);
        $this->assertNotNull($sesi->terakhir_pelanggaran_at);

        // Ketika halaman direfresh (komponen di-mount ulang), jumlah pelanggaran tetap 2
        // Jika terjadi pelanggaran ke-3, sesi otomatis terkunci (showViolationModal tidak bisa ditutup sendiri oleh siswa)
        Livewire::actingAs($this->siswaUser)
            ->test(ExamRunner::class, ['token_sesi' => $sesi->token_sesi])
            ->assertSet('violationCount', 2)
            ->call('triggerViolation')
            ->assertSet('violationCount', 3)
            ->assertSet('showViolationModal', true)
            ->call('tutupViolationModal')
            ->assertSet('showViolationModal', true);

        $this->assertTrue($sesi->fresh()->isTerblokirPelanggaran());

        // 3. Siswa lain tidak boleh membajak token_sesi milik siswa ini (403 Forbidden)
        $otherSiswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->actingAs($otherSiswaUser)
            ->get(route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]))
            ->assertStatus(403);

        // 4. Meskipun token 10 menit sudah kedaluwarsa, siswa yang sudah punya sesi aktif bisa klik Lanjutkan Ujian
        $ujian->update(['token_generated_at' => now()->subMinutes(20)]);
        $this->assertTrue($ujian->fresh()->isTokenExpired());

        Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->assertSee('Lanjutkan Mengerjakan Ujian')
            ->call('lanjutkanUjian', $ujian->id)
            ->assertRedirect(route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]));

        // 5. Proktor dapat melihat indikator pelanggaran/terkunci, menambah waktu +10 menit, dan mereset/membuka kunci sesi
        $peserta = UjianPeserta::where('ujian_id', $ujian->id)->where('siswa_id', $this->siswa->id)->first();
        Livewire::actingAs($this->guru)
            ->test(\App\Filament\Resources\Ujians\Pages\PengawasanUjian::class, ['record' => $ujian->id])
            ->assertSee('3x Pindah Tab')
            ->call('tambahWaktuSiswa', $peserta->id, 10)
            ->assertHasNoErrors()
            ->call('resetSesiSiswa', $peserta->id)
            ->assertHasNoErrors();

        $sesi->refresh();
        $this->assertEquals(55, $sesi->durasi_menit);
        $this->assertEquals(0, $sesi->jumlah_pelanggaran);
        $this->assertFalse($sesi->isTerblokirPelanggaran());
    }

    public function test_pwa_pengawas_login_and_portal_monitoring_actions(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Ujian PWA Pengawas',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 45,
            'status' => 'SCHEDULED',
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujian->kelas()->attach($this->kelas->id);
        $ujian->syncPesertaFromKelas();

        // 0. Mengakses /pengawas saat belum login harus redirect ke /pengawas/login (bukan /login siswa)
        $this->get('/pengawas')->assertRedirect(route('pengawas.login'));

        // 1. Siswa ditolak login di halaman khusus Pengawas PWA
        Livewire::test(\App\Livewire\Pengawas\PengawasLogin::class)
            ->set('login', '10234')
            ->set('password', 'password123')
            ->call('doLogin')
            ->assertHasErrors(['login']);

        // 2. Guru berhasil login di halaman khusus Pengawas PWA dan diarahkan ke /pengawas
        Livewire::test(\App\Livewire\Pengawas\PengawasLogin::class)
            ->set('login', 'guru@sekolah.sch.id')
            ->set('password', 'password')
            ->call('doLogin')
            ->assertRedirect(route('pengawas.portal'));

        // 3. Guru memilih ujian, memulai ujian, rilis token baru, dan mereset login siswa
        $portal = Livewire::actingAs($this->guru)
            ->test(\App\Livewire\Pengawas\PortalPengawas::class)
            ->assertSee('Ujian PWA Pengawas')
            ->call('pilihUjian', $ujian->id)
            ->assertSet('selectedUjianId', $ujian->id)
            ->call('mulaiUjian')
            ->assertHasNoErrors();

        $ujian->refresh();
        $this->assertEquals('ONGOING', $ujian->status);
        $firstToken = $ujian->token_ujian;
        $this->assertNotEmpty($firstToken);

        // Rilis token baru dari PWA Pengawas
        $portal->call('rilisTokenBaru');
        $ujian->refresh();
        $this->assertNotEquals($firstToken, $ujian->token_ujian);

        // Reset login siswa dari PWA Pengawas
        $peserta = UjianPeserta::where('ujian_id', $ujian->id)->where('siswa_id', $this->siswa->id)->first();
        $portal->call('resetSesiSiswa', $peserta->id)
            ->assertSee('berhasil di-reset');
    }

    public function test_single_device_lock_auto_close_and_print_berita_acara_kartu_peserta(): void
    {
        $ujian = Ujian::create([
            'nama_ujian' => 'Ujian Kunci Perangkat & Auto Close',
            'tipe_ujian' => Ujian::TIPE_ASESMEN_HARIAN,
            'mapel_id' => $this->mapel->id,
            'bank_soal_id' => $this->bankSoal->id,
            'guru_id' => $this->guru->id,
            'durasi_menit' => 30,
            'status' => 'SCHEDULED',
            'acak_soal' => false,
            'acak_opsi' => false,
        ]);
        $ujian->kelas()->attach($this->kelas->id);
        $ujian->syncPesertaFromKelas();
        $token = $ujian->startExam();

        // 1. Siswa login pertama kali -> active_session_token terisi
        Livewire::test(SiswaLogin::class)
            ->set('login', '10234')
            ->set('password', 'password123')
            ->call('doLogin')
            ->assertRedirect(route('siswa.portal'));

        $this->siswaUser->refresh();
        $this->assertNotNull($this->siswaUser->active_session_token);

        // 2. Percobaan login kedua dari perangkat/browser lain (ketika active_session_token masih terkunci) DITOLAK
        auth()->logout();
        session()->forget('cbt_device_token');
        Livewire::test(SiswaLogin::class)
            ->set('login', '10234')
            ->set('password', 'password123')
            ->call('doLogin')
            ->assertHasErrors(['login'])
            ->assertSee('sedang aktif');

        // 3. Pengawas menekan "Reset Login" -> kunci perangkat terbuka
        $peserta = UjianPeserta::where('ujian_id', $ujian->id)->where('siswa_id', $this->siswa->id)->first();
        Livewire::actingAs($this->guru)
            ->test(\App\Livewire\Pengawas\PortalPengawas::class)
            ->call('pilihUjian', $ujian->id)
            ->call('resetSesiSiswa', $peserta->id)
            ->assertHasNoErrors();

        $this->siswaUser->refresh();
        $this->assertNull($this->siswaUser->active_session_token);

        // 4. Sekarang siswa bisa login kembali dari perangkat baru
        auth()->logout();
        Livewire::test(SiswaLogin::class)
            ->set('login', '10234')
            ->set('password', 'password123')
            ->call('doLogin')
            ->assertHasNoErrors()
            ->assertRedirect(route('siswa.portal'));

        // 5. Uji Auto-Close sesi yang waktunya sudah habis
        Livewire::actingAs($this->siswaUser)
            ->test(PortalUjian::class)
            ->set('token_input', $token)
            ->call('submitToken')
            ->assertHasNoErrors();

        $sesi = \App\Models\UjianSesi::where('ujian_id', $ujian->id)->where('user_id', $this->siswaUser->id)->first();
        $this->assertNotNull($sesi);
        $this->assertEquals('mengerjakan', $sesi->status);

        // Simulasikan waktu mulai 40 menit yang lalu (durasi 30 menit -> sudah habis 10 menit lalu)
        $sesi->update(['waktu_mulai' => now()->subMinutes(40)]);

        $closedCount = $ujian->autoCloseExpiredSessions();
        $this->assertEquals(1, $closedCount);

        $sesi->refresh();
        $peserta->refresh();
        $this->assertEquals('selesai', $sesi->status);
        $this->assertEquals('SELESAI', $peserta->status);

        // 6. Uji halaman cetak Berita Acara & Daftar Hadir serta Kartu Peserta
        $this->actingAs($this->guru)
            ->get(route('hasil-ujian.berita-acara', $ujian->id))
            ->assertStatus(200)
            ->assertSee('BERITA ACARA PELAKSANAAN UJIAN CBT')
            ->assertSee('DAFTAR HADIR PESERTA UJIAN CBT')
            ->assertSee('Ahmad Siswa');

        $this->actingAs($this->guru)
            ->get(route('kartu-peserta.print'))
            ->assertStatus(200)
            ->assertSee('KARTU PESERTA')
            ->assertSee('10234')
            ->assertSee('Mahsun, M.Pd.')
            ->assertSee('E-SIGN VALID');

        // Test mode Kartu Meja (Hide Akun)
        $this->actingAs($this->guru)
            ->get(route('kartu-peserta.print', ['tipe' => 'meja']))
            ->assertStatus(200)
            ->assertSee('hide-credentials')
            ->assertSee('KARTU MEJA PESERTA')
            ->assertSee('No. Meja')
            ->assertSee('Mahsun, M.Pd.')
            ->assertSee('E-SIGN VALID');
    }

    public function test_kartu_meja_urutan_berselang_seling_antar_kelas(): void
    {
        $ruang = \App\Models\RuangUjian::create([
            'nama_ruang' => 'Ruang Khusus Ujian',
            'kapasitas' => 40,
            'is_active' => true,
        ]);

        $kelasA = \App\Models\Kelas::create([
            'nama_kelas' => 'X TB A',
            'tingkat' => '10',
            'jurusan' => 'Tata Busana',
        ]);

        $kelasB = \App\Models\Kelas::create([
            'nama_kelas' => 'X TBSM B',
            'tingkat' => '10',
            'jurusan' => 'Teknik Sepeda Motor',
        ]);

        // 3 Siswa Kelas A
        $sA1 = \App\Models\Siswa::create(['nama' => 'Andi TB', 'nis' => '1101', 'kelas_id' => $kelasA->id, 'ruang_ujian_id' => $ruang->id, 'is_active' => true]);
        $sA2 = \App\Models\Siswa::create(['nama' => 'Budi TB', 'nis' => '1102', 'kelas_id' => $kelasA->id, 'ruang_ujian_id' => $ruang->id, 'is_active' => true]);
        $sA3 = \App\Models\Siswa::create(['nama' => 'Cici TB', 'nis' => '1103', 'kelas_id' => $kelasA->id, 'ruang_ujian_id' => $ruang->id, 'is_active' => true]);

        // 2 Siswa Kelas B
        $sB1 = \App\Models\Siswa::create(['nama' => 'Dedi TBSM', 'nis' => '1201', 'kelas_id' => $kelasB->id, 'ruang_ujian_id' => $ruang->id, 'is_active' => true]);
        $sB2 = \App\Models\Siswa::create(['nama' => 'Eka TBSM', 'nis' => '1202', 'kelas_id' => $kelasB->id, 'ruang_ujian_id' => $ruang->id, 'is_active' => true]);

        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('kartu-peserta.print', [
                'ruang_ujian_id' => $ruang->id,
                'tipe' => 'meja',
                'urutan' => 'selang_seling',
            ]));

        $response->assertStatus(200);

        // Ambil urutan siswa dari view data
        $viewSiswas = $response->viewData('siswas');
        $this->assertCount(5, $viewSiswas);

        $kelasOrder = $viewSiswas->map(fn ($s) => $s->kelas->nama_kelas)->toArray();
        $mejaOrder = $viewSiswas->map(fn ($s) => $s->nomor_meja)->toArray();

        // Verifikasi nomor meja berurutan mulai 01 s/d 05
        $this->assertEquals(['01', '02', '03', '04', '05'], $mejaOrder);

        // Verifikasi selang-seling: tidak ada 2 meja berurutan dengan kelas yang sama!
        for ($i = 0; $i < count($kelasOrder) - 1; $i++) {
            $this->assertNotEquals(
                $kelasOrder[$i],
                $kelasOrder[$i + 1],
                "Meja " . ($i + 1) . " dan Meja " . ($i + 2) . " tidak boleh satu kelas!"
            );
        }
    }

    public function test_cetak_daftar_hadir_per_ruangan_dan_format_tanda_tangan(): void
    {
        $ruang = RuangUjian::create([
            'nama_ruang' => 'LAB KOMPUTER 1',
            'kode_ruang' => 'LAB-01',
            'kapasitas' => 20,
            'is_active' => true,
        ]);

        $siswa = Siswa::create([
            'nama' => 'Budi Peserta Hadir',
            'nis' => '998877',
            'kelas_id' => $this->kelas->id,
            'ruang_ujian_id' => $ruang->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('daftar-hadir.print', ['ruang_ujian_id' => $ruang->id]));

        $response->assertStatus(200);
        $response->assertSee('DAFTAR HADIR PESERTA');
        $response->assertSee('LAB KOMPUTER 1');
        $response->assertSee('Budi Peserta Hadir');
        $response->assertSee('998877');
        $response->assertSee('Tanda Tangan');
        $response->assertSee('Proktor');
        $response->assertSee('Pengawas');
    }

    public function test_isi_dan_cetak_berita_acara_ruangan(): void
    {
        $ruang = RuangUjian::create([
            'nama_ruang' => 'RUANG 2',
            'kode_ruang' => 'R-02',
            'kapasitas' => 25,
            'is_active' => true,
        ]);

        $bap = \App\Models\BeritaAcara::create([
            'ruang_ujian_id' => $ruang->id,
            'nama_ujian' => 'TES KEMAMPUAN AKADEMIK SMK',
            'tahun_pelajaran' => '2026',
            'hari' => 'Senin',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '07:30',
            'waktu_selesai' => '09:30',
            'username_sekolah' => 'K12130106',
            'nama_sekolah' => 'SMKS ASSUNIYAH',
            'id_server' => 'K12130106-AJ9K',
            'ruang' => 'RUANG 2',
            'sesi' => '1',
            'jumlah_peserta_seharusnya' => 20,
            'jumlah_hadir' => 19,
            'jumlah_tidak_hadir' => 1,
            'usernames_tidak_hadir' => ['TK1213010600134-3'],
            'catatan' => 'Pelaksanaan berjalan aman dan kondusif.',
            'nama_pengawas' => 'ATHIQ NUR AZIZAH',
            'nip_pengawas' => '198705122010012004',
            'nama_ketua_ujian' => 'Ahmad Daqiqi Syahrulloh, S.H.',
            'nip_ketua_ujian' => '198904152014021003',
            'nama_kepala_sekolah' => 'Mahsun, M.Pd.',
            'nip_kepala_sekolah' => '197508102005011008',
            'created_by' => $this->guru->id,
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('berita-acara.print', ['id' => $bap->id]));

        $response->assertStatus(200);
        $response->assertSee('BERITA ACARA PELAKSANAAN');
        $response->assertSee('RUANG 2');
        $response->assertSee('K12130106');
        $response->assertSee('SMKS ASSUNIYAH');
        $response->assertSee('TK1213010600134-3');
        $response->assertSee('Pelaksanaan berjalan aman dan kondusif.');
        $response->assertSee('Pengawas');
        $response->assertSee('Ketua Ujian');
        $response->assertSee('Kepala Sekolah');
        $response->assertSee('Mahsun, M.Pd.');
    }

    public function test_can_render_ruang_ujian_admin_page_with_actions(): void
    {
        RuangUjian::create([
            'nama_ruang' => 'RUANG 1',
            'kode_ruang' => 'R-01',
            'kapasitas' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(RuangUjianResource::getUrl('index'));

        $response->assertSuccessful();
        $response->assertSee('RUANG 1');
    }
}



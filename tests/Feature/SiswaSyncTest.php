<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AbsenSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiswaSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_sync_siswa_from_mocked_api(): void
    {
        // 1. Mock REST API Web Absensi
        Http::fake([
            '*/api/cbt-sync/data*' => Http::response([
                'success' => true,
                'school_id' => 6,
                'school_name' => 'SMK Assuniyah',
                'gurus' => [],
                'mapels' => [],
                'kelases' => [
                    [
                        'id' => 101,
                        'nama_kelas' => 'XII-RPL-1',
                        'tingkat' => 'XII',
                        'jurusan' => 'RPL',
                        'wali_kelas' => 'Budi',
                        'jumlah_siswa' => 1,
                        'school_id' => 6,
                        'school_name' => 'SMK Assuniyah',
                    ],
                ],
                'siswas' => [
                    [
                        'id' => 501,
                        'nama' => 'Ahmad Santoso',
                        'nis' => '10234',
                        'kelas_id' => 101,
                        'kelas_nama' => 'XII-RPL-1',
                        'no_wa' => '081234567890',
                        'email' => 'ahmad@siswa.cbt',
                        'password_hash' => null,
                        'school_id' => 6,
                        'school_name' => 'SMK Assuniyah',
                    ],
                ],
                'assignments' => [],
            ], 200),
        ]);

        $service = app(AbsenSyncService::class);
        $result = $service->syncData([
            'types' => ['kelas', 'siswa'],
            'school_id' => 6,
        ]);

        // Verifikasi hasil sinkronisasi
        $this->assertEquals(1, $result['kelas']['total']);
        $this->assertEquals(1, $result['siswa']['total']);
        $this->assertEquals(1, $result['siswa']['created']);

        // Verifikasi record siswa di DB
        $this->assertDatabaseHas('siswas', [
            'sync_id' => 501,
            'nis' => '10234',
            'nama' => 'Ahmad Santoso',
            'school_id' => 6,
            'school_name' => 'SMK Assuniyah',
        ]);

        // Verifikasi akun user CBT siswa dibuat
        $this->assertDatabaseHas('users', [
            'role' => 'siswa',
            'nip' => '10234',
            'name' => 'Ahmad Santoso',
            'email' => 'ahmad@siswa.cbt',
        ]);

        $siswa = Siswa::first();
        $this->assertNotNull($siswa->user_id);
        $this->assertNotNull($siswa->kelas_id);
        $this->assertEquals('XII-RPL-1', $siswa->kelas->nama_kelas);
        $this->assertTrue($siswa->user->isSiswa());
    }

    public function test_can_update_existing_siswa_on_subsequent_sync(): void
    {
        // Setup initial user & siswa
        $user = User::factory()->create([
            'role' => 'siswa',
            'nip' => '10234',
            'name' => 'Nama Lama',
            'email' => '10234@siswa.cbt',
        ]);

        $kelas = Kelas::create([
            'sync_id' => 101,
            'nama_kelas' => 'XII-RPL-1',
            'is_active' => true,
        ]);

        $siswa = Siswa::create([
            'sync_id' => 501,
            'user_id' => $user->id,
            'kelas_id' => $kelas->id,
            'nis' => '10234',
            'nama' => 'Nama Lama',
            'is_active' => true,
        ]);

        Http::fake([
            '*/api/cbt-sync/data*' => Http::response([
                'success' => true,
                'school_id' => 6,
                'school_name' => 'SMK Assuniyah',
                'gurus' => [],
                'mapels' => [],
                'kelases' => [],
                'siswas' => [
                    [
                        'id' => 501,
                        'nama' => 'Nama Baru Siswa',
                        'nis' => '10234',
                        'kelas_id' => 101,
                        'kelas_nama' => 'XII-RPL-1',
                        'no_wa' => '0899999999',
                        'email' => '10234@siswa.cbt',
                        'school_id' => 6,
                        'school_name' => 'SMK Assuniyah',
                    ],
                ],
                'assignments' => [],
            ], 200),
        ]);

        $service = app(AbsenSyncService::class);
        $result = $service->syncData([
            'types' => ['siswa'],
            'school_id' => 6,
        ]);

        $this->assertEquals(1, $result['siswa']['updated']);
        $this->assertEquals(0, $result['siswa']['created']);

        $siswa->refresh();
        $this->assertEquals('Nama Baru Siswa', $siswa->nama);
        $this->assertEquals('0899999999', $siswa->no_wa);

        $user->refresh();
        $this->assertEquals('Nama Baru Siswa', $user->name);
    }

    public function test_admin_can_access_siswa_list_page_and_guru_is_forbidden(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@cbt.com',
        ]);

        $guru = User::factory()->create([
            'role' => 'guru',
            'email' => 'guru@cbt.com',
        ]);

        // Admin berhasil membuka data siswa
        $response = $this->actingAs($admin)->get('/admin/siswas');
        $response->assertSuccessful();

        // Guru ditolak membuka data siswa
        $this->actingAs($guru)->get('/guru/siswas')->assertStatus(404);
        $this->actingAs($guru)->get('/admin/siswas')->assertStatus(403);
    }

    public function test_can_reset_password_individual_for_siswa(): void
    {
        $user = User::factory()->create([
            'role' => 'siswa',
            'nip' => '10234',
            'email' => '10234@siswa.cbt',
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword'),
        ]);

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nis' => '10234',
            'nama' => 'Ahmad Santoso',
            'is_active' => true,
        ]);

        // 1. Reset password ke NIS
        $siswa->resetPassword('10234');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('10234', $user->fresh()->password));

        // 2. Siswa bisa login dengan NIS dan password baru
        $this->assertTrue(\Illuminate\Support\Facades\Auth::attempt(['email' => $user->email, 'password' => '10234']));
    }

    public function test_can_reset_all_passwords_and_bulk_for_siswas(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin_reset@cbt.com']);

        $user1 = User::factory()->create(['role' => 'siswa', 'nip' => 'NIS01', 'email' => 'nis01@siswa.cbt', 'password' => \Illuminate\Support\Facades\Hash::make('old1')]);
        $siswa1 = Siswa::create(['user_id' => $user1->id, 'nis' => 'NIS01', 'nama' => 'Siswa 1', 'is_active' => true]);

        $user2 = User::factory()->create(['role' => 'siswa', 'nip' => 'NIS02', 'email' => 'nis02@siswa.cbt', 'password' => \Illuminate\Support\Facades\Hash::make('old2')]);
        $siswa2 = Siswa::create(['user_id' => $user2->id, 'nis' => 'NIS02', 'nama' => 'Siswa 2', 'is_active' => true]);

        // Uji action resetAllPassword di ListSiswas
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Siswas\Pages\ListSiswas::class)
            ->callAction('resetAllPassword', ['mode' => 'nis'])
            ->assertHasNoErrors()
            ->assertNotified('Reset Seluruh Password Selesai');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NIS01', $user1->fresh()->password));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NIS02', $user2->fresh()->password));

        // Uji action individual resetPassword di SiswaTable
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Siswas\Pages\ListSiswas::class)
            ->callTableAction('resetPassword', $siswa1, ['mode' => 'custom', 'password' => 'rahasia123'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Password Berhasil Direset');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('rahasia123', $user1->fresh()->password));
    }
}


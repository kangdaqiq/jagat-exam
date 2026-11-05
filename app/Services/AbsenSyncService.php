<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AbsenSyncService
{
    /**
     * Dapatkan konfigurasi default API dari .env
     */
    public static function getDefaultConfig(): array
    {
        return [
            'api_url' => env('ABSEN_API_URL', 'http://127.0.0.1:8080'),
            'api_key' => env('ABSEN_API_KEY', 'cbt_secret_sync_2026'),
        ];
    }

    /**
     * Tes koneksi API ke Web Absensi (Ping & Ringkasan Server)
     */
    public function testConnection(?string $apiUrl = null, ?string $apiKey = null): array
    {
        $default = self::getDefaultConfig();
        $url = rtrim($apiUrl ?: $default['api_url'], '/') . '/api/cbt-sync/ping';
        $key = $apiKey ?: $default['api_key'];

        try {
            $response = Http::withHeaders([
                'X-Exam-Key' => $key,
            ])->timeout(10)->get($url);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'message' => $json['message'] ?? 'Koneksi API Web Absensi Berhasil!',
                    'counts' => $json['counts'] ?? [],
                    'schools' => $json['schools'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke API (HTTP ' . $response->status() . '): ' . ($response->json('message') ?? $response->body()),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Kesalahan koneksi ke server Absensi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek & verifikasi nama sekolah berdasarkan Custom School ID via API
     */
    public function checkSchool(int|string $schoolId, ?string $apiUrl = null, ?string $apiKey = null): array
    {
        $default = self::getDefaultConfig();
        $url = rtrim($apiUrl ?: $default['api_url'], '/') . '/api/cbt-sync/check-school';
        $key = $apiKey ?: $default['api_key'];

        if (empty($schoolId)) {
            return [
                'success' => false,
                'message' => 'School ID tidak boleh kosong.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'X-Exam-Key' => $key,
            ])->timeout(10)->get($url, [
                'school_id' => $schoolId,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'found' => false,
                'message' => $response->json('message') ?? 'Sekolah tidak ditemukan atau server merespons error.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi server Web Absensi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar sekolah untuk opsi dropdown (jika admin ingin memilih dari daftar)
     */
    public function getSchoolsList(?string $apiUrl = null, ?string $apiKey = null): array
    {
        $test = $this->testConnection($apiUrl, $apiKey);
        if ($test['success'] && !empty($test['schools'])) {
            $options = [];
            foreach ($test['schools'] as $school) {
                $options[$school['id']] = $school['name'] . ' (ID: ' . $school['id'] . ' - ' . ($school['code'] ?? 'SCH') . ')';
            }
            return $options;
        }

        return [];
    }

    /**
     * Eksekusi penarikan data Guru, Mapel, dan Kelas via REST API Web Absensi
     */
    public function syncData(array $options): array
    {
        $default = self::getDefaultConfig();
        $url = rtrim($options['api_url'] ?? $default['api_url'], '/') . '/api/cbt-sync/data';
        $key = $options['api_key'] ?? $default['api_key'];
        $schoolId = !empty($options['school_id']) ? (int) $options['school_id'] : null;
        $types = $options['types'] ?? ['guru', 'mapel', 'kelas', 'siswa'];

        // Request ke API Web Absensi
        $queryParams = [];
        if ($schoolId) {
            $queryParams['school_id'] = $schoolId;
        }

        try {
            $response = Http::withHeaders([
                'X-Exam-Key' => $key,
            ])->timeout(60)->get($url, $queryParams);

            if (!$response->successful()) {
                throw new Exception('Gagal mengambil data dari API Web Absensi (HTTP ' . $response->status() . '): ' . $response->body());
            }

            $fetchedData = $response->json();
        } catch (Exception $e) {
            throw new Exception('Koneksi API gagal: ' . $e->getMessage());
        }

        $stats = [
            'guru' => ['created' => 0, 'updated' => 0, 'total' => 0],
            'mapel' => ['created' => 0, 'updated' => 0, 'total' => 0],
            'kelas' => ['created' => 0, 'updated' => 0, 'total' => 0],
            'siswa' => ['created' => 0, 'updated' => 0, 'total' => 0],
            'school_id' => $schoolId,
            'school_name' => $fetchedData['school_name'] ?? null,
            'synced_at' => now()->translatedFormat('d F Y H:i:s'),
        ];

        // 1. Simpan/Update Guru ke tabel Users (role: guru) di MySQL CBT
        if (in_array('guru', $types) && !empty($fetchedData['gurus'])) {
            $defaultPasswordHash = Hash::make('guru12345');
            $existingUsersBySyncId = User::whereNotNull('sync_id')->get()->keyBy('sync_id');
            $existingUsersByNip = User::whereNotNull('nip')->get()->keyBy('nip');
            $existingUsersByEmail = User::whereNotNull('email')->get()->keyBy('email');

            foreach ($fetchedData['gurus'] as $guruData) {
                $syncId = $guruData['id'] ?? null;
                $nip = !empty($guruData['nip']) ? trim($guruData['nip']) : null;
                $email = !empty($guruData['email']) ? trim($guruData['email']) : null;

                $user = null;
                if ($syncId && isset($existingUsersBySyncId[$syncId])) {
                    $user = $existingUsersBySyncId[$syncId];
                }
                if (!$user && $nip && isset($existingUsersByNip[$nip])) {
                    $user = $existingUsersByNip[$nip];
                }
                if (!$user && $email && isset($existingUsersByEmail[$email])) {
                    $user = $existingUsersByEmail[$email];
                }

                $name = trim($guruData['nama'] ?? 'Guru');
                $phone = $guruData['no_wa'] ?? null;
                $schoolIdRecord = $guruData['school_id'] ?? null;
                $schoolName = $guruData['school_name'] ?? $stats['school_name'];
                $passwordHash = $guruData['password_hash'] ?? null;

                if ($user) {
                    $user->name = $name;
                    if ($nip) $user->nip = $nip;
                    if ($phone) $user->phone = $phone;
                    $user->sync_id = $syncId;
                    $user->school_id = $schoolIdRecord;
                    if ($schoolName) $user->school_name = $schoolName;
                    if ($passwordHash) $user->password = $passwordHash;
                    $user->save();
                    $stats['guru']['updated']++;
                } else {
                    $fallbackEmail = $email ?: ($nip ? $nip . '@guru.cbt' : 'guru_' . ($syncId ?? Str::random(5)) . '@cbt.id');

                    if (User::where('email', $fallbackEmail)->exists()) {
                        $fallbackEmail = 'guru_' . ($syncId ?? Str::random(5)) . '_' . time() . '@cbt.id';
                    }

                    $newUser = User::create([
                        'sync_id' => $syncId,
                        'name' => $name,
                        'email' => $fallbackEmail,
                        'password' => $passwordHash ?: ($nip ? Hash::make($nip) : $defaultPasswordHash),
                        'role' => 'guru',
                        'nip' => $nip,
                        'phone' => $phone,
                        'school_id' => $schoolIdRecord,
                        'school_name' => $schoolName,
                        'is_active' => true,
                    ]);

                    if ($syncId) $existingUsersBySyncId[$syncId] = $newUser;
                    if ($nip) $existingUsersByNip[$nip] = $newUser;
                    $existingUsersByEmail[$fallbackEmail] = $newUser;

                    $stats['guru']['created']++;
                }
                $stats['guru']['total']++;
            }
        }

        // 2. Simpan/Update Mapel ke MySQL CBT
        if (in_array('mapel', $types) && !empty($fetchedData['mapels'])) {
            foreach ($fetchedData['mapels'] as $mapelData) {
                $syncId = $mapelData['id'] ?? null;
                $namaMapel = trim($mapelData['nama_mapel'] ?? '');
                if (empty($namaMapel)) continue;

                $mapel = null;
                if ($syncId) {
                    $mapel = Mapel::where('sync_id', $syncId)->first();
                }
                if (!$mapel) {
                    $mapel = Mapel::where('nama_mapel', $namaMapel)
                        ->where('school_id', $mapelData['school_id'] ?? null)
                        ->first();
                }

                $schoolName = $mapelData['school_name'] ?? $stats['school_name'];

                if ($mapel) {
                    $mapel->nama_mapel = $namaMapel;
                    $mapel->sync_id = $syncId;
                    $mapel->school_id = $mapelData['school_id'] ?? null;
                    if ($schoolName) $mapel->school_name = $schoolName;
                    $mapel->save();
                    $stats['mapel']['updated']++;
                } else {
                    Mapel::create([
                        'sync_id' => $syncId,
                        'nama_mapel' => $namaMapel,
                        'school_id' => $mapelData['school_id'] ?? null,
                        'school_name' => $schoolName,
                        'is_active' => true,
                    ]);
                    $stats['mapel']['created']++;
                }
                $stats['mapel']['total']++;
            }
        }

        // 3. Simpan/Update Kelas ke MySQL CBT
        if (in_array('kelas', $types) && !empty($fetchedData['kelases'])) {
            foreach ($fetchedData['kelases'] as $kelasData) {
                $syncId = $kelasData['id'] ?? null;
                $namaKelas = trim($kelasData['nama_kelas'] ?? '');
                if (empty($namaKelas)) continue;

                $kelas = null;
                if ($syncId) {
                    $kelas = Kelas::where('sync_id', $syncId)->first();
                }
                if (!$kelas) {
                    $kelas = Kelas::where('nama_kelas', $namaKelas)
                        ->where('school_id', $kelasData['school_id'] ?? null)
                        ->first();
                }

                $dataPayload = [
                    'sync_id' => $syncId,
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $kelasData['tingkat'] ?? null,
                    'jurusan' => $kelasData['jurusan'] ?? null,
                    'wali_kelas' => $kelasData['wali_kelas'] ?? null,
                    'jumlah_siswa' => (int) ($kelasData['jumlah_siswa'] ?? 0),
                    'school_id' => $kelasData['school_id'] ?? null,
                    'school_name' => $kelasData['school_name'] ?? $stats['school_name'],
                    'is_active' => true,
                ];

                if ($kelas) {
                    $kelas->update($dataPayload);
                    $stats['kelas']['updated']++;
                } else {
                    Kelas::create($dataPayload);
                    $stats['kelas']['created']++;
                }
                $stats['kelas']['total']++;
            }
        }

        // 4. Simpan/Update Siswa & Akun Login Siswa CBT ke MySQL
        if (in_array('siswa', $types) && !empty($fetchedData['siswas'])) {
            $kelasMapBySyncId = Kelas::whereNotNull('sync_id')->pluck('id', 'sync_id')->toArray();
            $defaultSiswaPasswordHash = Hash::make('siswa12345');

            foreach ($fetchedData['siswas'] as $siswaData) {
                $syncId = $siswaData['id'] ?? null;
                $nis = !empty($siswaData['nis']) ? trim($siswaData['nis']) : null;
                $nama = trim($siswaData['nama'] ?? 'Siswa');
                $noWa = $siswaData['no_wa'] ?? null;
                $email = !empty($siswaData['email']) ? trim($siswaData['email']) : null;
                $passwordHash = $siswaData['password_hash'] ?? null;
                $schoolIdRecord = $siswaData['school_id'] ?? $schoolId;
                $schoolName = $siswaData['school_name'] ?? $stats['school_name'];

                // Cari relasi ID kelas lokal di CBT
                $kelasSyncId = $siswaData['kelas_id'] ?? null;
                $kelasId = $kelasMapBySyncId[$kelasSyncId] ?? null;
                if (!$kelasId && !empty($siswaData['kelas_nama'])) {
                    $kelasId = Kelas::where('nama_kelas', $siswaData['kelas_nama'])
                        ->when($schoolIdRecord, fn ($q) => $q->where('school_id', $schoolIdRecord))
                        ->value('id');
                }

                // Cari data Siswa lokal jika sudah ada
                $siswa = null;
                if ($syncId) {
                    $siswa = Siswa::where('sync_id', $syncId)->first();
                }
                if (!$siswa && $nis) {
                    $siswa = Siswa::where('nis', $nis)
                        ->when($schoolIdRecord, fn ($q) => $q->where('school_id', $schoolIdRecord))
                        ->first();
                }

                // Cari akun User login siswa
                $user = null;
                if ($siswa && $siswa->user_id) {
                    $user = User::find($siswa->user_id);
                }
                if (!$user && $nis) {
                    $user = User::where('nip', $nis)->where('role', 'siswa')->first();
                }
                if (!$user && $email) {
                    $user = User::where('email', $email)->first();
                }

                $fallbackEmail = $email ?: ($nis ? $nis . '@siswa.cbt' : 'siswa_' . ($syncId ?? Str::random(5)) . '@siswa.cbt');

                if ($user) {
                    $user->name = $nama;
                    if ($nis) $user->nip = $nis;
                    if ($noWa) $user->phone = $noWa;
                    $user->school_id = $schoolIdRecord;
                    if ($schoolName) $user->school_name = $schoolName;
                    if ($passwordHash) {
                        $user->password = $passwordHash;
                    }
                    $user->save();
                } else {
                    if (User::where('email', $fallbackEmail)->exists()) {
                        $fallbackEmail = 'siswa_' . ($syncId ?? Str::random(5)) . '_' . time() . '@siswa.cbt';
                    }

                    $user = User::create([
                        'name' => $nama,
                        'email' => $fallbackEmail,
                        'password' => $passwordHash ?: ($nis ? Hash::make($nis) : $defaultSiswaPasswordHash),
                        'role' => 'siswa',
                        'nip' => $nis,
                        'phone' => $noWa,
                        'school_id' => $schoolIdRecord,
                        'school_name' => $schoolName,
                        'is_active' => true,
                    ]);
                }

                // Simpan / update ke tabel siswas
                $siswaPayload = [
                    'sync_id' => $syncId,
                    'user_id' => $user->id,
                    'school_id' => $schoolIdRecord,
                    'school_name' => $schoolName,
                    'kelas_id' => $kelasId,
                    'nis' => $nis,
                    'nama' => $nama,
                    'no_wa' => $noWa,
                    'is_active' => true,
                ];

                if ($siswa) {
                    $siswa->update($siswaPayload);
                    $stats['siswa']['updated']++;
                } else {
                    Siswa::create($siswaPayload);
                    $stats['siswa']['created']++;
                }
                $stats['siswa']['total']++;
            }

            // Update hitungan jumlah siswa di tabel kelas
            foreach (Kelas::cursor() as $k) {
                $count = Siswa::where('kelas_id', $k->id)->count();
                if ($count > 0 || $k->jumlah_siswa === 0) {
                    $k->update(['jumlah_siswa' => $count]);
                }
            }
        }

        // 5. Proses Sinkronisasi Jadwal Mengajar (Pemetaan Mapel & Kelas ke Guru)
        if (!empty($fetchedData['assignments'])) {
            $userMap = User::whereNotNull('sync_id')->pluck('id', 'sync_id')->toArray();
            $mapelMap = Mapel::whereNotNull('sync_id')->pluck('id', 'sync_id')->toArray();
            $kelasMap = Kelas::whereNotNull('sync_id')->pluck('id', 'sync_id')->toArray();

            $linkedJadwalCount = 0;

            foreach ($fetchedData['assignments'] as $assign) {
                $guruSyncId = $assign['guru_sync_id'] ?? null;
                $mapelSyncId = $assign['mapel_sync_id'] ?? null;
                $kelasSyncId = $assign['kelas_sync_id'] ?? null;

                $userId = $userMap[$guruSyncId] ?? null;
                $mapelId = $mapelMap[$mapelSyncId] ?? null;
                $kelasId = $kelasMap[$kelasSyncId] ?? null;

                // Hubungkan Guru dengan Mapel yang diampu
                if ($userId && $mapelId) {
                    \Illuminate\Support\Facades\DB::table('guru_mapel')->insertOrIgnore([
                        'user_id' => $userId,
                        'mapel_id' => $mapelId,
                    ]);
                }

                // Hubungkan Guru dengan Kelas yang diajar
                if ($userId && $kelasId) {
                    \Illuminate\Support\Facades\DB::table('guru_kelas')->insertOrIgnore([
                        'user_id' => $userId,
                        'kelas_id' => $kelasId,
                    ]);
                }

                // Hubungkan Kombinasi Spesifik Pengampu (Guru - Mapel - Kelas)
                if ($userId && $mapelId && $kelasId) {
                    \Illuminate\Support\Facades\DB::table('guru_pengampu')->insertOrIgnore([
                        'user_id' => $userId,
                        'mapel_id' => $mapelId,
                        'kelas_id' => $kelasId,
                        'school_id' => $assign['school_id'] ?? $schoolId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $linkedJadwalCount++;
                }
            }

            $stats['assignments'] = [
                'total_jadwal' => count($fetchedData['assignments']),
                'linked' => $linkedJadwalCount,
            ];
        }

        Cache::put('last_absen_sync_stats', $stats, now()->addDays(30));

        return $stats;
    }

    /**
     * Dapatkan ringkasan riwayat sinkronisasi terakhir
     */
    public static function getLastSyncStats(): ?array
    {
        return Cache::get('last_absen_sync_stats');
    }
}

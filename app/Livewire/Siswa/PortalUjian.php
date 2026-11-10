<?php

namespace App\Livewire\Siswa;

use App\Models\RuangUjian;
use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\UjianJawaban;
use App\Models\UjianPeserta;
use App\Models\UjianSesi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.exam')]
class PortalUjian extends Component
{
    public string $token_input = '';
    public ?int $selected_ujian_id = null;

    public function pilihTargetUjian(int $ujianId): void
    {
        $this->selected_ujian_id = $ujianId;
        $this->resetErrorBag('token_input');
    }

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (!$user->isSiswa() && ($user->isAdmin() || $user->isGuru())) {
            return redirect()->to('/admin');
        }

        // Validasi Single-Device Lock jika token perangkat tidak cocok
        if ($user->isSiswa() && filled($user->active_session_token) && filled(session('cbt_device_token'))) {
            if ($user->active_session_token !== session('cbt_device_token')) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login');
            }
        }
    }

    public function submitToken()
    {
        $this->validate([
            'token_input' => 'required|min:4|max:12',
        ], [
            'token_input.required' => 'Silakan masukkan kode token akses ujian.',
            'token_input.min' => 'Kode token minimal 4 karakter.',
        ]);

        $token = strtoupper(trim($this->token_input));
        $user = Auth::user();
        $siswa = $user->siswa?->loadMissing(['kelas', 'ruangUjian']);

        $ujian = null;
        $ruangToken = RuangUjian::where('token_ruang', $token)
            ->where('is_active', true)
            ->first();

        if (!$ruangToken) {
            $jadwal = \App\Models\JadwalRuang::where('token_ruang', $token)->with('ruangUjian')->first();
            if ($jadwal && $jadwal->ruangUjian && $jadwal->ruangUjian->is_active) {
                $ruangToken = $jadwal->ruangUjian;
            }
        }

        // 1A. PRIORITAS UTAMA: Token Ruangan Ujian (1 ruang bisa mix beberapa kelas, soal sesuai kelas/tingkat masing-masing siswa)
        if ($ruangToken) {
            // Validasi Otorisasi Ruang Ujian:
            // Siswa WAJIB terdaftar di ruangan ini untuk menggunakan token ruangan ini
            if (!$siswa || empty($siswa->ruang_ujian_id) || (int) $siswa->ruang_ujian_id !== (int) $ruangToken->id) {
                if ($siswa && !empty($siswa->ruang_ujian_id)) {
                    $namaRuangSiswa = $siswa->ruangUjian?->nama_ruang ?: 'ruangan lain';
                    $this->addError('token_input', "Kode token tersebut adalah token untuk {$ruangToken->nama_ruang}, sedangkan Anda terdaftar di {$namaRuangSiswa}. Silakan minta kode token kepada Guru Pengawas di {$namaRuangSiswa}.");
                } else {
                    $this->addError('token_input', "Kode token tersebut adalah token untuk {$ruangToken->nama_ruang}, sedangkan Anda belum terdaftar di ruangan ujian manapun. Silakan hubungi Proktor / Admin untuk penempatan ruang ujian Anda.");
                }
                return;
            }

            if ($ruangToken->isTokenExpired()) {
                $this->addError('token_input', "Kode token {$ruangToken->nama_ruang} telah KEDALUWARSA (masa berlaku 10 menit telah habis). Silakan minta Guru Pengawas ruangan untuk rilis token baru.");
                return;
            }

            if (!$siswa || !$siswa->kelas_id) {
                $this->addError('token_input', 'Akun Anda belum terhubung dengan Rombel Kelas/Tingkatan. Hubungi Proktor/Admin.');
                return;
            }

            // Cari jadwal ujian yang sesuai dengan kelas/tingkatan siswa ini
            $kandidatUjians = Ujian::query()
                ->whereIn('status', ['ONGOING', 'SCHEDULED'])
                ->where(function ($q) use ($siswa, $user) {
                    $q->whereHas('kelas', fn ($kq) => $kq->where('kelas.id', $siswa->kelas_id))
                      ->orWhereHas('pesertas', fn ($pq) => $pq->where('siswa_id', $siswa->id)->orWhere('user_id', $user->id));
                })
                ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 1 ELSE 2 END")
                ->orderBy('tanggal', 'asc')
                ->get();

            if ($this->selected_ujian_id) {
                $ujian = $kandidatUjians->firstWhere('id', $this->selected_ujian_id);
            }

            if (!$ujian) {
                // Utamakan ujian yang belum diselesaikan oleh siswa ini dan jadwalnya belum berakhir
                $selesaiUjianIds = UjianPeserta::where('user_id', $user->id)
                    ->where('status', 'SELESAI')
                    ->pluck('ujian_id')
                    ->all();

                $ujian = $kandidatUjians
                    ->reject(fn (Ujian $u) => in_array($u->id, $selesaiUjianIds) || $u->isJadwalBerakhir())
                    ->first()
                    ?: $kandidatUjians->first();
            }

            if (!$ujian) {
                $namaKelas = $siswa->kelas?->nama_kelas ?: 'Anda';
                $this->addError('token_input', "Token {$ruangToken->nama_ruang} valid, tetapi belum ada jadwal soal ujian aktif untuk Kelas/Tingkatan {$namaKelas}.");
                return;
            }

            // Jika status ujian untuk kelas ini masih SCHEDULED, otomatis aktifkan menjadi ONGOING saat token ruang digunakan
            if ($ujian->status === 'SCHEDULED' && !$ujian->isJadwalBerakhir()) {
                $ujian->update(['status' => 'ONGOING']);
            }
        } else {
            // 1B. FALLBACK: Cari Ujian yang sedang ONGOING dengan token per-ujian (untuk Ulangan Harian tanpa ruang / kompatibilitas)
            $ujian = Ujian::where('token_ujian', $token)
                ->where('status', 'ONGOING')
                ->first();

            if (!$ujian) {
                $this->addError('token_input', 'Kode token ruangan/ujian salah, sudah diganti, atau sesi belum dibuka oleh Guru Pengawas.');
                return;
            }

            // Jika siswa sudah terdaftar di ruang ujian, mereka wajib menggunakan token ruangan
            if ($siswa && !empty($siswa->ruang_ujian_id)) {
                $namaRuangSiswa = $siswa->ruangUjian?->nama_ruang ?: 'ruangan Anda';
                $this->addError('token_input', "Anda terdaftar di {$namaRuangSiswa}. Silakan masukkan kode token ruangan yang dirilis oleh Guru Pengawas di {$namaRuangSiswa}.");
                return;
            }

            // Jika ujian ini menggunakan sistem pembagian jadwal ruangan, siswa harus terdaftar di ruangan
            if ($ujian->jadwalRuangs()->exists()) {
                $this->addError('token_input', 'Ujian ini menggunakan sistem ruangan. Anda belum terdaftar di ruangan ujian manapun. Silakan hubungi Proktor.');
                return;
            }
        }

        // Cek apakah siswa sudah punya sesi aktif yang sedang dikerjakan pada ujian ini
        $existingActiveSesi = UjianSesi::where('ujian_id', $ujian->id)
            ->where('user_id', $user->id)
            ->where('status', 'mengerjakan')
            ->first();

        // Cek kedaluwarsa token per-ujian (jika masuk lewat token ujian langsung, bukan token ruang)
        if (!$ruangToken && !$existingActiveSesi && $ujian->isTokenExpired()) {
            $this->addError('token_input', 'Kode token ujian telah KEDALUWARSA (masa berlaku 10 menit telah habis). Silakan minta Guru Pengawas / Proktor untuk generate token baru.');
            return;
        }

        // Cek apakah jadwal waktu selesai ujian sudah terlewati
        if ($ujian->tanggal && $ujian->waktu_selesai) {
            $tglStr = $ujian->tanggal instanceof \Carbon\Carbon ? $ujian->tanggal->format('Y-m-d') : substr((string)$ujian->tanggal, 0, 10);
            $jadwalSelesai = \Carbon\Carbon::parse("{$tglStr} {$ujian->waktu_selesai}");
            if ($jadwalSelesai->isPast()) {
                $this->addError('token_input', 'Jadwal pelaksanaan sesi ujian ini telah berakhir pada pukul ' . substr($ujian->waktu_selesai, 0, 5) . ' WIB.');
                return;
            }
        }

        // 2. Cek apakah bank soal memiliki butir soal
        $bankSoal = $ujian->bankSoal;
        if (!$bankSoal || $bankSoal->soals()->count() === 0) {
            $this->addError('token_input', 'Paket bank soal untuk ujian ini belum memiliki butir soal.');
            return;
        }

        // 3. Verifikasi apakah siswa terdaftar sebagai peserta ujian ini
        $peserta = UjianPeserta::where('ujian_id', $ujian->id)
            ->where(function ($q) use ($siswa, $user) {
                if ($siswa) {
                    $q->where('siswa_id', $siswa->id)->orWhere('user_id', $user->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->first();

        // Jika belum ada di tabel peserta, cek apakah kelas siswa terdaftar di ujian ini
        if (!$peserta && $siswa && $siswa->kelas_id) {
            $isKelasIncluded = $ujian->kelas()->where('kelas.id', $siswa->kelas_id)->exists();
            if ($isKelasIncluded) {
                $peserta = UjianPeserta::create([
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                    'user_id' => $user->id,
                    'status' => 'BELUM_MULAI',
                ]);
            }
        }

        if (!$peserta) {
            $this->addError('token_input', 'Anda tidak terdaftar sebagai peserta pada ujian ini. Silakan hubungi Guru Pengawas / Proktor.');
            return;
        }

        // 4. Cek apakah siswa sudah menyelesaikan ujian ini
        if ($peserta->status === 'SELESAI') {
            $this->addError('token_input', 'Anda sudah menyelesaikan ujian ini dan lembar jawaban telah dikirim.');
            return;
        }

        // 5. Cari sesi pengerjaan yang sudah ada atau buat sesi baru
        $sesi = $existingActiveSesi;

        if (!$sesi) {
            $tokenSesi = Str::random(36);
            $durasiMenit = $ujian->durasi_menit ?: ($bankSoal->durasi_menit ?: 60);
            $waktuSelesai = now()->addMinutes($durasiMenit);

            // Jika ada batas jadwal waktu_selesai pada ruangan / ujian, sesuaikan agar durasi tidak melewati jadwal
            $targetTanggal = $ujian->tanggal;
            $targetWaktuSelesai = $ujian->waktu_selesai;

            // Prioritaskan waktu dari Jadwal Ruang jika siswa berada di ruangan ujian
            $effectiveRuangId = $ruangToken?->id ?? $siswa?->ruang_ujian_id;
            if ($effectiveRuangId) {
                $jr = \App\Models\JadwalRuang::where('ruang_ujian_id', $effectiveRuangId)
                    ->where('ujian_id', $ujian->id)
                    ->whereDate('tanggal', now()->toDateString())
                    ->latest()
                    ->first();
                if ($jr && $jr->waktu_selesai) {
                    $targetTanggal = $jr->tanggal;
                    $targetWaktuSelesai = $jr->waktu_selesai;
                }
            }

            if ($targetTanggal && $targetWaktuSelesai) {
                $tglStr = $targetTanggal instanceof \Carbon\Carbon ? $targetTanggal->format('Y-m-d') : substr((string)$targetTanggal, 0, 10);
                $jadwalSelesai = \Carbon\Carbon::parse("{$tglStr} {$targetWaktuSelesai}");
                if ($jadwalSelesai->isFuture() && $jadwalSelesai->lessThan($waktuSelesai)) {
                    $waktuSelesai = $jadwalSelesai;
                    $durasiMenit = max(1, (int) round(now()->diffInMinutes($jadwalSelesai)));
                }
            }

            $sesi = UjianSesi::create([
                'ujian_id' => $ujian->id,
                'ruang_ujian_id' => $ruangToken?->id ?? $siswa?->ruang_ujian_id,
                'bank_soal_id' => $bankSoal->id,
                'user_id' => $user->id,
                'token_sesi' => $tokenSesi,
                'device_token' => session('cbt_device_token'),
                'nama_peserta' => $siswa ? $siswa->nama : $user->name,
                'nomor_peserta' => $siswa ? $siswa->nis : ($user->nip ?: 'SISWA-' . $user->id),
                'waktu_mulai' => now(),
                'waktu_selesai' => $waktuSelesai,
                'durasi_menit' => $durasiMenit,
                'status' => 'mengerjakan',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Siapkan butir soal (acak atau urut sesuai pengaturan ujian / bank soal) dengan Bulk Insert 1 Query
            $soals = $bankSoal->soals()->with('opsiJawabans')->orderBy('nomor_urut', 'asc')->get();
            $acakSoal = $ujian->acak_soal ?? $bankSoal->acak_soal ?? true;
            $acakOpsi = $ujian->acak_opsi ?? $bankSoal->acak_opsi ?? true;

            if ($acakSoal) {
                $soals = $soals->shuffle();
            }

            $now = now();
            $jawabanRows = [];
            foreach ($soals->values() as $idx => $s) {
                $urutanOpsi = null;
                // Hanya acak opsi untuk tipe pilihan ganda dan pg kompleks (Benar/Salah tidak diacak)
                if ($acakOpsi && in_array($s->tipe_soal, ['pilihan_ganda', 'pg_kompleks']) && $s->opsiJawabans->isNotEmpty()) {
                    $urutanOpsi = $s->opsiJawabans->pluck('id')->shuffle()->values()->toArray();
                }

                $jawabanRows[] = [
                    'ujian_sesi_id' => $sesi->id,
                    'soal_id' => $s->id,
                    'nomor_urut' => $idx + 1,
                    'urutan_opsi' => $urutanOpsi !== null ? json_encode($urutanOpsi) : null,
                    'jawaban_pilihan' => null,
                    'jawaban_teks' => null,
                    'is_ragu' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($jawabanRows)) {
                UjianJawaban::insert($jawabanRows);
            }
        }

        // 6. Update status peserta menjadi SEDANG_MENGERJAKAN
        $peserta->update([
            'status' => 'SEDANG_MENGERJAKAN',
            'ujian_sesi_id' => $sesi->id,
            'waktu_mulai' => $peserta->waktu_mulai ?: now(),
        ]);

        session(["ujian_token_{$bankSoal->id}" => $sesi->token_sesi]);

        // 7. Arahkan siswa ke lembar ujian
        return redirect()->route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]);
    }

    /**
     * Lanjutkan sesi ujian yang sudah aktif tanpa harus mengetik ulang token 10 menit
     */
    public function lanjutkanUjian(int $ujianId)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $sesi = UjianSesi::where('ujian_id', $ujianId)
            ->where('user_id', $user->id)
            ->where('status', 'mengerjakan')
            ->first();

        if (!$sesi) {
            $this->addError('token_input', 'Sesi pengerjaan aktif tidak ditemukan. Silakan masukkan kode token dari Guru Pengawas.');
            return;
        }

        $ujian = $sesi->ujian;
        if ($ujian && $ujian->status === 'COMPLETED') {
            $sesi->koreksiDanSelesaikan();
            $this->addError('token_input', 'Pelaksanaan ujian ini telah ditutup oleh Guru Pengawas.');
            return;
        }

        session(["ujian_token_{$sesi->bank_soal_id}" => $sesi->token_sesi]);

        return redirect()->route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]);
    }

    public function logout()
    {
        $user = Auth::user();
        if ($user && $user->isSiswa()) {
            $user->update(['active_session_token' => null]);
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function render()
    {
        $user = Auth::user();
        $siswa = $user->siswa?->loadMissing(['kelas', 'ruangUjian']);

        // Auto-close sesi siswa yang sudah melewati batas waktu
        $expiredUserSesis = UjianSesi::where('user_id', $user->id)
            ->where('status', 'mengerjakan')
            ->whereNotNull('waktu_selesai')
            ->where('waktu_selesai', '<', now())
            ->get();

        foreach ($expiredUserSesis as $expSesi) {
            $expSesi->koreksiDanSelesaikan();
        }

        // Cari daftar ujian yang relevan untuk kelas siswa (optimasi eager loading soals_count)
        $daftarUjian = collect();
        if ($siswa && $siswa->kelas_id) {
            $daftarUjian = Ujian::whereHas('kelas', function ($q) use ($siswa) {
                $q->where('kelas.id', $siswa->kelas_id);
            })
            ->with([
                'mapel',
                'guru',
                'bankSoal' => fn ($q) => $q->withCount('soals'),
            ])
            ->whereIn('status', ['SCHEDULED', 'ONGOING', 'COMPLETED'])
            ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 1 WHEN status = 'SCHEDULED' THEN 2 ELSE 3 END")
            ->orderBy('tanggal', 'desc')
            ->take(6)
            ->get();
        }

        // Map sesi aktif siswa berdasarkan ujian_id agar siswa bisa langsung klik "Lanjutkan Ujian"
        $sesiAktifMap = UjianSesi::where('user_id', $user->id)
            ->where('status', 'mengerjakan')
            ->whereNotNull('ujian_id')
            ->get()
            ->keyBy('ujian_id');

        // Riwayat ujian siswa yang sudah selesai
        $riwayatUjian = UjianPeserta::where('user_id', $user->id)
            ->with(['ujian.mapel', 'ujian.bankSoal'])
            ->where('status', 'SELESAI')
            ->orderBy('waktu_selesai', 'desc')
            ->take(5)
            ->get();

        return view('livewire.siswa.portal-ujian', [
            'user' => $user,
            'siswa' => $siswa,
            'daftarUjian' => $daftarUjian,
            'sesiAktifMap' => $sesiAktifMap,
            'riwayatUjian' => $riwayatUjian,
        ]);
    }
}


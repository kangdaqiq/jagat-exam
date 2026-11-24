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
        $matchedJadwal = null;
        $ruangToken = RuangUjian::where('token_ruang', $token)
            ->where('is_active', true)
            ->first();

        if (!$ruangToken) {
            $matchedJadwal = \App\Models\JadwalRuang::where('token_ruang', $token)->with('ruangUjian')->first();
            if ($matchedJadwal && $matchedJadwal->ruangUjian && $matchedJadwal->ruangUjian->is_active) {
                $ruangToken = $matchedJadwal->ruangUjian;
            }
        } elseif ($siswa) {
            $matchedJadwal = \App\Models\JadwalRuang::where('ruang_ujian_id', $ruangToken->id)
                ->where('token_ruang', $token)
                ->latest()
                ->first();

            // Jika token dirilis langsung dari Ruang Ujian, sinkronkan ke JadwalRuang aktif hari ini
            if (! $matchedJadwal) {
                $timeNow = now()->format('H:i:s');
                $matchedJadwal = \App\Models\JadwalRuang::where('ruang_ujian_id', $ruangToken->id)
                    ->whereDate('tanggal', now()->toDateString())
                    ->where(function ($q) use ($timeNow) {
                        $q->where('waktu_mulai', '<=', $timeNow)
                          ->where('waktu_selesai', '>=', $timeNow);
                    })
                    ->first()
                    ?: \App\Models\JadwalRuang::where('ruang_ujian_id', $ruangToken->id)
                        ->whereDate('tanggal', now()->toDateString())
                        ->orderBy('waktu_mulai')
                        ->first();
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
            // Prioritaskan urutan subtest di JadwalRuang aktif jika tersedia
            $kandidatUjians = $matchedJadwal
                ? $matchedJadwal->getSubtestsForSiswa($siswa)
                : collect();

            if ($kandidatUjians->isEmpty()) {
                $kandidatUjians = Ujian::query()
                    ->whereIn('status', ['ONGOING', 'SCHEDULED'])
                    ->where(function ($q) use ($siswa, $user) {
                        $q->whereHas('kelas', fn ($kq) => $kq->where('kelas.id', $siswa->kelas_id))
                          ->orWhereHas('pesertas', fn ($pq) => $pq->where('siswa_id', $siswa->id)->orWhere('user_id', $user->id));
                    })
                    ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 1 ELSE 2 END")
                    ->orderBy('tanggal', 'asc')
                    ->get();
            }

            if ($this->selected_ujian_id) {
                $ujian = $kandidatUjians->firstWhere('id', $this->selected_ujian_id);
            }

            if (!$ujian) {
                // Utamakan subtest/ujian yang belum diselesaikan oleh siswa ini dan jadwalnya belum berakhir
                $selesaiUjianIds = UjianPeserta::where('user_id', $user->id)
                    ->where('status', 'SELESAI')
                    ->pluck('ujian_id')
                    ->all();

                $ujian = $kandidatUjians
                    ->reject(fn (Ujian $u) => in_array($u->id, $selesaiUjianIds) || in_array($u->status, ['COMPLETED', 'CANCELLED'], true) || $u->isJadwalBerakhir())
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
            if ($ujian->hasJadwalRuang()) {
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

        // 5. Cari sesi pengerjaan yang sudah ada atau buat sesi baru via helper bersama
        $sesi = UjianSesi::createOrResumeForSiswa(
            ujian: $ujian,
            user: $user,
            siswa: $siswa,
            peserta: $peserta,
            ruangUjianId: $ruangToken?->id ?? $siswa?->ruang_ujian_id,
            jadwalRuangId: $matchedJadwal?->id,
            deviceToken: session('cbt_device_token')
        );

        session(["ujian_token_{$bankSoal->id}" => $sesi->token_sesi]);

        // 6. Arahkan siswa ke lembar ujian
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

        // Cari daftar ujian yang relevan untuk kelas siswa HARI INI
        $daftarUjian = collect();
        $daftarAgenda = collect();
        $isRoomOngoing = false;

        if ($siswa && $siswa->kelas_id) {
            $today = now()->toDateString();

            // Cek apakah ada sesi di ruangan siswa yang sedang aktif/ONGOING hari ini
            if ($siswa->ruang_ujian_id) {
                $isRoomOngoing = \App\Models\JadwalRuang::where('ruang_ujian_id', $siswa->ruang_ujian_id)
                    ->whereDate('tanggal', $today)
                    ->where('status', 'ONGOING')
                    ->exists();

                if (! $isRoomOngoing && $siswa->ruangUjian) {
                    $isRoomOngoing = $siswa->ruangUjian->isTokenActive();
                }
            }

            $daftarUjian = Ujian::whereHas('kelas', function ($q) use ($siswa) {
                $q->where('kelas.id', $siswa->kelas_id);
            })
            ->with([
                'mapel',
                'guru',
                'bankSoal' => fn ($q) => $q->withCount('soals'),
            ])
            ->where(function ($q) use ($today) {
                $q->whereDate('tanggal', $today)
                  ->orWhereNull('tanggal');
            })
            ->whereIn('status', ['SCHEDULED', 'ONGOING', 'COMPLETED'])
            ->orderBy('waktu_mulai', 'asc')
            ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 1 WHEN status = 'SCHEDULED' THEN 2 ELSE 3 END")
            ->get();

            // Ambil jadwal ruangan siswa hari ini untuk mencocokkan nomor sesi resmi
            $jadwalRuangsToday = $siswa->ruang_ujian_id
                ? \App\Models\JadwalRuang::where('ruang_ujian_id', $siswa->ruang_ujian_id)
                    ->whereDate('tanggal', $today)
                    ->orderBy('waktu_mulai')
                    ->get()
                : collect();

            // Pasangkan info sesi dan rentang jam pengerjaan ke setiap ujian
            $daftarUjian->each(function (Ujian $uj, int $idx) use ($jadwalRuangsToday) {
                // 1. Jadwal Jam
                $waktuMulai = $uj->waktu_mulai ? substr((string) $uj->waktu_mulai, 0, 5) : null;
                $waktuSelesai = $uj->waktu_selesai ? substr((string) $uj->waktu_selesai, 0, 5) : null;
                $uj->jadwal_jam = ($waktuMulai && $waktuSelesai)
                    ? "{$waktuMulai} - {$waktuSelesai} WIB"
                    : ($waktuMulai ? "Mulai {$waktuMulai} WIB" : "Durasi {$uj->durasi_menit} Menit");

                // 2. Nomor Sesi
                $matchedJr = $jadwalRuangsToday->first(function ($jr) use ($uj) {
                    if ($jr->waktu_mulai && $uj->waktu_mulai) {
                        return substr((string) $jr->waktu_mulai, 0, 5) === substr((string) $uj->waktu_mulai, 0, 5);
                    }
                    return (int) $jr->ujian_id === (int) $uj->id || $jr->ujians->contains('id', $uj->id);
                });

                if ($matchedJr && $matchedJr->sesi_ke) {
                    $uj->nomor_sesi = "Sesi {$matchedJr->sesi_ke}";
                } elseif (preg_match('/\b(S\d+|Sesi\s*\d+)\b/i', $uj->nama_ujian, $matches)) {
                    $uj->nomor_sesi = strtoupper(str_replace('sesi', 'Sesi ', $matches[0]));
                } else {
                    $uj->nomor_sesi = 'Sesi ' . ($idx + 1);
                }
            });

            // =========================================================================
            // Kelompokkan sesi ujian gandeng (berurutan langsung) menjadi 1 tampilan agenda
            // =========================================================================
            $skipIds = [];
            for ($i = 0; $i < $daftarUjian->count(); $i++) {
                $uj = $daftarUjian[$i];
                if (in_array($uj->id, $skipIds)) {
                    continue;
                }

                $nextUj = $daftarUjian->get($i + 1);
                $isGandeng = false;

                if ($nextUj && ! in_array($nextUj->id, $skipIds)) {
                    $end1 = $uj->waktu_selesai ? strtotime((string) $uj->waktu_selesai) : null;
                    $start2 = $nextUj->waktu_mulai ? strtotime((string) $nextUj->waktu_mulai) : null;

                    if ($end1 && $start2) {
                        $diffMinutes = ($start2 - $end1) / 60;
                        if ($diffMinutes >= 0 && $diffMinutes <= 15) {
                            $isGandeng = true;
                        }
                    }

                    // Cek nomor sesi berurutan (misal Sesi 1 dan Sesi 2)
                    if (! $isGandeng && isset($uj->nomor_sesi, $nextUj->nomor_sesi)) {
                        preg_match('/\d+/', $uj->nomor_sesi, $m1);
                        preg_match('/\d+/', $nextUj->nomor_sesi, $m2);
                        if (! empty($m1[0]) && ! empty($m2[0]) && ((int) $m2[0] === (int) $m1[0] + 1)) {
                            if ($end1 && $start2) {
                                $diffMinutes = ($start2 - $end1) / 60;
                                if ($diffMinutes <= 15) {
                                    $isGandeng = true;
                                }
                            } else {
                                $isGandeng = true;
                            }
                        }
                    }
                }

                if ($isGandeng && $nextUj) {
                    $skipIds[] = $nextUj->id;

                    $item = new \stdClass();
                    $item->is_gandeng = true;
                    $item->id = $uj->id;
                    $item->secondary_id = $nextUj->id;
                    $item->ujians = collect([$uj, $nextUj]);
                    $item->nama_agenda = ($uj->mapel?->nama_mapel ?: $uj->nama_ujian) . ' & ' . ($nextUj->mapel?->nama_mapel ?: $nextUj->nama_ujian);
                    $item->nama_ujian = $item->nama_agenda;
                    $item->mapel_nama = ($uj->mapel?->nama_mapel ?: 'Subtest 1') . ' & ' . ($nextUj->mapel?->nama_mapel ?: 'Subtest 2');

                    $s1 = $uj->nomor_sesi ?: 'Sesi 1';
                    $s2 = $nextUj->nomor_sesi ?: 'Sesi 2';
                    $item->nomor_sesi = "{$s1} & {$s2} (Gandeng)";

                    $wMulai = $uj->waktu_mulai ? substr((string) $uj->waktu_mulai, 0, 5) : '07:30';
                    $wSelesai = $nextUj->waktu_selesai ? substr((string) $nextUj->waktu_selesai, 0, 5) : ($uj->waktu_selesai ? substr((string) $uj->waktu_selesai, 0, 5) : '09:30');
                    $item->jadwal_jam = "{$wMulai} - {$wSelesai} WIB";

                    $item->durasi_menit = ((int) ($uj->durasi_menit ?: 60)) + ((int) ($nextUj->durasi_menit ?: 60));
                    $item->total_soal = ($uj->bankSoal?->soals_count ?? 0) + ($nextUj->bankSoal?->soals_count ?? 0);
                    $item->guru_nama = $uj->guru?->name ?: ($nextUj->guru?->name ?: 'Guru Pengawas');
                    $item->tipe_label = $uj->getTipeLabel();

                    if ($uj->status === 'ONGOING' || $nextUj->status === 'ONGOING' || $isRoomOngoing) {
                        $item->status = 'ONGOING';
                    } elseif ($uj->status === 'COMPLETED' && $nextUj->status === 'COMPLETED') {
                        $item->status = 'COMPLETED';
                    } else {
                        $item->status = 'SCHEDULED';
                    }

                    $item->subtests = [
                        [
                            'urutan' => 1,
                            'nama' => $uj->mapel?->nama_mapel ?: $uj->nama_ujian,
                            'durasi' => $uj->durasi_menit ?: 60,
                            'soal' => $uj->bankSoal?->soals_count ?? 0,
                            'jam' => $uj->jadwal_jam,
                            'status' => $uj->status,
                            'ujian_id' => $uj->id,
                        ],
                        [
                            'urutan' => 2,
                            'nama' => $nextUj->mapel?->nama_mapel ?: $nextUj->nama_ujian,
                            'durasi' => $nextUj->durasi_menit ?: 60,
                            'soal' => $nextUj->bankSoal?->soals_count ?? 0,
                            'jam' => $nextUj->jadwal_jam,
                            'status' => $nextUj->status,
                            'ujian_id' => $nextUj->id,
                        ],
                    ];

                    $daftarAgenda->push($item);
                } else {
                    $item = new \stdClass();
                    $item->is_gandeng = false;
                    $item->id = $uj->id;
                    $item->secondary_id = null;
                    $item->ujians = collect([$uj]);
                    $item->nama_agenda = $uj->nama_ujian;
                    $item->nama_ujian = $uj->nama_ujian;
                    $item->mapel_nama = $uj->mapel?->nama_mapel ?: 'Mata Pelajaran';
                    $item->nomor_sesi = $uj->nomor_sesi ?: 'Sesi 1';
                    $item->jadwal_jam = $uj->jadwal_jam;
                    $item->durasi_menit = $uj->durasi_menit ?: 60;
                    $item->total_soal = $uj->bankSoal?->soals_count ?? ($uj->bankSoal?->soals()->count() ?: 0);
                    $item->guru_nama = $uj->guru?->name ?: 'Guru Pengawas';
                    $item->tipe_label = $uj->getTipeLabel();

                    if ($uj->status === 'ONGOING' || $isRoomOngoing) {
                        $item->status = 'ONGOING';
                    } else {
                        $item->status = $uj->status;
                    }

                    $item->subtests = [];

                    $daftarAgenda->push($item);
                }
            }
        }

        // Tentukan apakah ada sesi yang aktif/ONGOING saat ini
        $hasOngoingSesi = $isRoomOngoing || $daftarAgenda->contains(fn ($it) => $it->status === 'ONGOING');

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
            'daftarAgenda' => $daftarAgenda,
            'daftarUjian' => $daftarUjian,
            'hasOngoingSesi' => $hasOngoingSesi,
            'sesiAktifMap' => $sesiAktifMap,
            'riwayatUjian' => $riwayatUjian,
        ]);
    }
}


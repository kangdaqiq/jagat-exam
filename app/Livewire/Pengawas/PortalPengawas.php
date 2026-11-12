<?php

namespace App\Livewire\Pengawas;

use App\Models\BeritaAcara;
use App\Models\JadwalRuang;
use App\Models\RuangUjian;
use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\UjianPeserta;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.pengawas-pwa')]
#[Title('Portal Pengawas Ujian - Examuna PWA')]
class PortalPengawas extends Component
{
    #[Url(as: 'ruang')]
    public ?int $selectedRuangId = null;

    #[Url(as: 'sesi_ruang')]
    public ?int $selectedJadwalRuangId = null;

    #[Url(as: 'ujian')]
    public ?int $selectedUjianId = null;

    public string $tabMode = 'ruang'; // 'ruang' | 'ujian'
    public string $scopeUjian = 'saya'; // 'saya' | 'semua'
    public string $scopeRuang = 'saya'; // 'saya' | 'semua'
    public string $filterSesiRuang = 'semua'; // 'semua' | 'aktif' | 'selesai'
    public string $search = '';
    public string $filterStatus = 'all'; // 'all' | 'SEDANG_MENGERJAKAN' | 'BELUM_MULAI' | 'SELESAI' | 'PELANGGARAN'
    public ?int $filterKelas = null;
    public ?int $selectedPesertaId = null;
    public ?int $bapJadwalRuangId = null;
    public array $bapForm = [];
    public ?string $flashMessage = null;
    public string $flashType = 'success';

    public function mount(?int $ujian = null)
    {
        if (!Auth::check()) {
            return redirect()->route('pengawas.login');
        }

        $user = Auth::user();
        if (!$user->isGuru() && !$user->isAdmin()) {
            return redirect()->route('siswa.portal');
        }

        if ($user->isAdmin()) {
            $this->scopeUjian = 'semua';
            $this->scopeRuang = 'semua';
        }

        if ($ujian) {
            $this->selectedUjianId = $ujian;
            $this->tabMode = 'ujian';
        } elseif ($this->selectedJadwalRuangId || $this->selectedRuangId) {
            $this->tabMode = 'ruang';
        } elseif (RuangUjian::where('is_active', true)->count() === 0 && JadwalRuang::count() === 0) {
            $this->tabMode = 'ujian';
        }
    }

    public function pilihJadwalRuang(int $jadwalRuangId): void
    {
        $jadwal = JadwalRuang::find($jadwalRuangId);
        if (! $jadwal) {
            return;
        }

        $this->selectedJadwalRuangId = $jadwal->id;
        $this->selectedRuangId = null;
        $this->selectedUjianId = null;
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterKelas = null;
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
    }

    public function pilihRuang(int $ruangId): void
    {
        $ruang = RuangUjian::find($ruangId);
        if (!$ruang) {
            return;
        }

        $this->selectedRuangId = $ruang->id;
        $this->selectedJadwalRuangId = null;
        $this->selectedUjianId = null;
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterKelas = null;
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
    }

    public function pilihUjian(int $ujianId): void
    {
        $ujian = Ujian::find($ujianId);
        if (!$ujian) {
            return;
        }

        $this->selectedUjianId = $ujian->id;
        $this->selectedRuangId = null;
        $this->selectedJadwalRuangId = null;
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterKelas = null;
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
    }

    public function kembaliKeDaftarUjian(): void
    {
        $this->selectedUjianId = null;
        $this->selectedRuangId = null;
        $this->selectedJadwalRuangId = null;
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
    }

    public function getDaftarSesiRuangProperty()
    {
        $user = Auth::user();
        $query = JadwalRuang::with([
            'ruangUjian.siswas.kelas',
            'ujian.mapel',
            'ujian.kelas',
            'ujians.mapel',
            'ujians.kelas',
            'pengawas',
            'pengawasPendamping',
            'beritaAcaras',
        ]);

        if ($user && $user->isGuru() && $this->scopeRuang === 'saya') {
            $hasPenugasan = JadwalRuang::query()
                ->where(function ($q) use ($user) {
                    $q->where('pengawas_id', $user->id)
                      ->orWhere('pengawas_pendamping_id', $user->id)
                      ->orWhereHas('ruangUjian', fn ($rq) => $rq->where('pengawas_id', $user->id));
                })
                ->exists();

            if ($hasPenugasan) {
                $query->where(function ($q) use ($user) {
                    $q->where('pengawas_id', $user->id)
                      ->orWhere('pengawas_pendamping_id', $user->id)
                      ->orWhereHas('ruangUjian', fn ($rq) => $rq->where('pengawas_id', $user->id));
                });
            }
        }

        if ($this->filterSesiRuang === 'aktif') {
            $query->whereIn('status', ['SCHEDULED', 'ONGOING']);
        } elseif ($this->filterSesiRuang === 'selesai') {
            $query->where('status', 'COMPLETED');
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 0 WHEN status = 'SCHEDULED' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal')
            ->orderBy('sesi_ke')
            ->orderBy('waktu_mulai')
            ->get();
    }

    public function getActiveJadwalRuangProperty(): ?JadwalRuang
    {
        if (! $this->selectedJadwalRuangId) {
            return null;
        }

        $jr = JadwalRuang::with([
            'ruangUjian.siswas.kelas',
            'ujian.mapel',
            'ujian.kelas',
            'ujians.mapel',
            'ujians.kelas',
            'pengawas',
            'pengawasPendamping',
            'beritaAcaras',
        ])->find($this->selectedJadwalRuangId);

        if (! $jr) {
            return null;
        }

        foreach ($jr->getAllUjians() as $uj) {
            if ($uj->status === 'ONGOING') {
                $uj->autoCloseExpiredSessions();
            }
        }

        return $jr;
    }

    /**
     * Daftar peserta pada sesi JadwalRuang tertentu (persisten untuk sesi berlangsung maupun riwayat selesai).
     */
    public function getPesertasSesiRuangListProperty()
    {
        $jr = $this->activeJadwalRuang;
        if (! $jr) {
            return collect();
        }

        $siswas = $jr->getSiswasForSession();

        if (! empty($this->search)) {
            $term = mb_strtolower(trim($this->search));
            $siswas = $siswas->filter(function (Siswa $s) use ($term) {
                return str_contains(mb_strtolower((string) $s->nama), $term)
                    || str_contains(mb_strtolower((string) $s->nis), $term);
            })->values();
        }

        if (! empty($this->filterKelas)) {
            $siswas = $siswas->where('kelas_id', (int) $this->filterKelas)->values();
        }

        $allUjians = $jr->getAllUjians();
        $ujianIds = $allUjians->pluck('id')->all();

        $pesertasBySiswa = collect();
        if (! empty($ujianIds) && $siswas->isNotEmpty()) {
            $pesertasBySiswa = UjianPeserta::with([
                'ujian.mapel',
                'ujian.bankSoal' => fn ($q) => $q->withCount('soals'),
                'ujianSesi.jawabans',
            ])
                ->whereIn('ujian_id', $ujianIds)
                ->whereIn('siswa_id', $siswas->pluck('id')->all())
                ->get()
                ->groupBy('siswa_id');
        }

        $rows = collect();
        foreach ($siswas as $siswa) {
            $subtestsSiswa = $allUjians->filter(fn (Ujian $u) => $siswa->kelas_id && $u->kelas->contains('id', $siswa->kelas_id))->values();
            if ($subtestsSiswa->isEmpty()) {
                $subtestsSiswa = $allUjians;
            }

            $siswaPesertas = $pesertasBySiswa->get($siswa->id, collect());
            $pesertaAktif = $siswaPesertas->firstWhere('status', 'SEDANG_MENGERJAKAN')
                ?: $siswaPesertas->firstWhere('status', 'SELESAI')
                ?: $siswaPesertas->first();

            $targetUjian = $pesertaAktif?->ujian ?: $subtestsSiswa->first();
            if (! $pesertaAktif && $targetUjian && $jr->status !== 'COMPLETED') {
                $pesertaAktif = UjianPeserta::firstOrCreate(
                    [
                        'ujian_id' => $targetUjian->id,
                        'siswa_id' => $siswa->id,
                    ],
                    [
                        'user_id' => $siswa->user_id,
                        'status' => 'BELUM_MULAI',
                    ]
                );
                $pesertaAktif->load(['ujian.mapel', 'ujian.bankSoal' => fn ($q) => $q->withCount('soals'), 'ujianSesi.jawabans']);
            }

            $status = $pesertaAktif?->status ?: 'BELUM_MULAI';
            $sesi = $pesertaAktif?->ujianSesi;
            $jmlPelanggaran = (int) ($sesi?->jumlah_pelanggaran ?? 0);

            if ($this->filterStatus === 'PELANGGARAN' && $jmlPelanggaran <= 0) {
                continue;
            } elseif ($this->filterStatus !== 'all' && $this->filterStatus !== 'PELANGGARAN' && $status !== $this->filterStatus) {
                continue;
            }

            $rows->push((object) [
                'siswa' => $siswa,
                'peserta' => $pesertaAktif,
                'ujian' => $targetUjian,
                'sesi' => $sesi,
                'status' => $status,
                'subtests_peserta' => $siswaPesertas,
                'total_subtests' => $subtestsSiswa->count(),
            ]);
        }

        return $rows;
    }

    public function rilisTokenSesiRuang(): void
    {
        $jr = $this->activeJadwalRuang;
        if (! $jr) {
            return;
        }

        if ($jr->getSiswasForSession()->isEmpty()) {
            $this->flashType = 'warning';
            $this->flashMessage = "Sesi {$jr->ruangUjian?->nama_ruang} belum memiliki peserta yang terdaftar.";
            return;
        }

        if ($jr->isBelumMasukJadwal()) {
            $waktu = $jr->waktu_mulai ? ' (Mulai: ' . substr((string) $jr->waktu_mulai, 0, 5) . ' WIB)' : '';
            $this->flashType = 'warning';
            $this->flashMessage = "Waktu sesi ini belum dimulai{$waktu}. Rilis token ruangan belum dibuka.";
            return;
        }

        if ($jr->isJadwalBerakhir()) {
            $this->flashType = 'warning';
            $this->flashMessage = 'Waktu jadwal sesi ini telah berakhir. Rilis token ditutup.';
            return;
        }

        $token = $jr->rilisToken();

        $this->flashType = 'success';
        $this->flashMessage = "Token {$jr->ruangUjian?->nama_ruang} ({$jr->label_sesi_waktu}) berhasil dirilis: {$token} (Aktif 10 menit).";
    }

    public function selesaikanSesiRuang(): void
    {
        $jr = $this->activeJadwalRuang;
        if (! $jr) {
            return;
        }

        $jr->selesaikanSesi();
        $this->flashType = 'info';
        $this->flashMessage = "Sesi {$jr->label_sesi_waktu} di {$jr->ruangUjian?->nama_ruang} telah ditandai selesai. Daftar Hadir, nilai peserta, dan BAP tetap tersimpan dan bisa diakses kapanpun.";
    }

    public function bukaModalBap(int $jadwalRuangId): void
    {
        $jr = JadwalRuang::with(['ruangUjian', 'pengawas', 'ujians.mapel', 'ujian.mapel'])->find($jadwalRuangId);
        if (! $jr) {
            return;
        }

        $existingBap = BeritaAcara::where('jadwal_ruang_id', $jr->id)->latest()->first();
        $stats = $jr->computeAttendanceStats();
        $tglObj = $jr->tanggal ?: now();
        $hariIndo = match ($tglObj->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $this->bapJadwalRuangId = $jr->id;
        $this->bapForm = [
            'hari' => $existingBap?->hari ?: $hariIndo,
            'nama_ujian' => $existingBap?->nama_ujian ?: ($jr->daftar_nama_ujian ?: 'TES KEMAMPUAN AKADEMIK SMK'),
            'sesi' => $existingBap?->sesi ?: (string) ($jr->sesi_ke ?: '1'),
            'waktu_mulai' => $existingBap?->waktu_mulai ?: ($jr->waktu_mulai ? substr((string) $jr->waktu_mulai, 0, 5) : '07:30'),
            'waktu_selesai' => $existingBap?->waktu_selesai ?: ($jr->waktu_selesai ? substr((string) $jr->waktu_selesai, 0, 5) : '09:30'),
            'jumlah_peserta_seharusnya' => $existingBap?->jumlah_peserta_seharusnya ?? $stats['seharusnya'],
            'jumlah_hadir' => $existingBap?->jumlah_hadir ?? $stats['hadir'],
            'jumlah_tidak_hadir' => $existingBap?->jumlah_tidak_hadir ?? $stats['tidak_hadir'],
            'usernames_tidak_hadir' => is_array($existingBap?->usernames_tidak_hadir)
                ? implode(', ', $existingBap->usernames_tidak_hadir)
                : implode(', ', $stats['usernames_tidak_hadir']),
            'catatan' => $existingBap?->catatan ?: 'Pelaksanaan ujian berlangsung tertib, aman, dan lancar tanpa kendala.',
            'nama_pengawas' => $existingBap?->nama_pengawas ?: ($jr->pengawas?->name ?: Auth::user()?->name ?: '-'),
            'nip_pengawas' => $existingBap?->nip_pengawas ?: ($jr->pengawas?->nip ?: Auth::user()?->nip ?: '-'),
            'nama_ketua_ujian' => $existingBap?->nama_ketua_ujian ?: 'Ahmad Daqiqi Syahrulloh, S.H.',
            'nip_ketua_ujian' => $existingBap?->nip_ketua_ujian ?: '198904152014021003',
            'nama_kepala_sekolah' => $existingBap?->nama_kepala_sekolah ?: 'Mahsun, M.Pd.',
            'nip_kepala_sekolah' => $existingBap?->nip_kepala_sekolah ?: '197508102005011008',
        ];
    }

    public function tutupModalBap(): void
    {
        $this->bapJadwalRuangId = null;
        $this->bapForm = [];
    }

    public function simpanBap(): void
    {
        if (! $this->bapJadwalRuangId) {
            return;
        }

        $jr = JadwalRuang::with('ruangUjian')->find($this->bapJadwalRuangId);
        if (! $jr) {
            return;
        }

        $usernamesRaw = trim((string) ($this->bapForm['usernames_tidak_hadir'] ?? ''));
        $usernamesArr = $usernamesRaw !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $usernamesRaw))))
            : [];

        $seharusnya = max(0, (int) ($this->bapForm['jumlah_peserta_seharusnya'] ?? 0));
        $tidakHadir = max(0, (int) ($this->bapForm['jumlah_tidak_hadir'] ?? count($usernamesArr)));
        $hadir = max(0, (int) ($this->bapForm['jumlah_hadir'] ?? max(0, $seharusnya - $tidakHadir)));

        BeritaAcara::updateOrCreate(
            ['jadwal_ruang_id' => $jr->id],
            [
                'ruang_ujian_id' => $jr->ruang_ujian_id,
                'ujian_id' => $jr->ujian_id,
                'hari' => $this->bapForm['hari'] ?? null,
                'tanggal' => $jr->tanggal ?: now()->toDateString(),
                'waktu_mulai' => $this->bapForm['waktu_mulai'] ?? null,
                'waktu_selesai' => $this->bapForm['waktu_selesai'] ?? null,
                'nama_ujian' => $this->bapForm['nama_ujian'] ?? $jr->daftar_nama_ujian,
                'tahun_pelajaran' => now()->format('Y'),
                'username_sekolah' => 'K12130106',
                'nama_sekolah' => 'SMKS ASSUNIYAH',
                'id_server' => $jr->ruangUjian?->kode_ruang ? 'K12130106-' . strtoupper($jr->ruangUjian->kode_ruang) : 'K12130106-AJ9K',
                'ruang' => $jr->ruangUjian?->nama_ruang ?: 'RUANG 1',
                'sesi' => (string) ($this->bapForm['sesi'] ?? ($jr->sesi_ke ?: '1')),
                'jumlah_peserta_seharusnya' => $seharusnya,
                'jumlah_hadir' => $hadir,
                'jumlah_tidak_hadir' => $tidakHadir,
                'usernames_tidak_hadir' => $usernamesArr,
                'catatan' => $this->bapForm['catatan'] ?? null,
                'nama_pengawas' => $this->bapForm['nama_pengawas'] ?? null,
                'nip_pengawas' => $this->bapForm['nip_pengawas'] ?? null,
                'nama_ketua_ujian' => $this->bapForm['nama_ketua_ujian'] ?? null,
                'nip_ketua_ujian' => $this->bapForm['nip_ketua_ujian'] ?? null,
                'nama_kepala_sekolah' => $this->bapForm['nama_kepala_sekolah'] ?? null,
                'nip_kepala_sekolah' => $this->bapForm['nip_kepala_sekolah'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        $this->flashType = 'success';
        $this->flashMessage = "Berita Acara Pelaksanaan (BAP) untuk {$jr->ruangUjian?->nama_ruang} {$jr->label_sesi_waktu} berhasil disimpan dan dapat dicetak kapanpun.";
        $this->bapJadwalRuangId = null;
        $this->bapForm = [];
    }

    public function getDaftarRuangProperty()
    {
        $user = Auth::user();
        $query = RuangUjian::with(['pengawas', 'siswas.kelas', 'jadwalRuangs.pengawas', 'jadwalRuangs.pengawasPendamping'])
            ->withCount('siswas')
            ->where('is_active', true);

        if ($user && $user->isGuru() && $this->scopeRuang === 'saya') {
            $hasPenugasan = RuangUjian::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('pengawas_id', $user->id)
                      ->orWhereHas('jadwalRuangs', function ($jq) use ($user) {
                          $jq->where('pengawas_id', $user->id)
                             ->orWhere('pengawas_pendamping_id', $user->id);
                      });
                })->exists();

            if ($hasPenugasan) {
                $query->where(function ($q) use ($user) {
                    $q->where('pengawas_id', $user->id)
                      ->orWhereHas('jadwalRuangs', function ($jq) use ($user) {
                          $jq->where('pengawas_id', $user->id)
                             ->orWhere('pengawas_pendamping_id', $user->id);
                      });
                });
            }
        }

        return $query->orderBy('nama_ruang', 'asc')->get();
    }

    public function getJumlahRuangTugasSayaProperty(): int
    {
        $user = Auth::user();
        if (! $user) return 0;

        if (JadwalRuang::exists()) {
            return JadwalRuang::query()
                ->where(function ($q) use ($user) {
                    $q->where('pengawas_id', $user->id)
                      ->orWhere('pengawas_pendamping_id', $user->id)
                      ->orWhereHas('ruangUjian', fn ($rq) => $rq->where('pengawas_id', $user->id));
                })
                ->count();
        }

        return RuangUjian::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('pengawas_id', $user->id)
                  ->orWhereHas('jadwalRuangs', function ($jq) use ($user) {
                      $jq->where('pengawas_id', $user->id)
                         ->orWhere('pengawas_pendamping_id', $user->id);
                  });
            })
            ->count();
    }

    public function getJumlahSemuaRuangProperty(): int
    {
        if (JadwalRuang::exists()) {
            return JadwalRuang::count();
        }

        return RuangUjian::where('is_active', true)->count();
    }

    public function getActiveRuangProperty(): ?RuangUjian
    {
        if (!$this->selectedRuangId) {
            return null;
        }

        $ruang = RuangUjian::with(['pengawas', 'siswas.kelas'])->find($this->selectedRuangId);
        if (!$ruang) {
            return null;
        }

        // Auto-close sesi kedaluwarsa untuk seluruh jadwal ujian yang aktif di ruangan ini
        $activeUjians = $ruang->getActiveUjiansForRoom();
        foreach ($activeUjians as $uj) {
            if ($uj->status === 'ONGOING') {
                $uj->autoCloseExpiredSessions();
            }
        }

        return $ruang;
    }

    /**
     * Daftar peserta di dalam Ruang Ujian terpilih (bisa campuran berbagai kelas,
     * masing-masing terhubung ke Ujian sesuai kelas/tingkatannya).
     */
    public function getPesertasRuangListProperty()
    {
        $ruang = $this->activeRuang;
        if (!$ruang) {
            return collect();
        }

        $activeUjians = $ruang->getActiveUjiansForRoom();
        $activeUjianIds = $activeUjians->pluck('id')->all();

        // Pastikan setiap siswa di ruangan ini sudah tersinkron ke UjianPeserta sesuai kelasnya
        $siswasQuery = Siswa::with(['kelas', 'user'])
            ->where('ruang_ujian_id', $ruang->id)
            ->where('is_active', true);

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $siswasQuery->where(function ($q) use ($term) {
                $q->where('nama', 'like', $term)
                  ->orWhere('nis', 'like', $term);
            });
        }

        if (!empty($this->filterKelas)) {
            $siswasQuery->where('kelas_id', $this->filterKelas);
        }

        $siswas = $siswasQuery->orderBy('kelas_id', 'asc')->orderBy('nama', 'asc')->get();

        // Map Ujian aktif berdasarkan kelas_id
        $ujianByKelas = [];
        foreach ($activeUjians as $uj) {
            foreach ($uj->kelas as $kls) {
                // Utamakan ONGOING jika ada lebih dari 1 ujian pada kelas yang sama
                if (!isset($ujianByKelas[$kls->id]) || $uj->status === 'ONGOING') {
                    $ujianByKelas[$kls->id] = $uj;
                }
            }
        }

        // Ambil seluruh UjianPeserta milik siswa di ruangan ini untuk jadwal ujian yang aktif
        $pesertasMap = collect();
        if (!empty($activeUjianIds) && $siswas->isNotEmpty()) {
            $pesertas = UjianPeserta::with(['ujian.mapel', 'ujian.bankSoal' => fn ($q) => $q->withCount('soals'), 'ujianSesi.jawabans'])
                ->whereIn('ujian_id', $activeUjianIds)
                ->whereIn('siswa_id', $siswas->pluck('id')->all())
                ->get();

            foreach ($pesertas as $p) {
                $existing = $pesertasMap->get($p->siswa_id);
                if (!$existing || $p->status === 'SEDANG_MENGERJAKAN' || ($existing->status === 'BELUM_MULAI' && $p->status === 'SELESAI')) {
                    $pesertasMap->put($p->siswa_id, $p);
                }
            }
        }

        $rows = collect();
        foreach ($siswas as $siswa) {
            $peserta = $pesertasMap->get($siswa->id);
            $ujianKelas = $siswa->kelas_id ? ($ujianByKelas[$siswa->kelas_id] ?? null) : null;

            // Jika belum ada record UjianPeserta padahal kelasnya punya ujian aktif, buatkan otomatis
            if (!$peserta && $ujianKelas) {
                $peserta = UjianPeserta::firstOrCreate(
                    [
                        'ujian_id' => $ujianKelas->id,
                        'siswa_id' => $siswa->id,
                    ],
                    [
                        'user_id' => $siswa->user_id,
                        'status' => 'BELUM_MULAI',
                    ]
                );
                $peserta->load(['ujian.mapel', 'ujian.bankSoal' => fn ($q) => $q->withCount('soals'), 'ujianSesi.jawabans']);
            }

            $status = $peserta?->status ?: 'BELUM_MULAI';
            $sesi = $peserta?->ujianSesi;
            $jmlPelanggaran = (int) ($sesi?->jumlah_pelanggaran ?? 0);

            if ($this->filterStatus === 'PELANGGARAN' && $jmlPelanggaran <= 0) {
                continue;
            } elseif ($this->filterStatus !== 'all' && $this->filterStatus !== 'PELANGGARAN' && $status !== $this->filterStatus) {
                continue;
            }

            $rows->push((object) [
                'siswa' => $siswa,
                'peserta' => $peserta,
                'ujian' => $peserta?->ujian ?: $ujianKelas,
                'sesi' => $sesi,
                'status' => $status,
            ]);
        }

        return $rows;
    }

    /**
     * Rilis Token 6 Karakter untuk Ruang Ujian (10 menit)
     */
    public function rilisTokenRuang(): void
    {
        $ruang = $this->activeRuang;
        if (!$ruang) return;

        if ($ruang->siswas()->count() === 0) {
            $this->flashType = 'warning';
            $this->flashMessage = "Ruang {$ruang->nama_ruang} belum memiliki peserta. Silakan atur penempatan siswa di menu Ruang Ujian.";
            return;
        }

        if ($ruang->isBelumMasukJadwal()) {
            $waktu = $ruang->jadwal_berikutnya ? " (Mulai: {$ruang->jadwal_berikutnya})" : '';
            $this->flashType = 'warning';
            $this->flashMessage = "Waktu pelaksanaan ujian belum dimulai{$waktu}. Rilis token ruangan belum dibuka.";
            return;
        }

        if ($ruang->isSemuaJadwalBerakhir()) {
            $this->flashType = 'warning';
            $this->flashMessage = 'Waktu jadwal ujian untuk kelas di ruangan ini telah berakhir. Rilis token ditutup.';
            return;
        }

        $token = $ruang->rilisToken();

        $this->flashType = 'success';
        $this->flashMessage = "Token {$ruang->nama_ruang} berhasil dirilis: {$token} (Aktif 10 menit untuk seluruh siswa di ruangan ini).";
    }

    public function getDaftarUjianProperty()
    {
        $user = Auth::user();
        $query = Ujian::with(['mapel', 'guru', 'kelas', 'bankSoal' => fn ($q) => $q->withCount('soals')])
            ->withCount([
                'pesertas',
                'pesertas as sedang_mengerjakan_count' => fn ($q) => $q->where('status', 'SEDANG_MENGERJAKAN'),
                'pesertas as selesai_count' => fn ($q) => $q->where('status', 'SELESAI'),
            ]);

        if ($user && $user->isGuru() && $this->scopeUjian === 'saya') {
            $query->where('guru_id', $user->id);
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 1 WHEN status = 'SCHEDULED' THEN 2 ELSE 3 END")
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getActiveUjianProperty(): ?Ujian
    {
        if (!$this->selectedUjianId) {
            return null;
        }

        $ujian = Ujian::with(['mapel', 'guru', 'kelas', 'bankSoal' => fn ($q) => $q->withCount('soals')])
            ->find($this->selectedUjianId);

        if ($ujian && $ujian->status === 'ONGOING') {
            $ujian->autoCloseExpiredSessions();
        }

        return $ujian;
    }

    public function getPesertasListProperty()
    {
        $ujian = $this->activeUjian;
        if (!$ujian) {
            return collect();
        }

        $query = $ujian->pesertas()
            ->with(['siswa.kelas', 'siswa.ruangUjian', 'user', 'ujianSesi.jawabans'])
            ->join('siswas', 'ujian_pesertas.siswa_id', '=', 'siswas.id')
            ->select('ujian_pesertas.*');

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('siswas.nama', 'like', $term)
                  ->orWhere('siswas.nis', 'like', $term);
            });
        }

        if ($this->filterStatus === 'PELANGGARAN') {
            $query->whereHas('ujianSesi', function ($q) {
                $q->where('jumlah_pelanggaran', '>', 0);
            });
        } elseif ($this->filterStatus !== 'all') {
            $query->where('ujian_pesertas.status', $this->filterStatus);
        }

        if (!empty($this->filterKelas)) {
            $query->where('siswas.kelas_id', $this->filterKelas);
        }

        return $query->orderBy('siswas.nama', 'asc')->get();
    }

    /**
     * Mulai sesi ujian & rilis token akses 10 menit
     */
    public function mulaiUjian(): void
    {
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        if ($ujian->isJadwalBerakhir()) {
            $this->flashType = 'warning';
            $this->flashMessage = 'Waktu jadwal ujian telah habis. Rilis token sudah ditutup.';
            return;
        }

        $token = $ujian->startExam();
        $ujian->syncPesertaFromKelas();

        $this->flashType = 'success';
        $this->flashMessage = "Sesi ujian dimulai! Token Akses: {$token} (Aktif 10 menit).";
    }

    /**
     * Rilis / perbarui token akses 6 karakter baru (10 menit)
     */
    public function rilisTokenBaru(): void
    {
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        if ($ujian->isJadwalBerakhir()) {
            $this->flashType = 'warning';
            $this->flashMessage = 'Waktu jadwal ujian telah habis. Rilis token sudah ditutup.';
            return;
        }

        $newToken = $ujian->refreshToken();

        $this->flashType = 'success';
        $this->flashMessage = "Token baru berhasil dirilis: {$newToken} (Aktif 10 menit ke depan).";
    }

    /**
     * Sinkronkan daftar peserta dari rombel kelas ujian
     */
    public function syncPesertaKelas(): void
    {
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        $count = $ujian->syncPesertaFromKelas();

        $this->flashType = 'success';
        $this->flashMessage = "Sinkronisasi selesai ({$count} peserta baru ditambahkan dari rombel kelas).";
    }

    /**
     * Tutup sesi pelaksanaan ujian secara keseluruhan
     */
    public function selesaikanUjian(): void
    {
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        $ujian->finishExam();

        $this->flashType = 'info';
        $this->flashMessage = "Pelaksanaan ujian telah ditutup dan seluruh lembar jawaban dikoreksi otomatis.";
    }

    /**
     * Reset login akun siswa berdasarkan siswa_id (untuk siswa yang belum memiliki sesi)
     */
    public function resetLoginSiswaById(int $siswaId): void
    {
        $siswa = Siswa::with('user')->find($siswaId);
        if (!$siswa) return;

        if ($siswa->user) {
            $siswa->user->resetDeviceLogin();
        }

        $this->flashType = 'success';
        $this->flashMessage = "Kunci perangkat untuk {$siswa->nama} berhasil di-reset.";
    }

    /**
     * Reset login & buka kunci sesi siswa (tanpa menghapus jawaban tersimpan)
     */
    public function resetSesiSiswa(int $pesertaId): void
    {
        $peserta = UjianPeserta::with(['ujianSesi', 'siswa.user', 'user'])->find($pesertaId);
        if (!$peserta) return;

        // Lepas kunci sesi perangkat aktif pada akun siswa
        $targetUser = $peserta->user ?: $peserta->siswa?->user;
        if ($targetUser) {
            $targetUser->resetDeviceLogin();
        }

        $sesi = $peserta->ujianSesi;

        if ($peserta->status === 'SELESAI') {
            $peserta->update([
                'status' => 'SEDANG_MENGERJAKAN',
                'waktu_selesai' => null,
            ]);
        }

        if ($sesi) {
            $waktuSelesaiBaru = $sesi->waktu_selesai;
            if (!$waktuSelesaiBaru || now()->addMinutes(10)->greaterThan($waktuSelesaiBaru)) {
                $waktuSelesaiBaru = now()->addMinutes(15);
            }

            $sesi->update([
                'status' => 'mengerjakan',
                'submitted_at' => null,
                'waktu_selesai' => $waktuSelesaiBaru,
                'jumlah_pelanggaran' => 0,
                'device_token' => null,
            ]);
        }

        $nama = $peserta->siswa?->nama ?: 'Siswa';
        $this->flashType = 'success';
        $this->flashMessage = "Login & kunci perangkat {$nama} berhasil di-reset. Siswa dapat langsung login dari perangkat baru.";
    }

    /**
     * Tambah waktu pengerjaan siswa (+10 menit default)
     */
    public function tambahWaktuSiswa(int $pesertaId, int $menit = 10): void
    {
        $peserta = UjianPeserta::with(['ujianSesi', 'siswa'])->find($pesertaId);
        if (!$peserta || !$peserta->ujianSesi) return;

        $sesi = $peserta->ujianSesi;
        $baseTime = ($sesi->waktu_selesai && $sesi->waktu_selesai->isFuture()) ? $sesi->waktu_selesai : now();
        $newEndTime = $baseTime->copy()->addMinutes($menit);

        $sesi->update([
            'waktu_selesai' => $newEndTime,
            'durasi_menit' => ((int) $sesi->durasi_menit) + $menit,
            'status' => 'mengerjakan',
        ]);

        if ($peserta->status === 'SELESAI') {
            $peserta->update(['status' => 'SEDANG_MENGERJAKAN']);
        }

        $nama = $peserta->siswa?->nama ?: 'Siswa';
        $this->flashType = 'success';
        $this->flashMessage = "Waktu pengerjaan {$nama} ditambah +{$menit} menit (hingga {$newEndTime->format('H:i')} WIB).";
    }

    /**
     * Paksa selesaikan ujian siswa secara manual
     */
    public function paksaSelesaiSiswa(int $pesertaId): void
    {
        $peserta = UjianPeserta::with(['ujianSesi', 'siswa'])->find($pesertaId);
        if (!$peserta) return;

        $sesi = $peserta->ujianSesi;
        if ($sesi && !$sesi->isSelesai()) {
            $sesi->koreksiDanSelesaikan();
        } else {
            $peserta->update([
                'status' => 'SELESAI',
                'waktu_selesai' => now(),
                'nilai' => $peserta->nilai ?? 0,
            ]);
        }

        $nama = $peserta->siswa?->nama ?: 'Siswa';
        $this->flashType = 'info';
        $this->flashMessage = "Ujian untuk {$nama} telah diselesaikan secara manual.";
    }

    public function getSelectedPesertaProperty(): ?UjianPeserta
    {
        if (!$this->selectedPesertaId) {
            return null;
        }

        return UjianPeserta::with([
            'siswa.kelas',
            'ujianSesi.jawabans.soal.opsiJawabans',
        ])->find($this->selectedPesertaId);
    }

    public function bukaLembarJawaban(int $pesertaId): void
    {
        $this->selectedPesertaId = $pesertaId;
    }

    public function tutupLembarJawaban(): void
    {
        $this->selectedPesertaId = null;
    }

    public function getTugasJadwalHariIniProperty()
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        return JadwalRuang::query()
            ->hariIni()
            ->forUser($user)
            ->with(['ruangUjian', 'ujian.mapel', 'ujian.kelas', 'pengawas', 'pengawasPendamping'])
            ->orderBy('sesi_ke')
            ->orderBy('waktu_mulai')
            ->get();
    }

    public function rilisTokenJadwal(int $jadwalId): void
    {
        $jadwal = JadwalRuang::find($jadwalId);
        if (! $jadwal) {
            return;
        }

        $token = $jadwal->rilisToken();
        $namaRuang = $jadwal->ruangUjian?->nama_ruang ?: 'Ruang Ujian';
        $this->flashType = 'success';
        $this->flashMessage = "Token {$namaRuang} (Sesi {$jadwal->sesi_ke}) berhasil dirilis: {$token} (Aktif 10 menit)";

        // Otomatis arahkan view ke ruang ini
        $this->pilihRuang($jadwal->ruang_ujian_id);
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('pengawas.login');
    }

    public function render()
    {
        if (!Auth::check()) {
            return view('livewire.pengawas.portal-pengawas', [
                'user' => null,
                'tugasJadwalHariIni' => collect(),
                'daftarSesiRuang' => collect(),
                'activeJadwalRuang' => null,
                'pesertasSesiRuang' => collect(),
                'daftarRuang' => collect(),
                'activeRuang' => null,
                'pesertasRuang' => collect(),
                'daftarUjian' => collect(),
                'activeUjian' => null,
                'pesertas' => collect(),
            ]);
        }

        return view('livewire.pengawas.portal-pengawas', [
            'user' => Auth::user(),
            'tugasJadwalHariIni' => $this->tugasJadwalHariIni,
            'daftarSesiRuang' => $this->daftarSesiRuang,
            'activeJadwalRuang' => $this->activeJadwalRuang,
            'pesertasSesiRuang' => $this->pesertasSesiRuangList,
            'daftarRuang' => $this->daftarRuang,
            'activeRuang' => $this->activeRuang,
            'pesertasRuang' => $this->pesertasRuangList,
            'daftarUjian' => $this->daftarUjian,
            'activeUjian' => $this->activeUjian,
            'pesertas' => $this->pesertasList,
        ]);
    }
}

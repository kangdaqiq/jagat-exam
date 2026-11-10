<?php

namespace App\Livewire\Pengawas;

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

    #[Url(as: 'ujian')]
    public ?int $selectedUjianId = null;

    public string $tabMode = 'ruang'; // 'ruang' | 'ujian'
    public string $scopeUjian = 'saya'; // 'saya' | 'semua'
    public string $scopeRuang = 'saya'; // 'saya' | 'semua'
    public string $search = '';
    public string $filterStatus = 'all'; // 'all' | 'SEDANG_MENGERJAKAN' | 'BELUM_MULAI' | 'SELESAI' | 'PELANGGARAN'
    public ?int $filterKelas = null;
    public ?int $selectedPesertaId = null;
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
        } elseif ($this->selectedRuangId) {
            $this->tabMode = 'ruang';
        } elseif (RuangUjian::where('is_active', true)->count() === 0) {
            $this->tabMode = 'ujian';
        }
    }

    public function pilihRuang(int $ruangId): void
    {
        $ruang = RuangUjian::find($ruangId);
        if (!$ruang) {
            return;
        }

        $this->selectedRuangId = $ruang->id;
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
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
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
            'daftarRuang' => $this->daftarRuang,
            'activeRuang' => $this->activeRuang,
            'pesertasRuang' => $this->pesertasRuangList,
            'daftarUjian' => $this->daftarUjian,
            'activeUjian' => $this->activeUjian,
            'pesertas' => $this->pesertasList,
        ]);
    }
}

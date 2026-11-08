<?php

namespace App\Livewire\Pengawas;

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
    #[Url(as: 'ujian')]
    public ?int $selectedUjianId = null;

    public string $scopeUjian = 'saya'; // 'saya' | 'semua'
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
        }

        if ($ujian) {
            $this->selectedUjianId = $ujian;
        }
    }

    public function pilihUjian(int $ujianId): void
    {
        $ujian = Ujian::find($ujianId);
        if (!$ujian) {
            return;
        }

        $this->selectedUjianId = $ujian->id;
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterKelas = null;
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
    }

    public function kembaliKeDaftarUjian(): void
    {
        $this->selectedUjianId = null;
        $this->selectedPesertaId = null;
        $this->flashMessage = null;
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
            ->with(['siswa.kelas', 'user', 'ujianSesi.jawabans'])
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
     * Reset login & buka kunci sesi siswa (tanpa menghapus jawaban tersimpan)
     */
    public function resetSesiSiswa(int $pesertaId): void
    {
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        $peserta = UjianPeserta::where('ujian_id', $ujian->id)
            ->with(['ujianSesi', 'siswa.user', 'user'])
            ->find($pesertaId);

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
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        $peserta = UjianPeserta::where('ujian_id', $ujian->id)
            ->with(['ujianSesi', 'siswa'])
            ->find($pesertaId);

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
        $ujian = $this->activeUjian;
        if (!$ujian) return;

        $peserta = UjianPeserta::where('ujian_id', $ujian->id)
            ->with(['ujianSesi', 'siswa'])
            ->find($pesertaId);

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
        if (!$this->selectedPesertaId || !$this->selectedUjianId) {
            return null;
        }

        return UjianPeserta::where('ujian_id', $this->selectedUjianId)
            ->with([
                'siswa.kelas',
                'ujianSesi.jawabans.soal.opsiJawabans',
            ])
            ->find($this->selectedPesertaId);
    }

    public function bukaLembarJawaban(int $pesertaId): void
    {
        $this->selectedPesertaId = $pesertaId;
    }

    public function tutupLembarJawaban(): void
    {
        $this->selectedPesertaId = null;
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
                'daftarUjian' => collect(),
                'activeUjian' => null,
                'pesertas' => collect(),
            ]);
        }

        return view('livewire.pengawas.portal-pengawas', [
            'user' => Auth::user(),
            'daftarUjian' => $this->daftarUjian,
            'activeUjian' => $this->activeUjian,
            'pesertas' => $this->pesertasList,
        ]);
    }
}


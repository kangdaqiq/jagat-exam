<?php

namespace App\Filament\Resources\Ujians\Pages;

use App\Filament\Resources\Ujians\UjianResource;
use App\Models\Ujian;
use App\Models\UjianPeserta;
use App\Models\UjianSesi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PengawasanUjian extends Page
{
    use InteractsWithRecord;

    protected static string $resource = UjianResource::class;

    protected string $view = 'filament.pages.pengawasan-ujian';

    protected static ?string $title = 'Ruang Pengawasan Ujian';

    protected Width | string | null $maxContentWidth = Width::Full;

    public string $search = '';
    public string $filterStatus = 'all';
    public ?int $filterKelas = null;
    public ?int $selectedPesertaId = null;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getUjianProperty(): Ujian
    {
        return $this->record;
    }

    public function getPesertasListProperty()
    {
        if ($this->ujian->status === 'ONGOING') {
            $this->ujian->autoCloseExpiredSessions();
        }

        $query = $this->ujian->pesertas()
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
     * Memulai ujian dan merilis token 10 menit
     */
    public function mulaiUjian(): void
    {
        if ($this->ujian->isJadwalBerakhir()) {
            Notification::make()
                ->title('Waktu Jadwal Ujian Telah Habis')
                ->body('Batas jadwal pelaksanaan ujian sudah berakhir. Rilis token telah ditutup.')
                ->warning()
                ->send();
            return;
        }

        $token = $this->ujian->startExam();
        $this->ujian->syncPesertaFromKelas();
        $this->ujian->refresh();

        Notification::make()
            ->title('Sesi Ujian Berhasil Dimulai')
            ->body("KODE TOKEN AKSES UJIAN:\n\n# **{$token}**\n\nToken aktif selama 10 menit ke depan. Bagikan kode ini ke siswa di ruangan.")
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Generate / Rilis Token Baru (10 Menit Expire)
     */
    public function rilisTokenBaru(): void
    {
        if ($this->ujian->isJadwalBerakhir()) {
            Notification::make()
                ->title('Waktu Jadwal Ujian Telah Habis')
                ->body('Batas jadwal pelaksanaan ujian sudah berakhir. Rilis token telah ditutup.')
                ->warning()
                ->send();
            return;
        }

        $newToken = $this->ujian->refreshToken();
        $this->ujian->refresh();

        Notification::make()
            ->title('Token Ujian Baru Berhasil Dirilis')
            ->body("KODE TOKEN BARU (10 MENIT):\n\n# **{$newToken}**\n\nSiswa yang belum masuk dapat langsung menggunakan token ini.")
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Menyelesaikan ujian secara keseluruhan
     */
    public function selesaikanUjian(): void
    {
        $this->ujian->finishExam();
        $this->ujian->refresh();

        Notification::make()
            ->title('Pelaksanaan Ujian Telah Ditutup')
            ->body('Status ujian telah diubah menjadi COMPLETED. Seluruh sesi pengerjaan telah terkunci.')
            ->info()
            ->send();
    }

    /**
     * Sinkronkan peserta dari rombel kelas
     */
    public function syncPesertaKelas(): void
    {
        $count = $this->ujian->syncPesertaFromKelas();
        $this->ujian->refresh();

        Notification::make()
            ->title('Daftar Peserta Diperbarui')
            ->body("Sebanyak {$count} siswa baru dari rombel kelas berhasil ditambahkan ke daftar peserta ujian.")
            ->success()
            ->send();
    }

    /**
     * Proktor mereset sesi login siswa (jika mati lampu / browser crash / ter-submit tidak sengaja)
     */
    public function resetSesiSiswa(int $pesertaId): void
    {
        $peserta = UjianPeserta::with(['ujianSesi', 'siswa.user', 'user'])->find($pesertaId);
        if (!$peserta) return;

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
            // Jika waktu selesai sesi sudah habis atau sisa < 10 menit, berikan perpanjangan 15 menit dari sekarang
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

        Notification::make()
            ->title('Sesi Siswa Berhasil Direset')
            ->body("Siswa {$peserta->siswa?->nama} (NIS: {$peserta->siswa?->nis}) dapat login kembali dan melanjutkan ujian tanpa kehilangan jawaban tersimpan.")
            ->success()
            ->send();
    }

    /**
     * Reset total ujian siswa: hapus semua jawaban, reset waktu mulai, waktu selesai, dan status kembali ke awal (Belum Mulai).
     */
    public function resetUjianSiswaTotal(int $pesertaId): void
    {
        $peserta = UjianPeserta::with(['ujianSesi.jawabans', 'siswa.user', 'user'])->find($pesertaId);
        if (!$peserta) return;

        // Lepas device token
        $targetUser = $peserta->user ?: $peserta->siswa?->user;
        if ($targetUser) {
            $targetUser->resetDeviceLogin();
        }

        // Hapus atau reset sesi
        if ($peserta->ujianSesi) {
            $sesi = $peserta->ujianSesi;
            // Hapus semua jawaban siswa
            $sesi->jawabans()->delete();
            // Hapus sesi pengerjaan agar saat siswa input token kembali, sesi dibuat ulang dari awal
            $sesi->delete();
        }

        // Reset status peserta kembali ke BELUM_MULAI dan bersihkan nilai serta riwayat waktu
        $peserta->update([
            'status' => 'BELUM_MULAI',
            'ujian_sesi_id' => null,
            'nilai' => null,
            'waktu_mulai' => null,
            'waktu_selesai' => null,
        ]);

        Notification::make()
            ->title('Ujian Siswa Telah Direset Total')
            ->body("Jawaban dan waktu pengerjaan untuk {$peserta->siswa?->nama} berhasil direset dari nol. Siswa dapat memasukkan token untuk memulai ujian baru.")
            ->warning()
            ->send();
    }

    /**
     * Proktor memberikan tambahan waktu pengerjaan untuk siswa tertentu (+10 menit default)
     */
    public function tambahWaktuSiswa(int $pesertaId, int $menit = 10): void
    {
        $peserta = UjianPeserta::with('ujianSesi', 'siswa')->find($pesertaId);
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

        Notification::make()
            ->title("Waktu +{$menit} Menit Ditambahkan")
            ->body("Batas waktu pengerjaan {$peserta->siswa?->nama} diperpanjang hingga pukul {$newEndTime->format('H:i')} WIB.")
            ->success()
            ->send();
    }

    /**
     * Proktor memaksa selesaikan ujian siswa secara manual
     */
    public function paksaSelesaiSiswa(int $pesertaId): void
    {
        $peserta = UjianPeserta::with(['ujianSesi', 'siswa'])->find($pesertaId);
        if (!$peserta) return;

        $sesi = $peserta->ujianSesi;
        if ($sesi && !$sesi->isSelesai()) {
            $totalSkor = $sesi->koreksiDanSelesaikan();
        } else {
            $peserta->update([
                'status' => 'SELESAI',
                'waktu_selesai' => now(),
                'nilai' => $peserta->nilai ?? 0,
            ]);
        }

        Notification::make()
            ->title('Ujian Siswa Telah Diselesaikan')
            ->body("Ujian untuk siswa {$peserta->siswa?->nama} berhasil diselesaikan secara manual oleh pengawas.")
            ->info()
            ->send();
    }

    public function getSelectedPesertaProperty(): ?UjianPeserta
    {
        if (!$this->selectedPesertaId) {
            return null;
        }

        return UjianPeserta::with([
            'siswa.kelas',
            'ujianSesi.jawabans.soal.opsiJawabans',
            'ujian.bankSoal.soals',
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

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $prefix = $user && $user->isAdmin() ? '/admin' : '/guru';

        return [
            Action::make('rekap_nilai')
                ->label('Rekap & Hasil Nilai')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('success')
                ->url("{$prefix}/hasil-ujians/{$this->record->id}"),

            Action::make('kembali')
                ->label('Daftar Jadwal Ujian')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}


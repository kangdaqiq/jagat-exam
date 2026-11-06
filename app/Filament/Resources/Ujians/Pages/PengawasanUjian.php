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

        if ($this->filterStatus !== 'all') {
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
     * Proktor mereset sesi login siswa (jika mati lampu / browser crash)
     */
    public function resetSesiSiswa(int $pesertaId): void
    {
        $peserta = UjianPeserta::with('ujianSesi', 'siswa')->find($pesertaId);
        if (!$peserta) return;

        // Jika sesi sedang berjalan, perpanjang waktu atau biarkan melanjutkan
        if ($peserta->status === 'SELESAI') {
            $peserta->update(['status' => 'SEDANG_MENGERJAKAN']);
            if ($peserta->ujianSesi) {
                $peserta->ujianSesi->update(['status' => 'mengerjakan']);
            }
        }

        Notification::make()
            ->title('Sesi Siswa Berhasil Direset')
            ->body("Siswa {$peserta->siswa?->nama} (NIS: {$peserta->siswa?->nis}) dapat login kembali dan melanjutkan ujian tanpa kehilangan jawaban tersimpan.")
            ->success()
            ->send();
    }

    /**
     * Proktor memaksa selesaikan ujian siswa secara manual
     */
    public function paksaSelesaiSiswa(int $pesertaId): void
    {
        $peserta = UjianPeserta::with(['ujianSesi.jawabans.soal'])->find($pesertaId);
        if (!$peserta) return;

        $sesi = $peserta->ujianSesi;
        if ($sesi) {
            // Hitung skor total
            $totalSkor = (float) $sesi->jawabans()->sum('skor_didapat');
            $sesi->update([
                'status' => 'selesai',
                'submitted_at' => now(),
                'skor_total' => $totalSkor,
            ]);

            $peserta->update([
                'status' => 'SELESAI',
                'waktu_selesai' => now(),
                'nilai' => $totalSkor,
            ]);
        } else {
            $peserta->update([
                'status' => 'SELESAI',
                'waktu_selesai' => now(),
                'nilai' => 0,
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

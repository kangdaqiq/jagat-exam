<?php

namespace App\Filament\Resources\HasilUjians\Pages;

use App\Filament\Resources\HasilUjians\HasilUjianResource;
use App\Models\HasilUjian;
use App\Models\UjianJawaban;
use App\Models\UjianPeserta;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ViewHasilUjian extends Page
{
    use InteractsWithRecord;

    protected static string $resource = HasilUjianResource::class;

    protected string $view = 'filament.pages.detail-hasil-ujian';

    protected static ?string $title = 'Rekap & Analisis Nilai Siswa';

    protected Width | string | null $maxContentWidth = Width::Full;

    public string $search = '';
    public string $filterStatus = 'all';
    public ?int $filterKelas = null;
    public string $sortBy = 'nama';
    public string $sortDirection = 'asc';
    public ?int $selectedPesertaId = null;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->autoCloseExpiredSessions();
    }

    public function getHasilUjianProperty(): HasilUjian
    {
        return $this->record;
    }

    public function getPesertasListProperty()
    {
        $query = $this->hasilUjian->pesertas()
            ->with(['siswa.kelas', 'user', 'ujianSesi'])
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

        if ($this->sortBy === 'nilai') {
            $query->orderByRaw('ujian_pesertas.nilai ' . ($this->sortDirection === 'asc' ? 'ASC NULLS LAST' : 'DESC NULLS LAST'))
                  ->orderBy('siswas.nama', 'asc');
        } elseif ($this->sortBy === 'nis') {
            $query->orderBy('siswas.nis', $this->sortDirection);
        } else {
            $query->orderBy('siswas.nama', $this->sortDirection);
        }

        return $query->get();
    }

    public function toggleSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $column === 'nilai' ? 'desc' : 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = 'all';
        $this->filterKelas = null;
        $this->sortBy = 'nama';
        $this->sortDirection = 'asc';
    }

    public function exportCsv()
    {
        $pesertas = $this->pesertasList;
        $examName = Str::slug($this->hasilUjian->nama_ujian, '_');
        $filename = "rekap_nilai_{$examName}_" . now()->format('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($pesertas) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Microsoft Excel opens Indonesian characters correctly
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'NIS',
                'Nama Siswa',
                'Kelas',
                'Status Ujian',
                'Waktu Mulai',
                'Waktu Selesai',
                'Nilai Akhir',
            ]);

            foreach ($pesertas as $index => $p) {
                fputcsv($handle, [
                    $index + 1,
                    $p->siswa?->nis ?: '-',
                    $p->siswa?->nama ?: ($p->user?->name ?: '-'),
                    $p->siswa?->kelas?->nama_kelas ?: '-',
                    $p->status,
                    $p->waktu_mulai ? $p->waktu_mulai->format('Y-m-d H:i:s') : '-',
                    $p->waktu_selesai ? $p->waktu_selesai->format('Y-m-d H:i:s') : '-',
                    $p->nilai !== null ? (float) $p->nilai : 0,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    /**
     * Guru mengoreksi nilai soal essay siswa secara langsung
     */
    public function updateSkorEssay(int $jawabanId, float $skor): void
    {
        $jawaban = UjianJawaban::with('soal', 'sesi')->find($jawabanId);
        if (!$jawaban) return;

        $sesi = $jawaban->sesi;
        $maxBobot = $sesi ? $sesi->getEffectiveBobotForJawaban($jawaban) : (float) ($jawaban->soal?->bobot_nilai ?? 10);
        $finalSkor = max(0, min($maxBobot, $skor));

        $jawaban->update([
            'skor_didapat' => $finalSkor,
            'is_benar' => $finalSkor > 0,
        ]);

        // Hitung ulang total skor di sesi dan di tabel peserta
        if ($sesi) {
            $totalSkorBaru = (float) $sesi->jawabans()->sum('skor_didapat');
            $sesi->update(['skor_total' => $totalSkorBaru]);

            if ($sesi->ujian_id) {
                UjianPeserta::where('ujian_id', $sesi->ujian_id)
                    ->where('user_id', $sesi->user_id)
                    ->update(['nilai' => $totalSkorBaru]);
            }
        }

        Notification::make()
            ->title('Nilai Butir Soal Diperbarui')
            ->body("Skor berhasil disimpan: {$finalSkor} / {$maxBobot} poin.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Daftar Hasil Ujian')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}

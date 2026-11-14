<?php

namespace App\Filament\Pages;

use App\Filament\Resources\BankSoals\BankSoalResource;
use App\Models\BankSoal;
use App\Models\GuruPengampu;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ProgresPembuatanSoal extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Progres Pembuatan Soal';

    protected static ?string $title = 'Monitoring Progres Pembuatan Soal';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 2;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected string $view = 'filament.pages.progres-pembuatan-soal';

    public string $search = '';
    public string $filterTingkat = 'all';
    public string $filterMapel = 'all';
    public string $filterGuru = 'all';
    public string $filterStatus = 'all'; // 'all', 'siap', 'review', 'kosong'
    public string $activeTab = 'paket';  // 'paket' | 'guru_belum_buat'
    public string $sortBy = 'updated_at';
    public string $sortDirection = 'desc';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin', 'guru']);
    }

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->isGuru()) {
            $this->filterGuru = (string) $user->id;
        }
    }

    /**
     * Hitung metrik ringkasan (KPI Cards)
     */
    public function getStatsProperty(): array
    {
        $user = Auth::user();
        $query = BankSoal::with(['soals.opsiJawabans']);

        if ($user && $user->isGuru()) {
            $query->where('user_id', $user->id);
        }

        $allBanks = $query->get();
        $totalPaket = $allBanks->count();
        $totalButir = 0;
        $siapUji = 0;
        $perluReview = 0;
        $kosong = 0;

        foreach ($allBanks as $bank) {
            $soals = $bank->soals;
            $count = $soals->count();
            $totalButir += $count;
            $totalBobot = (float) $soals->sum('bobot_nilai');

            if ($count === 0) {
                $kosong++;
                continue;
            }

            $hasInvalidKey = false;
            foreach ($soals as $s) {
                $hasKey = false;
                if (in_array($s->tipe_soal, ['pilihan_ganda', 'benar_salah', 'pg_kompleks'])) {
                    $hasKey = $s->opsiJawabans->where('is_jawaban_benar', true)->isNotEmpty();
                } elseif ($s->tipe_soal === 'menjodohkan') {
                    $hasKey = $s->opsiJawabans->filter(fn ($o) => !empty($o->pasangan_matching))->isNotEmpty();
                } elseif ($s->tipe_soal === 'isian') {
                    $hasKey = !empty(trim($s->kunci_isian ?? ''));
                } elseif ($s->tipe_soal === 'essay') {
                    $hasKey = !empty(trim(strip_tags($s->pertanyaan ?? '')));
                }

                if (!$hasKey) {
                    $hasInvalidKey = true;
                    break;
                }
            }

            $isBobotValid = abs($totalBobot - 100.0) < 0.05;

            if ($isBobotValid && !$hasInvalidKey) {
                $siapUji++;
            } else {
                $perluReview++;
            }
        }

        // Hitung guru yang belum membuat bank soal sama sekali
        $guruBelumBuatCount = 0;
        if ($user && $user->isAdmin()) {
            $guruIdsWithBank = BankSoal::distinct()->pluck('user_id')->filter()->toArray();
            $guruBelumBuatCount = User::where('role', 'guru')
                ->where('is_active', true)
                ->whereNotIn('id', $guruIdsWithBank)
                ->count();
        }

        return [
            'total_paket' => $totalPaket,
            'total_butir' => $totalButir,
            'siap_uji' => $siapUji,
            'perlu_review' => $perluReview,
            'kosong' => $kosong,
            'guru_belum_buat' => $guruBelumBuatCount,
        ];
    }

    /**
     * Dapatkan daftar paket bank soal ter-evaluasi sesuai filter
     */
    public function getEvaluatedBankSoalsProperty(): Collection
    {
        $user = Auth::user();
        $query = BankSoal::with(['user', 'mapel', 'kelases', 'soals.opsiJawabans']);

        if ($user && $user->isGuru()) {
            $query->where('user_id', $user->id);
        }

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('judul', 'like', $term)
                  ->orWhere('kode_bank', 'like', $term)
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $term))
                  ->orWhereHas('mapel', fn ($mq) => $mq->where('nama_mapel', 'like', $term));
            });
        }

        if ($this->filterTingkat !== 'all') {
            $query->where('tingkat', $this->filterTingkat);
        }

        if ($this->filterMapel !== 'all') {
            $query->where('mapel_id', $this->filterMapel);
        }

        if ($this->filterGuru !== 'all' && ($user && $user->isAdmin())) {
            $query->where('user_id', $this->filterGuru);
        }

        $records = $query->latest('updated_at')->get();

        $evaluated = $records->map(function (BankSoal $bank) {
            $soals = $bank->soals;
            $totalSoal = $soals->count();
            $totalBobot = (float) $soals->sum('bobot_nilai');

            $soalTanpaKunci = 0;
            $komposisi = [
                'pilihan_ganda' => 0,
                'pg_kompleks' => 0,
                'benar_salah' => 0,
                'menjodohkan' => 0,
                'isian' => 0,
                'essay' => 0,
            ];

            foreach ($soals as $s) {
                if (isset($komposisi[$s->tipe_soal])) {
                    $komposisi[$s->tipe_soal]++;
                }

                $hasKey = false;
                if (in_array($s->tipe_soal, ['pilihan_ganda', 'benar_salah', 'pg_kompleks'])) {
                    $hasKey = $s->opsiJawabans->where('is_jawaban_benar', true)->isNotEmpty();
                } elseif ($s->tipe_soal === 'menjodohkan') {
                    $hasKey = $s->opsiJawabans->filter(fn ($o) => !empty($o->pasangan_matching))->isNotEmpty();
                } elseif ($s->tipe_soal === 'isian') {
                    $hasKey = !empty(trim($s->kunci_isian ?? ''));
                } elseif ($s->tipe_soal === 'essay') {
                    $hasKey = !empty(trim(strip_tags($s->pertanyaan ?? '')));
                }

                if (!$hasKey) {
                    $soalTanpaKunci++;
                }
            }

            $isBobotValid = $totalSoal > 0 && abs($totalBobot - 100.0) < 0.05;
            $isSiap = $totalSoal > 0 && $isBobotValid && $soalTanpaKunci === 0;

            if ($totalSoal === 0) {
                $statusKesiapan = 'kosong';
                $statusLabel = 'Belum Ada Butir';
            } elseif ($isSiap) {
                $statusKesiapan = 'siap';
                $statusLabel = 'Siap Digunakan';
            } else {
                $statusKesiapan = 'review';
                $statusLabel = 'Perlu Review';
            }

            $bobotPercent = min(100, max(0, (float) round($totalBobot, 1)));

            return (object) [
                'bank' => $bank,
                'id' => $bank->id,
                'judul' => $bank->judul,
                'kode_bank' => $bank->kode_bank,
                'tingkat' => $bank->tingkat,
                'guru' => $bank->user,
                'mapel' => $bank->mapel,
                'kelases' => $bank->kelases,
                'total_soal' => $totalSoal,
                'total_bobot' => $totalBobot,
                'bobot_percent' => $bobotPercent,
                'is_bobot_valid' => $isBobotValid,
                'soal_tanpa_kunci' => $soalTanpaKunci,
                'komposisi' => $komposisi,
                'status_kesiapan' => $statusKesiapan,
                'status_label' => $statusLabel,
                'updated_at' => $bank->updated_at,
            ];
        });

        // Filter status kesiapan
        if ($this->filterStatus !== 'all') {
            $evaluated = $evaluated->filter(fn ($item) => $item->status_kesiapan === $this->filterStatus);
        }

        // Sorting
        if ($this->sortBy === 'judul') {
            $evaluated = $this->sortDirection === 'asc'
                ? $evaluated->sortBy('judul', SORT_NATURAL | SORT_FLAG_CASE)
                : $evaluated->sortByDesc('judul', SORT_NATURAL | SORT_FLAG_CASE);
        } elseif ($this->sortBy === 'guru') {
            $evaluated = $this->sortDirection === 'asc'
                ? $evaluated->sortBy(fn ($i) => $i->guru?->name ?? '', SORT_NATURAL | SORT_FLAG_CASE)
                : $evaluated->sortByDesc(fn ($i) => $i->guru?->name ?? '', SORT_NATURAL | SORT_FLAG_CASE);
        } elseif ($this->sortBy === 'total_soal') {
            $evaluated = $this->sortDirection === 'asc'
                ? $evaluated->sortBy('total_soal')
                : $evaluated->sortByDesc('total_soal');
        } elseif ($this->sortBy === 'total_bobot') {
            $evaluated = $this->sortDirection === 'asc'
                ? $evaluated->sortBy('total_bobot')
                : $evaluated->sortByDesc('total_bobot');
        } else {
            $evaluated = $this->sortDirection === 'asc'
                ? $evaluated->sortBy('updated_at')
                : $evaluated->sortByDesc('updated_at');
        }

        return $evaluated->values();
    }

    /**
     * Dapatkan daftar guru yang belum membuat bank soal
     */
    public function getGuruBelumBuatListProperty(): Collection
    {
        $guruIdsWithBank = BankSoal::distinct()->pluck('user_id')->filter()->toArray();

        return User::where('role', 'guru')
            ->where('is_active', true)
            ->whereNotIn('id', $guruIdsWithBank)
            ->with(['mapels', 'kelases'])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Opsi filter dropdown
     */
    public function getMapelOptionsProperty(): array
    {
        return Mapel::orderBy('nama_mapel', 'asc')->pluck('nama_mapel', 'id')->toArray();
    }

    public function getGuruOptionsProperty(): array
    {
        return User::where('role', 'guru')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Quick Action: Bagi Rata Bobot ke 100 Poin
     */
    public function autoBagiRataBobot(int $bankSoalId): void
    {
        $bank = BankSoal::findOrFail($bankSoalId);

        abort_unless(BankSoalResource::canEdit($bank), 403, 'Akses ditolak.');

        if ($bank->soals()->count() === 0) {
            Notification::make()
                ->title('Gagal Membagi Bobot')
                ->body('Bank soal ini belum memiliki butir soal.')
                ->warning()
                ->send();
            return;
        }

        $bank->autoDistributeBobot(100.0);
        $bank->refresh();

        Notification::make()
            ->title('Bobot Berhasil Diratakan')
            ->body("Seluruh butir soal pada paket \"{$bank->judul}\" telah dibagi rata menjadi total 100 poin.")
            ->success()
            ->send();
    }

    public function toggleSort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = in_array($column, ['total_soal', 'total_bobot', 'updated_at']) ? 'desc' : 'asc';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function resetFilters(): void
    {
        $user = Auth::user();
        $this->search = '';
        $this->filterTingkat = 'all';
        $this->filterMapel = 'all';
        $this->filterGuru = ($user && $user->isGuru()) ? (string) $user->id : 'all';
        $this->filterStatus = 'all';
        $this->sortBy = 'updated_at';
        $this->sortDirection = 'desc';
    }

    /**
     * Ekspor rekap data progres ke CSV
     */
    public function exportCsv()
    {
        $items = $this->evaluatedBankSoals;
        $filename = "rekap_progres_pembuatan_soal_" . now()->format('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'Kode Soal',
                'Judul Bank Soal',
                'Guru Pembuat',
                'Mata Pelajaran',
                'Tingkat',
                'Target Rombel',
                'Total Butir',
                'Total Bobot',
                'Soal Tanpa Kunci',
                'Status Kesiapan',
                'Terakhir Diperbarui',
            ]);

            foreach ($items as $index => $item) {
                fputcsv($handle, [
                    $index + 1,
                    $item->kode_bank,
                    $item->judul,
                    $item->guru?->name ?? '-',
                    $item->mapel?->nama_mapel ?? '-',
                    $item->tingkat ? "Kelas {$item->tingkat}" : 'Semua',
                    $item->kelases->pluck('nama_kelas')->join(', ') ?: '-',
                    $item->total_soal,
                    (float) $item->total_bobot,
                    $item->soal_tanpa_kunci,
                    $item->status_label,
                    $item->updated_at ? $item->updated_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

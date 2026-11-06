<?php

namespace App\Filament\Widgets;

use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianSesi;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TailadminCbtMetricsWidget extends Widget
{
    protected static ?int $sort = 1; // Displayed right under banner

    protected string $view = 'filament.widgets.tailadmin-cbt-metrics';

    protected int | string | array $columnSpan = 'full';

    public function getMetrics(): array
    {
        $user = Auth::user();
        $isAdmin = $user && $user->isAdmin();

        if ($isAdmin) {
            $totalSiswa = Siswa::count();
            $totalKelas = Kelas::count();
            $ujianAktif = Ujian::where('status', 'aktif')->count();
            $totalUjian = Ujian::count();
            $totalSoal = Soal::count();
            $totalBank = BankSoal::count();
            $sesiSelesai = UjianSesi::where('status', 'selesai')->count();
            $sesiBerjalan = UjianSesi::where('status', 'mengerjakan')->count();

            return [
                [
                    'label' => 'Total Siswa Terdaftar',
                    'value' => number_format($totalSiswa, 0, ',', '.'),
                    'sub' => $totalKelas . ' Rombel Kelas',
                    'trend' => 'Aktif',
                    'badge_type' => 'success',
                    'icon_bg' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                    'icon' => 'user',
                ],
                [
                    'label' => 'Jadwal Ujian Aktif',
                    'value' => (string) $ujianAktif,
                    'sub' => 'Dari total ' . $totalUjian . ' jadwal',
                    'trend' => 'Token 10 Mnt',
                    'badge_type' => 'info',
                    'icon_bg' => 'bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-400',
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Bank Soal & Butir Soal',
                    'value' => number_format($totalSoal, 0, ',', '.'),
                    'sub' => $totalBank . ' Paket Bank Soal',
                    'trend' => 'AI Ready',
                    'badge_type' => 'warning',
                    'icon_bg' => 'bg-warning-50 text-warning-500 dark:bg-warning-500/15 dark:text-warning-400',
                    'icon' => 'document',
                ],
                [
                    'label' => 'Sesi Ujian Selesai',
                    'value' => number_format($sesiSelesai, 0, ',', '.'),
                    'sub' => $sesiBerjalan . ' Siswa Live Mengerjakan',
                    'trend' => '100 Poin',
                    'badge_type' => 'success',
                    'icon_bg' => 'bg-success-50 text-success-500 dark:bg-success-500/15 dark:text-success-400',
                    'icon' => 'trophy',
                ],
            ];
        }

        // Statistik untuk Guru
        $guruId = $user?->id;
        $myBankSoals = BankSoal::where('guru_id', $guruId)->get();
        $totalBank = $myBankSoals->count();
        $totalSoal = Soal::whereIn('bank_soal_id', $myBankSoals->pluck('id'))->count();
        $myUjians = Ujian::where('guru_id', $guruId)->get();
        $ujianAktif = $myUjians->where('status', 'aktif')->count();
        $sesiSelesai = UjianSesi::whereIn('ujian_id', $myUjians->pluck('id'))->where('status', 'selesai')->count();
        $totalSiswa = Siswa::count();

        return [
            [
                'label' => 'Bank Soal Saya',
                'value' => (string) $totalBank,
                'sub' => $totalSoal . ' Butir Soal Terbuat',
                'trend' => 'Studio AI',
                'badge_type' => 'brand',
                'icon_bg' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'icon' => 'document',
            ],
            [
                'label' => 'Ujian Aktif Saya',
                'value' => (string) $ujianAktif,
                'sub' => 'Dari ' . $myUjians->count() . ' Jadwal Ujian',
                'trend' => 'Token Live',
                'badge_type' => 'info',
                'icon_bg' => 'bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-400',
                'icon' => 'calendar',
            ],
            [
                'label' => 'Siswa Selesai Ujian',
                'value' => number_format($sesiSelesai, 0, ',', '.'),
                'sub' => 'Terkoreksi Otomatis',
                'trend' => 'Skala 100',
                'badge_type' => 'success',
                'icon_bg' => 'bg-success-50 text-success-500 dark:bg-success-500/15 dark:text-success-400',
                'icon' => 'trophy',
            ],
            [
                'label' => 'Total Siswa CBT',
                'value' => number_format($totalSiswa, 0, ',', '.'),
                'sub' => $user?->school_name ?: 'SMK Assuniyah',
                'trend' => 'Aktif',
                'badge_type' => 'warning',
                'icon_bg' => 'bg-warning-50 text-warning-500 dark:bg-warning-500/15 dark:text-warning-400',
                'icon' => 'user',
            ],
        ];
    }
}

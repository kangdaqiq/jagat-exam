<?php

namespace App\Filament\Widgets;

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            $totalGuru = User::where('role', 'guru')->count();
            $totalMapel = Mapel::count();
            $totalKelas = Kelas::count();
            $totalAdmin = User::where('role', 'admin')->count();

            return [
                Stat::make('Total Guru', $totalGuru)
                    ->description('Guru terdaftar di CBT')
                    ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                    ->color('success'),

                Stat::make('Mata Pelajaran', $totalMapel)
                    ->description('Mapel aktif dari absensi')
                    ->descriptionIcon(Heroicon::OutlinedBookOpen)
                    ->color('info'),

                Stat::make('Kelas / Rombel', $totalKelas)
                    ->description('Rombel peserta ujian')
                    ->descriptionIcon(Heroicon::OutlinedRectangleStack)
                    ->color('warning'),

                Stat::make('Administrator', $totalAdmin)
                    ->description('Pengelola sistem CBT')
                    ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                    ->color('danger'),
            ];
        }

        // Statistik Dashboard khusus Guru (Tanpa data kelas & mapel sesuai permintaan)
        $schoolName = $user?->school_name ?: 'SMK Assuniyah Tumijajar';
        $nipInfo = $user?->nip ? 'NIP: ' . $user->nip : 'Akun Terverifikasi';

        return [
            Stat::make('Status Guru', $user?->name ?? 'Bapak/Ibu Guru')
                ->description('Status: Guru Pengampu Aktif')
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->color('success'),

            Stat::make('Asal Sekolah', $schoolName)
                ->description($nipInfo)
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2)
                ->color('primary'),

            Stat::make('ExamGuard CBT Portal', 'Siap Digunakan')
                ->description('Keamanan Ujian Anti-Nyontek')
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('info'),

            Stat::make('Kelas & Mapel', 'Menu Tersedia')
                ->description('Lihat kelas, mapel & jumlah siswa')
                ->descriptionIcon(Heroicon::OutlinedArrowRightCircle)
                ->color('warning'),
        ];
    }
}

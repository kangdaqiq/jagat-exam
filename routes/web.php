<?php

use App\Http\Controllers\HasilUjianController;
use App\Livewire\ExamRunner;
use App\Livewire\Pengawas\PengawasLogin;
use App\Livewire\Pengawas\PortalPengawas;
use App\Livewire\Siswa\PortalUjian;
use App\Livewire\Siswa\SiswaLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. Root Route
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isSiswa()) {
            return redirect()->route('siswa.portal');
        } elseif ($user->isGuru()) {
            return redirect()->to('/guru');
        } elseif ($user->isAdmin()) {
            return redirect()->to('/admin');
        }
    }
    return redirect()->route('login');
});

// 1b. Dashboard Named Route (TailAdmin compatibility)
Route::get('/locale/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id', 'ar', 'es', 'de'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('locale.switch');

Route::get('/dashboard', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->to('/admin');
        } elseif ($user->isGuru()) {
            return redirect()->to('/guru');
        }
        return redirect()->route('siswa.portal');
    }
    return redirect()->route('login');
})->name('dashboard');

// 2. Autentikasi Siswa & Pengawas (PWA)
Route::get('/login', SiswaLogin::class)->name('login');
Route::get('/siswa/login', SiswaLogin::class)->name('siswa.login');
Route::get('/pengawas/login', PengawasLogin::class)->name('pengawas.login');

// 2b. Portal PWA Khusus Pengawas Ujian (Mobile-First Realtime Monitoring)
// Catatan: Pengecekan auth dilakukan di PortalPengawas::mount() agar redirect ke /pengawas/login (bukan /login siswa)
Route::get('/pengawas', PortalPengawas::class)->name('pengawas.portal');
Route::get('/pengawas/ujian/{ujian}', PortalPengawas::class)->name('pengawas.ujian');

// 3. Portal Siswa & Input Token Ujian
Route::middleware(['auth'])->group(function () {
    Route::get('/portal', PortalUjian::class)->name('siswa.portal');
    Route::get('/siswa', PortalUjian::class);
    Route::get('/siswa/portal', PortalUjian::class);

    // 4. Lembar Pengerjaan Soal via Token Sesi Resmi
    Route::get('/ujian/sesi/{token_sesi}', ExamRunner::class)->name('ujian.sesi');
});

// 5. Rute Kompatibilitas Bank Soal / Mode Preview Guru & Admin
Route::get('/exam/{bankSoal}', ExamRunner::class)->name('exam.run');
Route::get('/ujian/{bankSoal}', ExamRunner::class)->name('ujian.run');

// 5b. Rute Rekap Hasil Ujian, Berita Acara, Daftar Hadir, & Kartu Peserta
Route::middleware(['auth'])->group(function () {
    Route::get('/hasil-ujians/{ujian}/export-csv', [HasilUjianController::class, 'exportCsv'])->name('hasil-ujian.export-csv');
    Route::get('/hasil-ujians/{ujian}/print', [HasilUjianController::class, 'print'])->name('hasil-ujian.print');
    Route::get('/hasil-ujians/{ujian}/berita-acara', [HasilUjianController::class, 'beritaAcara'])->name('hasil-ujian.berita-acara');
    Route::get('/kartu-peserta/cetak', [HasilUjianController::class, 'cetakKartuPeserta'])->name('kartu-peserta.print');
});

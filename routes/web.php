<?php

use App\Livewire\ExamRunner;
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

// 2. Autentikasi Siswa
Route::get('/login', SiswaLogin::class)->name('login');
Route::get('/siswa/login', SiswaLogin::class)->name('siswa.login');

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

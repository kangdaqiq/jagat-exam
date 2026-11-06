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

// 6. Rute Demo & Halaman Template TailAdmin
Route::middleware(['auth'])->group(function () {
    Route::get('/calendar', fn () => view('pages.calender', ['title' => 'Calendar']))->name('calendar');
    Route::get('/profile', fn () => view('pages.profile', ['title' => 'Profile']))->name('profile');
    Route::get('/form-elements', fn () => view('pages.form.form-elements', ['title' => 'Form Elements']))->name('form-elements');
    Route::get('/basic-tables', fn () => view('pages.tables.basic-tables', ['title' => 'Basic Tables']))->name('basic-tables');
    Route::get('/blank', fn () => view('pages.blank', ['title' => 'Blank']))->name('blank');
    Route::get('/error-404', fn () => view('pages.errors.error-404', ['title' => 'Error 404']))->name('error-404');
    Route::get('/line-chart', fn () => view('pages.chart.line-chart', ['title' => 'Line Chart']))->name('line-chart');
    Route::get('/bar-chart', fn () => view('pages.chart.bar-chart', ['title' => 'Bar Chart']))->name('bar-chart');
    Route::get('/alerts', fn () => view('pages.ui-elements.alerts', ['title' => 'Alerts']))->name('alerts');
    Route::get('/avatars', fn () => view('pages.ui-elements.avatars', ['title' => 'Avatars']))->name('avatars');
    Route::get('/badge', fn () => view('pages.ui-elements.badges', ['title' => 'Badges']))->name('badges');
    Route::get('/buttons', fn () => view('pages.ui-elements.buttons', ['title' => 'Buttons']))->name('buttons');
    Route::get('/image', fn () => view('pages.ui-elements.images', ['title' => 'Images']))->name('images');
    Route::get('/videos', fn () => view('pages.ui-elements.videos', ['title' => 'Videos']))->name('videos');
});

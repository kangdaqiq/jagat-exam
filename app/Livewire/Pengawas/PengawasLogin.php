<?php

namespace App\Livewire\Pengawas;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pengawas-pwa')]
#[Title('Login Pengawas Ujian - Examuna PWA')]
class PengawasLogin extends Component
{
    public string $login = '';
    public string $password = '';
    public bool $remember = true;

    public function mount()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isGuru() || $user->isAdmin()) {
                return redirect()->route('pengawas.portal');
            }
            if ($user->isSiswa()) {
                return redirect()->route('siswa.portal');
            }
        }
    }

    public function doLogin()
    {
        $this->validate([
            'login' => 'required',
            'password' => 'required',
        ], [
            'login.required' => 'NIP, Username, atau Email Guru wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $input = trim($this->login);
        $throttleKey = 'pengawas-login:' . Str::lower($input) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('login', "Terlalu banyak percobaan login gagal. Silakan tunggu {$seconds} detik lagi.");
            return;
        }

        $user = User::where('email', $input)
            ->orWhere('nip', $input)
            ->when(!str_contains($input, '@'), function ($query) use ($input) {
                $query->orWhere('email', 'like', $input . '@%');
            })
            ->first();

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('login', 'Akun Guru Pengawas dengan NIP/Email tersebut tidak ditemukan.');
            return;
        }

        if (!$user->isGuru() && !$user->isAdmin()) {
            $this->addError('login', 'Akses ditolak: Portal ini khusus untuk Guru Pengawas dan Proktor Ujian.');
            return;
        }

        if (!$user->is_active) {
            $this->addError('login', 'Akun Anda sedang dinonaktifkan. Hubungi Administrator CBT.');
            return;
        }

        if (!Auth::attempt(['email' => $user->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('password', 'Kata sandi yang Anda masukkan salah.');
            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        return redirect()->route('pengawas.portal');
    }

    public function render()
    {
        return view('livewire.pengawas.pengawas-login');
    }
}


<?php

namespace App\Livewire\Siswa;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.exam')]
class SiswaLogin extends Component
{
    public string $login = '';
    public string $password = '';
    public bool $remember = false;

    public function mount()
    {
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
    }

    public function doLogin()
    {
        $this->validate([
            'login' => 'required',
            'password' => 'required',
        ], [
            'login.required' => 'NIS atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $input = trim($this->login);
        $throttleKey = 'cbt-login:' . Str::lower($input) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('login', "Terlalu banyak percobaan login gagal. Silakan tunggu {$seconds} detik sebelum mencoba kembali.");
            return;
        }

        // Cari user berdasarkan email/username penuh, NIP, NIS Siswa, atau format email standar siswa
        $user = User::where('email', $input)
            ->orWhere('nip', $input)
            ->orWhereHas('siswa', function ($q) use ($input) {
                $q->where('nis', $input);
            })
            ->when(!str_contains($input, '@'), function ($query) use ($input) {
                $query->orWhere('email', $input . '@siswa.cbt')
                      ->orWhere('email', 'like', $input . '@%');
            })
            ->first();

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('login', 'Akun dengan NIS/Email tersebut tidak ditemukan.');
            return;
        }

        if (!$user->is_active) {
            $this->addError('login', 'Akun Anda sedang dinonaktifkan. Silakan hubungi proktor/admin.');
            return;
        }

        // Kunci Sesi 1 Perangkat (Single-Device Lock) khusus Siswa
        if ($user->isSiswa()) {
            // Bersihkan sesi ujian yang waktu pengerjaannya sudah habis terlebih dahulu
            $expiredSesis = \App\Models\UjianSesi::where('user_id', $user->id)
                ->where('status', 'mengerjakan')
                ->whereNotNull('waktu_selesai')
                ->where('waktu_selesai', '<', now())
                ->get();

            foreach ($expiredSesis as $expSesi) {
                $expSesi->koreksiDanSelesaikan();
            }

            $currentBrowserToken = session('cbt_device_token');
            if (filled($user->active_session_token) && $user->active_session_token !== $currentBrowserToken) {
                $this->addError('login', 'Akun Anda sedang aktif login di perangkat/browser lain. Silakan minta Guru Pengawas menekan tombol "Reset Login" untuk membuka kunci perangkat.');
                return;
            }
        }

        if (!Auth::attempt(['email' => $user->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('password', 'Password yang Anda masukkan salah.');
            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        if ($user->isSiswa()) {
            $deviceToken = Str::random(40);
            session(['cbt_device_token' => $deviceToken]);
            $user->update([
                'active_session_token' => $deviceToken,
                'last_login_at' => now(),
            ]);

            return redirect()->route('siswa.portal');
        } elseif ($user->isGuru()) {
            return redirect()->to('/guru');
        }

        return redirect()->to('/admin');
    }

    public function render()
    {
        return view('livewire.siswa.siswa-login');
    }
}


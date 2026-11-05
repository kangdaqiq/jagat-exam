<?php

namespace App\Livewire\Siswa;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

        // Cari user berdasarkan email atau NIP/NIS atau relasi Siswa
        $user = User::where('email', $input)
            ->orWhere('nip', $input)
            ->orWhereHas('siswa', function ($q) use ($input) {
                $q->where('nis', $input);
            })
            ->first();

        if (!$user) {
            $this->addError('login', 'Akun dengan NIS/Email tersebut tidak ditemukan.');
            return;
        }

        if (!$user->is_active) {
            $this->addError('login', 'Akun Anda sedang dinonaktifkan. Silakan hubungi proktor/admin.');
            return;
        }

        if (!Auth::attempt(['email' => $user->email, 'password' => $this->password], $this->remember)) {
            $this->addError('password', 'Password yang Anda masukkan salah.');
            return;
        }

        session()->regenerate();

        if ($user->isSiswa()) {
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


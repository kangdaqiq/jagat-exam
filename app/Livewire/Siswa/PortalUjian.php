<?php

namespace App\Livewire\Siswa;

use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\UjianJawaban;
use App\Models\UjianPeserta;
use App\Models\UjianSesi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.exam')]
class PortalUjian extends Component
{
    public string $token_input = '';

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (!$user->isSiswa() && ($user->isAdmin() || $user->isGuru())) {
            return redirect()->to('/admin');
        }
    }

    public function submitToken()
    {
        $this->validate([
            'token_input' => 'required|min:4|max:12',
        ], [
            'token_input.required' => 'Silakan masukkan kode token akses ujian.',
            'token_input.min' => 'Kode token minimal 4 karakter.',
        ]);

        $token = strtoupper(trim($this->token_input));
        $user = Auth::user();
        $siswa = $user->siswa;

        // 1. Cari Ujian yang sedang ONGOING dengan token ini
        $ujian = Ujian::where('token_ujian', $token)
            ->where('status', 'ONGOING')
            ->first();

        if (!$ujian) {
            $this->addError('token_input', 'Kode token salah, sudah kedaluwarsa, atau ujian belum dibuka oleh Guru Pengawas.');
            return;
        }

        // 2. Cek apakah bank soal memiliki butir soal
        $bankSoal = $ujian->bankSoal;
        if (!$bankSoal || $bankSoal->soals()->count() === 0) {
            $this->addError('token_input', 'Paket bank soal untuk ujian ini belum memiliki butir soal.');
            return;
        }

        // 3. Verifikasi apakah siswa terdaftar sebagai peserta ujian ini
        $peserta = UjianPeserta::where('ujian_id', $ujian->id)
            ->where(function ($q) use ($siswa, $user) {
                if ($siswa) {
                    $q->where('siswa_id', $siswa->id)->orWhere('user_id', $user->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->first();

        // Jika belum ada di tabel peserta, cek apakah kelas siswa terdaftar di ujian ini
        if (!$peserta && $siswa && $siswa->kelas_id) {
            $isKelasIncluded = $ujian->kelas()->where('kelas.id', $siswa->kelas_id)->exists();
            if ($isKelasIncluded) {
                $peserta = UjianPeserta::create([
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                    'user_id' => $user->id,
                    'status' => 'BELUM_MULAI',
                ]);
            }
        }

        if (!$peserta) {
            $this->addError('token_input', 'Anda tidak terdaftar sebagai peserta pada ujian ini. Silakan hubungi Guru Pengawas / Proktor.');
            return;
        }

        // 4. Cek apakah siswa sudah menyelesaikan ujian ini
        if ($peserta->status === 'SELESAI') {
            $this->addError('token_input', 'Anda sudah menyelesaikan ujian ini dan lembar jawaban telah dikirim.');
            return;
        }

        // 5. Cari sesi pengerjaan yang sudah ada atau buat sesi baru
        $sesi = UjianSesi::where('ujian_id', $ujian->id)
            ->where('user_id', $user->id)
            ->where('status', 'mengerjakan')
            ->first();

        if (!$sesi) {
            $tokenSesi = Str::random(36);
            $durasiMenit = $ujian->durasi_menit ?: ($bankSoal->durasi_menit ?: 60);

            $sesi = UjianSesi::create([
                'ujian_id' => $ujian->id,
                'bank_soal_id' => $bankSoal->id,
                'user_id' => $user->id,
                'token_sesi' => $tokenSesi,
                'nama_peserta' => $siswa ? $siswa->nama : $user->name,
                'nomor_peserta' => $siswa ? $siswa->nis : ($user->nip ?: 'SISWA-' . $user->id),
                'waktu_mulai' => now(),
                'waktu_selesai' => now()->addMinutes($durasiMenit),
                'durasi_menit' => $durasiMenit,
                'status' => 'mengerjakan',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Siapkan butir soal (acak atau urut sesuai pengaturan ujian / bank soal)
            $soals = $bankSoal->soals()->with('opsiJawabans')->orderBy('nomor_urut', 'asc')->get();
            $acakSoal = $ujian->acak_soal ?? $bankSoal->acak_soal ?? true;
            $acakOpsi = $ujian->acak_opsi ?? $bankSoal->acak_opsi ?? true;

            if ($acakSoal) {
                $soals = $soals->shuffle();
            }

            foreach ($soals->values() as $idx => $s) {
                $urutanOpsi = null;
                // Hanya acak opsi untuk tipe pilihan ganda dan pg kompleks (Benar/Salah tidak diacak)
                if ($acakOpsi && in_array($s->tipe_soal, ['pilihan_ganda', 'pg_kompleks']) && $s->opsiJawabans->isNotEmpty()) {
                    $urutanOpsi = $s->opsiJawabans->pluck('id')->shuffle()->values()->toArray();
                }

                UjianJawaban::create([
                    'ujian_sesi_id' => $sesi->id,
                    'soal_id' => $s->id,
                    'nomor_urut' => $idx + 1,
                    'urutan_opsi' => $urutanOpsi,
                    'jawaban_pilihan' => null,
                    'jawaban_teks' => null,
                    'is_ragu' => false,
                ]);
            }
        }

        // 6. Update status peserta menjadi SEDANG_MENGERJAKAN
        $peserta->update([
            'status' => 'SEDANG_MENGERJAKAN',
            'ujian_sesi_id' => $sesi->id,
            'waktu_mulai' => $peserta->waktu_mulai ?: now(),
        ]);

        session(["ujian_token_{$bankSoal->id}" => $sesi->token_sesi]);

        // 7. Arahkan siswa ke lembar ujian
        return redirect()->route('ujian.sesi', ['token_sesi' => $sesi->token_sesi]);
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function render()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        // Cari daftar ujian yang relevan untuk kelas siswa
        $daftarUjian = collect();
        if ($siswa && $siswa->kelas_id) {
            $daftarUjian = Ujian::whereHas('kelas', function ($q) use ($siswa) {
                $q->where('kelas.id', $siswa->kelas_id);
            })
            ->with(['mapel', 'guru', 'bankSoal'])
            ->whereIn('status', ['SCHEDULED', 'ONGOING', 'COMPLETED'])
            ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 1 WHEN status = 'SCHEDULED' THEN 2 ELSE 3 END")
            ->orderBy('tanggal', 'desc')
            ->take(6)
            ->get();
        }

        // Riwayat ujian siswa yang sudah selesai
        $riwayatUjian = UjianPeserta::where('user_id', $user->id)
            ->with(['ujian.mapel', 'ujian.bankSoal'])
            ->where('status', 'SELESAI')
            ->orderBy('waktu_selesai', 'desc')
            ->take(5)
            ->get();

        return view('livewire.siswa.portal-ujian', [
            'user' => $user,
            'siswa' => $siswa,
            'daftarUjian' => $daftarUjian,
            'riwayatUjian' => $riwayatUjian,
        ]);
    }
}


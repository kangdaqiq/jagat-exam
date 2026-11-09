<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ujian extends Model
{
    use HasFactory;

    protected $table = 'ujians';

    protected $fillable = [
        'exam_id',
        'nama_ujian',
        'tipe_ujian',
        'mapel_id',
        'bank_soal_id',
        'guru_id',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'durasi_menit',
        'acak_soal',
        'acak_opsi',
        'status',
        'token_ujian',
        'token_generated_at',
        'keterangan',
    ];

    public const TIPE_ASESMEN_HARIAN = 'asesmen_harian';
    public const TIPE_TENGAH_SEMESTER = 'asesmen_tengah_semester';
    public const TIPE_AKHIR_SEMESTER = 'asesmen_akhir_semester';

    public const TOKEN_EXPIRY_MINUTES = 10;

    public static function getTipeOptions(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        // Guru hanya boleh membuat Asesmen Harian
        if ($user && $user->isGuru()) {
            return [
                self::TIPE_ASESMEN_HARIAN => 'Asesmen Harian (Ulangan Harian)',
            ];
        }

        // Admin berhak membuat semua jenis asesmen
        return [
            self::TIPE_ASESMEN_HARIAN => 'Asesmen Harian (Ulangan Harian)',
            self::TIPE_TENGAH_SEMESTER => 'Asesmen Tengah Semester (PTS / STS)',
            self::TIPE_AKHIR_SEMESTER => 'Asesmen Akhir Semester (PAS / SAS)',
        ];
    }

    public function isAsesmenHarian(): bool
    {
        return ($this->tipe_ujian ?? self::TIPE_ASESMEN_HARIAN) === self::TIPE_ASESMEN_HARIAN;
    }

    public function isAsesmenTengahSemester(): bool
    {
        return $this->tipe_ujian === self::TIPE_TENGAH_SEMESTER;
    }

    public function isAsesmenAkhirSemester(): bool
    {
        return $this->tipe_ujian === self::TIPE_AKHIR_SEMESTER;
    }

    public function getTipeLabel(): string
    {
        return match ($this->tipe_ujian) {
            self::TIPE_ASESMEN_HARIAN => 'Asesmen Harian',
            self::TIPE_TENGAH_SEMESTER => 'Tengah Semester (PTS/STS)',
            self::TIPE_AKHIR_SEMESTER => 'Akhir Semester (PAS/SAS)',
            default => 'Asesmen Harian',
        };
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'durasi_menit' => 'integer',
            'acak_soal' => 'boolean',
            'acak_opsi' => 'boolean',
            'token_generated_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function (Ujian $ujian) {
            if (empty($ujian->exam_id)) {
                $ujian->exam_id = static::generateExamId();
            }
        });
    }

    /**
     * Generate unique exam_id (contoh: EXAM-202611-7B8A)
     */
    public static function generateExamId(): string
    {
        do {
            $code = 'EXAM-' . date('Ym') . '-' . strtoupper(Str::random(4));
        } while (static::where('exam_id', $code)->exists());

        return $code;
    }

    /**
     * Generate kode token akses ujian 6 karakter (contoh: A7K9P2)
     * Menggunakan karakter yang jelas dan tidak membingungkan (tanpa 0, O, 1, I).
     */
    public static function generateToken(): string
    {
        $pool = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $token = '';
        for ($i = 0; $i < 6; $i++) {
            $token .= $pool[random_int(0, strlen($pool) - 1)];
        }
        return $token;
    }

    /**
     * Membuka sesi ujian oleh Guru (Status -> ONGOING, buat Token baru)
     */
    public function startExam(): string
    {
        $token = static::generateToken();
        $this->update([
            'status' => 'ONGOING',
            'token_ujian' => $token,
            'token_generated_at' => now(),
        ]);

        return $token;
    }

    /**
     * Menghasilkan ulang token baru saat ujian sedang berlangsung
     */
    public function refreshToken(): string
    {
        $token = static::generateToken();
        $this->update([
            'token_ujian' => $token,
            'token_generated_at' => now(),
        ]);

        return $token;
    }

    /**
     * Memeriksa apakah waktu jadwal pelaksanaan ujian sudah habis / lewat batas jadwal.
     */
    public function isJadwalBerakhir(): bool
    {
        if (in_array($this->status, ['COMPLETED', 'CANCELLED'], true)) {
            return true;
        }

        if ($this->tanggal && $this->waktu_selesai) {
            $tglStr = $this->tanggal instanceof \Carbon\Carbon
                ? $this->tanggal->format('Y-m-d')
                : substr((string) $this->tanggal, 0, 10);
            $jadwalSelesai = \Carbon\Carbon::parse("{$tglStr} {$this->waktu_selesai}");

            return now()->greaterThan($jadwalSelesai);
        }

        if ($this->tanggal && empty($this->waktu_selesai)) {
            $tgl = $this->tanggal instanceof \Carbon\Carbon
                ? $this->tanggal->copy()->endOfDay()
                : \Carbon\Carbon::parse((string) $this->tanggal)->endOfDay();

            return now()->greaterThan($tgl);
        }

        if (empty($this->tanggal) && $this->waktu_selesai) {
            $jadwalSelesai = \Carbon\Carbon::parse(now()->format('Y-m-d') . ' ' . $this->waktu_selesai);

            return now()->greaterThan($jadwalSelesai);
        }

        return false;
    }

    /**
     * Memeriksa apakah token masih boleh dirilis / diperbarui oleh pengawas.
     */
    public function canReleaseToken(): bool
    {
        return ! $this->isJadwalBerakhir();
    }

    /**
     * Memeriksa apakah token ujian saat ini sudah kedaluwarsa (berlaku 10 menit)
     */
    public function isTokenExpired(): bool
    {
        if ($this->status !== 'ONGOING' || $this->isJadwalBerakhir()) {
            return true;
        }

        if (empty($this->token_ujian) || ! $this->token_generated_at) {
            return true;
        }

        return now()->greaterThan($this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES));
    }

    /**
     * Memeriksa apakah token ujian saat ini masih aktif dan dapat digunakan
     */
    public function isTokenActive(): bool
    {
        return $this->status === 'ONGOING' && ! empty($this->token_ujian) && ! $this->isTokenExpired();
    }

    /**
     * Hitung sisa detik masa berlaku token sebelum kedaluwarsa (maks 10 menit / 600 detik)
     */
    public function sisaDetikToken(): int
    {
        if (! $this->isTokenActive() || ! $this->token_generated_at) {
            return 0;
        }

        $expiredAt = $this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES);

        return max(0, (int) now()->diffInSeconds($expiredAt, false));
    }

    /**
     * Format sisa waktu token (contoh: "08:45" atau "Kedaluwarsa")
     */
    public function sisaWaktuTokenFormatted(): string
    {
        $detik = $this->sisaDetikToken();
        if ($detik <= 0) {
            return 'Kedaluwarsa';
        }

        $m = floor($detik / 60);
        $s = $detik % 60;

        return sprintf('%02d:%02d', $m, $s);
    }

    /**
     * Menutup sesi ujian (Status -> COMPLETED)
     * Otomatis mengumpulkan & mengoreksi seluruh siswa yang masih mengerjakan
     */
    public function finishExam(): void
    {
        $this->update([
            'status' => 'COMPLETED',
        ]);

        // Otomatis paksa selesai & koreksi seluruh peserta yang masih mengerjakan
        $activePesertas = $this->pesertas()
            ->where('status', 'SEDANG_MENGERJAKAN')
            ->with('ujianSesi')
            ->get();

        foreach ($activePesertas as $peserta) {
            if ($peserta->ujianSesi && !$peserta->ujianSesi->isSelesai()) {
                $peserta->ujianSesi->koreksiDanSelesaikan();
            } else {
                $peserta->update([
                    'status' => 'SELESAI',
                    'waktu_selesai' => now(),
                    'nilai' => 0,
                ]);
            }
        }
    }

    /**
     * Otomatis mengoreksi & menyelesaikan sesi siswa yang waktu pengerjaannya sudah habis,
     * serta menutup status ujian jika batas jadwal pelaksanaan sudah berakhir.
     */
    public function autoCloseExpiredSessions(): int
    {
        $closedCount = 0;
        $jadwalHabis = $this->isJadwalBerakhir();

        $activeSesis = $this->ujianSesis()
            ->where('status', 'mengerjakan')
            ->get();

        foreach ($activeSesis as $sesi) {
            $batasDurasi = $sesi->waktu_mulai
                ? $sesi->waktu_mulai->copy()->addMinutes($sesi->durasi_menit ?: ($this->durasi_menit ?: 60))
                : null;

            $sesiHabis = ($sesi->waktu_selesai && now()->greaterThan($sesi->waktu_selesai))
                || ($batasDurasi && now()->greaterThan($batasDurasi))
                || (!$sesi->waktu_selesai && !$batasDurasi && $jadwalHabis);

            if ($sesiHabis) {
                $sesi->koreksiDanSelesaikan();
                $closedCount++;
            }
        }

        // Jika masih ada peserta berstatus SEDANG_MENGERJAKAN tapi sesinya sudah selesai
        $stalePesertas = $this->pesertas()
            ->where('status', 'SEDANG_MENGERJAKAN')
            ->with('ujianSesi')
            ->get();

        foreach ($stalePesertas as $peserta) {
            $sesi = $peserta->ujianSesi;
            if ($sesi && $sesi->status === 'selesai') {
                $peserta->update([
                    'status' => 'SELESAI',
                    'waktu_selesai' => $sesi->submitted_at ?? now(),
                    'nilai' => $sesi->skor_total ?? 0,
                ]);
                $closedCount++;
            } elseif (!$sesi && $jadwalHabis) {
                $peserta->update([
                    'status' => 'SELESAI',
                    'waktu_selesai' => now(),
                    'nilai' => 0,
                ]);
                $closedCount++;
            }
        }

        // Jika jadwal ujian sudah lewat dan tidak ada lagi sesi aktif yang sedang dikerjakan (misal tambahan waktu), tutup status ujian
        if ($this->status === 'ONGOING' && $jadwalHabis) {
            $masihAdaSesiAktif = $this->ujianSesis()->where('status', 'mengerjakan')->exists();
            if (! $masihAdaSesiAktif) {
                $this->update(['status' => 'COMPLETED']);
            }
        }

        return $closedCount;
    }

    /**
     * Jalankan auto-close untuk seluruh ujian yang sedang berlangsung atau memiliki sesi kedaluwarsa.
     */
    public static function runGlobalAutoClose(): int
    {
        $totalClosed = 0;

        $ujians = static::query()
            ->where('status', 'ONGOING')
            ->orWhereHas('ujianSesis', function ($q) {
                $q->where('status', 'mengerjakan')
                  ->whereNotNull('waktu_selesai')
                  ->where('waktu_selesai', '<', now());
            })
            ->get();

        foreach ($ujians as $ujian) {
            $totalClosed += $ujian->autoCloseExpiredSessions();
        }

        return $totalClosed;
    }

    /**
     * Sinkronkan peserta ujian otomatis dari semua kelas yang dipilih
     */
    public function syncPesertaFromKelas(): int
    {
        $kelasIds = $this->kelas()->pluck('kelas.id')->toArray();
        if (empty($kelasIds)) {
            return 0;
        }

        $siswas = Siswa::whereIn('kelas_id', $kelasIds)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->get();

        $count = 0;
        foreach ($siswas as $siswa) {
            $peserta = UjianPeserta::firstOrCreate(
                [
                    'ujian_id' => $this->id,
                    'siswa_id' => $siswa->id,
                ],
                [
                    'user_id' => $siswa->user_id,
                    'status' => 'BELUM_MULAI',
                ]
            );

            if ($peserta->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    // ==========================================
    // RELASI ELOQUENT
    // ==========================================

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'ujian_kelas', 'ujian_id', 'kelas_id')->withTimestamps();
    }

    public function pesertas(): HasMany
    {
        return $this->hasMany(UjianPeserta::class, 'ujian_id');
    }

    public function ujianSesis(): HasMany
    {
        return $this->hasMany(UjianSesi::class, 'ujian_id');
    }

    public function jadwalRuangs(): HasMany
    {
        return $this->hasMany(JadwalRuang::class, 'ujian_id');
    }

    public function ruangUjians(): BelongsToMany
    {
        return $this->belongsToMany(RuangUjian::class, 'jadwal_ruangs', 'ujian_id', 'ruang_ujian_id')
            ->withPivot(['pengawas_id', 'tanggal', 'sesi_ke', 'waktu_mulai', 'waktu_selesai', 'token_ruang', 'status'])
            ->withTimestamps();
    }

    /**
     * Scope untuk menyaring daftar ujian:
     * - Admin: semua ujian
     * - Guru: ujian di mana dia ditugaskan sebagai pengawas/pengampu (guru_id)
     */
    public function scopeForUser($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        return $query->where('guru_id', $user->id);
    }
}


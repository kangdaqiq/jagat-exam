<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalRuang extends Model
{
    use HasFactory;

    protected $table = 'jadwal_ruangs';

    public const TOKEN_EXPIRY_MINUTES = 10;

    protected $fillable = [
        'ruang_ujian_id',
        'ujian_id',
        'pengawas_id',
        'pengawas_pendamping_id',
        'tanggal',
        'sesi_ke',
        'waktu_mulai',
        'waktu_selesai',
        'token_ruang',
        'token_generated_at',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'token_generated_at' => 'datetime',
        ];
    }

    // ==========================================
    // RELASI ELOQUENT
    // ==========================================

    public function ruangUjian(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function pengawas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function pengawasPendamping(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_pendamping_id');
    }

    // ==========================================
    // METODE TOKEN & STATUS SESI
    // ==========================================

    /**
     * Rilis / perbarui token masuk untuk jadwal sesi ruang ini (berlaku 10 menit).
     * Otomatis mengaktifkan status jadwal menjadi ONGOING, menyinkronkan token ke RuangUjian,
     * serta memastikan Ujian dan peserta tersinkronkan.
     */
    public function rilisToken(): string
    {
        $token = RuangUjian::generateUniqueToken();
        $now = now();

        $this->update([
            'token_ruang' => $token,
            'token_generated_at' => $now,
            'status' => 'ONGOING',
        ]);

        // Sinkronkan ke Ruang Ujian induk
        if ($this->ruangUjian) {
            $this->ruangUjian->update([
                'token_ruang' => $token,
                'token_generated_at' => $now,
            ]);
        }

        // Sinkronkan status ujian terkait
        if ($this->ujian) {
            if ($this->ujian->status === 'SCHEDULED') {
                $this->ujian->update([
                    'status' => 'ONGOING',
                    'token_ujian' => $this->ujian->token_ujian ?: $token,
                    'token_generated_at' => $now,
                ]);
            }
            $this->ujian->syncPesertaFromKelas();
        }

        return $token;
    }

    public function isTokenExpired(): bool
    {
        if (empty($this->token_ruang) || ! $this->token_generated_at) {
            return true;
        }

        if ($this->status === 'COMPLETED' || $this->isJadwalBerakhir()) {
            return true;
        }

        return now()->greaterThan($this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES));
    }

    public function isTokenActive(): bool
    {
        return ! empty($this->token_ruang) && ! $this->isTokenExpired();
    }

    public function sisaDetikToken(): int
    {
        if (! $this->isTokenActive() || ! $this->token_generated_at) {
            return 0;
        }

        $expiredAt = $this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES);

        return max(0, (int) now()->diffInSeconds($expiredAt, false));
    }

    public function sisaWaktuTokenFormatted(): string
    {
        if (empty($this->token_ruang)) {
            return 'Belum dirilis';
        }

        if (! $this->isTokenActive()) {
            return 'Kedaluwarsa';
        }

        $s = $this->sisaDetikToken();

        return sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
    }

    /**
     * Memeriksa apakah waktu jadwal telah terlewat.
     */
    public function isJadwalBerakhir(): bool
    {
        if (in_array($this->status, ['COMPLETED', 'CANCELLED'], true)) {
            return true;
        }

        if ($this->tanggal && $this->waktu_selesai) {
            $tglStr = $this->tanggal instanceof Carbon ? $this->tanggal->format('Y-m-d') : substr((string) $this->tanggal, 0, 10);
            $selesai = Carbon::parse("{$tglStr} {$this->waktu_selesai}");

            return now()->greaterThan($selesai);
        }

        return false;
    }

    /**
     * Memeriksa apakah saat ini berada dalam rentang waktu pelaksanaan jadwal ini.
     */
    public function isSedangBerlangsung(): bool
    {
        if ($this->status === 'ONGOING') {
            return true;
        }

        if ($this->status !== 'SCHEDULED' || ! $this->tanggal) {
            return false;
        }

        $tglStr = $this->tanggal instanceof Carbon ? $this->tanggal->format('Y-m-d') : substr((string) $this->tanggal, 0, 10);
        $mulai = $this->waktu_mulai ? Carbon::parse("{$tglStr} {$this->waktu_mulai}") : null;
        $selesai = $this->waktu_selesai ? Carbon::parse("{$tglStr} {$this->waktu_selesai}") : null;

        if ($mulai && $selesai) {
            return now()->between($mulai, $selesai);
        }

        return false;
    }

    // ==========================================
    // ATTRIBUTES & HELPERS
    // ==========================================

    public function getLabelSesiWaktuAttribute(): string
    {
        $sesi = $this->sesi_ke ? "Sesi {$this->sesi_ke}" : 'Sesi 1';
        $waktu = '';
        if ($this->waktu_mulai && $this->waktu_selesai) {
            $waktu = ' (' . substr((string) $this->waktu_mulai, 0, 5) . ' - ' . substr((string) $this->waktu_selesai, 0, 5) . ')';
        }

        return "{$sesi}{$waktu}";
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal', now()->toDateString());
    }

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('pengawas_id', $user->id)
              ->orWhere('pengawas_pendamping_id', $user->id);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['SCHEDULED', 'ONGOING']);
    }
}


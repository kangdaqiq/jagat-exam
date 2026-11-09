<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';

    protected $fillable = [
        'sync_id',
        'user_id',
        'school_id',
        'school_name',
        'kelas_id',
        'ruang_ujian_id',
        'nis',
        'nisn',
        'password_cbt',
        'nama',
        'no_wa',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Siswa $siswa) {
            if (empty($siswa->password_cbt)) {
                $siswa->password_cbt = self::generateSixDigitPin();
            }
        });

        static::created(function (Siswa $siswa) {
            if (empty($siswa->user_id)) {
                $siswa->resetPassword($siswa->password_cbt ?: self::generateSixDigitPin());
            }
        });
    }

    /**
     * Generate 6 karakter angka acak (contoh: 482915) untuk password CBT siswa.
     */
    public static function generateSixDigitPin(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Generate password 6 angka acak baru dan langsung simpan ke akun User & Siswa.
     */
    public function generateRandomPassword(): string
    {
        $pin = self::generateSixDigitPin();
        $this->resetPassword($pin);

        return $pin;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'school_id' => 'integer',
            'sync_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function ruangUjian(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }

    public function ujianPesertas(): HasMany
    {
        return $this->hasMany(UjianPeserta::class, 'siswa_id');
    }

    public function ujianSesis(): HasMany
    {
        return $this->hasMany(UjianSesi::class, 'user_id', 'user_id');
    }

    /**
     * Scope untuk menyaring siswa berdasarkan hak akses:
     * - Admin: semua siswa
     * - Guru: siswa yang berada di kelas yang diajarnya
     */
    public function scopeForUser($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        if ($user->isGuru()) {
            return $query->whereHas('kelas.gurus', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Reset atau buatkan akun login User untuk siswa ini dengan password baru.
     */
    public function resetPassword(string $newPassword): User
    {
        if ($this->password_cbt !== $newPassword) {
            $this->update(['password_cbt' => $newPassword]);
        }

        if ($this->user) {
            $user = $this->user;
            $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
            $user->is_active = true;
            if (empty($user->nip) && !empty($this->nis)) {
                $user->nip = $this->nis;
            }
            $user->save();
            return $user;
        }

        $fallbackEmail = $this->nis ? $this->nis . '@siswa.cbt' : 'siswa_' . $this->id . '@siswa.cbt';
        if (User::where('email', $fallbackEmail)->exists()) {
            $fallbackEmail = 'siswa_' . $this->id . '_' . time() . '@siswa.cbt';
        }

        $user = User::create([
            'name' => $this->nama,
            'email' => $fallbackEmail,
            'password' => \Illuminate\Support\Facades\Hash::make($newPassword),
            'role' => 'siswa',
            'nip' => $this->nis,
            'phone' => $this->no_wa,
            'school_id' => $this->school_id,
            'school_name' => $this->school_name,
            'is_active' => true,
        ]);

        $this->update([
            'user_id' => $user->id,
            'password_cbt' => $newPassword,
        ]);

        return $user;
    }

    public function getNisnOrNisAttribute(): string
    {
        return $this->nisn ?: ($this->nis ?: '-');
    }
}

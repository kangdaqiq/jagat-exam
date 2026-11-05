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
        'nis',
        'nama',
        'no_wa',
        'is_active',
    ];

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
}

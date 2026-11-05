<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'sync_id',
        'nama_kelas',
        'tingkat',
        'jurusan',
        'wali_kelas',
        'jumlah_siswa',
        'school_id',
        'school_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'jumlah_siswa' => 'integer',
        ];
    }

    /**
     * Guru yang mengajar di kelas ini
     */
    public function gurus(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guru_kelas', 'kelas_id', 'user_id');
    }

    /**
     * Siswa di kelas ini
     */
    public function siswas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    /**
     * Ujian yang melibatkan kelas ini
     */
    public function ujians(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Ujian::class, 'ujian_kelas')->withTimestamps();
    }

    /**
     * Scope untuk menyaring kelas berdasarkan user login:
     * - Admin melihat semua kelas
     * - Guru hanya melihat kelas yang diajarnya
     */
    public function scopeForUser($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('gurus', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }
}


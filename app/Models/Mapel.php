<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapels';

    protected $fillable = [
        'sync_id',
        'nama_mapel',
        'kode',
        'school_id',
        'school_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Guru pengampu mata pelajaran ini
     */
    public function gurus(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guru_mapel', 'mapel_id', 'user_id');
    }

    /**
     * Scope untuk menyaring mapel berdasarkan user login:
     * - Admin melihat semua mapel
     * - Guru hanya melihat mapel yang diampunya
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


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BankSoal extends Model
{
    use HasFactory;

    protected $table = 'bank_soal';

    protected $fillable = [
        'user_id',
        'mapel_id',
        'judul',
        'kode_bank',
        'tingkat',
        'jurusan',
        'deskripsi',
        'acak_soal',
        'acak_opsi',
        'total_bobot',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'acak_soal' => 'boolean',
            'acak_opsi' => 'boolean',
            'total_bobot' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::creating(function (BankSoal $bankSoal) {
            if (empty($bankSoal->kode_bank)) {
                $bankSoal->kode_bank = static::generateKodeBank();
            }
        });
    }

    /**
     * Generate kode soal / bank soal otomatis (Contoh: SOAL-202611-X4K9)
     */
    public static function generateKodeBank(): string
    {
        do {
            $code = 'SOAL-' . date('Ym') . '-' . strtoupper(Str::random(5));
        } while (static::where('kode_bank', $code)->exists());

        return $code;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('kode_bank', $value)
            ->first() ?? abort(404, 'Paket Bank Soal tidak ditemukan.');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ujians(): HasMany
    {
        return $this->hasMany(Ujian::class, 'bank_soal_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function kelases(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'bank_soal_kelas', 'bank_soal_id', 'kelas_id')
            ->withTimestamps();
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class, 'bank_soal_id')->orderBy('nomor_urut', 'asc');
    }

    public function sesis(): HasMany
    {
        return $this->hasMany(UjianSesi::class, 'bank_soal_id');
    }


    /**
     * Scope untuk membatasi bank soal berdasarkan pengguna (Guru vs Admin)
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        // Guru hanya melihat bank soal miliknya atau yang terikat pada mapel yang diampunya
        $mapelIds = $user->mapels()->pluck('mapels.id');

        return $query->where(function (Builder $q) use ($user, $mapelIds) {
            $q->where('user_id', $user->id)
              ->orWhereIn('mapel_id', $mapelIds);
        });
    }
}


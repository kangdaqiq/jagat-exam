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
     * Otomatis membagi bobot soal ke total 100 poin:
     * - Jika ada objektif (PG/PGK/BS/Matching) dan subjektif (Isian/Essay): Rasio 75 : 25
     * - Jika hanya objektif: 100%
     * - Jika hanya subjektif: 100%
     */
    public function autoDistributeBobot(): void
    {
        $soals = $this->soals()->get();
        if ($soals->isEmpty()) return;

        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        $objSoals = $soals->filter(fn($s) => in_array($s->tipe_soal, $objTypes));
        $subSoals = $soals->filter(fn($s) => in_array($s->tipe_soal, $subTypes));

        $hasObj = $objSoals->isNotEmpty();
        $hasSub = $subSoals->isNotEmpty();

        $targetObj = ($hasObj && $hasSub) ? 75.0 : ($hasObj ? 100.0 : 0.0);
        $targetSub = ($hasObj && $hasSub) ? 25.0 : ($hasSub ? 100.0 : 0.0);

        if ($hasObj) {
            $countObj = $objSoals->count();
            $bobotPerObj = round($targetObj / $countObj, 2);
            $accumulated = 0.0;
            $i = 0;
            foreach ($objSoals as $s) {
                $i++;
                if ($i === $countObj) {
                    $val = round($targetObj - $accumulated, 2);
                } else {
                    $val = $bobotPerObj;
                    $accumulated += $val;
                }
                $s->update(['bobot_nilai' => $val]);
            }
        }

        if ($hasSub) {
            $countSub = $subSoals->count();
            $bobotPerSub = round($targetSub / $countSub, 2);
            $accumulated = 0.0;
            $j = 0;
            foreach ($subSoals as $s) {
                $j++;
                if ($j === $countSub) {
                    $val = round($targetSub - $accumulated, 2);
                } else {
                    $val = $bobotPerSub;
                    $accumulated += $val;
                }
                $s->update(['bobot_nilai' => $val]);
            }
        }

        $this->update(['total_bobot' => 100]);
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

        // Guru hanya dapat mengakses, melihat, dan mengedit bank soal miliknya sendiri
        return $query->where('user_id', $user->id);
    }
}


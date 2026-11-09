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
     * Otomatis membagi bobot soal ke total 100 poin secara sama rata (flat).
     *
     * Jika bank soal memuat soal campuran objektif (PG/PGK/BS/Menjodohkan) dan subjektif (Isian/Essay),
     * bobot dialokasikan dengan standar komposisi 75% objektif : 25% subjektif, di mana di dalam
     * masing-masing kelompok soal didistribusikan sama rata ke setiap butir soal.
     */
    public function autoDistributeBobot(float $targetTotal = 100.0): void
    {
        $soals = $this->soals()->orderBy('nomor_urut', 'asc')->get();
        if ($soals->isEmpty()) return;

        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        $objSoals = $soals->filter(fn($s) => in_array($s->tipe_soal, $objTypes));
        $subSoals = $soals->filter(fn($s) => in_array($s->tipe_soal, $subTypes));

        $hasObj = $objSoals->isNotEmpty();
        $hasSub = $subSoals->isNotEmpty();

        $distributeGroup = function ($groupSoals, float $target) {
            $count = $groupSoals->count();
            if ($count === 0) return;

            $unitValue = round($target / $count, 2);
            $accumulated = 0.0;
            $lastIdx = $count - 1;

            foreach ($groupSoals->values() as $idx => $s) {
                if ($idx === $lastIdx) {
                    $val = round($target - $accumulated, 2);
                } else {
                    $val = $unitValue;
                    $accumulated += $val;
                }

                $s->update(['bobot_nilai' => max(0.1, $val)]);
            }
        };

        if ($hasObj && $hasSub) {
            $distributeGroup($objSoals, $targetTotal * 0.75);
            $distributeGroup($subSoals, $targetTotal * 0.25);
        } elseif ($hasObj) {
            $distributeGroup($objSoals, $targetTotal);
        } else {
            $distributeGroup($subSoals, $targetTotal);
        }

        $this->update(['total_bobot' => $targetTotal]);
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


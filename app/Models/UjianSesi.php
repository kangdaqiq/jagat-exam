<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UjianSesi extends Model
{
    use HasFactory;

    protected $table = 'ujian_sesis';

    protected $fillable = [
        'ujian_id',
        'bank_soal_id',
        'user_id',
        'token_sesi',
        'nama_peserta',
        'nomor_peserta',
        'waktu_mulai',
        'waktu_selesai',
        'durasi_menit',
        'status',
        'skor_total',
        'submitted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'submitted_at' => 'datetime',
            'skor_total' => 'decimal:2',
            'durasi_menit' => 'integer',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jawabans(): HasMany
    {
        return $this->hasMany(UjianJawaban::class, 'ujian_sesi_id')->orderBy('nomor_urut', 'asc');
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai' || ($this->waktu_selesai && now()->greaterThan($this->waktu_selesai));
    }

    public function sisaDetik(): int
    {
        if ($this->status === 'selesai') return 0;
        if (!$this->waktu_selesai) return 0;
        return max(0, (int) now()->diffInSeconds($this->waktu_selesai, false));
    }

    /**
     * Hitung alokasi target bobot kelompok objektif (75 jika ada essay/isian, 100 jika murni objektif)
     */
    public function getTargetBobotObjektif(): float
    {
        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        $hasObj = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $objTypes))->exists();
        $hasSub = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $subTypes))->exists();

        if ($hasObj && $hasSub) return 75.0;
        if ($hasObj) return 100.0;
        return 0.0;
    }

    /**
     * Hitung alokasi target bobot kelompok subjektif (25 jika ada objektif, 100 jika murni essay/isian)
     */
    public function getTargetBobotSubjektif(): float
    {
        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        $hasObj = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $objTypes))->exists();
        $hasSub = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $subTypes))->exists();

        if ($hasObj && $hasSub) return 25.0;
        if ($hasSub) return 100.0;
        return 0.0;
    }

    /**
     * Menghitung bobot efektif butir soal berdasarkan rasio 75:25 atau 100%
     */
    public function getEffectiveBobotForJawaban(UjianJawaban $jawaban): float
    {
        $soal = $jawaban->soal;
        if (!$soal) return 0.0;

        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        $isObj = in_array($soal->tipe_soal, $objTypes);
        $target = $isObj ? $this->getTargetBobotObjektif() : $this->getTargetBobotSubjektif();

        $groupTypes = $isObj ? $objTypes : $subTypes;
        $groupJawabans = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $groupTypes))->with('soal')->get();

        $totalBobotGroup = (float) $groupJawabans->sum(fn($j) => (float) ($j->soal?->bobot_nilai ?? 1.0));

        if ($totalBobotGroup <= 0) {
            $count = $groupJawabans->count();
            return $count > 0 ? round($target / $count, 2) : 0.0;
        }

        $rawBobot = (float) ($soal->bobot_nilai ?? 1.0);
        return round(($rawBobot / $totalBobotGroup) * $target, 2);
    }
}


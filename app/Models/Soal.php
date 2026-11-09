<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Soal extends Model
{
    use HasFactory;

    protected $table = 'soals';

    protected $fillable = [
        'bank_soal_id',
        'nomor_urut',
        'tipe_soal',
        'pertanyaan',
        'is_rtl',
        'media_type',
        'media_file',
        'audio_max_play',
        'bobot_nilai',
        'tingkat_kesulitan',
        'taksonomi_bloom',
        'kunci_isian',
        'rubrik_essay',
        'pembahasan',
    ];

    protected function casts(): array
    {
        return [
            'is_rtl' => 'boolean',
            'audio_max_play' => 'integer',
            'nomor_urut' => 'integer',
            'bobot_nilai' => 'decimal:2',
        ];
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function opsiJawabans(): HasMany
    {
        return $this->hasMany(OpsiJawaban::class, 'soal_id')->orderBy('nomor_urut', 'asc');
    }

    /**
     * Helper label tipe soal
     */
    public function getTipeSoalLabelAttribute(): string
    {
        return match ($this->tipe_soal) {
            'pilihan_ganda' => 'Pilihan Ganda (Single)',
            'pg_kompleks' => 'Pilihan Ganda Kompleks',
            'benar_salah' => 'Benar / Salah',
            'menjodohkan' => 'Menjodohkan (Matching)',
            'isian' => 'Isian Singkat',
            'essay' => 'Uraian / Essay',
            default => ucfirst(str_replace('_', ' ', $this->tipe_soal)),
        };
    }
}


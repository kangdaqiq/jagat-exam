<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjianJawaban extends Model
{
    use HasFactory;

    protected $table = 'ujian_jawabans';

    protected $fillable = [
        'ujian_sesi_id',
        'soal_id',
        'nomor_urut',
        'urutan_opsi',
        'jawaban_pilihan',
        'jawaban_teks',
        'is_ragu',
        'is_benar',
        'skor_didapat',
    ];

    protected function casts(): array
    {
        return [
            'urutan_opsi' => 'array',
            'jawaban_pilihan' => 'array',
            'is_ragu' => 'boolean',
            'is_benar' => 'boolean',
            'skor_didapat' => 'decimal:2',
            'nomor_urut' => 'integer',
        ];
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(UjianSesi::class, 'ujian_sesi_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }
}


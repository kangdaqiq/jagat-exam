<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsiJawaban extends Model
{
    use HasFactory;

    protected $table = 'opsi_jawabans';

    protected $fillable = [
        'soal_id',
        'label',
        'teks_opsi',
        'gambar_opsi',
        'is_jawaban_benar',
        'pasangan_matching',
        'nomor_urut',
    ];

    protected function casts(): array
    {
        return [
            'is_jawaban_benar' => 'boolean',
            'nomor_urut' => 'integer',
        ];
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjianPeserta extends Model
{
    use HasFactory;

    protected $table = 'ujian_pesertas';

    protected $fillable = [
        'ujian_id',
        'siswa_id',
        'user_id',
        'status',
        'ujian_sesi_id',
        'nilai',
        'waktu_mulai',
        'waktu_selesai',
    ];

    protected function casts(): array
    {
        return [
            'nilai' => 'decimal:2',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ujianSesi(): BelongsTo
    {
        return $this->belongsTo(UjianSesi::class, 'ujian_sesi_id');
    }
}


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
}


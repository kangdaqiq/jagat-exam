<?php

namespace App\Models;

class HasilUjian extends Ujian
{
    protected $table = 'ujians';

    /**
     * Hitung rata-rata nilai siswa yang sudah selesai
     */
    public function getRataRataNilaiAttribute(): ?float
    {
        $avg = $this->pesertas()->where('status', 'SELESAI')->whereNotNull('nilai')->avg('nilai');
        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * Nilai tertinggi
     */
    public function getNilaiTertinggiAttribute(): ?float
    {
        $max = $this->pesertas()->where('status', 'SELESAI')->whereNotNull('nilai')->max('nilai');
        return $max !== null ? (float) $max : null;
    }

    /**
     * Nilai terendah
     */
    public function getNilaiTerendahAttribute(): ?float
    {
        $min = $this->pesertas()->where('status', 'SELESAI')->whereNotNull('nilai')->min('nilai');
        return $min !== null ? (float) $min : null;
    }

    /**
     * Jumlah peserta yang sudah selesai
     */
    public function getJumlahSelesaiAttribute(): int
    {
        return $this->pesertas()->where('status', 'SELESAI')->count();
    }
}


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

    /**
     * Kembalikan daftar opsi sesuai urutan yang tampil di layar siswa beserta status terpilihnya.
     */
    public function getOrderedOpsiWithSelection(): array
    {
        $soal = $this->soal;
        if (!$soal || !$soal->relationLoaded('opsiJawabans') && $soal->opsiJawabans->isEmpty()) {
            return [];
        }

        $opsiCollection = $soal->opsiJawabans;
        if (!empty($this->urutan_opsi) && is_array($this->urutan_opsi)) {
            $urutan = $this->urutan_opsi;
            $opsiCollection = $opsiCollection->sortBy(function ($item) use ($urutan) {
                $pos = array_search($item->id, $urutan);
                return $pos !== false ? $pos : 999;
            })->values();
        } else {
            $opsiCollection = $opsiCollection->values();
        }

        $rawPilihan = $this->jawaban_pilihan;
        $selectedLabels = is_array($rawPilihan)
            ? array_map('strval', $rawPilihan)
            : (filled($rawPilihan) ? [(string) $rawPilihan] : []);

        $result = [];
        foreach ($opsiCollection as $i => $opt) {
            $displayLabel = (!empty($this->urutan_opsi) && is_array($this->urutan_opsi))
                ? chr(65 + $i)
                : ($opt->label ?: chr(65 + $i));

            $result[] = [
                'id' => $opt->id,
                'label' => $displayLabel,
                'teks_opsi' => $opt->teks_opsi,
                'pasangan_matching' => $opt->pasangan_matching,
                'is_selected' => in_array($displayLabel, $selectedLabels, true),
                'is_jawaban_benar' => (bool) $opt->is_jawaban_benar,
            ];
        }

        return $result;
    }
}


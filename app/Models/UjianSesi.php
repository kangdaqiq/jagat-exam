<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UjianSesi extends Model
{
    use HasFactory;

    protected $table = 'ujian_sesis';

    protected $fillable = [
        'ujian_id',
        'bank_soal_id',
        'user_id',
        'token_sesi',
        'device_token',
        'nama_peserta',
        'nomor_peserta',
        'waktu_mulai',
        'waktu_selesai',
        'durasi_menit',
        'status',
        'skor_total',
        'jumlah_pelanggaran',
        'terakhir_pelanggaran_at',
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
            'terakhir_pelanggaran_at' => 'datetime',
            'skor_total' => 'decimal:2',
            'durasi_menit' => 'integer',
            'jumlah_pelanggaran' => 'integer',
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
     * Catat pelanggaran kecurangan siswa (pindah tab / keluar aplikasi) secara persisten ke database
     */
    public function catatPelanggaran(): int
    {
        $newCount = ((int) $this->jumlah_pelanggaran) + 1;
        $this->update([
            'jumlah_pelanggaran' => $newCount,
            'terakhir_pelanggaran_at' => now(),
        ]);

        return $newCount;
    }

    /**
     * Hitung alokasi target bobot kelompok objektif (75 jika ada essay/isian, 100 jika murni objektif)
     */
    public function getTargetBobotObjektif(?Collection $preloadedJawabans = null): float
    {
        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        if ($preloadedJawabans !== null) {
            $hasObj = $preloadedJawabans->contains(fn ($j) => $j->soal && in_array($j->soal->tipe_soal, $objTypes));
            $hasSub = $preloadedJawabans->contains(fn ($j) => $j->soal && in_array($j->soal->tipe_soal, $subTypes));
        } else {
            $hasObj = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $objTypes))->exists();
            $hasSub = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $subTypes))->exists();
        }

        if ($hasObj && $hasSub) return 75.0;
        if ($hasObj) return 100.0;
        return 0.0;
    }

    /**
     * Hitung alokasi target bobot kelompok subjektif (25 jika ada objektif, 100 jika murni essay/isian)
     */
    public function getTargetBobotSubjektif(?Collection $preloadedJawabans = null): float
    {
        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        if ($preloadedJawabans !== null) {
            $hasObj = $preloadedJawabans->contains(fn ($j) => $j->soal && in_array($j->soal->tipe_soal, $objTypes));
            $hasSub = $preloadedJawabans->contains(fn ($j) => $j->soal && in_array($j->soal->tipe_soal, $subTypes));
        } else {
            $hasObj = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $objTypes))->exists();
            $hasSub = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $subTypes))->exists();
        }

        if ($hasObj && $hasSub) return 25.0;
        if ($hasSub) return 100.0;
        return 0.0;
    }

    /**
     * Menghitung bobot efektif butir soal berdasarkan rasio 75:25 atau 100%
     */
    public function getEffectiveBobotForJawaban(UjianJawaban $jawaban, ?Collection $preloadedJawabans = null): float
    {
        $soal = $jawaban->soal;
        if (!$soal) return 0.0;

        $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
        $subTypes = ['isian', 'essay'];

        $isObj = in_array($soal->tipe_soal, $objTypes);
        $target = $isObj
            ? $this->getTargetBobotObjektif($preloadedJawabans)
            : $this->getTargetBobotSubjektif($preloadedJawabans);

        $groupTypes = $isObj ? $objTypes : $subTypes;

        if ($preloadedJawabans !== null) {
            $groupJawabans = $preloadedJawabans->filter(fn ($j) => $j->soal && in_array($j->soal->tipe_soal, $groupTypes));
        } else {
            $groupJawabans = $this->jawabans()->whereHas('soal', fn($q) => $q->whereIn('tipe_soal', $groupTypes))->with('soal')->get();
        }

        $totalBobotGroup = (float) $groupJawabans->sum(fn($j) => (float) ($j->soal?->bobot_nilai ?? 1.0));

        if ($totalBobotGroup <= 0) {
            $count = $groupJawabans->count();
            return $count > 0 ? round($target / $count, 2) : 0.0;
        }

        $rawBobot = (float) ($soal->bobot_nilai ?? 1.0);
        return round(($rawBobot / $totalBobotGroup) * $target, 2);
    }

    /**
     * Otomatis mengoreksi seluruh jawaban siswa dan menandai sesi selesai
     * Dioptimasi tanpa N+1 query dan dibungkus transaksi database.
     */
    public function koreksiDanSelesaikan(): float
    {
        return DB::transaction(function () {
            $totalSkor = 0.0;
            $jawabans = $this->jawabans()->with('soal.opsiJawabans')->get();

            foreach ($jawabans as $jawaban) {
                $soal = $jawaban->soal;
                if (!$soal) continue;

                $bobot = $this->getEffectiveBobotForJawaban($jawaban, $jawabans);
                $isBenar = false;
                $skor = 0.0;

                if ($soal->tipe_soal === 'pilihan_ganda' || $soal->tipe_soal === 'benar_salah') {
                    $selectedLabel = is_array($jawaban->jawaban_pilihan) ? ($jawaban->jawaban_pilihan[0] ?? null) : $jawaban->jawaban_pilihan;

                    if (!empty($jawaban->urutan_opsi) && is_array($jawaban->urutan_opsi) && $selectedLabel) {
                        $labelIndex = ord(strtoupper($selectedLabel)) - 65;
                        $selectedOpsiId = $jawaban->urutan_opsi[$labelIndex] ?? null;
                        if ($selectedOpsiId) {
                            $selectedOpsi = $soal->opsiJawabans->firstWhere('id', $selectedOpsiId);
                            if ($selectedOpsi && $selectedOpsi->is_jawaban_benar) {
                                $isBenar = true;
                                $skor = $bobot;
                            }
                        }
                    } else {
                        $kunci = $soal->opsiJawabans->firstWhere('is_jawaban_benar', true);
                        if ($kunci && $selectedLabel === $kunci->label) {
                            $isBenar = true;
                            $skor = $bobot;
                        }
                    }
                } elseif ($soal->tipe_soal === 'pg_kompleks') {
                    $siswaAns = is_array($jawaban->jawaban_pilihan) ? $jawaban->jawaban_pilihan : [];

                    if (!empty($jawaban->urutan_opsi) && is_array($jawaban->urutan_opsi)) {
                        $selectedOpsiIds = [];
                        foreach ($siswaAns as $lbl) {
                            $idx = ord(strtoupper($lbl)) - 65;
                            if (isset($jawaban->urutan_opsi[$idx])) {
                                $selectedOpsiIds[] = (int) $jawaban->urutan_opsi[$idx];
                            }
                        }
                        sort($selectedOpsiIds);
                        $correctOpsiIds = $soal->opsiJawabans->where('is_jawaban_benar', true)->pluck('id')->map(fn($id) => (int)$id)->sort()->values()->toArray();
                        if (!empty($correctOpsiIds) && $selectedOpsiIds === $correctOpsiIds) {
                            $isBenar = true;
                            $skor = $bobot;
                        }
                    } else {
                        $kuncis = $soal->opsiJawabans->where('is_jawaban_benar', true)->pluck('label')->sort()->values()->toArray();
                        $sortedSiswaAns = collect($siswaAns)->sort()->values()->toArray();
                        if ($kuncis === $sortedSiswaAns && !empty($kuncis)) {
                            $isBenar = true;
                            $skor = $bobot;
                        }
                    }
                } elseif ($soal->tipe_soal === 'menjodohkan') {
                    $opsiList = $soal->opsiJawabans->values();
                    $siswaMatching = is_array($jawaban->jawaban_pilihan) ? $jawaban->jawaban_pilihan : [];
                    $totalPasangan = $opsiList->count();
                    $pasanganBenar = 0;

                    foreach ($opsiList as $idx => $opt) {
                        $kunciKanan = trim($opt->pasangan_matching ?? '');
                        $jawabanKanan = trim($siswaMatching[$idx] ?? '');
                        if (filled($kunciKanan) && $kunciKanan === $jawabanKanan) {
                            $pasanganBenar++;
                        }
                    }

                    if ($totalPasangan > 0 && $pasanganBenar === $totalPasangan) {
                        $isBenar = true;
                        $skor = $bobot;
                    }
                } elseif ($soal->tipe_soal === 'isian') {
                    $kunciIsian = strtolower(trim($soal->kunci_isian ?? ''));
                    $siswaIsian = strtolower(trim($jawaban->jawaban_teks ?? ''));
                    if (filled($kunciIsian) && $kunciIsian === $siswaIsian) {
                        $isBenar = true;
                        $skor = $bobot;
                    }
                }

                $jawaban->update([
                    'is_benar' => $isBenar,
                    'skor_didapat' => $skor,
                ]);

                $totalSkor += $skor;
            }

            $this->update([
                'status' => 'selesai',
                'submitted_at' => now(),
                'skor_total' => $totalSkor,
                'device_token' => null,
            ]);

            if ($this->ujian_id && $this->user_id) {
                UjianPeserta::where('ujian_id', $this->ujian_id)
                    ->where('user_id', $this->user_id)
                    ->update([
                        'status' => 'SELESAI',
                        'nilai' => $totalSkor,
                        'waktu_selesai' => now(),
                    ]);
            }

            if ($this->user_id) {
                $stillActive = static::where('user_id', $this->user_id)
                    ->where('status', 'mengerjakan')
                    ->where('id', '!=', $this->id)
                    ->exists();
                if (! $stillActive) {
                    User::where('id', $this->user_id)->update(['active_session_token' => null]);
                }
            }

            return $totalSkor;
        });
    }
}


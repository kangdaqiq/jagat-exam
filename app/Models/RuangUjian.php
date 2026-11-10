<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class RuangUjian extends Model
{
    use HasFactory;

    protected $table = 'ruang_ujians';

    public const TOKEN_EXPIRY_MINUTES = 10;

    protected $fillable = [
        'kode_ruang',
        'nama_ruang',
        'kapasitas',
        'pengawas_id',
        'token_ruang',
        'token_generated_at',
        'is_active',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
            'is_active' => 'boolean',
            'token_generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RuangUjian $ruang) {
            if (empty($ruang->kode_ruang)) {
                $count = self::count() + 1;
                $ruang->kode_ruang = 'RUANG-' . str_pad((string) $count, 2, '0', STR_PAD_LEFT);
            }
        });
    }

    public function pengawas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'ruang_ujian_id');
    }

    public function ujianSesis(): HasMany
    {
        return $this->hasMany(UjianSesi::class, 'ruang_ujian_id');
    }

    public function jadwalRuangs(): HasMany
    {
        return $this->hasMany(JadwalRuang::class, 'ruang_ujian_id');
    }

    public function beritaAcaras(): HasMany
    {
        return $this->hasMany(BeritaAcara::class, 'ruang_ujian_id');
    }

    /**
     * Ambil daftar nama kelas unik dari siswa yang berada di ruang ujian ini.
     */
    public function getDaftarNamaKelasAttribute(): string
    {
        $names = $this->siswas()
            ->with('kelas')
            ->get()
            ->pluck('kelas.nama_kelas')
            ->filter()
            ->unique()
            ->values();

        return $names->isNotEmpty() ? $names->join(', ') : '-';
    }

    /**
     * Ambil guru pengawas yang bertugas untuk ruangan ini.
     * Prioritas:
     * 1. Jadwal Ruang hari ini
     * 2. Jadwal Ruang terdekat di masa depan
     * 3. Pengawas default pada ruang_ujians
     */
    public function getNamaPengawasTugasAttribute(): string
    {
        // 1. Prioritas Jadwal Ruang hari ini
        $jadwalHariIni = $this->jadwalRuangs()
            ->whereDate('tanggal', now()->toDateString())
            ->whereNotNull('pengawas_id')
            ->with(['pengawas', 'pengawasPendamping'])
            ->orderBy('sesi_ke')
            ->orderBy('waktu_mulai')
            ->first();

        if ($jadwalHariIni && $jadwalHariIni->pengawas) {
            $nama = $jadwalHariIni->pengawas->name;
            if ($jadwalHariIni->pengawasPendamping) {
                $nama .= ' & ' . $jadwalHariIni->pengawasPendamping->name;
            }
            return $nama;
        }

        // 2. Prioritas Jadwal Ruang terdekat mendatang
        $jadwalTerdekat = $this->jadwalRuangs()
            ->whereDate('tanggal', '>=', now()->toDateString())
            ->whereNotNull('pengawas_id')
            ->with(['pengawas', 'pengawasPendamping'])
            ->orderBy('tanggal')
            ->orderBy('sesi_ke')
            ->orderBy('waktu_mulai')
            ->first();

        if ($jadwalTerdekat && $jadwalTerdekat->pengawas) {
            $nama = $jadwalTerdekat->pengawas->name;
            if ($jadwalTerdekat->pengawasPendamping) {
                $nama .= ' & ' . $jadwalTerdekat->pengawasPendamping->name;
            }
            return $nama;
        }

        // 3. Fallback pengawas bawaan ruangan
        if ($this->pengawas) {
            return $this->pengawas->name;
        }

        return 'Belum Ditugaskan';
    }

    /**
     * Ambil informasi rentang jam jadwal pelaksanaan sesi hari ini / terdekat untuk ruangan ini.
     */
    public function getInfoJadwalWaktuAttribute(): string
    {
        // 1. Prioritas Jadwal Ruang hari ini
        $jadwalHariIni = $this->jadwalRuangs()
            ->whereDate('tanggal', now()->toDateString())
            ->orderBy('sesi_ke')
            ->orderBy('waktu_mulai')
            ->get();

        if ($jadwalHariIni->isNotEmpty()) {
            return $jadwalHariIni->map(fn (JadwalRuang $j) => $j->label_sesi_waktu)->join(', ');
        }

        // 2. Prioritas Jadwal Ruang terdekat mendatang
        $jadwalTerdekat = $this->jadwalRuangs()
            ->whereDate('tanggal', '>', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('sesi_ke')
            ->orderBy('waktu_mulai')
            ->first();

        if ($jadwalTerdekat) {
            $tgl = $jadwalTerdekat->tanggal ? $jadwalTerdekat->tanggal->format('d M') : '';
            return "{$tgl} - {$jadwalTerdekat->label_sesi_waktu}";
        }

        // 3. Fallback Jadwal master ujian kelas
        $ujian = $this->getActiveUjiansForRoom()->first();
        if ($ujian && ($ujian->waktu_mulai || $ujian->waktu_selesai)) {
            $mulai = $ujian->waktu_mulai ? substr((string) $ujian->waktu_mulai, 0, 5) : '00:00';
            $selesai = $ujian->waktu_selesai ? substr((string) $ujian->waktu_selesai, 0, 5) : 'Selesai';
            return "{$mulai} - {$selesai} WIB";
        }

        return 'Sesuai Jam Pelaksanaan';
    }

    /**
     * Ambil koleksi ujian yang sedang aktif/terjadwal untuk rombel kelas siswa yang ada di ruangan ini.
     */
    public function getActiveUjiansForRoom(): Collection
    {
        $kelasIds = $this->siswas()
            ->whereNotNull('kelas_id')
            ->pluck('kelas_id')
            ->unique()
            ->values()
            ->all();

        if (empty($kelasIds)) {
            return collect();
        }

        return Ujian::query()
            ->with(['mapel', 'bankSoal', 'kelas'])
            ->whereIn('status', ['ONGOING', 'SCHEDULED'])
            ->whereHas('kelas', fn ($q) => $q->whereIn('kelas.id', $kelasIds))
            ->orderByRaw("CASE WHEN status = 'ONGOING' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Memeriksa apakah saat ini belum memasuki waktu jadwal ujian untuk ruangan ini.
     */
    public function isBelumMasukJadwal(): bool
    {
        // Prioritas 1: Jika ada Jadwal Ruang khusus yang dijadwalkan hari ini
        $todayJadwals = $this->jadwalRuangs()
            ->whereDate('tanggal', now()->toDateString())
            ->get();

        if ($todayJadwals->isNotEmpty()) {
            return $todayJadwals->every(fn (JadwalRuang $j) => $j->isBelumMasukJadwal());
        }

        // Jika ada jadwal ruang di masa depan (belum hari H)
        $hasFutureJadwals = $this->jadwalRuangs()
            ->whereDate('tanggal', '>', now()->toDateString())
            ->exists();

        if ($hasFutureJadwals) {
            return true;
        }

        // Prioritas 2: Jadwal master ujian kelas
        $ujians = $this->getActiveUjiansForRoom();
        if ($ujians->isNotEmpty()) {
            return $ujians->every(fn (Ujian $u) => $u->isBelumMasukJadwal());
        }

        return false;
    }

    /**
     * Memeriksa info jam/tanggal jadwal ujian terdekat yang akan berlangsung di ruangan ini.
     */
    public function getJadwalBerikutnyaAttribute(): ?string
    {
        $todayJadwal = $this->jadwalRuangs()
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', '!=', 'COMPLETED')
            ->orderBy('waktu_mulai')
            ->first();

        if ($todayJadwal && $todayJadwal->waktu_mulai) {
            return substr((string) $todayJadwal->waktu_mulai, 0, 5) . ' WIB';
        }

        $futureJadwal = $this->jadwalRuangs()
            ->whereDate('tanggal', '>', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->first();

        if ($futureJadwal) {
            $tgl = $futureJadwal->tanggal ? $futureJadwal->tanggal->format('d/m/Y') : '';
            $jam = $futureJadwal->waktu_mulai ? ' ' . substr((string) $futureJadwal->waktu_mulai, 0, 5) . ' WIB' : '';
            return "{$tgl}{$jam}";
        }

        $ujian = $this->getActiveUjiansForRoom()->first();
        if ($ujian && $ujian->waktu_mulai) {
            return substr((string) $ujian->waktu_mulai, 0, 5) . ' WIB';
        }

        return null;
    }

    /**
     * Memeriksa apakah masih ada jadwal ujian aktif/belum habis untuk siswa di ruangan ini,
     * dan sudah memasuki waktu jadwal pelaksanaan.
     */
    public function canReleaseToken(): bool
    {
        if ($this->isBelumMasukJadwal()) {
            return false;
        }

        return ! $this->isSemuaJadwalBerakhir();
    }

    /**
     * Memeriksa apakah semua ujian untuk siswa di ruangan ini sudah melewati batas jadwal.
     */
    public function isSemuaJadwalBerakhir(): bool
    {
        // Prioritas 1: Jika ada Jadwal Ruang khusus hari ini
        $todayJadwals = $this->jadwalRuangs()
            ->whereDate('tanggal', now()->toDateString())
            ->get();

        if ($todayJadwals->isNotEmpty()) {
            return $todayJadwals->every(fn (JadwalRuang $j) => $j->isJadwalBerakhir());
        }

        // Prioritas 2: Jadwal master ujian kelas
        $ujians = $this->getActiveUjiansForRoom();
        if ($ujians->isEmpty()) {
            return false;
        }

        return $ujians->every(fn (Ujian $u) => $u->isJadwalBerakhir());
    }

    /**
     * Generate 6 karakter token unik untuk ruangan ini.
     */
    public static function generateUniqueToken(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $token = '';
            for ($i = 0; $i < 6; $i++) {
                $token .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (
            self::where('token_ruang', $token)->exists()
            || Ujian::where('token_ujian', $token)->where('status', 'ONGOING')->exists()
        );

        return $token;
    }

    /**
     * Rilis / perbarui token masuk untuk ruangan ujian ini (berlaku 10 menit),
     * sekaligus otomatis mengaktifkan (ONGOING) ujian yang dijadwalkan untuk kelas-kelas di ruangan ini.
     */
    public function rilisToken(): string
    {
        $token = self::generateUniqueToken();

        $this->update([
            'token_ruang' => $token,
            'token_generated_at' => now(),
        ]);

        // Otomatis buka status ONGOING & sync peserta untuk ujian kelas-kelas di ruangan ini yang belum berakhir jadwalnya
        $ujians = $this->getActiveUjiansForRoom();
        foreach ($ujians as $ujian) {
            if (!$ujian->isJadwalBerakhir()) {
                if ($ujian->status === 'SCHEDULED') {
                    $ujian->update([
                        'status' => 'ONGOING',
                        'token_ujian' => $ujian->token_ujian ?: $token,
                        'token_generated_at' => now(),
                    ]);
                }
                $ujian->syncPesertaFromKelas();
            }
        }

        return $token;
    }

    public function isTokenExpired(): bool
    {
        if (empty($this->token_ruang) || !$this->token_generated_at) {
            return true;
        }

        if ($this->isSemuaJadwalBerakhir()) {
            return true;
        }

        return now()->greaterThan($this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES));
    }

    public function isTokenActive(): bool
    {
        return !empty($this->token_ruang) && !$this->isTokenExpired();
    }

    public function sisaDetikToken(): int
    {
        if (!$this->isTokenActive() || !$this->token_generated_at) {
            return 0;
        }

        $expiredAt = $this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES);

        return max(0, (int) now()->diffInSeconds($expiredAt, false));
    }

    public function sisaWaktuTokenFormatted(): string
    {
        if (empty($this->token_ruang)) {
            return 'Belum dirilis';
        }

        if (!$this->isTokenActive()) {
            return 'Kedaluwarsa';
        }

        $s = $this->sisaDetikToken();

        return sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
    }
}


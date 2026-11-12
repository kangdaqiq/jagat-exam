<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class JadwalRuang extends Model
{
    use HasFactory;

    protected $table = 'jadwal_ruangs';

    public const TOKEN_EXPIRY_MINUTES = 10;

    protected static function booted(): void
    {
        static::saved(function (JadwalRuang $jadwal) {
            if ($jadwal->ujian_id) {
                $jadwal->ujians()->syncWithoutDetaching([$jadwal->ujian_id]);
            }
        });
    }

    protected $fillable = [
        'ruang_ujian_id',
        'ujian_id',
        'pengawas_id',
        'pengawas_pendamping_id',
        'tanggal',
        'sesi_ke',
        'waktu_mulai',
        'waktu_selesai',
        'token_ruang',
        'token_generated_at',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'token_generated_at' => 'datetime',
        ];
    }

    // ==========================================
    // RELASI ELOQUENT
    // ==========================================

    public function ruangUjian(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function ujians(): BelongsToMany
    {
        return $this->belongsToMany(Ujian::class, 'jadwal_ruang_ujian', 'jadwal_ruang_id', 'ujian_id')
            ->withPivot('urutan')
            ->withTimestamps()
            ->orderBy('jadwal_ruang_ujian.urutan')
            ->orderBy('ujians.id');
    }

    public function ujianSesis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UjianSesi::class, 'jadwal_ruang_id');
    }

    /**
     * Ambil seluruh agenda ujian yang terkait dengan jadwal ruangan ini (terurut sesuai urutan subtest).
     */
    public function getAllUjians(): Collection
    {
        $collection = $this->ujians()
            ->with(['mapel', 'kelas', 'bankSoal'])
            ->get();

        if ($this->ujian_id && ! $collection->contains('id', $this->ujian_id) && $this->ujian) {
            $collection->prepend($this->ujian->loadMissing(['mapel', 'kelas', 'bankSoal']));
        }

        return $collection;
    }

    /**
     * Ambil daftar subtest (agenda soal) di sesi ruangan ini yang diperuntukkan bagi kelas siswa.
     */
    public function getSubtestsForSiswa(?Siswa $siswa): Collection
    {
        $all = $this->getAllUjians();
        if (! $siswa) {
            return collect();
        }

        return $all->filter(function (Ujian $u) use ($siswa) {
            if ($siswa->kelas_id && $u->kelas->contains('id', $siswa->kelas_id)) {
                return true;
            }

            return UjianPeserta::where('ujian_id', $u->id)
                ->where(function ($q) use ($siswa) {
                    $q->where('siswa_id', $siswa->id);
                    if ($siswa->user_id) {
                        $q->orWhere('user_id', $siswa->user_id);
                    }
                })
                ->exists();
        })->values();
    }

    /**
     * Cari subtest berikutnya setelah ujian saat ini pada sesi jadwal ruang yang sama,
     * tanpa siswa perlu memasukkan token ulang.
     */
    public function getNextSubtestForSiswa(int $currentUjianId, ?Siswa $siswa): ?Ujian
    {
        if (! $siswa || $this->isJadwalBerakhir()) {
            return null;
        }

        $subtests = $this->getSubtestsForSiswa($siswa);
        $currentIndex = $subtests->search(fn (Ujian $u) => (int) $u->id === $currentUjianId);
        if ($currentIndex === false) {
            return null;
        }

        $selesaiIds = UjianPeserta::where(function ($q) use ($siswa) {
            $q->where('siswa_id', $siswa->id);
            if ($siswa->user_id) {
                $q->orWhere('user_id', $siswa->user_id);
            }
        })->where('status', 'SELESAI')->pluck('ujian_id')->all();

        foreach ($subtests->slice($currentIndex + 1) as $candidate) {
            if (in_array($candidate->status, ['COMPLETED', 'CANCELLED'], true) || $candidate->isJadwalBerakhir()) {
                continue;
            }
            if (in_array($candidate->id, $selesaiIds, true)) {
                continue;
            }
            if (! $candidate->bankSoal || $candidate->bankSoal->soals()->count() === 0) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * Ambil daftar siswa di ruang ini yang menjadi peserta untuk sesi jadwal ini.
     */
    public function getSiswasForSession(): Collection
    {
        $ruang = $this->ruangUjian;
        if (! $ruang) {
            return collect();
        }

        $siswas = Siswa::with(['kelas', 'user'])
            ->where('ruang_ujian_id', $ruang->id)
            ->where('is_active', true)
            ->orderBy('kelas_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $ujians = $this->getAllUjians();
        $allowedKelasIds = $ujians->flatMap(fn (Ujian $u) => $u->kelas->pluck('id'))->filter()->unique()->values()->all();

        if (! empty($allowedKelasIds)) {
            $filtered = $siswas->whereIn('kelas_id', $allowedKelasIds)->values();
            if ($filtered->isNotEmpty()) {
                return $filtered;
            }
        }

        return $siswas;
    }

    /**
     * Hitung statistik kehadiran peserta riil pada sesi jadwal ruang ini (unik per siswa, mendukung multi-subtest).
     */
    public function computeAttendanceStats(): array
    {
        $siswas = $this->getSiswasForSession();
        $seharusnya = $siswas->count();
        $ujianIds = $this->getAllUjians()->pluck('id')->all();

        if ($seharusnya === 0 || empty($ujianIds)) {
            return [
                'seharusnya' => $seharusnya,
                'hadir' => $seharusnya,
                'tidak_hadir' => 0,
                'usernames_tidak_hadir' => [],
            ];
        }

        $hadirSiswaIds = UjianPeserta::whereIn('ujian_id', $ujianIds)
            ->whereIn('siswa_id', $siswas->pluck('id')->all())
            ->whereIn('status', ['SEDANG_MENGERJAKAN', 'SELESAI'])
            ->distinct()
            ->pluck('siswa_id')
            ->all();

        if (empty($hadirSiswaIds) && $this->status === 'SCHEDULED') {
            return [
                'seharusnya' => $seharusnya,
                'hadir' => $seharusnya,
                'tidak_hadir' => 0,
                'usernames_tidak_hadir' => [],
            ];
        }

        $absentSiswas = $siswas->reject(fn (Siswa $s) => in_array((int) $s->id, array_map('intval', $hadirSiswaIds), true));
        $hadir = count(array_unique(array_map('intval', $hadirSiswaIds)));
        $tidakHadir = $absentSiswas->count();

        return [
            'seharusnya' => $seharusnya,
            'hadir' => $hadir,
            'tidak_hadir' => $tidakHadir,
            'usernames_tidak_hadir' => $absentSiswas
                ->map(fn (Siswa $s) => $s->nis ?: ($s->user?->nip ?: "SISWA-{$s->id}"))
                ->values()
                ->all(),
        ];
    }

    /**
     * Tutup sesi ruangan secara manual & tandai COMPLETED tanpa menghapus data/riwayat/BAP.
     */
    public function selesaikanSesi(): void
    {
        $this->update(['status' => 'COMPLETED']);
    }

    public function syncOrderedUjians(array $ujianIds, bool $detaching = true): void
    {
        $syncData = [];
        $pos = 1;
        foreach ($ujianIds as $id) {
            $intId = (int) $id;
            if ($intId > 0 && ! isset($syncData[$intId])) {
                $syncData[$intId] = ['urutan' => $pos++];
            }
        }

        if ($detaching) {
            $this->ujians()->sync($syncData);
        } else {
            $this->ujians()->syncWithoutDetaching($syncData);
        }

        $firstId = array_key_first($syncData);
        if ($firstId !== null && (int) $this->ujian_id !== $firstId) {
            $this->updateQuietly(['ujian_id' => $firstId]);
        }
    }

    public function getDaftarNamaUjianAttribute(): string
    {
        $ujians = $this->getAllUjians();
        if ($ujians->isEmpty()) {
            return '-';
        }

        return $ujians->map(fn (Ujian $u) => $u->nama_ujian)->join(', ');
    }

    public function pengawas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function pengawasPendamping(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_pendamping_id');
    }

    public function beritaAcaras(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BeritaAcara::class, 'jadwal_ruang_id');
    }

    // ==========================================
    // METODE TOKEN & STATUS SESI
    // ==========================================

    /**
     * Rilis / perbarui token masuk untuk jadwal sesi ruang ini (berlaku 10 menit).
     * Otomatis mengaktifkan status jadwal menjadi ONGOING, menyinkronkan token ke RuangUjian,
     * serta memastikan semua Ujian terkait dan peserta tersinkronkan.
     */
    public function rilisToken(): string
    {
        $token = RuangUjian::generateUniqueToken();
        $now = now();

        $this->update([
            'token_ruang' => $token,
            'token_generated_at' => $now,
            'status' => 'ONGOING',
        ]);

        // Sinkronkan ke Ruang Ujian induk
        if ($this->ruangUjian) {
            $this->ruangUjian->update([
                'token_ruang' => $token,
                'token_generated_at' => $now,
            ]);
        }

        // Sinkronkan status semua ujian terkait pada sesi ruangan ini
        foreach ($this->getAllUjians() as $ujianItem) {
            if ($ujianItem->status === 'SCHEDULED') {
                $ujianItem->update([
                    'status' => 'ONGOING',
                    'token_generated_at' => $now,
                ]);
            }
            $ujianItem->syncPesertaFromKelas();
        }

        return $token;
    }

    public function isTokenExpired(): bool
    {
        if (empty($this->token_ruang) || ! $this->token_generated_at) {
            return true;
        }

        if ($this->status === 'COMPLETED' || $this->isJadwalBerakhir()) {
            return true;
        }

        return now()->greaterThan($this->token_generated_at->copy()->addMinutes(self::TOKEN_EXPIRY_MINUTES));
    }

    public function isTokenActive(): bool
    {
        return ! empty($this->token_ruang) && ! $this->isTokenExpired();
    }

    public function sisaDetikToken(): int
    {
        if (! $this->isTokenActive() || ! $this->token_generated_at) {
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

        if (! $this->isTokenActive()) {
            return 'Kedaluwarsa';
        }

        $s = $this->sisaDetikToken();

        return sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
    }

    /**
     * Memeriksa apakah waktu jadwal telah terlewat.
     */
    public function isJadwalBerakhir(): bool
    {
        if (in_array($this->status, ['COMPLETED', 'CANCELLED'], true)) {
            return true;
        }

        if ($this->tanggal && $this->waktu_selesai) {
            $tglStr = $this->tanggal instanceof Carbon ? $this->tanggal->format('Y-m-d') : substr((string) $this->tanggal, 0, 10);
            $selesai = Carbon::parse("{$tglStr} {$this->waktu_selesai}");

            return now()->greaterThan($selesai);
        }

        return false;
    }

    /**
     * Memeriksa apakah saat ini belum memasuki waktu jadwal pelaksanaan sesi.
     */
    public function isBelumMasukJadwal(): bool
    {
        if (! $this->tanggal) {
            return false;
        }

        $tglStr = $this->tanggal instanceof Carbon ? $this->tanggal->format('Y-m-d') : substr((string) $this->tanggal, 0, 10);

        if ($this->waktu_mulai) {
            $mulai = Carbon::parse("{$tglStr} {$this->waktu_mulai}");
            return now()->lessThan($mulai);
        }

        $tglMulai = Carbon::parse($tglStr)->startOfDay();
        return now()->lessThan($tglMulai);
    }

    /**
     * Memeriksa apakah saat ini sudah masuk jadwal dan belum berakhir (layak rilis token).
     */
    public function isSudahMasukJadwal(): bool
    {
        return ! $this->isBelumMasukJadwal() && ! $this->isJadwalBerakhir();
    }

    /**
     * Memeriksa apakah saat ini berada dalam rentang waktu pelaksanaan jadwal ini.
     */
    public function isSedangBerlangsung(): bool
    {
        if ($this->status === 'ONGOING') {
            return true;
        }

        if ($this->status !== 'SCHEDULED' || ! $this->tanggal) {
            return false;
        }

        $tglStr = $this->tanggal instanceof Carbon ? $this->tanggal->format('Y-m-d') : substr((string) $this->tanggal, 0, 10);
        $mulai = $this->waktu_mulai ? Carbon::parse("{$tglStr} {$this->waktu_mulai}") : null;
        $selesai = $this->waktu_selesai ? Carbon::parse("{$tglStr} {$this->waktu_selesai}") : null;

        if ($mulai && $selesai) {
            return now()->between($mulai, $selesai);
        }

        return false;
    }

    // ==========================================
    // ATTRIBUTES & HELPERS
    // ==========================================

    public function getLabelSesiWaktuAttribute(): string
    {
        $sesi = $this->sesi_ke ? "Sesi {$this->sesi_ke}" : 'Sesi 1';
        $waktu = '';
        if ($this->waktu_mulai && $this->waktu_selesai) {
            $waktu = ' (' . substr((string) $this->waktu_mulai, 0, 5) . ' - ' . substr((string) $this->waktu_selesai, 0, 5) . ')';
        }

        return "{$sesi}{$waktu}";
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal', now()->toDateString());
    }

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('pengawas_id', $user->id)
              ->orWhere('pengawas_pendamping_id', $user->id);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['SCHEDULED', 'ONGOING']);
    }
}


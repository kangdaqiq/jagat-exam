<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcara extends Model
{
    use HasFactory;

    protected $table = 'berita_acaras';

    protected $fillable = [
        'ruang_ujian_id',
        'ujian_id',
        'jadwal_ruang_id',
        'hari',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'nama_ujian',
        'tahun_pelajaran',
        'username_sekolah',
        'nama_sekolah',
        'id_server',
        'ruang',
        'sesi',
        'jumlah_peserta_seharusnya',
        'jumlah_hadir',
        'jumlah_tidak_hadir',
        'usernames_tidak_hadir',
        'catatan',
        'nama_pengawas',
        'nip_pengawas',
        'nama_ketua_ujian',
        'nip_ketua_ujian',
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah_peserta_seharusnya' => 'integer',
            'jumlah_hadir' => 'integer',
            'jumlah_tidak_hadir' => 'integer',
            'usernames_tidak_hadir' => 'array',
        ];
    }

    public function ruangUjian(): BelongsTo
    {
        return $this->belongsTo(RuangUjian::class, 'ruang_ujian_id');
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function jadwalRuang(): BelongsTo
    {
        return $this->belongsTo(JadwalRuang::class, 'jadwal_ruang_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Format daftar username / NIS yang tidak hadir dipisahkan koma
     */
    public function getFormattedUsernamesTidakHadirAttribute(): string
    {
        if (empty($this->usernames_tidak_hadir)) {
            return '-';
        }

        if (is_array($this->usernames_tidak_hadir)) {
            return implode(', ', $this->usernames_tidak_hadir);
        }

        return (string) $this->usernames_tidak_hadir;
    }
}


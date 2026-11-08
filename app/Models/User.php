<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sync_id',
        'name',
        'email',
        'password',
        'role',
        'school_id',
        'school_name',
        'nip',
        'phone',
        'is_active',
        'active_session_token',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Lepas kunci sesi perangkat aktif untuk siswa (dipanggil saat Pengawas klik Reset Login).
     */
    public function resetDeviceLogin(): void
    {
        $this->update([
            'active_session_token' => null,
        ]);

        UjianSesi::where('user_id', $this->id)
            ->where('status', 'mengerjakan')
            ->update(['device_token' => null]);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $this->id)
                    ->delete();
            }
        } catch (\Throwable $e) {
            // Abaikan jika driver session bukan database
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        if ($panel->getId() === 'guru') {
            return $this->isGuru() || $this->isAdmin();
        }

        return false;
    }

    /**
     * Profil siswa (jika role = siswa)
     */
    public function siswa(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    /**
     * Mata Pelajaran yang diampu guru
     */
    public function mapels(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Mapel::class, 'guru_mapel', 'user_id', 'mapel_id');
    }

    /**
     * Rombel Kelas yang diajar guru
     */
    public function kelases(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'guru_kelas', 'user_id', 'kelas_id');
    }

    /**
     * Jadwal pengampu spesifik (Guru - Mapel - Kelas)
     */
    public function guruPengampus(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GuruPengampu::class, 'user_id');
    }

    public function ujiansSebagaiGuru(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ujian::class, 'guru_id');
    }

    public function ujianPesertas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UjianPeserta::class, 'user_id');
    }
}

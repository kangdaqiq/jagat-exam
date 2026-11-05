<?php

namespace App\Filament\Resources\Ujians\Schemas;

use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Ujian;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama & Tipe Ujian')
                    ->description('Tentukan tipe asesmen, nama agenda, dan paket bank soal.')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('tipe_ujian')
                                ->label('Tipe Pelaksanaan Ujian')
                                ->options(fn () => Ujian::getTipeOptions(auth()->user()))
                                ->default(Ujian::TIPE_ASESMEN_HARIAN)
                                ->required()
                                ->live()
                                ->columnSpan(1)
                                ->helperText(function () {
                                    $user = auth()->user();
                                    if ($user && $user->isGuru()) {
                                        return 'Guru berwenang membuat Asesmen Harian. Asesmen Tengah/Akhir Semester dijadwalkan oleh Admin.';
                                    }
                                    return 'Pilih jenis asesmen.';
                                }),

                            TextInput::make('nama_ujian')
                                ->label('Nama Agenda Ujian')
                                ->placeholder('Contoh: Penilaian Harian Matematika Bab 2')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('mapel_id')
                                ->label('Mata Pelajaran')
                                ->relationship('mapel', 'nama_mapel')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('bank_soal_id', null)),

                            Select::make('bank_soal_id')
                                ->label('Paket Bank Soal')
                                ->options(function ($get) {
                                    $mapelId = $get('mapel_id');
                                    $query = BankSoal::query()->where('is_active', true);
                                    if ($mapelId) {
                                        $query->where('mapel_id', $mapelId);
                                    }
                                    return $query->get()->mapWithKeys(function ($b) {
                                        $count = $b->soals()->count();
                                        return [$b->id => "{$b->judul} ({$count} Soal - Tingkat {$b->tingkat})"];
                                    });
                                })
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->helperText('Pilih paket soal yang sudah disiapkan di menu Bank Soal.')
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $bank = BankSoal::find($state);
                                        if ($bank && !empty($bank->durasi_menit)) {
                                            $set('durasi_menit', $bank->durasi_menit);
                                        }
                                    }
                                }),
                        ]),
                    ]),

                Section::make('Sasaran Peserta & Guru Pengawas')
                    ->description('Tentukan rombel kelas dan guru yang bertanggung jawab.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('guru_id')
                                ->label('Guru Pengawas / Pengampu')
                                ->options(function () {
                                    return User::whereIn('role', ['guru', 'admin'])
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->default(fn () => auth()->id())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Guru yang berhak membuka sesi ujian dan membagikan token ke siswa.'),

                            Select::make('kelas')
                                ->label('Rombel Kelas Peserta')
                                ->relationship('kelas', 'nama_kelas')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Semua siswa aktif di kelas yang dipilih akan otomatis dimasukkan sebagai peserta ujian.'),
                        ]),
                    ]),

                Section::make('Jadwal & Waktu Pelaksanaan')
                    ->description('Waktu dimulainya ujian dan alokasi durasi pengerjaan siswa.')
                    ->schema([
                        // Opsi Instan untuk Asesmen Harian: Guru langsung bisa generate token saat membuat ujian
                        Toggle::make('langsung_generate_token')
                            ->label('Langsung Buka Sesi & Generate Token Sekarang (Asesmen Harian)?')
                            ->helperText('Jika diaktifkan, status ujian langsung ONGOING dan sistem otomatis membuat Kode Token Akses untuk langsung dikerjakan siswa tanpa menunggu jadwal.')
                            ->default(false)
                            ->visible(fn ($get, string $operation) =>
                                $operation === 'create' && ($get('tipe_ujian') === Ujian::TIPE_ASESMEN_HARIAN || empty($get('tipe_ujian')))
                            )
                            ->dehydrated(false),

                        Grid::make(3)->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal Pelaksanaan')
                                ->default(now())
                                ->required(fn ($get) => in_array($get('tipe_ujian'), [Ujian::TIPE_TENGAH_SEMESTER, Ujian::TIPE_AKHIR_SEMESTER]))
                                ->helperText(fn ($get) => $get('tipe_ujian') === Ujian::TIPE_ASESMEN_HARIAN ? 'Opsional untuk asesmen harian' : 'Wajib diisi'),

                            TimePicker::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->default('07:30:00'),

                            TimePicker::make('waktu_selesai')
                                ->label('Waktu Selesai (Opsional)')
                                ->default('09:30:00'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('durasi_menit')
                                ->label('Alokasi Durasi Pengerjaan')
                                ->numeric()
                                ->default(60)
                                ->suffix('Menit')
                                ->required()
                                ->helperText('Waktu hitung mundur per siswa begitu mereka mulai mengerjakan.'),

                            Select::make('status')
                                ->label('Status Ujian')
                                ->options([
                                    'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                                    'ONGOING' => 'ONGOING (Sedang Berlangsung)',
                                    'COMPLETED' => 'COMPLETED (Selesai)',
                                    'CANCELLED' => 'CANCELLED (Dibatalkan)',
                                ])
                                ->default('SCHEDULED')
                                ->required()
                                ->disabled(fn (string $operation) => $operation === 'create')
                                ->helperText('Saat baru dibuat, status otomatis SCHEDULED (kecuali jika memilih langsung generate token).'),
                        ]),

                        Textarea::make('keterangan')
                            ->label('Petunjuk / Catatan Ujian (Opsional)')
                            ->placeholder('Contoh: Dilarang menggunakan kalkulator, tertib saat ujian.')
                            ->rows(3),
                    ]),
            ]);
    }
}

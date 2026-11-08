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
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                // Kolom Kiri / Utama (2 Kolom di Desktop - 66.6% Lebar Halaman)
                Group::make([
                    Section::make('Informasi & Paket Ujian')
                        ->description('Tentukan agenda asesmen, mata pelajaran, serta paket bank soal.')
                        ->icon(Heroicon::OutlinedAcademicCap)
                        ->schema([
                            TextInput::make('nama_ujian')
                                ->label('Nama Agenda Ujian')
                                ->placeholder('Contoh: Penilaian Harian Matematika - Bab 2 Trigonometri')
                                ->required()
                                ->maxLength(255),

                            Grid::make(2)->schema([
                                Select::make('mapel_id')
                                    ->label('Mata Pelajaran')
                                    ->options(function () {
                                        $user = auth()->user();
                                        if ($user && $user->isGuru()) {
                                            $assignedIds = $user->mapels()->pluck('mapels.id')->toArray();
                                            $ownBankMapelIds = BankSoal::where('user_id', $user->id)->pluck('mapel_id')->toArray();
                                            $allowedIds = array_unique(array_merge($assignedIds, $ownBankMapelIds));
                                            if (!empty($allowedIds)) {
                                                return Mapel::whereIn('id', $allowedIds)->pluck('nama_mapel', 'id');
                                            }
                                        }
                                        return Mapel::pluck('nama_mapel', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('bank_soal_id', null)),

                                Select::make('bank_soal_id')
                                    ->label('Paket Bank Soal')
                                    ->options(function ($get) {
                                        $mapelId = $get('mapel_id');
                                        $query = BankSoal::query()
                                            ->forUser(auth()->user())
                                            ->where('is_active', true);

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
                                    ->helperText(fn () => auth()->user()?->isGuru()
                                        ? 'Hanya menampilkan Bank Soal milik Anda sendiri.'
                                        : 'Otomatis mengisi durasi pengerjaan & preferensi pengacakan.')
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                            $bank = BankSoal::query()->forUser(auth()->user())->find($state);
                                            if ($bank) {
                                                if (empty($get('mapel_id')) && !empty($bank->mapel_id)) {
                                                    $set('mapel_id', $bank->mapel_id);
                                                }
                                                if (!empty($bank->durasi_menit)) {
                                                    $set('durasi_menit', $bank->durasi_menit);
                                                }
                                                $set('acak_soal', $bank->acak_soal ?? true);
                                                $set('acak_opsi', $bank->acak_opsi ?? true);
                                            }
                                        }
                                    }),
                            ]),
                        ]),

                    Section::make('Sasaran Peserta & Guru Pengawas')
                        ->description('Tentukan rombel kelas yang akan mengikuti ujian dan guru penanggung jawab.')
                        ->icon(Heroicon::OutlinedUserGroup)
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('kelas')
                                    ->label('Rombel Kelas Peserta')
                                    ->relationship('kelas', 'nama_kelas')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Seluruh siswa aktif di rombel kelas terpilih otomatis terdaftar.'),

                                Select::make('guru_id')
                                    ->label('Guru Pengawas / Pengampu')
                                    ->options(function () {
                                        $user = auth()->user();
                                        if ($user && $user->isGuru()) {
                                            return [$user->id => $user->name];
                                        }
                                        return User::whereIn('role', ['guru', 'admin'])
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->default(fn () => auth()->id())
                                    ->disabled(fn () => auth()->user()?->isGuru() ?? false)
                                    ->dehydrated()
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Guru yang berwenang membuka sesi dan merilis token.'),
                            ]),
                        ]),

                    Section::make('Petunjuk & Instruksi Pengerjaan')
                        ->description('Catatan penting atau tata tertib yang dibaca siswa sebelum mulai.')
                        ->icon(Heroicon::OutlinedInformationCircle)
                        ->collapsible()
                        ->schema([
                            Textarea::make('keterangan')
                                ->label('Petunjuk / Catatan Ujian')
                                ->placeholder('Contoh: Berdoalah sebelum mengerjakan. Dilarang membuka tab lain atau membawa catatan.')
                                ->rows(3),
                        ]),
                ])->columnSpan(['default' => 1, 'lg' => 2]),

                // Kolom Kanan / Sidebar Pengaturan & Jadwal (1 Kolom di Desktop - 33.3% Lebar Halaman)
                Group::make([
                    Section::make('Tipe & Mode Pelaksanaan')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->schema([
                            Select::make('tipe_ujian')
                                ->label('Tipe Pelaksanaan Ujian')
                                ->options(fn () => Ujian::getTipeOptions(auth()->user()))
                                ->default(Ujian::TIPE_ASESMEN_HARIAN)
                                ->required()
                                ->live()
                                ->helperText(function () {
                                    $user = auth()->user();
                                    if ($user && $user->isGuru()) {
                                        return 'Guru berwenang membuat Asesmen Harian.';
                                    }
                                    return 'Pilih tipe asesmen (Harian, Tengah, atau Akhir Semester).';
                                }),

                            Toggle::make('langsung_generate_token')
                                ->label('Buka Sesi & Rilis Token Sekarang')
                                ->helperText('Status otomatis ONGOING & token 10 menit langsung dirilis tanpa menunggu jadwal.')
                                ->default(false)
                                ->visible(fn ($get, string $operation) =>
                                    $operation === 'create' && ($get('tipe_ujian') === Ujian::TIPE_ASESMEN_HARIAN || empty($get('tipe_ujian')))
                                )
                                ->dehydrated(false),

                            Select::make('status')
                                ->label('Status Pelaksanaan')
                                ->options([
                                    'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                                    'ONGOING' => 'ONGOING (Sedang Berlangsung)',
                                    'COMPLETED' => 'COMPLETED (Selesai)',
                                    'CANCELLED' => 'CANCELLED (Dibatalkan)',
                                ])
                                ->default('SCHEDULED')
                                ->required()
                                ->disabled(fn (string $operation) => $operation === 'create')
                                ->helperText('Status baru dibuat default SCHEDULED.'),
                        ]),

                    Section::make('Jadwal & Waktu Pelaksanaan')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal Pelaksanaan')
                                ->default(now())
                                ->required(fn ($get) => in_array($get('tipe_ujian'), [Ujian::TIPE_TENGAH_SEMESTER, Ujian::TIPE_AKHIR_SEMESTER]))
                                ->helperText(fn ($get) => $get('tipe_ujian') === Ujian::TIPE_ASESMEN_HARIAN ? 'Opsional untuk harian' : 'Wajib diisi'),

                            Grid::make(2)->schema([
                                TimePicker::make('waktu_mulai')
                                    ->label('Waktu Mulai')
                                    ->default('07:30:00'),

                                TimePicker::make('waktu_selesai')
                                    ->label('Waktu Selesai')
                                    ->default('09:30:00'),
                            ]),

                            TextInput::make('durasi_menit')
                                ->label('Alokasi Durasi Pengerjaan')
                                ->numeric()
                                ->default(60)
                                ->suffix('Menit')
                                ->required()
                                ->helperText('Hitung mundur waktu per siswa.'),
                        ]),

                    Section::make('Pengacakan & Integritas')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->schema([
                            Toggle::make('acak_soal')
                                ->label('Acak Urutan Soal')
                                ->helperText('Urutan butir soal acak unik untuk tiap siswa.')
                                ->default(true),

                            Toggle::make('acak_opsi')
                                ->label('Acak Opsi Pilihan (A-E)')
                                ->helperText('Posisi opsi jawaban diacak unik.')
                                ->default(true),
                        ]),
                ])->columnSpan(['default' => 1, 'lg' => 1]),
            ]);
    }
}

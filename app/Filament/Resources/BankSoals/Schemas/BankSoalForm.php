<?php

namespace App\Filament\Resources\BankSoals\Schemas;

use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Form informasi paket Bank Soal.
 *
 * Butir-butir soal TIDAK lagi diinput di sini (repeater raksasa),
 * melainkan lewat tab "Butir Soal" (SoalsRelationManager) dengan modal editor per soal.
 */
class BankSoalForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();

        // Opsi Mapel: Jika guru, hanya mapel yang diampunya
        $mapelOptions = function () use ($user) {
            if ($user && $user->isGuru()) {
                return $user->mapels()->pluck('nama_mapel', 'mapels.id');
            }
            return Mapel::pluck('nama_mapel', 'id');
        };

        // Opsi Kelas: Sesuaikan dengan tingkat soal yang dipilih (& kelas guru jika diajar)
        $kelasOptions = function ($get) use ($user) {
            $tingkat = $get('tingkat');
            $query = Kelas::query();

            if ($user && $user->isGuru()) {
                $guruKelasIds = $user->kelases()->pluck('kelas.id')->toArray();
                if (!empty($guruKelasIds)) {
                    $query->whereIn('id', $guruKelasIds);
                }
            }

            if (!empty($tingkat) && $tingkat !== 'Semua') {
                $query->where('tingkat', $tingkat);
            }

            return $query->orderBy('tingkat')->orderBy('nama_kelas')->pluck('nama_kelas', 'id');
        };

        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                // KIRI: Informasi utama paket
                Section::make('Informasi Paket')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            TextInput::make('judul')
                                ->label('Judul Bank Soal')
                                ->placeholder('Contoh: Penilaian Akhir Semester (PAS) Gasal')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(['default' => 1, 'md' => 2]),

                            TextInput::make('kode_bank')
                                ->label('Kode Soal (Bank Soal)')
                                ->placeholder('Otomatis digenerate sistem (misal: SOAL-202611-ABCD)')
                                ->default(fn () => BankSoal::generateKodeBank())
                                ->disabled()
                                ->dehydrated()
                                ->helperText('Kode soal dibuat otomatis oleh sistem.')
                                ->columnSpan(['default' => 1, 'md' => 1]),

                            Select::make('mapel_id')
                                ->label('Mata Pelajaran')
                                ->options($mapelOptions)
                                ->required()
                                ->searchable()
                                ->preload(),

                            Select::make('tingkat')
                                ->label('Tingkat')
                                ->options([
                                    'Semua' => 'Semua Tingkat',
                                    '10' => 'Kelas X (10)',
                                    '11' => 'Kelas XI (11)',
                                    '12' => 'Kelas XII (12)',
                                ])
                                ->default('Semua')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if (!empty($state) && $state !== 'Semua') {
                                        $current = (array) ($get('kelases') ?? []);
                                        if (!empty($current)) {
                                            $validIds = Kelas::where('tingkat', $state)
                                                ->pluck('id')
                                                ->map(fn ($id) => (string) $id)
                                                ->toArray();
                                            $set('kelases', array_values(array_intersect(
                                                array_map('strval', $current),
                                                $validIds
                                            )));
                                        }
                                    }
                                })
                                ->native(false),

                            Select::make('kelases')
                                ->label('Kelas Sasaran')
                                ->relationship('kelases', 'nama_kelas')
                                ->options($kelasOptions)
                                ->multiple()
                                ->preload()
                                ->searchable(),
                        ]),

                        Textarea::make('deskripsi')
                            ->label('Petunjuk Pengerjaan (untuk siswa)')
                            ->placeholder('Tuliskan petunjuk umum pengerjaan ujian...')
                            ->rows(3)
                            ->autosize(),
                    ])
                    ->columnSpan(['default' => 1, 'lg' => 2]),

                // KANAN: Status & ringkasan
                Group::make([
                    Section::make('Pengaturan')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->compact()
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->helperText('Bank soal aktif dapat dijadwalkan pada sesi ujian.')
                                ->default(true),

                            Toggle::make('acak_soal')
                                ->label('Acak Urutan Soal')
                                ->helperText('Urutan nomor butir soal diacak otomatis untuk setiap siswa.')
                                ->default(true),

                            Toggle::make('acak_opsi')
                                ->label('Acak Opsi Pilihan Ganda')
                                ->helperText('Posisi pilihan jawaban (A, B, C, D, E) diacak untuk tiap siswa.')
                                ->default(true),

                            TextInput::make('total_bobot')
                                ->label('Target Total Bobot')
                                ->numeric()
                                ->default(100)
                                ->suffix('poin'),
                        ]),

                    Section::make('Ringkasan Soal')
                        ->description('Kelola soal di tab "Butir Soal".')
                        ->icon(Heroicon::OutlinedChartBar)
                        ->compact()
                        ->visibleOn('edit')
                        ->schema([
                            TextEntry::make('ringkasan_jumlah')
                                ->label('Jumlah Butir')
                                ->state(fn ($record) => $record?->soals()->count() . ' soal'),

                            TextEntry::make('ringkasan_bobot')
                                ->label('Total Bobot Terisi')
                                ->state(function ($record) {
                                    if (! $record) {
                                        return '-';
                                    }
                                    $terisi = (float) $record->soals()->sum('bobot_nilai');
                                    $target = (float) ($record->total_bobot ?? 0);

                                    return rtrim(rtrim(number_format($terisi, 2, ',', ''), '0'), ',')
                                        . ' / ' . rtrim(rtrim(number_format($target, 2, ',', ''), '0'), ',') . ' poin';
                                })
                                ->color(fn ($record) => $record && (float) $record->soals()->sum('bobot_nilai') == (float) $record->total_bobot ? 'success' : 'warning'),
                        ]),
                ])->columnSpan(['default' => 1, 'lg' => 1]),
            ]);
    }
}

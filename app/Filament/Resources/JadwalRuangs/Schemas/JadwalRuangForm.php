<?php

namespace App\Filament\Resources\JadwalRuangs\Schemas;

use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class JadwalRuangForm
{
    public static function ujianOptions(): array
    {
        return Ujian::query()
            ->with(['mapel', 'kelas'])
            ->whereIn('status', ['SCHEDULED', 'ONGOING'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (Ujian $u) {
                $tgl = $u->tanggal ? $u->tanggal->format('d/m/Y') : 'Tanpa Tanggal';
                $mapel = $u->mapel?->nama_mapel ?: 'Umum';
                $kelas = $u->kelas->pluck('nama_kelas')->join(', ');
                $kelasText = $kelas ? " - [{$kelas}]" : '';
                return [$u->id => "[{$tgl}] {$u->nama_ujian} ({$mapel}){$kelasText}"];
            })
            ->all();
    }

    public static function extractSubtestStages(mixed $items): array
    {
        $stages = [];
        $seen = [];

        foreach ((array) $items as $item) {
            $rawIds = [];
            if (is_array($item) && array_key_exists('ujian_ids', $item)) {
                $rawIds = (array) $item['ujian_ids'];
            } elseif (is_array($item) && array_key_exists('ujian_id', $item)) {
                $rawIds = [$item['ujian_id']];
            } elseif (! is_array($item)) {
                $rawIds = [$item];
            }

            $stageIds = [];
            foreach ($rawIds as $rawId) {
                $intId = (int) $rawId;
                if ($intId > 0 && ! in_array($intId, $seen, true)) {
                    $stageIds[] = $intId;
                    $seen[] = $intId;
                }
            }

            if (! empty($stageIds)) {
                $stages[] = $stageIds;
            }
        }

        return $stages;
    }

    public static function extractOrderedUjianIds(mixed $items): array
    {
        return array_values(array_merge(...(self::extractSubtestStages($items) ?: [[]])));
    }

    public static function subtestRepeater(string $fieldName = 'daftar_subtest', bool $withScheduleSync = true): Repeater
    {
        return Repeater::make($fieldName)
            ->label('Daftar Subtest / Agenda Ujian (Opsional - Mode ANBK Otomatis)')
            ->helperText('💡 MODE ANBK (Direkomendasikan): Biarkan KOSONG agar sistem otomatis menyajikan soal sesuai rombel/kelas masing-masing siswa yang duduk di ruangan ini pada sesi waktu ini. Tambahkan baris subtest HANYA jika ingin menentukan mapel secara manual/spesifik.')
            ->table([
                TableColumn::make('Agenda Ujian / Soal per Tahap (Bisa Pilih Banyak / Lintas Kelas)'),
            ])
            ->schema([
                Select::make('ujian_ids')
                    ->hiddenLabel()
                    ->placeholder('Pilih satu atau beberapa agenda ujian (lintas kelas)...')
                    ->options(fn () => self::ujianOptions())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->live(),
            ])
            ->minItems(0)
            ->defaultItems(0)
            ->reorderable()
            ->reorderableWithButtons()
            ->addActionLabel('Tambah Subtest Manual')
            ->live()
            ->afterStateUpdated(function (?array $state, Set $set, Get $get) use ($withScheduleSync) {
                if (! $withScheduleSync) {
                    return;
                }
                $firstId = self::extractOrderedUjianIds($state ?? [])[0] ?? null;
                if (! $firstId) {
                    return;
                }
                $ujian = Ujian::find($firstId);
                if (! $ujian) {
                    return;
                }
                if ($ujian->tanggal && empty($get('tanggal'))) {
                    $set('tanggal', $ujian->tanggal);
                }
                if ($ujian->waktu_mulai && empty($get('waktu_mulai'))) {
                    $set('waktu_mulai', $ujian->waktu_mulai);
                }
                if ($ujian->waktu_selesai && empty($get('waktu_selesai'))) {
                    $set('waktu_selesai', $ujian->waktu_selesai);
                }
            });
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penetapan Ruang & Urutan Subtest / Agenda Ujian')
                    ->description('Pilih ruang ujian fisik lalu susun agenda ujian atau subtest berurutan pada sesi ini.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->schema([
                        Checkbox::make('apply_all_rooms')
                            ->label('Terapkan Jadwal Sesi Ini ke SEMUA Ruang Ujian Aktif Sekaligus')
                            ->helperText('💡 Cukup atur jam sesi & tanggal sekali, sistem otomatis membuat jadwal untuk seluruh ruangan aktif.')
                            ->default(false)
                            ->live()
                            ->visibleOn('create'),

                        Select::make('ruang_ujian_id')
                            ->label('Ruang Ujian')
                            ->options(fn () => RuangUjian::query()
                                ->where('is_active', true)
                                ->orderBy('nama_ruang')
                                ->pluck('nama_ruang', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => ! $get('apply_all_rooms'))
                            ->hidden(fn (Get $get) => (bool) $get('apply_all_rooms'))
                            ->live()
                            ->helperText(function ($get) {
                                $id = $get('ruang_ujian_id');
                                if (! $id) return null;
                                $ruang = RuangUjian::withCount('siswas')->find($id);
                                return $ruang ? "Peserta ruangan: {$ruang->siswas_count} Siswa ({$ruang->daftar_nama_kelas})" : null;
                            }),

                        self::subtestRepeater('daftar_subtest', withScheduleSync: true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Penugasan Guru Pengawas (Pengawas Silang)')
                    ->description('Tentukan guru yang bertugas mengawasi ruangan ini untuk sesi jadwal ini.')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('pengawas_id')
                                ->label('Guru Pengawas Utama')
                                ->options(fn () => User::query()
                                    ->whereIn('role', ['guru', 'admin'])
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Guru yang berwenang membuka sesi dan merilis token ruangan.'),

                            Select::make('pengawas_pendamping_id')
                                ->label('Pengawas Pendamping / Proktor (Opsional)')
                                ->options(fn () => User::query()
                                    ->whereIn('role', ['guru', 'admin'])
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->helperText('Guru pendamping atau teknisi IT laboratorium.'),
                        ]),
                    ]),

                Section::make('Waktu, Sesi & Status Pelaksanaan')
                    ->description('Atur sesi pelaksanaan, rentang jam, dan status aktif.')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make(4)->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal Ujian')
                                ->default(now())
                                ->required(),

                            TextInput::make('sesi_ke')
                                ->label('Sesi Pelaksanaan')
                                ->default('1')
                                ->placeholder('Contoh: 1, 2, atau Sesi Pagi')
                                ->required(),

                            TimePicker::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->default('07:30:00')
                                ->required(),

                            TimePicker::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->default('09:30:00')
                                ->required(),
                        ]),

                        Select::make('status')
                            ->label('Status Pelaksanaan Ruangan')
                            ->options([
                                'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                                'ONGOING' => 'ONGOING (Sedang Berlangsung)',
                                'COMPLETED' => 'COMPLETED (Selesai)',
                                'CANCELLED' => 'CANCELLED (Dibatalkan)',
                            ])
                            ->default('SCHEDULED')
                            ->required(),

                        Textarea::make('catatan')
                            ->label('Catatan Khusus / Berita Acara Singkat')
                            ->placeholder('Contoh: Seluruh peserta hadir lengkap, kondisi tertib dan lancar.')
                            ->rows(3),
                    ]),
            ]);
    }
}


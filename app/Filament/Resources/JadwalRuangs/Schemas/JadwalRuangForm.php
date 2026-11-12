<?php

namespace App\Filament\Resources\JadwalRuangs\Schemas;

use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class JadwalRuangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penetapan Ruang & Agenda Ujian')
                    ->description('Pilih ruang ujian fisik serta agenda ujian yang akan dilaksanakan.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('ruang_ujian_id')
                                ->label('Ruang Ujian')
                                ->options(fn () => RuangUjian::query()
                                    ->where('is_active', true)
                                    ->orderBy('nama_ruang')
                                    ->pluck('nama_ruang', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText(function ($get) {
                                    $id = $get('ruang_ujian_id');
                                    if (! $id) return null;
                                    $ruang = RuangUjian::withCount('siswas')->find($id);
                                    return $ruang ? "Kapasitas: {$ruang->siswas_count} / {$ruang->kapasitas} Siswa ({$ruang->daftar_nama_kelas})" : null;
                                }),

                            Select::make('ujians')
                                ->label('Agenda Jadwal Ujian (Bisa Pilih Banyak / Lintas Kelas)')
                                ->relationship(
                                    name: 'ujians',
                                    titleAttribute: 'nama_ujian',
                                    modifyQueryUsing: fn ($query) => $query
                                        ->with(['mapel', 'kelas'])
                                        ->whereIn('status', ['SCHEDULED', 'ONGOING'])
                                        ->orderByDesc('tanggal')
                                        ->orderByDesc('id')
                                )
                                ->getOptionLabelFromRecordUsing(function (Ujian $u) {
                                    $tgl = $u->tanggal ? $u->tanggal->format('d/m/Y') : 'Tanpa Tanggal';
                                    $mapel = $u->mapel?->nama_mapel ?: 'Umum';
                                    $kelas = $u->kelas->pluck('nama_kelas')->join(', ');
                                    $kelasText = $kelas ? " - [{$kelas}]" : '';
                                    return "[{$tgl}] {$u->nama_ujian} ({$mapel}){$kelasText}";
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText('Pilih agenda ujian (lintas kelas maupun subtest berantai, misal Soal 1 & Soal 2). Urutan pilihan menentukan urutan pengerjaan subtest tanpa token ulang.')
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $firstId = is_array($state) ? ($state[0] ?? null) : $state;
                                    if ($firstId) {
                                        $ujian = Ujian::find($firstId);
                                        if ($ujian) {
                                            if ($ujian->tanggal && empty($get('tanggal'))) {
                                                $set('tanggal', $ujian->tanggal);
                                            }
                                            if ($ujian->waktu_mulai && empty($get('waktu_mulai'))) {
                                                $set('waktu_mulai', $ujian->waktu_mulai);
                                            }
                                            if ($ujian->waktu_selesai && empty($get('waktu_selesai'))) {
                                                $set('waktu_selesai', $ujian->waktu_selesai);
                                            }
                                        }
                                    }
                                }),
                        ]),
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


<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Filament\Resources\JadwalRuangs\Schemas\JadwalRuangForm;
use App\Models\JadwalRuang;
use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListJadwalRuangs extends ListRecords
{
    protected static string $resource = JadwalRuangResource::class;

    protected static function guruOptions(): array
    {
        return User::query()
            ->whereIn('role', ['guru', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (User $u) {
                $label = $u->nip ? "{$u->name} ({$u->nip})" : $u->name;
                return [$u->id => $label];
            })
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Jadwal Ruang')
                ->icon(Heroicon::OutlinedPlus),

            Action::make('generateOtomatis')
                ->label('Generate ke Semua Ruang')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('info')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->modalHeading('Generate Jadwal Ujian Otomatis ke Seluruh Ruang')
                ->modalDescription('Sistem akan membuat jadwal sesi untuk seluruh Ruang Ujian aktif sekaligus berdasarkan agenda ujian yang dipilih.')
                ->modalSubmitActionLabel('Buat Jadwal untuk Semua Ruang')
                ->modalWidth(Width::FiveExtraLarge)
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->form([
                    JadwalRuangForm::subtestRepeater('daftar_subtest', withScheduleSync: true),

                    Grid::make(4)->schema([
                        DatePicker::make('tanggal')
                            ->label('Tanggal Pelaksanaan')
                            ->default(now())
                            ->required(),

                        TextInput::make('sesi_ke')
                            ->label('Sesi Pelaksanaan')
                            ->default('1')
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

                    Select::make('mode_pengawas')
                        ->label('Penetapan Pengawas Ruangan')
                        ->options([
                            'ruang_default' => 'Gunakan Pengawas Tetap Bawaan Masing-masing Ruang',
                            'pilih_per_ruang' => 'Pilih Pengawas per Ruangan (Dropdown Daftar Guru + Search)',
                            'rotasi_guru_pilihan' => 'Pilih Daftar Guru Bertugas (Multi-pilih + Search), lalu Bagi Merata',
                            'rotasi_guru' => 'Bagi Merata (Rotasi Bergilir) Seluruh Guru Pengawas Aktif',
                        ])
                        ->default('ruang_default')
                        ->live()
                        ->required(),

                    Select::make('rotasi_guru_ids')
                        ->label('Pilih Daftar Guru Pengawas yang Bertugas')
                        ->placeholder('Ketik nama atau NIP guru untuk mencari...')
                        ->options(fn () => self::guruOptions())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('mode_pengawas') === 'rotasi_guru_pilihan')
                        ->required(fn (Get $get) => $get('mode_pengawas') === 'rotasi_guru_pilihan')
                        ->helperText('Ketik nama guru untuk memfilter jika daftar guru banyak, lalu sistem akan membagi merata ke seluruh ruangan.'),

                    Section::make('Pilih Pengawas per Ruangan (Daftar Guru)')
                        ->visible(fn (Get $get) => $get('mode_pengawas') === 'pilih_per_ruang')
                        ->schema(function () {
                            $opts = self::guruOptions();
                            return RuangUjian::where('is_active', true)
                                ->orderBy('nama_ruang')
                                ->get()
                                ->map(fn (RuangUjian $ruang) => Select::make("pengawas_ruang_{$ruang->id}")
                                    ->label($ruang->nama_ruang . ($ruang->kode_ruang ? " ({$ruang->kode_ruang})" : ''))
                                    ->options($opts)
                                    ->default($ruang->pengawas_id)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Ketik nama / NIP guru...'))
                                ->all();
                        })
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $stages = JadwalRuangForm::extractSubtestStages($data['daftar_subtest'] ?? []);
                    $ujianIds = JadwalRuangForm::extractOrderedUjianIds($data['daftar_subtest'] ?? []);
                    if (empty($ujianIds)) return;

                    $firstUjianId = (int) $ujianIds[0];
                    $ujians = Ujian::whereIn('id', $ujianIds)->get();
                    if ($ujians->isEmpty()) return;

                    $ruangs = RuangUjian::where('is_active', true)->orderBy('nama_ruang')->get();
                    if ($ruangs->isEmpty()) {
                        Notification::make()
                            ->title('Tidak Ada Ruang Ujian Aktif')
                            ->body('Silakan buat ruang ujian terlebih dahulu di menu Ruang Ujian.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $gurus = User::whereIn('role', ['guru', 'admin'])->where('is_active', true)->orderBy('name')->get();
                    $guruCount = $gurus->count();
                    $selectedRotasiIds = array_values(array_filter(array_map('intval', (array) ($data['rotasi_guru_ids'] ?? []))));
                    $selectedRotasiCount = count($selectedRotasiIds);

                    $createdCount = 0;
                    foreach ($ruangs as $idx => $ruang) {
                        $pengawasId = match ($data['mode_pengawas']) {
                            'pilih_per_ruang' => (int) ($data["pengawas_ruang_{$ruang->id}"] ?? 0) ?: ($ruang->pengawas_id ?: ($guruCount > 0 ? $gurus[0]->id : null)),
                            'rotasi_guru_pilihan' => $selectedRotasiCount > 0 ? $selectedRotasiIds[$idx % $selectedRotasiCount] : ($ruang->pengawas_id ?: null),
                            'rotasi_guru' => $guruCount > 0 ? $gurus[$idx % $guruCount]->id : null,
                            default => $ruang->pengawas_id ?: ($guruCount > 0 ? $gurus[0]->id : null),
                        };

                        $jadwal = JadwalRuang::firstOrCreate(
                            [
                                'ruang_ujian_id' => $ruang->id,
                                'tanggal' => $data['tanggal'],
                                'sesi_ke' => $data['sesi_ke'],
                            ],
                            [
                                'ujian_id' => $firstUjianId,
                                'pengawas_id' => $pengawasId,
                                'waktu_mulai' => $data['waktu_mulai'],
                                'waktu_selesai' => $data['waktu_selesai'],
                                'status' => 'SCHEDULED',
                            ]
                        );

                        $jadwal->syncSubtestStages($stages, false);

                        $createdCount++;
                    }

                    $namaAgenda = $ujians->pluck('nama_ujian')->join(', ');
                    Notification::make()
                        ->title("Jadwal Berhasil Dibuat untuk {$createdCount} Ruangan")
                        ->body("Agenda ({$namaAgenda}) telah dijadwalkan pada {$data['tanggal']}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}


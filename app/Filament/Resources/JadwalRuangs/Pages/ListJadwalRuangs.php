<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
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
use Filament\Support\Icons\Heroicon;

class ListJadwalRuangs extends ListRecords
{
    protected static string $resource = JadwalRuangResource::class;

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
                ->form([
                    Select::make('ujian_id')
                        ->label('Pilih Agenda Ujian')
                        ->options(function () {
                            return Ujian::query()
                                ->with(['mapel'])
                                ->whereIn('status', ['SCHEDULED', 'ONGOING'])
                                ->orderByDesc('tanggal')
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(function (Ujian $u) {
                                    $tgl = $u->tanggal ? $u->tanggal->format('d/m/Y') : 'Tanpa Tanggal';
                                    $mapel = $u->mapel?->nama_mapel ?: 'Umum';
                                    return [$u->id => "[{$tgl}] {$u->nama_ujian} ({$mapel})"];
                                });
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state) {
                                $u = Ujian::find($state);
                                if ($u) {
                                    if ($u->tanggal && empty($get('tanggal'))) {
                                        $set('tanggal', $u->tanggal);
                                    }
                                    if ($u->waktu_mulai && empty($get('waktu_mulai'))) {
                                        $set('waktu_mulai', $u->waktu_mulai);
                                    }
                                    if ($u->waktu_selesai && empty($get('waktu_selesai'))) {
                                        $set('waktu_selesai', $u->waktu_selesai);
                                    }
                                }
                            }
                        }),

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

                    Select::make('mode_pengawas')
                        ->label('Penetapan Pengawas Ruangan')
                        ->options([
                            'ruang_default' => 'Gunakan Pengawas Tetap Bawaan Masing-masing Ruang',
                            'rotasi_guru' => 'Bagi Merata (Rotasi Bergilir) Seluruh Guru Pengawas Aktif',
                        ])
                        ->default('ruang_default')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $ujianId = $data['ujian_id'];
                    $ujian = Ujian::find($ujianId);
                    if (! $ujian) return;

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

                    $createdCount = 0;
                    foreach ($ruangs as $idx => $ruang) {
                        $pengawasId = null;
                        if ($data['mode_pengawas'] === 'rotasi_guru' && $guruCount > 0) {
                            $pengawasId = $gurus[$idx % $guruCount]->id;
                        } else {
                            $pengawasId = $ruang->pengawas_id ?: ($guruCount > 0 ? $gurus[0]->id : null);
                        }

                        JadwalRuang::firstOrCreate(
                            [
                                'ruang_ujian_id' => $ruang->id,
                                'ujian_id' => $ujian->id,
                                'tanggal' => $data['tanggal'],
                                'sesi_ke' => $data['sesi_ke'],
                            ],
                            [
                                'pengawas_id' => $pengawasId,
                                'waktu_mulai' => $data['waktu_mulai'],
                                'waktu_selesai' => $data['waktu_selesai'],
                                'status' => 'SCHEDULED',
                            ]
                        );

                        $createdCount++;
                    }

                    Notification::make()
                        ->title("Jadwal Berhasil Dibuat untuk {$createdCount} Ruangan")
                        ->body("Agenda ujian {$ujian->nama_ujian} telah dijadwalkan pada {$data['tanggal']}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}


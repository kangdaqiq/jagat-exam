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
                    Select::make('ujian_ids')
                        ->label('Pilih Agenda Ujian (Bisa Pilih Banyak / Lintas Kelas)')
                        ->options(function () {
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
                                });
                        })
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->helperText('Pilih semua agenda ujian yang berjalan di sesi ini (misal K10, K11, K12 sekaligus).')
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $firstId = is_array($state) ? ($state[0] ?? null) : $state;
                            if ($firstId) {
                                $u = Ujian::find($firstId);
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
                    $ujianIds = array_values(array_filter((array) ($data['ujian_ids'] ?? [])));
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

                    $createdCount = 0;
                    foreach ($ruangs as $idx => $ruang) {
                        $pengawasId = null;
                        if ($data['mode_pengawas'] === 'rotasi_guru' && $guruCount > 0) {
                            $pengawasId = $gurus[$idx % $guruCount]->id;
                        } else {
                            $pengawasId = $ruang->pengawas_id ?: ($guruCount > 0 ? $gurus[0]->id : null);
                        }

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

                        $jadwal->syncOrderedUjians($ujianIds, false);

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


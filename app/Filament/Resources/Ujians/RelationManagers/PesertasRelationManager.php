<?php

namespace App\Filament\Resources\Ujians\RelationManagers;

use App\Models\Siswa;
use App\Models\UjianPeserta;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PesertasRelationManager extends RelationManager
{
    protected static string $relationship = 'pesertas';

    protected static ?string $title = 'Daftar Peserta Ujian & Status Mengerjakan';

    protected static ?string $modelLabel = 'Peserta Ujian';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('siswa_id')
                    ->label('Pilih Siswa')
                    ->options(function () {
                        return Siswa::where('is_active', true)
                            ->whereNotNull('user_id')
                            ->with('kelas')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "{$s->nis} - {$s->nama} (" . ($s->kelas?->nama_kelas ?? 'Tanpa Kelas') . ")"]);
                    })
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('siswa.nama')
            ->columns([
                TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('siswa.kelas.nama_kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status Mengerjakan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BELUM_MULAI' => 'gray',
                        'SEDANG_MENGERJAKAN' => 'warning',
                        'SELESAI' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'BELUM_MULAI' => 'Belum Mulai',
                        'SEDANG_MENGERJAKAN' => 'Sedang Mengerjakan',
                        'SELESAI' => 'Selesai',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('nilai')
                    ->label('Nilai Akhir')
                    ->badge()
                    ->color('primary')
                    ->weight('bold')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('waktu_mulai')
                    ->label('Mulai Pengerjaan')
                    ->dateTime('d M H:i')
                    ->placeholder('-'),

                TextColumn::make('waktu_selesai')
                    ->label('Selesai')
                    ->dateTime('d M H:i')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Siswa Manual')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->mutateFormDataUsing(function (array $data): array {
                        $siswa = Siswa::find($data['siswa_id']);
                        $data['user_id'] = $siswa->user_id;
                        $data['status'] = 'BELUM_MULAI';
                        return $data;
                    }),

                Action::make('tarik_semua_kelas')
                    ->label('Tarik Semua Siswa dari Kelas Terpilih')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(function () {
                        $ujian = $this->getOwnerRecord();
                        $count = $ujian->syncPesertaFromKelas();
                        \Filament\Notifications\Notification::make()
                            ->title('Peserta Berhasil Ditarik')
                            ->body("{$count} siswa baru berhasil ditambahkan sebagai peserta.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                // Reset sesi siswa jika ada kendala teknis / laptop mati
                Action::make('reset_sesi')
                    ->label('Reset Sesi')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Sesi Pengerjaan Siswa?')
                    ->modalDescription('Apakah Anda yakin ingin mereset status siswa ini menjadi Belum Mulai? Siswa dapat login kembali menggunakan token ujian.')
                    ->visible(fn (UjianPeserta $record): bool => $record->status !== 'BELUM_MULAI')
                    ->action(function (UjianPeserta $record) {
                        if ($record->ujianSesi) {
                            $record->ujianSesi->update(['status' => 'mengerjakan']);
                        }
                        $record->update(['status' => 'BELUM_MULAI']);
                        \Filament\Notifications\Notification::make()
                            ->title('Sesi Siswa Berhasil Direset')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()->label('Hapus Peserta'),
            ]);
    }
}


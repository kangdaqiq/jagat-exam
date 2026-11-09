<?php

namespace App\Filament\Resources\RuangUjians\Tables;

use App\Models\RuangUjian;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RuangUjianTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('kode_ruang')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_ruang')
                    ->label('Nama Ruang Ujian')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('pengawas.name')
                    ->label('Pengawas Ruangan')
                    ->placeholder('Semua Pengawas')
                    ->searchable(),

                TextColumn::make('siswas_count')
                    ->counts('siswas')
                    ->label('Jumlah Peserta')
                    ->formatStateUsing(fn ($state, RuangUjian $record) => "{$state} / {$record->kapasitas} Siswa")
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('komposisi_kelas')
                    ->label('Mix Kelas di Ruangan')
                    ->getStateUsing(function (RuangUjian $record) {
                        $kelasNames = $record->siswas()
                            ->with('kelas')
                            ->get()
                            ->pluck('kelas.nama_kelas')
                            ->filter()
                            ->unique()
                            ->values();

                        return $kelasNames->isNotEmpty() ? $kelasNames->join(', ') : 'Belum ada siswa';
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('token_ruang')
                    ->label('Token Ruangan (10m)')
                    ->getStateUsing(function (RuangUjian $record) {
                        if (!$record->token_ruang) {
                            return 'BELUM DIRILIS';
                        }
                        $status = $record->sisaWaktuTokenFormatted();
                        return "{$record->token_ruang} ({$status})";
                    })
                    ->badge()
                    ->color(fn (RuangUjian $record) => $record->isTokenActive() ? 'success' : ($record->token_ruang ? 'danger' : 'gray'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyableState(fn (RuangUjian $record) => $record->token_ruang)
                    ->copyMessage('Token ruangan disalin'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('rilisTokenRuang')
                    ->label(fn (RuangUjian $record) => $record->token_ruang ? 'Rilis Token Baru' : 'Rilis Token Ruang')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (RuangUjian $record) => "Rilis Token Akses: {$record->nama_ruang}")
                    ->modalDescription('Token 6 karakter ini berlaku selama 10 menit untuk seluruh siswa di ruangan ini. Soal ujian yang terbuka akan otomatis menyesuaikan kelas/tingkat masing-masing siswa.')
                    ->modalSubmitActionLabel('Rilis Token Sekarang')
                    ->action(function (RuangUjian $record) {
                        if ($record->siswas()->count() === 0) {
                            Notification::make()
                                ->title('Ruang Belum Memiliki Peserta')
                                ->body("Silakan tempatkan siswa terlebih dahulu ke {$record->nama_ruang} sebelum merilis token.")
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($record->isSemuaJadwalBerakhir()) {
                            Notification::make()
                                ->title('Jadwal Ujian Telah Berakhir')
                                ->body('Seluruh jadwal ujian untuk kelas yang ada di ruangan ini telah melewati jam selesai.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $token = $record->rilisToken();

                        Notification::make()
                            ->title("Token {$record->nama_ruang} Dirilis: {$token}")
                            ->body("Token {$token} aktif selama 10 menit untuk seluruh peserta di {$record->nama_ruang}.")
                            ->success()
                            ->send();
                    }),

                Action::make('cetakKartuRuang')
                    ->label('Kartu Ruang')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('info')
                    ->url(fn (RuangUjian $record) => route('kartu-peserta.print', ['ruang_ujian_id' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('bukaPengawasRuang')
                    ->label('Monitor Ruang')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->color('primary')
                    ->url(fn (RuangUjian $record) => route('pengawas.portal', ['ruang' => $record->id])),

                EditAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ]);
    }
}


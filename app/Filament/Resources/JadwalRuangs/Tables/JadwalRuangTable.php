<?php

namespace App\Filament\Resources\JadwalRuangs\Tables;

use App\Models\JadwalRuang;
use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JadwalRuangTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ruangUjian.nama_ruang')
                    ->label('Ruang Ujian')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (JadwalRuang $record) => $record->ruangUjian?->kode_ruang ?: '-'),

                TextColumn::make('ujian.nama_ujian')
                    ->label('Agenda Ujian')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (JadwalRuang $record) => $record->ujian?->mapel?->nama_mapel ?: 'Umum'),

                TextColumn::make('pengawas.name')
                    ->label('Guru Pengawas')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->description(fn (JadwalRuang $record) => $record->pengawasPendamping ? "Pendamping: {$record->pengawasPendamping->name}" : null),

                TextColumn::make('tanggal')
                    ->label('Tanggal & Sesi')
                    ->date('d M Y')
                    ->sortable()
                    ->description(fn (JadwalRuang $record) => $record->label_sesi_waktu),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ONGOING' => 'success',
                        'SCHEDULED' => 'warning',
                        'COMPLETED' => 'gray',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('token_ruang')
                    ->label('Token Ruang')
                    ->getStateUsing(function (JadwalRuang $record) {
                        if (empty($record->token_ruang)) {
                            return 'Belum Dirilis';
                        }
                        if ($record->isTokenExpired()) {
                            return "{$record->token_ruang} (Kedaluwarsa)";
                        }
                        return "{$record->token_ruang} ({$record->sisaWaktuTokenFormatted()})";
                    })
                    ->badge()
                    ->color(fn (JadwalRuang $record) => $record->isTokenActive() ? 'success' : ($record->token_ruang ? 'danger' : 'gray'))
                    ->copyable()
                    ->copyableState(fn (JadwalRuang $record) => $record->token_ruang),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('ruang_ujian_id')
                    ->label('Filter Ruang Ujian')
                    ->options(fn () => RuangUjian::pluck('nama_ruang', 'id')),

                SelectFilter::make('ujian_id')
                    ->label('Filter Agenda Ujian')
                    ->options(fn () => Ujian::pluck('nama_ujian', 'id')),

                SelectFilter::make('pengawas_id')
                    ->label('Filter Guru Pengawas')
                    ->options(fn () => User::whereIn('role', ['guru', 'admin'])->pluck('name', 'id')),

                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                        'ONGOING' => 'ONGOING (Sedang Berlangsung)',
                        'COMPLETED' => 'COMPLETED (Selesai)',
                        'CANCELLED' => 'CANCELLED (Dibatalkan)',
                    ]),
            ])
            ->actions([
                Action::make('rilisToken')
                    ->label(fn (JadwalRuang $record) => $record->token_ruang ? 'Rilis Ulang Token' : 'Rilis Token')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('success')
                    ->disabled(fn (JadwalRuang $record) => $record->isBelumMasukJadwal() || $record->isJadwalBerakhir())
                    ->tooltip(function (JadwalRuang $record) {
                        if ($record->isBelumMasukJadwal()) {
                            return "Belum masuk jadwal pelaksanaan ({$record->label_sesi_waktu}).";
                        }
                        if ($record->isJadwalBerakhir()) {
                            return "Jadwal sesi ini telah berakhir.";
                        }
                        return null;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (JadwalRuang $record) => "Rilis Token Ruang: {$record->ruangUjian?->nama_ruang} - {$record->label_sesi_waktu}")
                    ->modalDescription('Token 6 karakter baru akan di-generate dan berlaku 10 menit. Status jadwal otomatis diubah menjadi ONGOING.')
                    ->action(function (JadwalRuang $record) {
                        if ($record->isBelumMasukJadwal()) {
                            Notification::make()
                                ->title('Belum Masuk Jadwal')
                                ->body("Sesi ini belum memasuki jam pelaksanaan ({$record->label_sesi_waktu}).")
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($record->isJadwalBerakhir()) {
                            Notification::make()
                                ->title('Jadwal Telah Berakhir')
                                ->body("Waktu jadwal sesi ini telah berakhir.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $token = $record->rilisToken();
                        Notification::make()
                            ->title("Token Ruangan Berhasil Dirilis: {$token}")
                            ->body("Token berlaku 10 menit untuk {$record->ruangUjian?->nama_ruang}.")
                            ->success()
                            ->send();
                    }),

                Action::make('portalPengawas')
                    ->label('Buka Portal')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->color('primary')
                    ->url(fn (JadwalRuang $record) => route('pengawas.portal', ['ruang' => $record->ruang_ujian_id]))
                    ->openUrlInNewTab(),

                Action::make('daftarHadir')
                    ->label('Daftar Hadir')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('warning')
                    ->tooltip('Cetak Daftar Hadir Peserta Ruangan (Sebelum Ujian Dimulai)')
                    ->url(fn (JadwalRuang $record) => route('daftar-hadir.print', [
                        'ruang_ujian_id' => $record->ruang_ujian_id,
                        'jadwal_ruang_id' => $record->id,
                        'ujian_id' => $record->ujian_id,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('cetakBeritaAcara')
                    ->label('Berita Acara (BAP)')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('success')
                    ->tooltip('Cetak Berita Acara Pelaksanaan (Setelah Ujian Selesai)')
                    ->url(fn (JadwalRuang $record) => route('berita-acara.print', [
                        'ruang_ujian_id' => $record->ruang_ujian_id,
                        'jadwal_ruang_id' => $record->id,
                        'ujian_id' => $record->ujian_id,
                    ]))
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}


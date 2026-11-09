<?php

namespace App\Filament\Resources\Ujians\Tables;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Filament\Resources\Ujians\UjianResource;
use App\Models\Ujian;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UjianTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('exam_id')
                    ->label('Exam ID')
                    ->badge()
                    ->color('gray')
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Exam ID disalin')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_ujian')
                    ->label('Nama Ujian')
                    ->weight('bold')
                    ->grow()
                    ->description(fn (Ujian $record): string => ($record->mapel?->nama_mapel ?? 'Mapel') . ' • ' . ($record->bankSoal?->judul ?? 'Bank Soal'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipe_ujian')
                    ->label('Tipe Asesmen')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        Ujian::TIPE_ASESMEN_HARIAN => 'info',
                        Ujian::TIPE_TENGAH_SEMESTER => 'warning',
                        Ujian::TIPE_AKHIR_SEMESTER => 'danger',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Ujian::TIPE_ASESMEN_HARIAN => 'Asesmen Harian',
                        Ujian::TIPE_TENGAH_SEMESTER => 'Tengah Semester',
                        Ujian::TIPE_AKHIR_SEMESTER => 'Akhir Semester',
                        default => 'Asesmen Harian',
                    })
                    ->sortable(),

                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas Peserta')
                    ->badge()
                    ->color('info')
                    ->separator(', '),

                TextColumn::make('guru.name')
                    ->label('Guru Pengawas')
                    ->icon(Heroicon::OutlinedUser)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Jadwal Pelaksanaan')
                    ->date('d M Y')
                    ->description(fn (Ujian $record): string => ($record->waktu_mulai ? substr($record->waktu_mulai, 0, 5) : '00:00') . ' - ' . ($record->waktu_selesai ? substr($record->waktu_selesai, 0, 5) : 'Selesai'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SCHEDULED' => 'warning',
                        'ONGOING' => 'success',
                        'COMPLETED' => 'info',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                        'ONGOING' => 'ONGOING (Berlangsung)',
                        'COMPLETED' => 'COMPLETED (Selesai)',
                        'CANCELLED' => 'Dibatalkan',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('token_ujian')
                    ->label('TOKEN AKSES (10 MNT)')
                    ->badge()
                    ->color(fn (Ujian $record): string =>
                        $record->status !== 'ONGOING'
                            ? 'gray'
                            : ($record->isTokenActive() ? 'success' : 'danger')
                    )
                    ->weight('heavy')
                    ->copyable(fn (Ujian $record): bool => $record->isTokenActive())
                    ->copyMessage('Token disalin!')
                    ->placeholder('-')
                    ->formatStateUsing(function (?string $state, Ujian $record): string {
                        if ($record->status !== 'ONGOING' || empty($state)) {
                            return '-';
                        }
                        if ($record->isTokenExpired()) {
                            return "{$state} (EXP)";
                        }
                        return $state;
                    })
                    ->description(function (Ujian $record): ?string {
                        if ($record->status !== 'ONGOING' || empty($record->token_ujian)) {
                            return null;
                        }
                        if ($record->isTokenExpired()) {
                            return 'Kedaluwarsa (10 mnt)';
                        }
                        return 'Sisa waktu: ' . $record->sisaWaktuTokenFormatted();
                    })
                    ->extraAttributes(fn (?string $state, Ujian $record): array => [
                        'style' => $record->status === 'ONGOING'
                            ? 'font-family: monospace; font-size: 15px; font-weight: 800; letter-spacing: 0.15em;'
                            : '',
                    ]),

                TextColumn::make('pesertas_count')
                    ->label('Peserta')
                    ->counts('pesertas')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => ($state ?? 0) . ' Siswa'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipe_ujian')
                    ->label('Filter Tipe Asesmen')
                    ->options([
                        Ujian::TIPE_ASESMEN_HARIAN => 'Asesmen Harian',
                        Ujian::TIPE_TENGAH_SEMESTER => 'Asesmen Tengah Semester',
                        Ujian::TIPE_AKHIR_SEMESTER => 'Asesmen Akhir Semester',
                    ]),

                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                        'ONGOING' => 'ONGOING (Sedang Berlangsung)',
                        'COMPLETED' => 'COMPLETED (Selesai)',
                    ]),

                SelectFilter::make('mapel_id')
                    ->label('Filter Mata Pelajaran')
                    ->relationship('mapel', 'nama_mapel'),
            ])
            ->recordUrl(fn (Ujian $record): string => UjianResource::getUrl('pengawasan', ['record' => $record]))
            ->recordActions([
                // ACTION UTAMA: RUANG PENGAWASAN
                Action::make('pengawasan')
                    ->label('Ruang Pengawasan')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->color('primary')
                    ->url(fn (Ujian $record): string => UjianResource::getUrl('pengawasan', ['record' => $record])),

                ActionGroup::make([
                    // ACTION 1: MULAI UJIAN & GENERATE TOKEN
                    Action::make('mulai_ujian')
                        ->label('Mulai Ujian')
                        ->icon(Heroicon::OutlinedPlay)
                        ->color('success')
                        ->disabled(fn (Ujian $record): bool => $record->isJadwalBerakhir())
                        ->requiresConfirmation()
                        ->modalHeading('Mulai Pelaksanaan Ujian?')
                        ->modalDescription('Server akan mengubah status menjadi ONGOING dan menghasilkan 6 karakter Kode Akses Ujian (Token). Bagikan kode tersebut kepada siswa di kelas.')
                        ->modalSubmitActionLabel('Mulai Ujian Sekarang')
                        ->visible(fn (Ujian $record): bool =>
                            ($record->status === 'SCHEDULED' || ($record->status === 'ONGOING' && empty($record->token_ujian)))
                            && ! $record->isJadwalBerakhir()
                        )
                        ->action(function (Ujian $record) {
                            if ($record->isJadwalBerakhir()) {
                                Notification::make()
                                    ->title('Waktu Jadwal Ujian Telah Habis')
                                    ->body('Rilis token telah ditutup karena melewati batas jadwal ujian.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            $token = $record->startExam();
                            // Pastikan siswa di kelas ter-sinkron
                            $synced = $record->syncPesertaFromKelas();

                            Notification::make()
                                ->title('Ujian Berhasil Dimulai! Status: ONGOING')
                                ->body("KODE TOKEN AKSES UJIAN:\n\n# **{$token}**\n\nToken berlaku selama **10 menit** ({$synced} siswa tersinkron). Bagikan kode ini ke siswa di kelas.")
                                ->success()
                                ->persistent()
                                ->send();
                        }),

                    // ACTION 2: REFRESH TOKEN / GENERATE TOKEN BARU (Masa berlaku 10 menit)
                    Action::make('refresh_token')
                        ->label(fn (Ujian $record): string =>
                            $record->isTokenExpired() ? 'Generate Token Baru' : 'Ganti Token'
                        )
                        ->icon(fn (Ujian $record): Heroicon =>
                            $record->isTokenExpired() ? Heroicon::OutlinedSparkles : Heroicon::OutlinedArrowPath
                        )
                        ->color(fn (Ujian $record): string =>
                            $record->isTokenExpired() ? 'danger' : 'warning'
                        )
                        ->disabled(fn (Ujian $record): bool => $record->isJadwalBerakhir())
                        ->requiresConfirmation()
                        ->modalHeading(fn (Ujian $record): string =>
                            $record->isTokenExpired()
                                ? 'Generate Token Ujian Baru (Token Telah Kedaluwarsa)'
                                : 'Perbarui Kode Token Ujian?'
                        )
                        ->modalDescription(fn (Ujian $record): string =>
                            $record->isTokenExpired()
                                ? 'Token ujian sebelumnya telah kedaluwarsa setelah 10 menit. Klik tombol di bawah untuk membuat token baru yang aktif selama 10 menit ke depan.'
                                : 'Apakah Anda yakin ingin mengganti kode token sesi ujian saat ini dengan kode baru? Token baru akan aktif selama 10 menit ke depan.'
                        )
                        ->modalSubmitActionLabel('Rilis Token Sekarang')
                        ->visible(fn (Ujian $record): bool => $record->status === 'ONGOING' && ! $record->isJadwalBerakhir())
                        ->action(function (Ujian $record) {
                            if ($record->isJadwalBerakhir()) {
                                Notification::make()
                                    ->title('Waktu Jadwal Ujian Telah Habis')
                                    ->body('Rilis token telah ditutup karena melewati batas jadwal ujian.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            $newToken = $record->refreshToken();

                            Notification::make()
                                ->title('Token Ujian Berhasil Digenerate!')
                                ->body("TOKEN BARU (BERLAKU 10 MENIT):\n\n# **{$newToken}**\n\nToken aktif selama 10 menit ke depan. Bagikan kode ini kepada siswa di ruangan ujian.")
                                ->success()
                                ->persistent()
                                ->send();
                        }),

                    // ACTION 3: SELESAI / TUTUP UJIAN
                    Action::make('selesai_ujian')
                        ->label('Tutup Ujian')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tutup dan Selesaikan Ujian?')
                        ->modalDescription('Status akan diubah menjadi COMPLETED. Siswa tidak dapat lagi memulai pengerjaan soal dengan token ini.')
                        ->modalSubmitActionLabel('Selesaikan Ujian')
                        ->visible(fn (Ujian $record): bool => $record->status === 'ONGOING')
                        ->action(function (Ujian $record) {
                            $record->finishExam();

                            Notification::make()
                                ->title('Ujian Selesai')
                                ->body('Status ujian telah diubah menjadi COMPLETED.')
                                ->info()
                                ->send();
                        }),

                    // ACTION: JADWAL RUANG & PENGAWAS SILANG
                    Action::make('jadwalRuang')
                        ->label('Jadwal Ruang & Pengawas')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->color('warning')
                        ->url(fn (Ujian $record): string => JadwalRuangResource::getUrl('index', [
                            'tableFilters' => [
                                'ujian_id' => [
                                'value' => $record->id,
                                ],
                            ],
                        ])),

                    // ACTION 4: SINKRONKAN PESERTA DARI KELAS
                    Action::make('sync_peserta')
                        ->label('Tarik Siswa Kelas')
                        ->icon(Heroicon::OutlinedUserGroup)
                        ->color('gray')
                        ->visible(fn (Ujian $record): bool => $record->status !== 'COMPLETED')
                        ->action(function (Ujian $record) {
                            $count = $record->syncPesertaFromKelas();
                            Notification::make()
                                ->title('Peserta Diperbarui')
                                ->body("Berhasil memperbarui daftar peserta dari rombel kelas ({$count} siswa baru ditambahkan).")
                                ->success()
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->label('Opsi')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('gray'),
            ])
            ->emptyStateHeading('Belum Ada Agenda Ujian')
            ->emptyStateDescription('Klik tombol "Buat Ujian Baru" untuk menjadwalkan pelaksanaan ujian bagi siswa.');
    }
}


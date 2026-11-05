<?php

namespace App\Filament\Resources\Ujians\Tables;

use App\Models\Ujian;
use Filament\Actions\Action;
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
                    ->label('TOKEN AKSES')
                    ->badge()
                    ->color('primary')
                    ->weight('heavy')
                    ->copyable()
                    ->copyMessage('Token disalin!')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state, Ujian $record): string =>
                        $record->status === 'ONGOING' && $state ? $state : '-'
                    )
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
            ->recordActions([
                // ACTION 1: MULAI UJIAN & GENERATE TOKEN
                Action::make('mulai_ujian')
                    ->label('Mulai Ujian')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mulai Pelaksanaan Ujian?')
                    ->modalDescription('Server akan mengubah status menjadi ONGOING dan menghasilkan 6 karakter Kode Akses Ujian (Token). Bagikan kode tersebut kepada siswa di kelas.')
                    ->modalSubmitActionLabel('Mulai Ujian Sekarang')
                    ->visible(fn (Ujian $record): bool =>
                        $record->status === 'SCHEDULED' || ($record->status === 'ONGOING' && empty($record->token_ujian))
                    )
                    ->action(function (Ujian $record) {
                        $token = $record->startExam();
                        // Pastikan siswa di kelas ter-sinkron
                        $synced = $record->syncPesertaFromKelas();

                        Notification::make()
                            ->title('Ujian Berhasil Dimulai! Status: ONGOING')
                            ->body("KODE TOKEN AKSES UJIAN:\n\n# **{$token}**\n\nSiswa dapat memasukkan kode ini di portal ujian masing-masing ({$synced} siswa tersinkron).")
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                // ACTION 2: REFRESH TOKEN
                Action::make('refresh_token')
                    ->label('Ganti Token')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Perbarui Kode Token Ujian?')
                    ->modalDescription('Apakah Anda yakin ingin mengganti kode token sesi ujian saat ini dengan kode baru?')
                    ->modalSubmitActionLabel('Generate Token Baru')
                    ->visible(fn (Ujian $record): bool => $record->status === 'ONGOING')
                    ->action(function (Ujian $record) {
                        $newToken = $record->refreshToken();

                        Notification::make()
                            ->title('Token Ujian Berhasil Diperbarui!')
                            ->body("TOKEN BARU:\n\n# **{$newToken}**")
                            ->warning()
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
            ->emptyStateHeading('Belum Ada Agenda Ujian')
            ->emptyStateDescription('Klik tombol "Buat Ujian Baru" untuk menjadwalkan pelaksanaan ujian bagi siswa.');
    }
}


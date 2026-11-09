<?php

namespace App\Filament\Resources\HasilUjians\Tables;

use App\Filament\Resources\HasilUjians\Pages\ViewHasilUjian;
use App\Models\HasilUjian;
use App\Models\Ujian;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HasilUjianTable
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
                    ->grow()
                    ->description(fn (HasilUjian $record): string => ($record->mapel?->nama_mapel ?? 'Mapel') . ' • ' . ($record->bankSoal?->judul ?? 'Bank Soal'))
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
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tanggal')
                    ->label('Pelaksanaan')
                    ->date('d M Y')
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
                        'SCHEDULED' => 'Dijadwalkan',
                        'ONGOING' => 'Berlangsung',
                        'COMPLETED' => 'Selesai',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('progres_peserta')
                    ->label('Partisipasi Siswa')
                    ->badge()
                    ->color('primary')
                    ->state(function (HasilUjian $record): string {
                        $total = $record->pesertas()->count();
                        $selesai = $record->pesertas()->where('status', 'SELESAI')->count();
                        return "{$selesai} / {$total} Selesai";
                    }),

                TextColumn::make('rata_rata_nilai')
                    ->label('Rata-rata')
                    ->badge()
                    ->color('success')
                    ->weight('bold')
                    ->placeholder('-')
                    ->state(fn (HasilUjian $record): ?string =>
                        $record->rata_rata_nilai !== null ? number_format($record->rata_rata_nilai, 1) : null
                    )
                    ->sortable(),

                TextColumn::make('rentang_nilai')
                    ->label('Min - Max')
                    ->placeholder('-')
                    ->state(function (HasilUjian $record): ?string {
                        $min = $record->nilai_terendah;
                        $max = $record->nilai_tertinggi;
                        if ($min === null && $max === null) return null;
                        return (float)$min . ' - ' . (float)$max;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipe_ujian')
                    ->label('Filter Tipe Asesmen')
                    ->options([
                        Ujian::TIPE_ASESMEN_HARIAN => 'Asesmen Harian',
                        Ujian::TIPE_TENGAH_SEMESTER => 'Tengah Semester',
                        Ujian::TIPE_AKHIR_SEMESTER => 'Akhir Semester',
                    ]),

                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'SCHEDULED' => 'Dijadwalkan',
                        'ONGOING' => 'Sedang Berlangsung',
                        'COMPLETED' => 'Selesai',
                    ]),

                SelectFilter::make('mapel_id')
                    ->label('Filter Mata Pelajaran')
                    ->relationship('mapel', 'nama_mapel'),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Rekap & Lembar Jawaban')
                    ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                    ->color('primary')
                    ->url(fn (HasilUjian $record): string => ViewHasilUjian::getUrl(['record' => $record])),

                Action::make('daftarHadir')
                    ->label('Daftar Hadir')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('warning')
                    ->url(fn (HasilUjian $record): string => route('daftar-hadir.print', ['ujian_id' => $record->id]))
                    ->openUrlInNewTab(true),

                Action::make('beritaAcara')
                    ->label('Berita Acara')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('info')
                    ->url(fn (HasilUjian $record): string => route('berita-acara.print', ['ujian_id' => $record->id]))
                    ->openUrlInNewTab(true),

                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->url(fn (HasilUjian $record): string => route('hasil-ujian.export-csv', $record->id))
                    ->openUrlInNewTab(false),

                Action::make('cetak')
                    ->label('Cetak Rekap')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (HasilUjian $record): string => route('hasil-ujian.print', $record->id))
                    ->openUrlInNewTab(true),
            ])
            ->emptyStateHeading('Belum Ada Hasil Ujian')
            ->emptyStateDescription('Hasil nilai siswa akan otomatis masuk dan terekap di sini begitu siswa menyelesaikan pengerjaan ujian.');
    }
}


<?php

namespace App\Filament\Resources\KelasMapel\Tables;

use App\Models\GuruPengampu;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KelasMapelTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas / Rombel')
                    ->badge()
                    ->color('warning')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kelas.tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('kelas.jurusan')
                    ->label('Jurusan')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('mapel.nama_mapel')
                    ->label('Mata Pelajaran yang Diampu')
                    ->weight('bold')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mapel.kode_mapel')
                    ->label('Kode Mapel')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('kelas.jumlah_siswa')
                    ->label('Jumlah Siswa')
                    ->badge()
                    ->color('success')
                    ->icon(Heroicon::OutlinedUsers)
                    ->formatStateUsing(fn ($state): string => ($state ?? 0) . ' Siswa')
                    ->sortable(),

                TextColumn::make('kelas.wali_kelas')
                    ->label('Wali Kelas')
                    ->icon(Heroicon::OutlinedUser)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('user.school_name')
                    ->label('Asal Sekolah')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('kelas_id', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Belum Ada Penugasan Kelas & Mapel')
            ->emptyStateDescription('Data kelas, mata pelajaran, dan siswa yang Anda ampu otomatis disinkronkan dari Web Absensi.');
    }
}


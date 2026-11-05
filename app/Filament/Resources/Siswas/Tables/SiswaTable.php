<?php

namespace App\Filament\Resources\Siswas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SiswaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('NIS disalin'),

                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas / Rombel')
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.email')
                    ->label('Akun Login CBT')
                    ->placeholder('Belum dibuat')
                    ->badge()
                    ->color('success')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('no_wa')
                    ->label('No. WhatsApp')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('school_name')
                    ->label('Sekolah')
                    ->badge()
                    ->color('gray')
                    ->placeholder('CBT Pusat')
                    ->searchable(),

                TextColumn::make('sync_id')
                    ->label('ID Absensi')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Disinkronkan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'nama_kelas'),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Hanya Siswa Aktif')
                    ->falseLabel('Hanya Siswa Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}


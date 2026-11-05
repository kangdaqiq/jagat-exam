<?php

namespace App\Filament\Resources\Mapels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MapelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_mapel')
                    ->label('Nama Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('kode')
                    ->label('Kode Mapel')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('school_name')
                    ->label('Sekolah')
                    ->badge()
                    ->color('info')
                    ->placeholder('Lokal CBT')
                    ->searchable(),

                TextColumn::make('gurus_count')
                    ->counts('gurus')
                    ->label('Guru Pengampu')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('sync_id')
                    ->label('ID Absensi')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
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

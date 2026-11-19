<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('nip')
                    ->label('NIP / ID Guru')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Alamat email disalin')
                    ->toggleable(),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'guru' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'guru' => 'Guru',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('No. WhatsApp')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('school_name')
                    ->label('Sekolah')
                    ->badge()
                    ->color('info')
                    ->placeholder('CBT Pusat')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('mapels_count')
                    ->counts('mapels')
                    ->label('Mapel Diampu')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('kelases_count')
                    ->counts('kelases')
                    ->label('Kelas Diajar')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sync_id')
                    ->label('ID Absensi')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Peran')
                    ->options([
                        'admin' => 'Administrator',
                        'guru' => 'Guru',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Hanya Akun Aktif')
                    ->falseLabel('Hanya Akun Nonaktif'),
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

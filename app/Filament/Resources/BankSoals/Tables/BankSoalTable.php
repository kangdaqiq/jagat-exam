<?php

namespace App\Filament\Resources\BankSoals\Tables;

use App\Models\BankSoal;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankSoalTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')
                    ->label('Judul Bank Soal')
                    ->weight('bold')
                    ->grow()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('kode_bank')
                    ->label('Kode Soal')
                    ->badge()
                    ->color('info')
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Kode soal disalin')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('mapel.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('primary')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('kelases.nama_kelas')
                    ->label('Target Rombel')
                    ->badge()
                    ->color('warning')
                    ->separator(', ')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('soals_count')
                    ->label('Jumlah Butir')
                    ->counts('soals')
                    ->badge()
                    ->color('success')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->formatStateUsing(fn ($state): string => ($state ?? 0) . ' Soal')
                    ->toggleable(),

                TextColumn::make('total_bobot')
                    ->label('Target Bobot')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' Poin')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('Pembuat')
                    ->icon(Heroicon::OutlinedUser)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('link_preview')
                    ->label('Link Preview')
                    ->getStateUsing(fn (BankSoal $record): string => route('bank-soal.preview-guru', ['bankSoal' => $record->id]))
                    ->icon(Heroicon::OutlinedLink)
                    ->color('danger')
                    ->copyable()
                    ->copyMessage('Link preview disalin')
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('previewGuru')
                    ->label('Preview Guru')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('danger')
                    ->url(fn (BankSoal $record): string => route('bank-soal.preview-guru', ['bankSoal' => $record->id]))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label('Kelola Soal')
                    ->icon(Heroicon::OutlinedPencilSquare),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Belum Ada Bank Soal')
            ->emptyStateDescription('Klik tombol "Buat Bank Soal Baru" untuk mulai membuat paket ujian dan menginput butir soal.');
    }
}


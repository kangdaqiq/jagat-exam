<?php

namespace App\Filament\Resources\BankSoals\Pages;

use App\Filament\Resources\BankSoals\BankSoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListBankSoals extends ListRecords
{
    protected static string $resource = BankSoalResource::class;

    protected static ?string $title = 'Bank Soal Ujian';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Bank Soal Baru')
                ->icon(Heroicon::OutlinedPlusCircle),
        ];
    }
}


<?php

namespace App\Filament\Resources\Ujians\Pages;

use App\Filament\Resources\Ujians\UjianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUjians extends ListRecords
{
    protected static string $resource = UjianResource::class;

    protected static ?string $title = 'Jadwal & Sesi Pelaksanaan Ujian';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Jadwalkan Ujian Baru')
                ->icon(Heroicon::OutlinedPlusCircle),
        ];
    }
}


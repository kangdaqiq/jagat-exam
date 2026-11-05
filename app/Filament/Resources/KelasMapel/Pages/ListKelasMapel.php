<?php

namespace App\Filament\Resources\KelasMapel\Pages;

use App\Filament\Resources\KelasMapel\KelasMapelResource;
use Filament\Resources\Pages\ListRecords;

class ListKelasMapel extends ListRecords
{
    protected static string $resource = KelasMapelResource::class;

    protected static ?string $title = 'Kelas & Mapel yang Diampu';

    protected function getHeaderActions(): array
    {
        return [
            // Guru hanya membaca data penugasan, tidak ada create manual
        ];
    }
}


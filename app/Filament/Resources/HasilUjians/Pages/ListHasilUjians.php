<?php

namespace App\Filament\Resources\HasilUjians\Pages;

use App\Filament\Resources\HasilUjians\HasilUjianResource;
use Filament\Resources\Pages\ListRecords;

class ListHasilUjians extends ListRecords
{
    protected static string $resource = HasilUjianResource::class;

    protected static ?string $title = 'Rekapitulasi & Hasil Nilai Ujian';
}


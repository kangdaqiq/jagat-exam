<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalRuang extends CreateRecord
{
    protected static string $resource = JadwalRuangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


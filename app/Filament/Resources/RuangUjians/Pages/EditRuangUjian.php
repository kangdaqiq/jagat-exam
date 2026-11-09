<?php

namespace App\Filament\Resources\RuangUjians\Pages;

use App\Filament\Resources\RuangUjians\RuangUjianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRuangUjian extends EditRecord
{
    protected static string $resource = RuangUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}


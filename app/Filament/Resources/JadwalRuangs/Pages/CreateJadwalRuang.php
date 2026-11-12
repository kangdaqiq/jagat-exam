<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalRuang extends CreateRecord
{
    protected static string $resource = JadwalRuangResource::class;

    protected function afterCreate(): void
    {
        $orderedIds = array_values(array_filter((array) ($this->data['ujians'] ?? [])));
        if (! empty($orderedIds)) {
            $this->record->syncOrderedUjians($orderedIds, true);
        } else {
            $firstUjianId = $this->record->ujians()->first()?->id;
            if ($firstUjianId && $this->record->ujian_id !== $firstUjianId) {
                $this->record->updateQuietly(['ujian_id' => $firstUjianId]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJadwalRuang extends EditRecord
{
    protected static string $resource = JadwalRuangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->ujian_id && $this->record->ujians()->doesntExist()) {
            $this->record->ujians()->syncWithoutDetaching([$this->record->ujian_id]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $orderedIds = array_values(array_filter((array) ($this->data['ujians'] ?? [])));
        if (! empty($orderedIds)) {
            $this->record->syncOrderedUjians($orderedIds, true);
        } else {
            $firstUjianId = $this->record->ujians()->first()?->id;
            if ($firstUjianId !== $this->record->ujian_id) {
                $this->record->updateQuietly(['ujian_id' => $firstUjianId]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


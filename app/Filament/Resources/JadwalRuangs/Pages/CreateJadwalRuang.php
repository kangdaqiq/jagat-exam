<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Filament\Resources\JadwalRuangs\Schemas\JadwalRuangForm;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalRuang extends CreateRecord
{
    protected static string $resource = JadwalRuangResource::class;

    protected array $subtestStages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->subtestStages = JadwalRuangForm::extractSubtestStages($data['daftar_subtest'] ?? []);
        unset($data['daftar_subtest']);

        $firstId = $this->subtestStages[0][0] ?? null;
        if ($firstId) {
            $data['ujian_id'] = $firstId;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->subtestStages)) {
            $this->record->syncSubtestStages($this->subtestStages, true);
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


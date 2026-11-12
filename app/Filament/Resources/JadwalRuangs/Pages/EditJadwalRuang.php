<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Filament\Resources\JadwalRuangs\Schemas\JadwalRuangForm;
use App\Models\Ujian;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJadwalRuang extends EditRecord
{
    protected static string $resource = JadwalRuangResource::class;

    protected array $subtestStages = [];

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

        $data['daftar_subtest'] = $this->record->getAllUjians()
            ->groupBy(fn (Ujian $u) => (int) ($u->pivot?->urutan ?? 1))
            ->sortKeys()
            ->map(fn ($group) => ['ujian_ids' => $group->pluck('id')->values()->all()])
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->subtestStages = JadwalRuangForm::extractSubtestStages($data['daftar_subtest'] ?? []);
        unset($data['daftar_subtest']);

        $firstId = $this->subtestStages[0][0] ?? null;
        if ($firstId) {
            $data['ujian_id'] = $firstId;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! empty($this->subtestStages)) {
            $this->record->syncSubtestStages($this->subtestStages, true);
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


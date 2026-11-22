<?php

namespace App\Filament\Resources\JadwalRuangs\Pages;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Filament\Resources\JadwalRuangs\Schemas\JadwalRuangForm;
use App\Models\RuangUjian;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJadwalRuang extends CreateRecord
{
    protected static string $resource = JadwalRuangResource::class;

    protected array $subtestStages = [];
    protected bool $isBatchCreated = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->subtestStages = JadwalRuangForm::extractSubtestStages($data['daftar_subtest'] ?? []);
        unset($data['daftar_subtest']);

        $firstId = $this->subtestStages[0][0] ?? null;
        $data['ujian_id'] = $firstId ?: null;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $applyAll = (bool) ($data['apply_all_rooms'] ?? false);
        unset($data['apply_all_rooms']);

        if (! $applyAll) {
            return parent::handleRecordCreation($data);
        }

        $ruangs = RuangUjian::where('is_active', true)->orderBy('nama_ruang')->get();
        if ($ruangs->isEmpty()) {
            return parent::handleRecordCreation($data);
        }

        $firstRecord = null;
        $count = 0;
        foreach ($ruangs as $ruang) {
            $roomData = $data;
            $roomData['ruang_ujian_id'] = $ruang->id;
            if (empty($roomData['pengawas_id']) && $ruang->pengawas_id) {
                $roomData['pengawas_id'] = $ruang->pengawas_id;
            }

            $record = static::getModel()::create($roomData);
            if (! empty($this->subtestStages)) {
                $record->syncSubtestStages($this->subtestStages, true);
            }
            if (! $firstRecord) {
                $firstRecord = $record;
            }
            $count++;
        }

        $this->isBatchCreated = true;

        Notification::make()
            ->title('Jadwal Berhasil Diterapkan ke Semua Ruang')
            ->body("Sesi berhasil dibuat untuk {$count} ruang ujian aktif (Mode ANBK).")
            ->success()
            ->send();

        return $firstRecord;
    }

    protected function afterCreate(): void
    {
        if ($this->isBatchCreated) {
            return;
        }

        if (! empty($this->subtestStages)) {
            $this->record->syncSubtestStages($this->subtestStages, true);
        } else {
            $this->record->ujians()->detach();
            $this->record->updateQuietly(['ujian_id' => null]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


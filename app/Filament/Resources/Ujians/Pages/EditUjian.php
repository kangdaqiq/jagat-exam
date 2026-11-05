<?php

namespace App\Filament\Resources\Ujians\Pages;

use App\Filament\Resources\Ujians\UjianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditUjian extends EditRecord
{
    protected static string $resource = UjianResource::class;

    protected static ?string $title = 'Kelola Jadwal & Sesi Ujian';

    protected Width | string | null $maxContentWidth = Width::SevenExtraLarge;

    protected function afterSave(): void
    {
        // Perbarui peserta jika ada penambahan/perubahan kelas
        $this->getRecord()->syncPesertaFromKelas();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}


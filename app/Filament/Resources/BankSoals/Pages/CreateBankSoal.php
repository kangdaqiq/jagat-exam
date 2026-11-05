<?php

namespace App\Filament\Resources\BankSoals\Pages;

use App\Filament\Resources\BankSoals\BankSoalResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreateBankSoal extends CreateRecord
{
    protected static string $resource = BankSoalResource::class;

    protected static ?string $title = 'Buat Bank Soal';

    protected Width | string | null $maxContentWidth = Width::SevenExtraLarge;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        if (empty($data['kode_bank'])) {
            $data['kode_bank'] = \App\Models\BankSoal::generateKodeBank();
        }
        return $data;
    }

    /** Setelah paket dibuat, langsung ke studio kelola butir soal. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Paket bank soal dibuat. Silakan tambahkan butir soal.';
    }
}

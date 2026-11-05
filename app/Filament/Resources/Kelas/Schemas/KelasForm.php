<?php

namespace App\Filament\Resources\Kelas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_kelas')
                    ->label('Nama Rombel / Kelas')
                    ->placeholder('Contoh: X-PPLG-1, XII-MIPA-2')
                    ->required()
                    ->maxLength(255),

                TextInput::make('tingkat')
                    ->label('Tingkat / Jenjang')
                    ->placeholder('Contoh: X, XI, XII atau 10, 11, 12')
                    ->maxLength(50),

                TextInput::make('jurusan')
                    ->label('Jurusan / Peminatan')
                    ->placeholder('Contoh: PPLG, Teknik Otomotif')
                    ->maxLength(100),

                TextInput::make('wali_kelas')
                    ->label('Wali Kelas')
                    ->placeholder('Nama Guru Wali Kelas')
                    ->maxLength(255),

                TextInput::make('school_name')
                    ->label('Sekolah Asal')
                    ->placeholder('Otomatis terisi dari Web Absensi')
                    ->disabled(),

                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true),
            ]);
    }
}

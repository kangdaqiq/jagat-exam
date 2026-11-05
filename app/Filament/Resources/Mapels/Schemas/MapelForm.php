<?php

namespace App\Filament\Resources\Mapels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MapelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_mapel')
                    ->label('Nama Mata Pelajaran')
                    ->placeholder('Contoh: Matematika, Bahasa Indonesia')
                    ->required()
                    ->maxLength(255),

                TextInput::make('kode')
                    ->label('Kode Mapel')
                    ->placeholder('Contoh: MTK-10, BIND-11')
                    ->maxLength(50),

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

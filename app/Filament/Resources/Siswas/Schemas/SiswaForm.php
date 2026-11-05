<?php

namespace App\Filament\Resources\Siswas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nis')
                    ->label('NIS / Nomor Induk Siswa')
                    ->placeholder('Contoh: 10234')
                    ->required()
                    ->maxLength(50),

                TextInput::make('nama')
                    ->label('Nama Lengkap Siswa')
                    ->placeholder('Contoh: Muhammad Rizki')
                    ->required()
                    ->maxLength(255),

                Select::make('kelas_id')
                    ->label('Kelas / Rombel')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Rombongan Belajar'),

                TextInput::make('no_wa')
                    ->label('Nomor WhatsApp / HP Siswa')
                    ->placeholder('08123456789')
                    ->tel()
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


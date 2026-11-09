<?php

namespace App\Filament\Resources\RuangUjians;

use App\Filament\Resources\RuangUjians\Pages\CreateRuangUjian;
use App\Filament\Resources\RuangUjians\Pages\EditRuangUjian;
use App\Filament\Resources\RuangUjians\Pages\ListRuangUjians;
use App\Filament\Resources\RuangUjians\Schemas\RuangUjianForm;
use App\Filament\Resources\RuangUjians\Tables\RuangUjianTable;
use App\Models\RuangUjian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RuangUjianResource extends Resource
{
    protected static ?string $model = RuangUjian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $modelLabel = 'Ruang Ujian';

    protected static ?string $pluralModelLabel = 'Ruang Ujian & Token';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nama_ruang';

    public static function form(Schema $schema): Schema
    {
        return RuangUjianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RuangUjianTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user ? ($user->isAdmin() || $user->isGuru()) : false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRuangUjians::route('/'),
            'create' => CreateRuangUjian::route('/create'),
            'edit' => EditRuangUjian::route('/{record}/edit'),
        ];
    }
}


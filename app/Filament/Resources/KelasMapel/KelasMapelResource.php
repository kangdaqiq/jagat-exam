<?php

namespace App\Filament\Resources\KelasMapel;

use App\Filament\Resources\KelasMapel\Pages\ListKelasMapel;
use App\Filament\Resources\KelasMapel\Tables\KelasMapelTable;
use App\Models\GuruPengampu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class KelasMapelResource extends Resource
{
    protected static ?string $model = GuruPengampu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Kelas & Mapel';

    protected static ?string $modelLabel = 'Kelas & Mapel';

    protected static ?string $pluralModelLabel = 'Kelas & Mapel yang Diampu';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master CBT';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->isGuru() || $user->isAdmin());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with(['kelas', 'mapel', 'user']);

        // Jika Guru, filter hanya penugasan guru tersebut
        if ($user && $user->isGuru()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return KelasMapelTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKelasMapel::route('/'),
        ];
    }
}


<?php

namespace App\Filament\Resources\Ujians;

use App\Filament\Resources\Ujians\Pages\CreateUjian;
use App\Filament\Resources\Ujians\Pages\EditUjian;
use App\Filament\Resources\Ujians\Pages\ListUjians;
use App\Filament\Resources\Ujians\Pages\PengawasanUjian;
use App\Filament\Resources\Ujians\RelationManagers\PesertasRelationManager;
use App\Filament\Resources\Ujians\Schemas\UjianForm;
use App\Filament\Resources\Ujians\Tables\UjianTable;
use App\Models\Ujian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UjianResource extends Resource
{
    protected static ?string $model = Ujian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Pelaksanaan Ujian';

    protected static ?string $modelLabel = 'Jadwal & Sesi Ujian';

    protected static ?string $pluralModelLabel = 'Jadwal & Sesi Ujian';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_ujian';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin', 'guru']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forUser(Auth::user())
            ->with(['mapel', 'bankSoal', 'guru', 'kelas'])
            ->withCount('pesertas');
    }

    public static function form(Schema $schema): Schema
    {
        return UjianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UjianTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PesertasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUjians::route('/'),
            'create' => CreateUjian::route('/create'),
            'edit' => EditUjian::route('/{record}/edit'),
            'pengawasan' => PengawasanUjian::route('/{record}/pengawasan'),
        ];
    }
}


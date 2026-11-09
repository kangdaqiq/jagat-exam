<?php

namespace App\Filament\Resources\BankSoals;

use App\Filament\Resources\BankSoals\Pages\CreateBankSoal;
use App\Filament\Resources\BankSoals\Pages\EditBankSoal;
use App\Filament\Resources\BankSoals\Pages\ListBankSoals;
use App\Filament\Resources\BankSoals\RelationManagers\SoalsRelationManager;
use App\Filament\Resources\BankSoals\Schemas\BankSoalForm;
use App\Filament\Resources\BankSoals\Tables\BankSoalTable;
use App\Models\BankSoal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BankSoalResource extends Resource
{
    protected static ?string $model = BankSoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Bank Soal';

    protected static ?string $modelLabel = 'Bank Soal';

    protected static ?string $pluralModelLabel = 'Bank Soal Ujian';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'judul';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin', 'guru']);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;

        // Guru hanya boleh melihat/mengakses bank soal miliknya sendiri
        return (int) $record->user_id === (int) $user->id;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;

        // Guru hanya boleh edit bank soal miliknya sendiri
        return (int) $record->user_id === (int) $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;

        return (int) $record->user_id === (int) $user->id;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forUser(Auth::user())
            ->with(['mapel', 'kelases', 'user']);
    }

    public static function form(Schema $schema): Schema
    {
        return BankSoalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankSoalTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Seluruh butir soal dikelola langsung di Studio 2-Pane (EditBankSoal)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankSoals::route('/'),
            'create' => CreateBankSoal::route('/create'),
            'edit' => EditBankSoal::route('/{record}/edit'),
        ];
    }
}


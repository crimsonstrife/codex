<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionSetResource\Pages;
use App\Filament\Resources\PermissionSetResource\Schemas\PermissionSetForm;
use App\Filament\Resources\PermissionSetResource\Tables\PermissionSetsTable;
use App\Models\PermissionSet;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PermissionSetResource extends Resource
{
    protected static ?string $model = PermissionSet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Security';

    protected static ?string $navigationLabel = 'Permission Sets';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PermissionSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionSetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissionSets::route('/'),
            'create' => Pages\CreatePermissionSet::route('/create'),
            'edit' => Pages\EditPermissionSet::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canManage() || (auth()->user()?->can('permission_sets.view') ?? false);
    }

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManage();
    }

    protected static function canManage(): bool
    {
        return auth()->user()?->can('permission_sets.manage') ?? false;
    }
}

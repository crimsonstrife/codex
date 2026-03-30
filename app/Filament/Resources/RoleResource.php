<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\Schemas\RoleForm;
use App\Filament\Resources\RoleResource\Tables\RolesTable;
use App\Models\Role;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Security';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canManage() || (auth()->user()?->can('roles.view') ?? false);
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
        return auth()->user()?->can('roles.manage') ?? false;
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\Schemas\PermissionForm;
use App\Filament\Resources\PermissionResource\Tables\PermissionsTable;
use App\Models\Permission;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Security';

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PermissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canManage() || (auth()->user()?->can('permissions.view') ?? false);
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
        return auth()->user()?->can('permissions.manage') ?? false;
    }
}

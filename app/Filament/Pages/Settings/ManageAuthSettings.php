<?php

namespace App\Filament\Pages\Settings;

use App\Settings\AuthSettings;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageAuthSettings extends SettingsPage
{
    protected static string $settings = AuthSettings::class;

    protected static ?string $title = 'Authentication';

    protected static ?string $navigationLabel = 'Authentication';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'settings/authentication';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Registration')
                ->schema([
                    Toggle::make('allowRegistration')
                        ->label('Allow self-registration')
                        ->helperText('When disabled, only existing accounts and admin-created users can sign in.'),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function canEdit(): bool
    {
        return static::canAccess();
    }
}

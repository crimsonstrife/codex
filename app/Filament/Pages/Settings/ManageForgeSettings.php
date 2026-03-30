<?php

namespace App\Filament\Pages\Settings;

use App\Settings\ForgeSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ManageForgeSettings extends SettingsPage
{
    protected static string $settings = ForgeSettings::class;

    protected static ?string $title = 'Forge';

    protected static ?string $navigationLabel = 'Forge';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'settings/forge';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Integration')
                ->description('Forge OAuth client IDs, client secrets, and machine-to-machine credentials remain environment-managed in v1.')
                ->schema([
                    Toggle::make('enabled')
                        ->label('Enable Forge integration')
                        ->helperText('Controls Forge SSO, workspace linking, and cross-app navigation.'),
                    TextInput::make('url')
                        ->label('Forge URL')
                        ->url()
                        ->placeholder('https://forge.example.com')
                        ->required(fn (Get $get): bool => (bool) $get('enabled')),
                    Toggle::make('disableTlsVerification')
                        ->label('Disable TLS verification')
                        ->helperText('Only enable this for trusted Forge instances using self-signed certificates.'),
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

<?php

namespace App\Filament\Pages\Settings;

use App\Settings\CodexSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageCodexSettings extends SettingsPage
{
    protected static string $settings = CodexSettings::class;

    protected static ?string $title = 'Codex';

    protected static ?string $navigationLabel = 'Codex';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'settings/codex';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Editor Defaults')
                ->schema([
                    Select::make('defaultPageContentType')
                        ->label('Default page editor')
                        ->options([
                            'markdown' => 'Markdown',
                            'richtext' => 'Rich Text',
                        ])
                        ->required(),
                ]),
            Section::make('Diagram Editor')
                ->schema([
                    TextInput::make('drawioUrl')
                        ->label('draw.io URL')
                        ->url()
                        ->required()
                        ->helperText('Used for embedded draw.io diagram create, edit, and preview flows.'),
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

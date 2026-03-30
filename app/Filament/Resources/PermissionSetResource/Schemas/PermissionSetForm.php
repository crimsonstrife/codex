<?php

namespace App\Filament\Resources\PermissionSetResource\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PermissionSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Permission Set Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('permissions')
                        ->options(fn (): array => Permission::query()->orderBy('name')->pluck('name', 'name')->all())
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Permission sets store permission names and can be assigned directly to users.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}

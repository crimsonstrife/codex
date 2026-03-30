<?php

namespace App\Filament\Resources\PermissionResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Permission Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('guard_name')
                        ->default('web')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),
        ]);
    }
}

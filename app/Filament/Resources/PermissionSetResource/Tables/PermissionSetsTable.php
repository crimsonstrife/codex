<?php

namespace App\Filament\Resources\PermissionSetResource\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class PermissionSetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(80)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('permissions')
                    ->label('Permissions')
                    ->formatStateUsing(fn (?array $state): string => implode(', ', $state ?? []))
                    ->wrap(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

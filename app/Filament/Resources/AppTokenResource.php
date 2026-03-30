<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppTokenResource\Pages;
use App\Models\AppToken;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppTokenResource extends Resource
{
    protected static ?string $model = AppToken::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'App Tokens';

    protected static ?string $modelLabel = 'App Token';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('Abilities')
                    ->formatStateUsing(static fn (mixed $state): string => static::formatAbilities($state))
                    ->wrap(),
                Tables\Columns\TextColumn::make('token')
                    ->label('Token Hash')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                DeleteAction::make()
                    ->label('Revoke')
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppTokens::route('/'),
        ];
    }

    protected static function formatAbilities(mixed $state): string
    {
        if (is_string($state)) {
            $decoded = json_decode($state, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $state = $decoded;
            }
        }

        if (! is_array($state)) {
            return blank($state) ? '' : (string) $state;
        }

        return implode(', ', array_values(array_filter(
            array_map(static fn (mixed $ability): string => trim((string) $ability), $state),
            static fn (string $ability): bool => $ability !== '',
        )));
    }
}

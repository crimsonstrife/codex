<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use App\Models\AppToken;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAppTokens extends ListRecords
{
    protected static string $resource = AppTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label('Create App Token')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create App Token')
                ->modalSubmitActionLabel('Create Token')
                ->form([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Forge'),
                    TagsInput::make('abilities')
                        ->default(['*'])
                        ->helperText('Use * for full access. You can also enter scoped abilities for future integrations.'),
                ])
                ->action(function (array $data): void {
                    $abilities = array_values(array_unique(array_filter(
                        array_map(static fn (string $ability): string => trim($ability), $data['abilities'] ?? ['*']),
                        static fn (string $ability): bool => $ability !== ''
                    )));

                    if ($abilities === []) {
                        $abilities = ['*'];
                    }

                    ['plaintext' => $plaintext, 'token' => $token] = AppToken::generate(
                        (string) $data['name'],
                        $abilities,
                    );

                    Notification::make()
                        ->title('App token created — copy it now')
                        ->body("App Token: {$plaintext}\n\nName: {$token->name}\nAbilities: ".implode(', ', $token->abilities)."\n\nThis value will not be shown again. Store it as CODEX_APP_TOKEN in Forge.")
                        ->warning()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}

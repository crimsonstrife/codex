<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiagramResource\Pages;
use App\Models\Diagram;
use App\Services\DiagramDeletionGuard;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class DiagramResource extends Resource
{
    protected static ?string $model = Diagram::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Diagram Details')->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Select::make('workspace_id')
                    ->label('Workspace')
                    ->relationship('workspace', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('author_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('diagram_type')
                    ->options([
                        'mermaid' => '🧩 Mermaid — Native (no external service)',
                        'drawio' => '🎨 draw.io — Visual editor',
                        'mindmap' => '🧠 Mind Map — Native (Mermaid)',
                        'flowchart' => '🔀 Flowchart — Native (Mermaid)',
                    ])
                    ->default('mermaid')
                    ->required(),
                Toggle::make('is_published')
                    ->label('Published'),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('workspace.name')->label('Workspace')->sortable(),
                Tables\Columns\TextColumn::make('author.name')->label('Author'),
                Tables\Columns\TextColumn::make('diagram_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mermaid' => 'success',
                        'drawio' => 'primary',
                        'mindmap' => 'info',
                        'flowchart' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('page_embeds_count')
                    ->counts('pageEmbeds')
                    ->label('Embedded In')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('workspace')
                    ->relationship('workspace', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                static::getDeleteAction(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    static::getDeleteBulkAction(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiagrams::route('/'),
            'create' => Pages\CreateDiagram::route('/create'),
            'edit' => Pages\EditDiagram::route('/{record}/edit'),
        ];
    }

    public static function getDeleteAction(): Actions\DeleteAction
    {
        return Actions\DeleteAction::make()
            ->before(function (Actions\DeleteAction $action, Diagram $record): void {
                $guard = app(DiagramDeletionGuard::class);

                if ($guard->canDelete($record)) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title('Diagram is still embedded')
                    ->body($guard->blockingMessage($record))
                    ->persistent()
                    ->send();

                $action->halt();
            });
    }

    public static function getDeleteBulkAction(): Actions\DeleteBulkAction
    {
        return Actions\DeleteBulkAction::make()
            ->before(function (Actions\DeleteBulkAction $action, EloquentCollection $records): void {
                $guard = app(DiagramDeletionGuard::class);
                $blockedCount = $records
                    ->filter(fn (Diagram $record): bool => ! $guard->canDelete($record))
                    ->count();

                if ($blockedCount < 1) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title('Some diagrams are still embedded')
                    ->body('Remove embedded diagrams from pages before bulk deleting them.')
                    ->persistent()
                    ->send();

                $action->halt();
            });
    }
}

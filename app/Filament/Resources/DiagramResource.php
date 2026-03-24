<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiagramResource\Pages;
use App\Models\Diagram;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                        'mermaid'   => '🧩 Mermaid — Native (no external service)',
                        'drawio'    => '🎨 draw.io — Visual editor',
                        'mindmap'   => '🧠 Mind Map — Native (Mermaid)',
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
                        'mermaid'   => 'success',
                        'drawio'    => 'primary',
                        'mindmap'   => 'info',
                        'flowchart' => 'warning',
                        default     => 'gray',
                    }),
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
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListDiagrams::route('/'),
            'create' => Pages\CreateDiagram::route('/create'),
            'edit'   => Pages\EditDiagram::route('/{record}/edit'),
        ];
    }
}

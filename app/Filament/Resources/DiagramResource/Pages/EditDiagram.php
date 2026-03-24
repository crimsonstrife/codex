<?php

namespace App\Filament\Resources\DiagramResource\Pages;

use App\Filament\Resources\DiagramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiagram extends EditRecord
{
    protected static string $resource = DiagramResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}

<?php

namespace App\Filament\Resources\DiagramResource\Pages;

use App\Filament\Resources\DiagramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDiagrams extends ListRecords
{
    protected static string $resource = DiagramResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

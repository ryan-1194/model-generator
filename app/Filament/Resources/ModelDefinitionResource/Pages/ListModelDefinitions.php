<?php

namespace App\Filament\Resources\ModelDefinitionResource\Pages;

use App\Filament\Resources\ModelDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModelDefinitions extends ListRecords
{
    protected static string $resource = ModelDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

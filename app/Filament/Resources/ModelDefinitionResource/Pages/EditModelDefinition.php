<?php

namespace App\Filament\Resources\ModelDefinitionResource\Pages;

use App\Filament\Resources\ModelDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModelDefinition extends EditRecord
{
    protected static string $resource = ModelDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

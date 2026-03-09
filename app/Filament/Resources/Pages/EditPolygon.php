<?php

namespace App\Filament\Resources\Polygons\Pages;

use App\Filament\Resources\Polygons\PolygonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPolygon extends EditRecord
{
    protected static string $resource = PolygonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

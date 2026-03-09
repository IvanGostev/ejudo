<?php

namespace App\Filament\Resources\Polygons\Pages;

use App\Filament\Resources\Polygons\PolygonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPolygons extends ListRecords
{
    protected static string $resource = PolygonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

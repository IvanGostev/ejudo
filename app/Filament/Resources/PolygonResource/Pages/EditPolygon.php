<?php

namespace App\Filament\Resources\PolygonResource\Pages;

use App\Filament\Resources\PolygonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolygon extends EditRecord
{
    protected static string $resource = PolygonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\FkkoCodeResource\Pages;

use App\Filament\Resources\FkkoCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFkkoCodes extends ListRecords
{
    protected static string $resource = FkkoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Добавить код'),
        ];
    }
}

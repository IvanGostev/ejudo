<?php

namespace App\Filament\Resources\WasteBalanceResource\Pages;

use App\Filament\Resources\WasteBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWasteBalances extends ListRecords
{
    protected static string $resource = WasteBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

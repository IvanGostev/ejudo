<?php

namespace App\Filament\Resources\WasteBalanceResource\Pages;

use App\Filament\Resources\WasteBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWasteBalance extends EditRecord
{
    protected static string $resource = WasteBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ActResource\Pages;

use App\Filament\Resources\ActResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAct extends EditRecord
{
    protected static string $resource = ActResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Удалить'),
        ];
    }

    public function getTitle(): string
    {
        return 'Редактирование Акт';
    }
}

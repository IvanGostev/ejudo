<?php

namespace App\Filament\Resources\FkkoCodeResource\Pages;

use App\Filament\Resources\FkkoCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFkkoCode extends EditRecord
{
    protected static string $resource = FkkoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Удалить'),
        ];
    }

    public function getTitle(): string
    {
        return 'Редактирование код ФККО';
    }
}

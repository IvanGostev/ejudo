<?php

namespace App\Filament\Resources\UserCompanyResource\Pages;

use App\Filament\Resources\UserCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserCompany extends EditRecord
{
    protected static string $resource = UserCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Удалить'),
        ];
    }

    public function getTitle(): string
    {
        return 'Редактирование Компания пользователя';
    }
}

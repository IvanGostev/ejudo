<?php

namespace App\Filament\Resources\UserCompanyResource\Pages;

use App\Filament\Resources\UserCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserCompanies extends ListRecords
{
    protected static string $resource = UserCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Новая компания'),
        ];
    }
}

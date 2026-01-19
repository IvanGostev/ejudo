<?php

namespace App\Filament\Resources\UserCompanyResource\Pages;

use App\Filament\Resources\UserCompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserCompany extends CreateRecord
{
    protected static string $resource = UserCompanyResource::class;

    public function getTitle(): string
    {
        return 'Новая компания';
    }
}

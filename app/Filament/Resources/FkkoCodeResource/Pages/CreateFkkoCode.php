<?php

namespace App\Filament\Resources\FkkoCodeResource\Pages;

use App\Filament\Resources\FkkoCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFkkoCode extends CreateRecord
{
    protected static string $resource = FkkoCodeResource::class;

    public function getTitle(): string
    {
        return 'Новый код ФККО';
    }
}

<?php

namespace App\Filament\Resources\ReferralEarningResource\Pages;

use App\Filament\Resources\ReferralEarningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralEarnings extends ListRecords
{
    protected static string $resource = ReferralEarningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

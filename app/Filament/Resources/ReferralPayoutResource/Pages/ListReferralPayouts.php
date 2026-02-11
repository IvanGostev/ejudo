<?php

namespace App\Filament\Resources\ReferralPayoutResource\Pages;

use App\Filament\Resources\ReferralPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralPayouts extends ListRecords
{
    protected static string $resource = ReferralPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}

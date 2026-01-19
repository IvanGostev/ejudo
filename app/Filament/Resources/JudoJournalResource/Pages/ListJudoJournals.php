<?php

namespace App\Filament\Resources\JudoJournalResource\Pages;

use App\Filament\Resources\JudoJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJudoJournals extends ListRecords
{
    protected static string $resource = JudoJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Новый журнал'),
        ];
    }
}

<?php

namespace App\Filament\Resources\JudoJournalResource\Pages;

use App\Filament\Resources\JudoJournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJudoJournal extends CreateRecord
{
    protected static string $resource = JudoJournalResource::class;

    public function getTitle(): string
    {
        return 'Новый журнал';
    }
}

<?php

namespace App\Filament\Resources\JudoJournalResource\Pages;

use App\Filament\Resources\JudoJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJudoJournal extends EditRecord
{
    protected static string $resource = JudoJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Удалить'),
        ];
    }

    public function getTitle(): string
    {
        return 'Редактирование Журнал';
    }
}

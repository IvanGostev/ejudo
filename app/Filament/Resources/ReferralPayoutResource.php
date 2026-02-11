<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralPayoutResource\Pages;
use App\Models\ReferralPayout;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralPayoutResource extends Resource
{
    protected static ?string $model = ReferralPayout::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'Заявка на выплату';
    protected static ?string $pluralModelLabel = 'Заявки на выплату';
    protected static ?string $navigationLabel = 'Выплаты реферальных';
    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная система';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'email')
                        ->getOptionLabelFromRecordUsing(fn(\App\Models\User $record) => $record->email ?? $record->phone ?? 'ID: ' . $record->id)
                        ->label('Пользователь')
                        ->disabled(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Сумма')
                        ->disabled(),
                    Forms\Components\TextInput::make('payment_method')
                        ->label('Метод')
                        ->disabled(),
                    Forms\Components\TextInput::make('payment_details')
                        ->label('Реквизиты')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                                'pending' => 'В обработке',
                                'completed' => 'Выполнено',
                                'cancelled' => 'Отклонено',
                            ])
                        ->required(),
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Заметка админа')
                        ->maxLength(65535),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('user.email')
                        ->label('Email')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('amount')
                        ->label('Сумма')
                        ->money('RUB')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'pending' => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        }),
                    Tables\Columns\TextColumn::make('payment_method')
                        ->label('Метод'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата заявки')
                        ->dateTime()
                        ->sortable(),
                ])
            ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                                'pending' => 'В обработке',
                                'completed' => 'Выполнено',
                                'cancelled' => 'Отклонено',
                            ]),
                ])
            ->actions([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\Action::make('mark_completed')
                        ->label('Выплачено')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn(ReferralPayout $record) => $record->update(['status' => 'completed']))
                        ->visible(fn(ReferralPayout $record) => $record->status === 'pending'),
                ])
            ->bulkActions([
                    \Filament\Actions\BulkActionGroup::make([
                        \Filament\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralPayouts::route('/'),
        ];
    }
}

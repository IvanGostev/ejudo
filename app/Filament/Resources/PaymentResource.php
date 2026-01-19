<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $modelLabel = 'Платеж';
    protected static ?string $pluralModelLabel = 'Платежи';
    protected static ?string $navigationLabel = 'Платежи';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Forms\Components\Select::make('user_id')
                        ->label('Пользователь')
                        ->relationship('user', 'phone'),
                    Forms\Components\Select::make('company_id')
                        ->label('Компания')
                        ->relationship('company', 'name'),
                    Forms\Components\TextInput::make('amount')
                        ->label('Сумма')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('status')
                        ->label('Статус')
                        ->required(),
                    Forms\Components\TextInput::make('transaction_id')
                        ->label('ID Транзакции'),
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Дата оплаты'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('user.phone')
                        ->label('Пользователь')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('amount')
                        ->label('Сумма')
                        ->money('RUB')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('paid_at')
                        ->label('Дата оплаты')
                        ->dateTime()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создан')
                        ->dateTime()
                        ->sortable(),
                ])
            ->filters([
                    //
                ])
            ->actions([
                    \Filament\Actions\EditAction::make()->label('Редактировать'),
                ])
            ->bulkActions([
                    \Filament\Actions\BulkActionGroup::make([
                        \Filament\Actions\DeleteBulkAction::make()->label('Удалить выбранные'),
                    ]),
                ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

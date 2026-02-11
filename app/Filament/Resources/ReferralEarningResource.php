<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralEarningResource\Pages;
use App\Models\ReferralEarning;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralEarningResource extends Resource
{
    protected static ?string $model = ReferralEarning::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $modelLabel = 'Начисление реферальных';
    protected static ?string $pluralModelLabel = 'Начисления реферальных';
    protected static ?string $navigationLabel = 'История начислений';
    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная система';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('user.email')
                        ->label('Кто заработал')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('referral.email')
                        ->label('От кого (Реферал)')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('amount')
                        ->label('Сумма')
                        ->money('RUB')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('percent')
                        ->label('%')
                        ->suffix('%'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата')
                        ->dateTime()
                        ->sortable(),
                ])
            ->filters([

                ])
            ->actions([

                ])
            ->bulkActions([

                ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralEarnings::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Пользователь';
    protected static ?string $pluralModelLabel = 'Пользователи';
    protected static ?string $navigationLabel = 'Пользователи';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('tariff')
                        ->label('Тариф')
                        ->options([
                                'free' => 'Бесплатный',
                                'paid' => 'Платный',
                            ])
                        ->required()
                        ->default('free'),
                    Forms\Components\Select::make('role')
                        ->label('Роль')
                        ->options([
                                'waste_generator' => 'Отходообразователь',
                                'waste_processor' => 'Переработчик отходов',
                            ]),
                    Forms\Components\Toggle::make('phone_verified')
                        ->label('Телефон подтвержден')
                        ->required(),
                    Forms\Components\DateTimePicker::make('subscription_ends_at')
                        ->label('Подписка до'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('phone')
                        ->label('Телефон')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('tariff')
                        ->label('Тариф')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('role')
                        ->label('Роль')
                        ->searchable(),
                    Tables\Columns\IconColumn::make('phone_verified')
                        ->label('Подтвержден')
                        ->boolean(),
                    Tables\Columns\TextColumn::make('subscription_ends_at')
                        ->label('Подписка до')
                        ->dateTime()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата регистрации')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

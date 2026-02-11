<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
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
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(255),
                    Forms\Components\Select::make('tariff')
                        ->label('Тариф')
                        ->options([
                                'free' => 'Бесплатный',
                                'paid' => 'Платный',
                            ])
                        ->required()
                        ->default('free'),

                    Forms\Components\DateTimePicker::make('subscription_ends_at')
                        ->label('Подписка до'),

                    \Filament\Schemas\Components\Section::make('Реферальная программа')
                        ->schema([
                                Forms\Components\TextInput::make('referral_code')
                                    ->label('Реферальный код')
                                    ->disabled(),
                                Forms\Components\TextInput::make('referral_balance')
                                    ->label('Реферальный баланс')
                                    ->numeric()
                                    ->prefix('₽'),
                                Forms\Components\Select::make('referrer_id')
                                    ->relationship('referrer', 'email')
                                    ->getOptionLabelFromRecordUsing(fn(User $record) => $record->email ?? $record->phone ?? 'ID: ' . $record->id)
                                    ->label('Кто пригласил')
                                    ->searchable(),
                            ])->columns(3),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('email')
                        ->label('Email')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('phone')
                        ->label('Телефон')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('referral_balance')
                        ->label('Баланс (₽)')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('referrals_count')
                        ->label('Рефералы')
                        ->counts('referrals'),
                    Tables\Columns\TextColumn::make('tariff')
                        ->label('Тариф')
                        ->formatStateUsing(function ($state, User $record) {
                            if ($record->subscription_ends_at && $record->subscription_ends_at->isFuture()) {
                                return 'Платный';
                            }
                            return $state === 'paid' ? 'Платный' : 'Бесплатный';
                        })
                        ->badge()
                        ->color(
                            fn(string $state, User $record): string =>
                            ($record->subscription_ends_at && $record->subscription_ends_at->isFuture()) || $state === 'paid'
                            ? 'success'
                            : 'gray'
                        ),

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

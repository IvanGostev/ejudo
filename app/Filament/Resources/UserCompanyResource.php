<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserCompanyResource\Pages;
use App\Models\UserCompany;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserCompanyResource extends Resource
{
    protected static ?string $model = UserCompany::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $modelLabel = 'Компания пользователя';
    protected static ?string $pluralModelLabel = 'Компании пользователей';
    protected static ?string $navigationLabel = 'Компании';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Forms\Components\Select::make('user_id')
                        ->label('Пользователь')
                        ->relationship('user', 'phone')
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('inn')
                        ->label('ИНН')
                        ->maxLength(12),
                    Forms\Components\TextInput::make('ogrn')
                        ->label('ОГРН')
                        ->maxLength(15),
                    Forms\Components\TextInput::make('kpp')
                        ->label('КПП')
                        ->maxLength(9),
                    Forms\Components\TextInput::make('type')
                        ->label('Тип')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('legal_address')
                        ->label('Юридический адрес')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('fact_address')
                        ->label('Фактический адрес')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активна')
                        ->required(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('user.phone')
                        ->label('Пользователь')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('name')
                        ->label('Название')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('inn')
                        ->label('ИНН')
                        ->searchable(),
                    Tables\Columns\BooleanColumn::make('is_active')
                        ->label('Активна'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата создания')
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
            'index' => Pages\ListUserCompanies::route('/'),
            'create' => Pages\CreateUserCompany::route('/create'),
            'edit' => Pages\EditUserCompany::route('/{record}/edit'),
        ];
    }
}

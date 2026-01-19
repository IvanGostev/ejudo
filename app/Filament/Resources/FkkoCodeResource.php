<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FkkoCodeResource\Pages;
use App\Models\FkkoCode;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FkkoCodeResource extends Resource
{
    protected static ?string $model = FkkoCode::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $modelLabel = 'Код ФККО';
    protected static ?string $pluralModelLabel = 'Коды ФККО';
    protected static ?string $navigationLabel = 'ФККО';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Forms\Components\TextInput::make('code')
                        ->label('Код')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('name')
                        ->label('Наименование')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Select::make('hazard_class')
                        ->label('Класс опасности')
                        ->options([
                                1 => 'I класс',
                                2 => 'II класс',
                                3 => 'III класс',
                                4 => 'IV класс',
                                5 => 'V класс',
                            ])
                        ->required(),
                    Forms\Components\TextInput::make('unit')
                        ->label('Единица измерения'),
                    Forms\Components\TextInput::make('category')
                        ->label('Категория'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('code')
                        ->label('Код')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('name')
                        ->label('Наименование')
                        ->searchable()
                        ->limit(50),
                    Tables\Columns\TextColumn::make('hazard_class')
                        ->label('Класс')
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_active')
                        ->label('Активен')
                        ->boolean(),
                ])
            ->filters([
                    //
                ])
            ->actions([
                    \Filament\Actions\EditAction::make()->label('Редактировать'),
                    \Filament\Actions\DeleteAction::make()->label('Удалить'),
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
            'index' => Pages\ListFkkoCodes::route('/'),
            'create' => Pages\CreateFkkoCode::route('/create'),
            'edit' => Pages\EditFkkoCode::route('/{record}/edit'),
        ];
    }
}

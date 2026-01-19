<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActResource\Pages;
use App\Models\Act;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActResource extends Resource
{
    protected static ?string $model = Act::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Акт';
    protected static ?string $pluralModelLabel = 'Акты';
    protected static ?string $navigationLabel = 'Акты';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Forms\Components\Select::make('company_id')
                        ->label('Компания')
                        ->relationship('company', 'name')
                        ->required(),
                    Forms\Components\TextInput::make('filename')
                        ->label('Имя файла на сервере')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('original_name')
                        ->label('Оригинальное имя файла')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('file_size')
                        ->label('Размер файла')
                        ->numeric(),
                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                                'pending' => 'Ожидает',
                                'processing' => 'В обработке',
                                'completed' => 'Завершен',
                                'failed' => 'Ошибка',
                            ])
                        ->required(),
                    Forms\Components\KeyValue::make('act_data')
                        ->label('Данные акта'),
                    Forms\Components\KeyValue::make('processing_result')
                        ->label('Результат обработки'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('company.name')
                        ->label('Компания')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('original_name')
                        ->label('Файл')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Загружен')
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
            'index' => Pages\ListActs::route('/'),
            'create' => Pages\CreateAct::route('/create'),
            'edit' => Pages\EditAct::route('/{record}/edit'),
        ];
    }
}

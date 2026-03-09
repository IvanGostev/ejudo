<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WasteBalanceResource\Pages;
use App\Models\FkkoCode;
use App\Models\WasteBalance;
use App\Services\PolygonModeService;
use App\Services\TenantService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WasteBalanceResource extends Resource
{
    protected static ?string $model = WasteBalance::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $modelLabel = 'Баланс отходов';
    protected static ?string $pluralModelLabel = 'Балансы отходов';
    protected static ?string $navigationLabel = 'Балансы отходов';

    protected static function getCurrentCompany(): ?\App\Models\UserCompany
    {
        return app(TenantService::class)->getCompany();
    }

    public static function form(Schema $schema): Schema
    {
        $hasPolygons = PolygonModeService::isEnabled(static::getCurrentCompany());

        return $schema
            ->components([
                Forms\Components\Select::make('company_id')
                    ->label('Компания')
                    ->relationship('company', 'name')
                    ->required(),

                Forms\Components\Select::make('polygon_id')
                    ->label('Полигон')
                    ->relationship('polygon', 'name')
                    ->required($hasPolygons)
                    ->visible($hasPolygons)
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('fkko_code')
                    ->label('Код ФККО')
                    ->options(fn () => FkkoCode::query()->pluck('name', 'code')->toArray())
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('period')
                    ->label('Период')
                    ->required(),

                Forms\Components\TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->minValue(0)
                    ->required(),

                Forms\Components\TextInput::make('unit')
                    ->label('Единица измерения')
                    ->default('т')
                    ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        $hasPolygons = PolygonModeService::isEnabled(static::getCurrentCompany());

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Компания')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('polygon.name')
                    ->label('Полигон')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—')
                    ->visible($hasPolygons),

                Tables\Columns\TextColumn::make('fkko_code')
                    ->label('Код ФККО')
                    ->searchable(),

                Tables\Columns\TextColumn::make('period')
                    ->label('Период')
                    ->date('Y-m')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Ед. изм.'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('polygon_id')
                    ->label('Полигон')
                    ->relationship('polygon', 'name')
                    ->visible($hasPolygons),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Редактировать'),
                Tables\Actions\DeleteAction::make()->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранные'),
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
            'index'  => Pages\ListWasteBalances::route('/'),
            'create' => Pages\CreateWasteBalance::route('/create'),
            'edit'   => Pages\EditWasteBalance::route('/{record}/edit'),
        ];
    }
}

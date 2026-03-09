<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JudoJournalResource\Pages;
use App\Models\JudoJournal;
use App\Services\PolygonModeService;
use App\Services\TenantService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JudoJournalResource extends Resource
{
    protected static ?string $model = JudoJournal::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $modelLabel = 'Журнал';
    protected static ?string $pluralModelLabel = 'Журналы';
    protected static ?string $navigationLabel = 'Журналы';

    protected static function getCurrentCompany(): ?\App\Models\UserCompany
    {
        return app(TenantService::class)->getCompany();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('company_id')
                    ->label('Компания')
                    ->relationship('company', 'name')
                    ->required(),

                Forms\Components\Select::make('polygon_id')
                    ->label('Полигон')
                    ->relationship('polygon', 'name')
                    ->required(function () {
                        return PolygonModeService::isEnabled(static::getCurrentCompany());
                    })
                    ->visible(function () {
                        return PolygonModeService::isEnabled(static::getCurrentCompany());
                    })
                    ->searchable()
                    ->preload(),

                Forms\Components\DatePicker::make('period')
                    ->label('Период')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Тип периода')
                    ->options([
                        'month'   => 'Месяц',
                        'quarter' => 'Квартал',
                        'year'    => 'Год',
                    ])
                    ->required(),
                Forms\Components\Select::make('role')
                    ->label('Роль')
                    ->options([
                        'waste_generator' => 'Отходообразователь',
                        'waste_processor' => 'Переработчик отходов',
                    ]),
                Forms\Components\Toggle::make('is_paid')
                    ->label('Оплачен')
                    ->required(),
                Forms\Components\TextInput::make('pdf_path')
                    ->label('Путь к PDF')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('downloaded_at')
                    ->label('Скачан'),
                Forms\Components\KeyValue::make('table1_data')
                    ->label('Таблица 1 (Состав)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $hasPolygons = PolygonModeService::isEnabled(static::getCurrentCompany());

        $notifyUnassigned = $hasPolygons && JudoJournal::where(
            'company_id', static::getCurrentCompany()?->id
        )->whereNull('polygon_id')->exists();

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

                Tables\Columns\TextColumn::make('period')
                    ->label('Период')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип'),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Оплачен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('polygon_id')
                    ->label('Полигон')
                    ->relationship('polygon', 'name')
                    ->visible($hasPolygons),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Редактировать'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Удалить выбранные'),
                ]),
            ])
            ->when($notifyUnassigned, fn ($t) => $t->description(
                '⚠️ Найдены записи без привязки к полигону. Рекомендуем распределить их по полигонам для более точного учёта.'
            ));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJudoJournals::route('/'),
            'create' => Pages\CreateJudoJournal::route('/create'),
            'edit'   => Pages\EditJudoJournal::route('/{record}/edit'),
        ];
    }
}

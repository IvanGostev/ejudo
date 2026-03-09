<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolygonResource\Pages;
use App\Models\FkkoCode;
use App\Models\Polygon;
use App\Services\PolygonModeService;
use App\Services\TenantService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PolygonResource extends Resource
{
    protected static ?string $model = Polygon::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'Полигон';
    protected static ?string $pluralModelLabel = 'Полигоны';
    protected static ?string $navigationLabel = 'Полигоны';

    public static function canViewAny(): bool
    {
        $company = app(TenantService::class)->getCompany();
        return PolygonModeService::isEnabled($company);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('address')
                    ->label('Адрес')
                    ->required()
                    ->rows(2),

                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3),

                Forms\Components\TextInput::make('area')
                    ->label('Площадь (га)')
                    ->numeric()
                    ->minValue(0),

                Forms\Components\Select::make('waste_types')
                    ->label('Разрешённые типы отходов (ФККО)')
                    ->multiple()
                    ->options(fn () => FkkoCode::query()->pluck('name', 'code')->toArray())
                    ->searchable(),

                Forms\Components\TextInput::make('capacity')
                    ->label('Вместимость (тонн)')
                    ->numeric()
                    ->minValue(0),

                Forms\Components\TextInput::make('current_load')
                    ->label('Текущая загрузка (тонн)')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'active'   => 'Активен',
                        'inactive' => 'Неактивен',
                    ])
                    ->default('active')
                    ->required(),

                \Filament\Schemas\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('coordinates.lat')
                            ->label('Широта (lat)')
                            ->numeric(),
                        Forms\Components\TextInput::make('coordinates.lng')
                            ->label('Долгота (lng)')
                            ->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'   => 'Активен',
                        'inactive' => 'Неактивен',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('load_percent')
                    ->label('Загрузка')
                    ->state(function (Polygon $record): string {
                        if (!$record->capacity || $record->capacity == 0) {
                            return '—';
                        }
                        $percent = ((float) $record->current_load / (float) $record->capacity) * 100;
                        return number_format($percent, 1) . '% (' . $record->current_load . ' / ' . $record->capacity . ' т)';
                    }),

                Tables\Columns\TextColumn::make('judo_journals_count')
                    ->label('Записей в журнале')
                    ->counts('judoJournals'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active'   => 'Активен',
                        'inactive' => 'Неактивен',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->before(function (Polygon $record, \Filament\Actions\DeleteAction $action) {
                        if ($record->judoJournals()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Нельзя удалить полигон')
                                ->body('К этому полигону привязаны записи журнала. Перенесите записи перед удалением.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
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
            'index'  => Pages\ListPolygons::route('/'),
            'create' => Pages\CreatePolygon::route('/create'),
            'edit'   => Pages\EditPolygon::route('/{record}/edit'),
        ];
    }
}

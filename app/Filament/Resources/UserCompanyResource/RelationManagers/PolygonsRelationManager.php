<?php

namespace App\Filament\Resources\UserCompanyResource\RelationManagers;

use App\Services\PolygonModeService;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PolygonsRelationManager extends RelationManager
{
    protected static string $relationship = 'polygons';

    protected static ?string $title = 'Полигоны';
    protected static ?string $modelLabel = 'Полигон';
    protected static ?string $pluralModelLabel = 'Полигоны';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return PolygonModeService::isEnabled($ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Адрес')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Описание'),
                Forms\Components\TextInput::make('capacity')
                    ->label('Вместимость (тонн)')
                    ->numeric(),
                Forms\Components\Toggle::make('status')
                    ->label('Статус')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default('active')
                    ->acceptedValues(['active']),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Создать новый полигон')
                    ->before(function (\Filament\Actions\CreateAction $action) {
                        if (!PolygonModeService::isEnabled($this->getOwnerRecord())) {
                            $action->requiresConfirmation()
                                ->modalHeading('Включение режима полигонов')
                                ->modalDescription('Вы собираетесь создать первый полигон. После этого в интерфейсе появятся дополнительные поля для выбора полигона. Все существующие записи останутся в общем журнале. Вы сможете позже привязать их к конкретным полигонам. Продолжить?')
                                ->modalSubmitActionLabel('Да, продолжить');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

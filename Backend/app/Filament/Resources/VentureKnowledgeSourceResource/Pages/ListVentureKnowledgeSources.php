<?php

namespace App\Filament\Resources\VentureKnowledgeSourceResource\Pages;

use App\Filament\Resources\VentureKnowledgeSourceResource;
use App\Models\VentureKnowledgeSource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ListVentureKnowledgeSources extends ListRecords
{
    protected static string $resource = VentureKnowledgeSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VentureKnowledgeSource::query())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'blue' => 'industry_report',
                        'green' => 'market_data',
                        'orange' => 'template',
                        'purple' => 'methodology',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'industry_report' => 'Industry Report',
                        'market_data' => 'Market Data',
                        'template' => 'Template',
                        'methodology' => 'Methodology',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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

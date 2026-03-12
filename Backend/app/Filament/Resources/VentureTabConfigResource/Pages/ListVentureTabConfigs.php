<?php

namespace App\Filament\Resources\VentureTabConfigResource\Pages;

use App\Filament\Resources\VentureTabConfigResource;
use App\Models\VentureTabConfig;
use App\Models\VentureSectionConfig;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ListVentureTabConfigs extends ListRecords
{
    protected static string $resource = VentureTabConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VentureTabConfig::query())
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),
                Tables\Columns\IconColumn::make('icon')
                    ->label('Icon')
                    ->icon(fn ($record) => $record->icon ? ('heroicon-o-' . $record->icon) : 'heroicon-o-rectangle-stack')
                    ->width('60px'),
                Tables\Columns\TextColumn::make('tab_slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('label_en')
                    ->label('Label (EN)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label_ar')
                    ->label('Label (AR)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->state(fn ($record) => VentureSectionConfig::where('tab_slug', $record->tab_slug)->count())
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility'),
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

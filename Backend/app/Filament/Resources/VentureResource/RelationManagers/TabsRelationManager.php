<?php

namespace App\Filament\Resources\VentureResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TabsRelationManager extends RelationManager
{
    protected static string $relationship = 'tabs';

    protected static ?string $title = 'Venture Tabs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label_en')
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label_en')
                    ->label('Label (EN)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label_ar')
                    ->label('Label (AR)'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->state(fn ($record) => $record->sections()->count()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tabs are created by the Venture, not through this relation manager
            ])
            ->actions([
                Tables\Actions\Action::make('viewSections')
                    ->label('View Sections')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->modalHeading(fn ($record) => 'Sections in ' . $record->label_en)
                    ->modalContent(fn ($record) => view('filament.resources.venture-resource.relation-managers.sections-modal', [
                        'tab' => $record,
                        'sections' => $record->sections()->get(),
                    ]))
                    ->modalSubmitActionLabel('Close'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk actions for tabs
                ]),
            ]);
    }
}

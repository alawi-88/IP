<?php

namespace App\Filament\Resources\RegistrationEvaluationFormResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CriteriaRelationManager extends RelationManager
{
    protected static string $relationship = 'criteria';

    protected static ?string $title = 'Evaluation Criteria / معايير التقييم';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name.en')
                ->label('Criterion Name (English)')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('name.ar')
                ->label('اسم المعيار (عربي)')
                ->maxLength(255)
                ->extraFieldWrapperAttributes(['class' => 'text-right']),

            Forms\Components\Textarea::make('description.en')
                ->label('Description (English)')
                ->rows(2),

            Forms\Components\Textarea::make('description.ar')
                ->label('الوصف (عربي)')
                ->rows(2)
                ->extraFieldWrapperAttributes(['class' => 'text-right']),

            Forms\Components\TextInput::make('max_score')
                ->label('Max Score / الدرجة القصوى')
                ->numeric()
                ->required()
                ->minValue(1)
                ->default(10),

            Forms\Components\TextInput::make('weight')
                ->label('Weight (%) / الوزن')
                ->numeric()
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->default(100)
                ->helperText('Percentage weight for final score calculation'),

            Forms\Components\TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('name->en', 'like', "%{$search}%")
                              ->orWhere('name->ar', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->getStateUsing(fn ($record) => \Str::limit($record->getTranslation('description', 'en'), 50))
                    ->wrap(),

                Tables\Columns\TextColumn::make('max_score')
                    ->label('Max Score')
                    ->sortable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Weight %')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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

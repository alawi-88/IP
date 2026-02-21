<?php

namespace App\Filament\Resources\RegistrationEvaluationFormResource\Pages;

use App\Filament\Resources\RegistrationEvaluationFormResource;
use App\Models\Program;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditRegistrationEvaluationForm extends EditRecord
{
    protected static string $resource = RegistrationEvaluationFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Evaluation Form Details / تفاصيل نموذج التقييم')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('program_id')
                        ->label('Program / البرنامج')
                        ->options(fn () => Program::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('title', 'en')]))
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('name.en')
                        ->label('Name (English)')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('name.ar')
                        ->label('الاسم (عربي)')
                        ->maxLength(255)
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\Textarea::make('description.en')
                        ->label('Description (English)')
                        ->rows(3),

                    Forms\Components\Textarea::make('description.ar')
                        ->label('الوصف (عربي)')
                        ->rows(3)
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\TextInput::make('dimension')
                        ->label('Dimension / البُعد')
                        ->maxLength(100),

                    Forms\Components\Select::make('scoring_scale')
                        ->label('Scoring Scale / مقياس التقييم')
                        ->options([
                            '1-5' => '1-5',
                            '1-10' => '1-10',
                            '1-100' => '1-100',
                        ])
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'archived' => 'Archived',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric(),
                ]),
        ]);
    }
}

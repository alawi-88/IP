<?php

namespace App\Filament\Resources\TaskTemplateResource\Pages;

use App\Filament\Resources\TaskTemplateResource;
use App\Models\Competition;
use App\Models\Form;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form as FilamentForm;
use Filament\Resources\Pages\EditRecord;

class EditTaskTemplate extends EditRecord
{
    protected static string $resource = TaskTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function form(FilamentForm $form): FilamentForm
    {
        return $form->schema([
            Forms\Components\Section::make('Task Template / قالب المهمة')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('competition_id')
                        ->label('Program / البرنامج')
                        ->options(fn () => Competition::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('title', 'en')]))
                        ->required()
                        ->searchable()
                        ->reactive(),

                    Forms\Components\Select::make('form_id')
                        ->label('Form Template / نموذج التسليم')
                        ->options(function (callable $get) {
                            $competitionId = $get('competition_id');
                            if (!$competitionId) return Form::pluck('name', 'id')->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t);
                            return Form::where('competition_id', $competitionId)
                                ->pluck('name', 'id')
                                ->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t);
                        })
                        ->searchable(),

                    Forms\Components\TextInput::make('title.en')
                        ->label('Title (English)')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('title.ar')
                        ->label('العنوان (عربي)')
                        ->maxLength(255)
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\RichEditor::make('description.en')
                        ->label('Description (English)')
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('description.ar')
                        ->label('الوصف (عربي)')
                        ->columnSpanFull()
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\RichEditor::make('instructions.en')
                        ->label('Instructions (English)')
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('instructions.ar')
                        ->label('التعليمات (عربي)')
                        ->columnSpanFull()
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\Select::make('difficulty_level')
                        ->options([
                            'easy' => 'Easy',
                            'medium' => 'Medium',
                            'hard' => 'Hard',
                        ]),

                    Forms\Components\TextInput::make('estimated_hours')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('category')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('version')
                        ->numeric(),

                    Forms\Components\Toggle::make('is_archived')
                        ->label('Archived'),
                ]),
        ]);
    }
}

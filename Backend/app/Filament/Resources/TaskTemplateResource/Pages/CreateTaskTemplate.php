<?php

namespace App\Filament\Resources\TaskTemplateResource\Pages;

use App\Filament\Resources\TaskTemplateResource;
use App\Models\Program;
use App\Models\Form;
use Filament\Forms;
use Filament\Forms\Form as FilamentForm;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskTemplate extends CreateRecord
{
    protected static string $resource = TaskTemplateResource::class;

    public function form(FilamentForm $form): FilamentForm
    {
        return $form->schema([
            Forms\Components\Section::make('Task Template / قالب المهمة')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('program_id')
                        ->label('Program / البرنامج')
                        ->options(fn () => Program::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('title', 'en')]))
                        ->required()
                        ->searchable()
                        ->reactive(),

                    Forms\Components\Select::make('form_id')
                        ->label('Form Template / نموذج التسليم')
                        ->options(function (callable $get) {
                            $programId = $get('program_id');
                            if (!$programId) return Form::pluck('name', 'id')->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t);
                            return Form::where('program_id', $programId)
                                ->pluck('name', 'id')
                                ->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t);
                        })
                        ->searchable()
                        ->helperText('Optional: Link a form from the Form Builder for task deliverables'),

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
                        ->label('Difficulty Level')
                        ->options([
                            'easy' => 'Easy',
                            'medium' => 'Medium',
                            'hard' => 'Hard',
                        ])
                        ->default('medium'),

                    Forms\Components\TextInput::make('estimated_hours')
                        ->label('Estimated Hours')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('category')
                        ->label('Category')
                        ->maxLength(100)
                        ->helperText('e.g., Documentation, Development, Research'),

                    Forms\Components\TextInput::make('version')
                        ->label('Version')
                        ->numeric()
                        ->default(1),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => auth()->id()),

                    Forms\Components\Toggle::make('is_archived')
                        ->label('Archived')
                        ->default(false),
                ]),
        ]);
    }
}

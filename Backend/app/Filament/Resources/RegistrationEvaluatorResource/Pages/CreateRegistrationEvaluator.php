<?php

namespace App\Filament\Resources\RegistrationEvaluatorResource\Pages;

use App\Filament\Resources\RegistrationEvaluatorResource;
use App\Models\Program;
use App\Models\RegistrationEvaluationForm;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateRegistrationEvaluator extends CreateRecord
{
    protected static string $resource = RegistrationEvaluatorResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assign Evaluator / تعيين مقيّم')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('program_id')
                        ->label('Program / البرنامج')
                        ->options(fn () => Program::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('title', 'en')]))
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('section_form_ids', [])),

                    Forms\Components\Select::make('user_id')
                        ->label('Admin User / المستخدم')
                        ->options(fn () => User::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active / نشط')
                        ->default(true),

                    Forms\Components\Select::make('section_form_ids')
                        ->label('Assigned Evaluation Sections / أقسام التقييم المعينة')
                        ->multiple()
                        ->options(function (callable $get) {
                            $programId = $get('program_id');
                            if (!$programId) return [];
                            return RegistrationEvaluationForm::where('program_id', $programId)
                                ->where('status', 'published')
                                ->get()
                                ->mapWithKeys(fn ($f) => [$f->id => $f->getTranslation('name', 'en')]);
                        })
                        ->helperText('Select which evaluation form sections this evaluator will score. Leave empty to assign all sections.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected function afterCreate(): void
    {
        $sectionFormIds = $this->data['section_form_ids'] ?? [];

        if (empty($sectionFormIds)) {
            // Assign all published forms for this program
            $sectionFormIds = RegistrationEvaluationForm::where('program_id', $this->record->program_id)
                ->where('status', 'published')
                ->pluck('id')
                ->toArray();
        }

        foreach ($sectionFormIds as $formId) {
            $this->record->assignedSections()->create([
                'registration_evaluation_form_id' => $formId,
            ]);
        }
    }
}

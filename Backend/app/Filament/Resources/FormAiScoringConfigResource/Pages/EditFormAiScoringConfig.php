<?php

namespace App\Filament\Resources\FormAiScoringConfigResource\Pages;

use App\Filament\Resources\FormAiScoringConfigResource;
use App\Models\FormAssessmentCriterion;
use App\Models\FormAiEnhancementConfig;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\EditRecord;

class EditFormAiScoringConfig extends EditRecord
{
    protected static string $resource = FormAiScoringConfigResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Weight Summary / ملخص الأوزان')
                    ->schema([
                        TextEntry::make('total_weight')
                            ->label('Total Weight / الوزن الإجمالي')
                            ->formatStateUsing(fn ($record) => (string) ($record->total_weight ?? 0))
                            ->badge()
                            ->color('info')
                            ->size('lg'),
                        TextEntry::make('allocated_weight')
                            ->label('Allocated Weight / الوزن المخصص')
                            ->getStateUsing(function ($record) {
                                return (string) FormAssessmentCriterion::where('form_id', $record->form_id)
                                    ->where('status', 'active')
                                    ->sum('weight');
                            })
                            ->badge()
                            ->color(function ($record) {
                                $allocated = FormAssessmentCriterion::where('form_id', $record->form_id)
                                    ->where('status', 'active')
                                    ->sum('weight');
                                return $allocated > ($record->total_weight ?? 0) ? 'danger' : 'success';
                            })
                            ->size('lg'),
                        TextEntry::make('remaining_weight')
                            ->label('Remaining Weight / الوزن المتبقي')
                            ->getStateUsing(function ($record) {
                                $allocated = FormAssessmentCriterion::where('form_id', $record->form_id)
                                    ->where('status', 'active')
                                    ->sum('weight');
                                return (string) max(0, ($record->total_weight ?? 0) - $allocated);
                            })
                            ->badge()
                            ->color(function ($record) {
                                $allocated = FormAssessmentCriterion::where('form_id', $record->form_id)
                                    ->where('status', 'active')
                                    ->sum('weight');
                                $remaining = max(0, ($record->total_weight ?? 0) - $allocated);
                                return $remaining > 0 ? 'warning' : 'success';
                            })
                            ->size('lg'),
                    ])
                    ->columns(3),
            ]);
    }

    public function form(Form $form): Form
    {
        $currentForm = $this->record->form;
        $currentFormId = $this->record->form_id;
        
        return $form->schema([
            \Filament\Forms\Components\Section::make('Select Form / اختر النموذج')
                ->schema([
                    \Filament\Forms\Components\Select::make('program_id')
                        ->label('Program / البرنامج')
                        ->options(function () {
                            $user = auth()->user();
                            $currentProgramId = currentProgramId();

                            if ($user->isSuperAdmin()) {
                                $programs = \App\Models\Program::pluck('title', 'id')->toArray();
                            } else {
                                $supervisorPrograms = \App\Models\UserProgram::where('user_id', $user->id)
                                    ->pluck('program_id')
                                    ->toArray();

                                $programs = \App\Models\Program::whereIn('id', $supervisorPrograms)
                                    ->pluck('title', 'id')
                                    ->toArray();
                            }

                            // Prioritize current program - move it to the top
                            if ($currentProgramId && isset($programs[$currentProgramId])) {
                                $currentTitle = $programs[$currentProgramId];
                                unset($programs[$currentProgramId]);
                                $programs = [$currentProgramId => $currentTitle] + $programs;
                            }

                            return $programs;
                        })
                        ->default($currentForm?->program_id)
                        ->required()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->helperText('Please select a program / يرجى اختيار برنامج'),

                    \Filament\Forms\Components\Select::make('form_type')
                        ->label('Form Type / نوع النموذج')
                        ->placeholder('Select form type / اختر نوع النموذج')
                        ->options(\App\Models\Form::getAvailableFormTypes())
                        ->default($currentForm?->type)
                        ->required()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Please select a form type / يرجى اختيار نوع النموذج'),

                    \Filament\Forms\Components\Select::make('form_id')
                        ->label('Form / النموذج')
                        ->placeholder('Select form / اختر النموذج')
                        ->options(function () use ($currentFormId) {
                            $form = \App\Models\Form::find($currentFormId);
                            if (!$form) {
                                return [];
                            }
                            $name = is_array($form->name) 
                                ? ($form->name['en'] ?? reset($form->name)) 
                                : $form->name;
                            return [$currentFormId => $name];
                        })
                        ->default($currentFormId)
                        ->required()
                        ->disabled()
                        ->dehydrated(true) // Keep form_id in the saved data
                        ->helperText('Please select a form / يرجى اختيار النموذج'),
                ])
                ->columns(1),

            \Filament\Forms\Components\Section::make('AI Configuration / إعدادات الذكاء الاصطناعي')
                ->schema([
                    \Filament\Forms\Components\Textarea::make('ai_prompt')
                        ->label('AI Prompt / توجيه الذكاء الاصطناعي')
                        ->placeholder('Enter AI prompt (e.g., "You are an expert on fintech and you are assessing based on...") / أدخل توجيه الذكاء الاصطناعي')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Define the AI\'s role and assessment context / حدد دور الذكاء الاصطناعي وسياق التقييم'),

                    \Filament\Forms\Components\TextInput::make('total_weight')
                        ->label('Total Weight / الوزن الإجمالي')
                        ->placeholder('Enter total weight (e.g., 100) / أدخل الوزن الإجمالي')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(100)
                        ->helperText('Total weight for this form / الوزن الإجمالي لهذا النموذج'),
                ])
                ->columns(1),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Add program_id and form_type for the form (read-only for display)
        if ($this->record->form) {
            $data['program_id'] = $this->record->form->program_id;
            $data['form_type'] = $this->record->form->type;
            $data['form_id'] = $this->record->form_id;
        }

        // Convert legacy format (array of slugs) to new format (array of objects with slug and instructions)
        if (isset($data['ai_enhancement_fields']) && is_array($data['ai_enhancement_fields']) && !empty($data['ai_enhancement_fields'])) {
            // Check if it's legacy format (array of strings/slugs)
            $firstItem = reset($data['ai_enhancement_fields']);
            if (is_string($firstItem) || (is_array($firstItem) && !isset($firstItem['slug']))) {
                // Convert to new format
                $data['ai_enhancement_fields'] = array_map(function ($slug) {
                    return [
                        'slug' => $slug,
                        'instructions' => '',
                    ];
                }, $data['ai_enhancement_fields']);
            }
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove program_id and form_type from data as they're not in the model
        unset($data['program_id']);
        unset($data['form_type']);
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Check if AI Enhancement config exists and is enabled
                    $enhancementConfig = FormAiEnhancementConfig::where('form_id', $record->form_id)->first();
                    if ($enhancementConfig && $enhancementConfig->ai_enhancement_enabled) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot Delete / لا يمكن الحذف')
                            ->body('Cannot delete AI Scoring configuration because AI Enhancement is enabled. Please delete AI Enhancements first from the AI Enhancements section. / لا يمكن حذف إعدادات تقييم الذكاء الاصطناعي لأن تحسين الذكاء الاصطناعي مفعّل. يرجى حذف تحسينات الذكاء الاصطناعي أولاً من قسم تحسينات الذكاء الاصطناعي.')
                            ->danger()
                            ->send();
                        
                        // Prevent deletion
                        throw new \Filament\Support\Exceptions\Halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


<?php

namespace App\Filament\Resources\FormAiHintsResource\Pages;

use App\Filament\Resources\FormAiHintsResource;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditFormAiHints extends EditRecord
{
    protected static string $resource = FormAiHintsResource::class;

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

            \Filament\Forms\Components\Section::make('AI Enhancement / تحسين الذكاء الاصطناعي')
                ->schema([
                    \Filament\Forms\Components\Toggle::make('ai_enhancement_enabled')
                        ->label('Enable AI Enhancement / تفعيل تحسين الذكاء الاصطناعي')
                        ->default(false)
                        ->columnSpanFull()
                        ->helperText('Allow participants to enhance their form submissions using AI / السماح للمشاركين بتحسين إجاباتهم باستخدام الذكاء الاصطناعي')
                        ->live(),

                    \Filament\Forms\Components\Repeater::make('ai_enhancement_fields')
                        ->label('Fields with Instructions / الحقول مع التعليمات')
                        ->schema([
                            \Filament\Forms\Components\Select::make('slug')
                                ->label('Field / الحقل')
                                ->options(function () use ($currentFormId) {
                                    if (!$currentFormId) {
                                        return [];
                                    }

                                    $form = \App\Models\Form::with('fields')->find($currentFormId);
                                    if (!$form) {
                                        return [];
                                    }

                                    // Only show text and textarea fields that can be enhanced
                                    $enhanceableTypes = ['text', 'textarea', 'email', 'url'];

                                    return $form->fields()
                                        ->whereIn('type', $enhanceableTypes)
                                        ->orderBy('sort')
                                        ->get()
                                        ->unique('id')
                                        ->mapWithKeys(function ($field) {
                                            $label = is_array($field->label)
                                                ? ($field->label['en'] ?? reset($field->label))
                                                : $field->label;
                                            return [$field->slug => $label];
                                        })
                                        ->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->reactive()
                                ->columnSpanFull(),
                            \Filament\Forms\Components\Textarea::make('instructions')
                                ->label('Instructions / التعليمات')
                                ->placeholder('Enter specific instructions for this field (e.g., "Improve clarity and professionalism")')
                                ->rows(2)
                                ->required()
                                ->columnSpanFull(),
                            \Filament\Forms\Components\Select::make('context')
                                ->label('Context Field / حقل السياق')
                                ->options(function () use ($currentFormId) {
                                    if (!$currentFormId) {
                                        return [];
                                    }

                                    $form = \App\Models\Form::with('fields')->find($currentFormId);
                                    if (!$form) {
                                        return [];
                                    }

                                    // Show all fields that can provide context
                                    return $form->fields()
                                        ->orderBy('sort')
                                        ->get()
                                        ->unique('id')
                                        ->mapWithKeys(function ($field) {
                                            $label = is_array($field->label)
                                                ? ($field->label['en'] ?? reset($field->label))
                                                : $field->label;
                                            return [$field->slug => $label];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select a field to use as context / اختر حقل ليكون سياق')
                                ->helperText('The value of this field will be sent as context to help AI understand better / قيمة هذا الحقل سترسل كسياق لمساعدة الذكاء الاصطناعي')
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->columnSpanFull()
                        ->visible(fn (callable $get) => $get('ai_enhancement_enabled') && $currentFormId)
                        ->helperText('Add fields with specific instructions and context. Leave empty to enhance all text/textarea fields with default instructions. / أضف حقول مع تعليمات وسياق محدد. اتركه فارغًا لتحسين جميع حقول النص بتعليمات افتراضية.')
                        ->itemLabel(fn (array $state): ?string => $state['slug'] ?? null)
                        ->defaultItems(0)
                        ->collapsible()
                        ->disabled(fn () => !$currentFormId),
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

        // Convert legacy format (array of slugs) to new format (array of objects with slug, instructions, and context)
        if (isset($data['ai_enhancement_fields']) && is_array($data['ai_enhancement_fields']) && !empty($data['ai_enhancement_fields'])) {
            // Check if it's legacy format (array of strings/slugs)
            $firstItem = reset($data['ai_enhancement_fields']);
            if (is_string($firstItem) || (is_array($firstItem) && !isset($firstItem['slug']))) {
                // Convert to new format
                $data['ai_enhancement_fields'] = array_map(function ($slug) {
                    return [
                        'slug' => $slug,
                        'instructions' => '',
                        'context' => '',
                    ];
                }, $data['ai_enhancement_fields']);
            } else {
                // Ensure context exists for existing fields
                $data['ai_enhancement_fields'] = array_map(function ($field) {
                    if (!isset($field['context'])) {
                        $field['context'] = '';
                    }
                    return $field;
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
                ->label('Delete')
                ->modalHeading('Delete')
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

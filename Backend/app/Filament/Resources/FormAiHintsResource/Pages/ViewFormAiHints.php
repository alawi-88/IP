<?php

namespace App\Filament\Resources\FormAiHintsResource\Pages;

use App\Filament\Resources\FormAiHintsResource;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewFormAiHints extends ViewRecord
{
    protected static string $resource = FormAiHintsResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Form Information / معلومات النموذج')
                    ->schema([
                        TextEntry::make('form.competition.title')
                            ->label('Program / البرنامج')
                            ->getStateUsing(function ($record) {
                                if (!$record->form || !$record->form->competition) {
                                    return 'N/A';
                                }
                                $competition = $record->form->competition;
                                $title = $competition->title;
                                
                                if (is_array($title)) {
                                    return $title['en'] ?? reset($title);
                                }
                                
                                if (method_exists($competition, 'getTranslation')) {
                                    return $competition->getTranslation('title', 'en') ?? $title;
                                }
                                
                                return $title;
                            }),
                        TextEntry::make('form.type')
                            ->label('Form Type / نوع النموذج')
                            ->getStateUsing(function ($record) {
                                if (!$record->form) {
                                    return 'N/A';
                                }
                                $type = $record->form->type;
                                $formTypes = \App\Models\Form::getAvailableFormTypes();
                                return $formTypes[$type] ?? $type;
                            }),
                        TextEntry::make('form.name')
                            ->label('Form / النموذج')
                            ->getStateUsing(function ($record) {
                                if (!$record->form) {
                                    return 'N/A';
                                }
                                $name = $record->form->name;
                                if (is_array($name)) {
                                    return $name['en'] ?? reset($name);
                                }
                                return $name;
                            }),
                    ])
                    ->columns(3),

                Section::make('AI Enhancement Status / حالة تحسين الذكاء الاصطناعي')
                    ->schema([
                        TextEntry::make('ai_enhancement_enabled')
                            ->label('Enabled / مفعّل')
                            ->getStateUsing(function ($record) {
                                $enabled = $record->ai_enhancement_enabled;
                                if (is_array($enabled)) {
                                    $enabled = reset($enabled);
                                }
                                return (bool) $enabled;
                            })
                            ->formatStateUsing(fn ($state) => $state ? 'Yes / نعم' : 'No / لا')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                    ]),

                Section::make('AI Enhancement Fields / حقول تحسين الذكاء الاصطناعي')
                    ->schema(function ($record) {
                        $fields = $record->ai_enhancement_fields ?? [];
                        
                        if (empty($fields) || !is_array($fields)) {
                            return [
                                TextEntry::make('no_fields')
                                    ->label('')
                                    ->getStateUsing(fn () => 'No specific fields configured / لا توجد حقول محددة')
                                    ->columnSpanFull(),
                            ];
                        }

                        // Get form fields for label lookup
                        $formFields = [];
                        if ($record->form) {
                            $formFields = $record->form->fields()
                                ->get()
                                ->mapWithKeys(function ($field) {
                                    $label = is_array($field->label)
                                        ? ($field->label['en'] ?? reset($field->label))
                                        : $field->label;
                                    return [$field->slug => $label];
                                })
                                ->toArray();
                        }

                        $sections = [];
                        foreach ($fields as $index => $field) {
                            if (!is_array($field) || !isset($field['slug'])) {
                                continue;
                            }

                            $slug = $field['slug'];
                            $fieldLabel = $formFields[$slug] ?? $slug;
                            $instructions = $field['instructions'] ?? 'No instructions';
                            $contextSlug = $field['context'] ?? null;
                            $contextLabel = $contextSlug ? ($formFields[$contextSlug] ?? $contextSlug) : null;

                            $sections[] = Section::make($fieldLabel)
                                ->schema([
                                    TextEntry::make("field_{$index}_instructions")
                                        ->label('Instructions / التعليمات')
                                        ->getStateUsing(fn () => $instructions)
                                        ->icon('heroicon-o-document-text')
                                        ->columnSpanFull(),
                                    TextEntry::make("field_{$index}_context")
                                        ->label('Context Field / حقل السياق')
                                        ->getStateUsing(fn () => $contextLabel ?: 'None / لا يوجد')
                                        ->icon('heroicon-o-link')
                                        ->color($contextLabel ? 'primary' : 'gray')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->collapsed(false)
                                ->columnSpan(1);
                        }

                        return $sections;
                    })
                    ->columns(2)
                    ->visible(fn ($record) => $record->ai_enhancement_enabled),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->label('Delete')
                ->modalHeading('Delete')
        ];
    }
}

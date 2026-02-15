<?php

namespace App\Filament\Resources\FormAiScoringConfigResource\Pages;

use App\Filament\Resources\FormAiScoringConfigResource;
use App\Models\FormAssessmentCriterion;
use App\Models\FormAiEnhancementConfig;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewFormAiScoringConfig extends ViewRecord
{
    protected static string $resource = FormAiScoringConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Form Information / معلومات النموذج')
                    ->schema([
                        TextEntry::make('form.competition.title')
                            ->label('Program / البرنامج'),
                        TextEntry::make('form.type')
                            ->label('Form Type / نوع النموذج')
                            ->formatStateUsing(fn ($state) => \App\Models\Form::getAvailableFormTypes()[$state] ?? $state),
                        TextEntry::make('form.name')
                            ->label('Form / النموذج')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state['en'] ?? reset($state);
                                }
                                return $state;
                            }),
                    ])
                    ->columns(3),

                Section::make('AI Configuration / إعدادات الذكاء الاصطناعي')
                    ->schema([
                        TextEntry::make('ai_prompt')
                            ->label('AI Prompt / توجيه الذكاء الاصطناعي')
                            ->columnSpanFull(),
                    ]),

                Section::make('Weight Summary / ملخص الأوزان')
                    ->schema([
                        TextEntry::make('total_weight')
                            ->label('Total Weight / الوزن الإجمالي')
                            ->formatStateUsing(fn ($record) => $record->total_weight ?? 0)
                            ->badge()
                            ->color('info')
                            ->size('lg'),
                        TextEntry::make('allocated_weight')
                            ->label('Allocated Weight / الوزن المخصص')
                            ->getStateUsing(function ($record) {
                                return FormAssessmentCriterion::where('form_id', $record->form_id)
                                    ->where('status', 'active')
                                    ->sum('weight');
                            })
                            ->formatStateUsing(fn ($state) => (string) $state)
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
                                return max(0, ($record->total_weight ?? 0) - $allocated);
                            })
                            ->formatStateUsing(fn ($state) => (string) $state)
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
}


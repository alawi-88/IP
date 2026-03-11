<?php

namespace App\Filament\Resources\VentureResource\RelationManagers;

use App\Models\VentureSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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

                Tables\Actions\Action::make('editSections')
                    ->label('Edit Sections')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(fn ($record) => 'Edit Sections in ' . ($record->label_en ?? $record->slug))
                    ->modalWidth('5xl')
                    ->form(function ($record) {
                        $sections = $record->sections()->orderBy('sort_order')->get();
                        $fields = [];

                        if ($sections->isEmpty()) {
                            $fields[] = Forms\Components\Placeholder::make('no_sections')
                                ->content('No sections found in this tab.');
                            return $fields;
                        }

                        foreach ($sections as $section) {
                            $label = $section->label_en ?: ucwords(str_replace(['_', '-'], ' ', $section->slug));
                            $statusBadge = match($section->status) {
                                'completed' => ' ✅',
                                'failed' => ' ❌',
                                'generating' => ' ⏳',
                                default => '',
                            };

                            $fields[] = Forms\Components\Section::make($label . $statusBadge)
                                ->description('Slug: ' . $section->slug . ' | Type: ' . ($section->component_type ?? 'N/A'))
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Toggle::make("sections.{$section->id}.is_visible")
                                                ->label('Visible')
                                                ->default((bool) $section->is_visible)
                                                ->columnSpan(1),
                                            Forms\Components\TextInput::make("sections.{$section->id}.label_en")
                                                ->label('Label (EN)')
                                                ->default($section->label_en ?? '')
                                                ->columnSpan(1),
                                            Forms\Components\Select::make("sections.{$section->id}.component_type")
                                                ->label('Component Type')
                                                ->default($section->component_type ?? 'text_content')
                                                ->options([
                                                    'text_content' => 'Text Content',
                                                    'stat_cards' => 'Stat Cards',
                                                    'swot_grid' => 'SWOT Grid',
                                                    'comparison_table' => 'Comparison Table',
                                                    'risk_matrix' => 'Risk Matrix',
                                                    'timeline' => 'Timeline',
                                                    'journey_timeline' => 'Journey Timeline',
                                                    'persona_cards' => 'Persona Cards',
                                                    'viability_score' => 'Viability Score',
                                                    'key_value' => 'Key Value',
                                                    'progress_bars' => 'Progress Bars',
                                                    'funnel_chart' => 'Funnel Chart',
                                                    'pricing_cards' => 'Pricing Cards',
                                                    'cost_table' => 'Cost Table',
                                                    'line_chart' => 'Line Chart',
                                                    'canvas_grid' => 'Canvas Grid',
                                                ])
                                                ->columnSpan(1),
                                        ]),
                                    Forms\Components\Textarea::make("sections.{$section->id}.content")
                                        ->label('Content (JSON)')
                                        ->default(is_array($section->content) ? json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($section->content ?? '{}'))
                                        ->rows(10)
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->collapsed(count($sections) > 3);
                        }

                        return $fields;
                    })
                    ->action(function ($record, array $data) {
                        $sectionsData = $data['sections'] ?? [];
                        $updated = 0;

                        foreach ($sectionsData as $sectionId => $sectionData) {
                            $section = VentureSection::find($sectionId);
                            if (!$section) continue;

                            $updates = [];

                            if (isset($sectionData['is_visible'])) {
                                $updates['is_visible'] = (bool) $sectionData['is_visible'];
                            }

                            if (isset($sectionData['label_en']) && !empty($sectionData['label_en'])) {
                                $updates['label_en'] = $sectionData['label_en'];
                            }

                            if (isset($sectionData['component_type']) && !empty($sectionData['component_type'])) {
                                $updates['component_type'] = $sectionData['component_type'];
                            }

                            if (isset($sectionData['content']) && !empty($sectionData['content'])) {
                                $decoded = json_decode($sectionData['content'], true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $updates['content'] = $decoded;
                                }
                            }

                            if (!empty($updates)) {
                                $section->update($updates);
                                $updated++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title("Updated {$updated} section(s)")
                            ->send();
                    })
                    ->modalSubmitActionLabel('Save Changes')
                    ->visible(fn () => auth()->user()?->hasRole(['super-admin', 'admin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk actions for tabs
                ]),
            ]);
    }
}

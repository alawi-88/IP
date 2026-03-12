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

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Unique identifier for the tab (e.g., financial_projections)')
                    ->columnSpan(2),
                Forms\Components\TextInput::make('label_en')
                    ->label('Label (English)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),
                Forms\Components\TextInput::make('label_ar')
                    ->label('Label (Arabic)')
                    ->maxLength(255)
                    ->columnSpan(1),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->integer()
                    ->default(0)
                    ->columnSpan(1),
                Forms\Components\Toggle::make('is_visible')
                    ->label('Visible')
                    ->default(true)
                    ->columnSpan(1),
            ])
            ->columns(2);
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
                Tables\Actions\CreateAction::make()
                    ->label('Add Tab')
                    ->icon('heroicon-o-plus'),
            ])
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\Action::make('viewSections')
                    ->label('View Sections')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->modalHeading(fn ($record) => 'Sections in ' . ($record->label_en ?? $record->slug))
                    ->modalContent(fn ($record) => view('filament.resources.venture-resource.relation-managers.sections-modal', [
                        'tab' => $record,
                        'sections' => $record->sections()->orderBy('sort_order')->get(),
                    ]))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('editSections')
                    ->label('Edit Sections')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(fn ($record) => 'Edit Sections in ' . ($record->label_en ?? $record->slug))
                    ->modalWidth('7xl')
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

                            $contentFields = static::buildContentFields($section);

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
                                                    'pestel' => 'PESTEL Analysis',
                                                    'funding_strategy' => 'Funding Strategy',
                                                    'growth_channels' => 'Growth Channels',
                                                    'partnerships' => 'Partnerships',
                                                    'differentiators' => 'Differentiators',
                                                    'tech_architecture' => 'Tech Architecture',
                                                    'development_roadmap' => 'Development Roadmap',
                                                    'launch_plan' => 'Launch Plan',
                                                    'mvp_definition' => 'MVP Definition',
                                                    'milestones' => 'Milestones',
                                                ])
                                                ->searchable()
                                                ->columnSpan(1),
                                        ]),
                                    ...$contentFields,
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

                            // Handle visual editor content
                            if (isset($sectionData['content_visual'])) {
                                $updates['content'] = $sectionData['content_visual'];
                            }
                            // Handle raw JSON fallback
                            elseif (isset($sectionData['content']) && !empty($sectionData['content'])) {
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
                    ->visible(fn () => auth()->user()?->can('update Venture')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('update Venture')),
                ]),
            ]);
    }

    /**
     * Build structured content editing fields based on component type and content shape.
     * Provides visual editors for known data structures, with JSON fallback.
     */
    protected static function buildContentFields(VentureSection $section): array
    {
        $content = $section->content;
        $componentType = $section->component_type ?? 'text_content';
        $sectionId = $section->id;
        $fields = [];

        // Tabs for Visual Editor + Raw JSON toggle
        $fields[] = Forms\Components\Tabs::make("content_tabs_{$sectionId}")
            ->tabs([
                Forms\Components\Tabs\Tab::make('Visual Editor')
                    ->icon('heroicon-o-eye')
                    ->schema(static::buildVisualEditorFields($sectionId, $componentType, $content)),
                Forms\Components\Tabs\Tab::make('Raw JSON')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Forms\Components\Textarea::make("sections.{$sectionId}.content")
                            ->label('Content (JSON)')
                            ->default(is_array($content) ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($content ?? '{}'))
                            ->rows(12)
                            ->columnSpanFull()
                            ->helperText('Edit raw JSON directly. Changes here override the visual editor.'),
                    ]),
            ])
            ->columnSpanFull();

        return $fields;
    }

    /**
     * Build visual editor fields based on component type.
     */
    protected static function buildVisualEditorFields(int $sectionId, string $componentType, $content): array
    {
        if (!is_array($content) || empty($content)) {
            return [
                Forms\Components\Placeholder::make("visual_empty_{$sectionId}")
                    ->content('No content to display. Use the Raw JSON tab to add content.'),
            ];
        }

        return match($componentType) {
            'stat_cards' => static::buildStatCardsEditor($sectionId, $content),
            'key_value' => static::buildKeyValueEditor($sectionId, $content),
            'swot_grid' => static::buildSwotEditor($sectionId, $content),
            'text_content' => static::buildTextContentEditor($sectionId, $content),
            'persona_cards' => static::buildPersonaCardsEditor($sectionId, $content),
            'timeline', 'journey_timeline' => static::buildTimelineEditor($sectionId, $content),
            'progress_bars' => static::buildProgressBarsEditor($sectionId, $content),
            'comparison_table', 'cost_table' => static::buildTableEditor($sectionId, $content),
            'risk_matrix' => static::buildRiskMatrixEditor($sectionId, $content),
            default => static::buildGenericEditor($sectionId, $content),
        };
    }

    protected static function buildStatCardsEditor(int $sectionId, array $content): array
    {
        // Find the cards array in content (could be nested under 'cards', 'stats', 'items', 'metrics', or root)
        $items = $content['cards'] ?? $content['stats'] ?? $content['items'] ?? $content['metrics'] ?? $content;
        if (!is_array($items) || empty($items)) {
            return static::buildGenericEditor($sectionId, $content);
        }

        // Check if it's a list of card objects
        $firstItem = reset($items);
        if (!is_array($firstItem)) {
            return static::buildGenericEditor($sectionId, $content);
        }

        return [
            Forms\Components\Repeater::make("sections.{$sectionId}.content_visual")
                ->label('Stat Cards')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('trend')
                        ->label('Trend')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('icon')
                        ->label('Icon/Emoji')
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->default(array_values($items))
                ->addActionLabel('Add Card')
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    protected static function buildKeyValueEditor(int $sectionId, array $content): array
    {
        return [
            Forms\Components\KeyValue::make("sections.{$sectionId}.content_visual")
                ->label('Key-Value Pairs')
                ->default($content)
                ->columnSpanFull(),
        ];
    }

    protected static function buildSwotEditor(int $sectionId, array $content): array
    {
        $fields = [];
        $quadrants = ['strengths', 'weaknesses', 'opportunities', 'threats'];
        $defaults = [];

        foreach ($quadrants as $q) {
            $items = $content[$q] ?? [];
            $defaults[$q] = is_array($items) ? implode("\n", $items) : (string) $items;
        }

        $fields[] = Forms\Components\Grid::make(2)
            ->schema([
                Forms\Components\Textarea::make("sections.{$sectionId}.content_visual.strengths")
                    ->label('💪 Strengths')
                    ->default($defaults['strengths'])
                    ->rows(4)
                    ->helperText('One item per line'),
                Forms\Components\Textarea::make("sections.{$sectionId}.content_visual.weaknesses")
                    ->label('⚠️ Weaknesses')
                    ->default($defaults['weaknesses'])
                    ->rows(4)
                    ->helperText('One item per line'),
                Forms\Components\Textarea::make("sections.{$sectionId}.content_visual.opportunities")
                    ->label('🚀 Opportunities')
                    ->default($defaults['opportunities'])
                    ->rows(4)
                    ->helperText('One item per line'),
                Forms\Components\Textarea::make("sections.{$sectionId}.content_visual.threats")
                    ->label('🔥 Threats')
                    ->default($defaults['threats'])
                    ->rows(4)
                    ->helperText('One item per line'),
            ]);

        return $fields;
    }

    protected static function buildTextContentEditor(int $sectionId, array $content): array
    {
        // Text content can be a string, sections array, or object with 'text'/'content'
        $text = '';
        if (isset($content['text'])) {
            $text = is_array($content['text']) ? implode("\n\n", $content['text']) : $content['text'];
        } elseif (isset($content['content'])) {
            $text = is_array($content['content']) ? implode("\n\n", $content['content']) : $content['content'];
        } elseif (isset($content['sections'])) {
            // Handle sections format
            return [
                Forms\Components\Repeater::make("sections.{$sectionId}.content_visual.sections")
                    ->label('Content Sections')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Section Title'),
                        Forms\Components\Textarea::make('content')
                            ->label('Content')
                            ->rows(4),
                    ])
                    ->default($content['sections'])
                    ->addActionLabel('Add Section')
                    ->collapsible()
                    ->columnSpanFull(),
            ];
        } else {
            $text = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return [
            Forms\Components\RichEditor::make("sections.{$sectionId}.content_visual.text")
                ->label('Content')
                ->default($text)
                ->columnSpanFull(),
        ];
    }

    protected static function buildPersonaCardsEditor(int $sectionId, array $content): array
    {
        $personas = $content['personas'] ?? $content['cards'] ?? $content['items'] ?? $content;
        $firstItem = reset($personas);
        if (!is_array($firstItem)) {
            return static::buildGenericEditor($sectionId, $content);
        }

        return [
            Forms\Components\Repeater::make("sections.{$sectionId}.content_visual")
                ->label('Persona Cards')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('age')
                        ->label('Age')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('role')
                        ->label('Role/Occupation')
                        ->columnSpan(2),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpan(2),
                    Forms\Components\TagsInput::make('goals')
                        ->label('Goals')
                        ->columnSpan(1),
                    Forms\Components\TagsInput::make('pain_points')
                        ->label('Pain Points')
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->default(array_values($personas))
                ->addActionLabel('Add Persona')
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    protected static function buildTimelineEditor(int $sectionId, array $content): array
    {
        $items = $content['stages'] ?? $content['milestones'] ?? $content['items'] ?? $content['timeline'] ?? $content;
        $firstItem = reset($items);
        if (!is_array($firstItem)) {
            return static::buildGenericEditor($sectionId, $content);
        }

        return [
            Forms\Components\Repeater::make("sections.{$sectionId}.content_visual")
                ->label('Timeline Items')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('date')
                        ->label('Date/Period')
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('status')
                        ->label('Status')
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->default(array_values($items))
                ->addActionLabel('Add Item')
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    protected static function buildProgressBarsEditor(int $sectionId, array $content): array
    {
        $items = $content['items'] ?? $content['bars'] ?? $content['metrics'] ?? $content;
        $firstItem = reset($items);
        if (!is_array($firstItem)) {
            return static::buildGenericEditor($sectionId, $content);
        }

        return [
            Forms\Components\Repeater::make("sections.{$sectionId}.content_visual")
                ->label('Progress Bars')
                ->schema([
                    Forms\Components\TextInput::make('label')
                        ->label('Label')
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('value')
                        ->label('Value (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('color')
                        ->label('Color')
                        ->columnSpan(1),
                ])
                ->columns(4)
                ->default(array_values($items))
                ->addActionLabel('Add Bar')
                ->columnSpanFull(),
        ];
    }

    protected static function buildTableEditor(int $sectionId, array $content): array
    {
        // Tables usually have headers and rows
        if (isset($content['headers']) && isset($content['rows'])) {
            return [
                Forms\Components\TagsInput::make("sections.{$sectionId}.content_visual.headers")
                    ->label('Column Headers')
                    ->default($content['headers'])
                    ->columnSpanFull(),
                Forms\Components\Repeater::make("sections.{$sectionId}.content_visual.rows")
                    ->label('Table Rows')
                    ->schema([
                        Forms\Components\TagsInput::make('values')
                            ->label('Row Values')
                            ->columnSpanFull(),
                    ])
                    ->default(array_map(fn($row) => ['values' => is_array($row) ? $row : [$row]], $content['rows']))
                    ->addActionLabel('Add Row')
                    ->columnSpanFull(),
            ];
        }

        return static::buildGenericEditor($sectionId, $content);
    }

    protected static function buildRiskMatrixEditor(int $sectionId, array $content): array
    {
        $risks = $content['risks'] ?? $content['items'] ?? $content;
        $firstItem = reset($risks);
        if (!is_array($firstItem)) {
            return static::buildGenericEditor($sectionId, $content);
        }

        return [
            Forms\Components\Repeater::make("sections.{$sectionId}.content_visual")
                ->label('Risk Items')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Risk Name')
                        ->columnSpan(2),
                    Forms\Components\Select::make('likelihood')
                        ->label('Likelihood')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                        ->columnSpan(1),
                    Forms\Components\Select::make('impact')
                        ->label('Impact')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('mitigation')
                        ->label('Mitigation Strategy')
                        ->rows(2)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('category')
                        ->label('Category')
                        ->columnSpan(2),
                ])
                ->columns(2)
                ->default(array_values($risks))
                ->addActionLabel('Add Risk')
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    /**
     * Fallback: display content as structured key-value pairs for unknown types.
     */
    protected static function buildGenericEditor(int $sectionId, array $content): array
    {
        $fields = [];

        // Try to create meaningful form fields from top-level keys
        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $fields[] = Forms\Components\Textarea::make("sections.{$sectionId}.content_visual.{$key}")
                    ->label(ucwords(str_replace(['_', '-'], ' ', $key)))
                    ->default($value)
                    ->rows(3)
                    ->columnSpan(1);
            } elseif (is_numeric($value)) {
                $fields[] = Forms\Components\TextInput::make("sections.{$sectionId}.content_visual.{$key}")
                    ->label(ucwords(str_replace(['_', '-'], ' ', $key)))
                    ->default($value)
                    ->numeric()
                    ->columnSpan(1);
            } elseif (is_bool($value)) {
                $fields[] = Forms\Components\Toggle::make("sections.{$sectionId}.content_visual.{$key}")
                    ->label(ucwords(str_replace(['_', '-'], ' ', $key)))
                    ->default($value)
                    ->columnSpan(1);
            } elseif (is_array($value)) {
                // Nested arrays shown as JSON textarea
                $fields[] = Forms\Components\Textarea::make("sections.{$sectionId}.content_visual.{$key}")
                    ->label(ucwords(str_replace(['_', '-'], ' ', $key)))
                    ->default(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                    ->rows(5)
                    ->columnSpan(2)
                    ->helperText('JSON format');
            }
        }

        if (empty($fields)) {
            $fields[] = Forms\Components\Placeholder::make("generic_empty_{$sectionId}")
                ->content('Content structure not recognized. Use the Raw JSON tab to edit.');
        }

        return [
            Forms\Components\Grid::make(2)->schema($fields),
        ];
    }
}

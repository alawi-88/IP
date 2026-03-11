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
                    ->modalHeading(fn ($record) => 'Edit Sections in ' . $record->label_en)
                    ->form(function ($record) {
                        $sections = $record->sections()->orderBy('sort_order')->get();
                        $fields = [];

                        foreach ($sections as $section) {
                            $label = $section->label_en ?: ucwords(str_replace(['_', '-'], ' ', $section->slug));
                            $fields[] = Forms\Components\Section::make($label)
                                ->schema([
                                    Forms\Components\Toggle::make("sections.{$section->id}.is_visible")
                                        ->label('Visible')
                                        ->default($section->is_visible),
                                    Forms\Components\Textarea::make("sections.{$section->id}.content")
                                        ->label('Content (JSON)')
                                        ->default(json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                        ->rows(8)
                                        ->columnSpanFull(),
                                ])
                                ->collapsed()
                                ->collapsible();
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
                                $updates['is_visible'] = $sectionData['is_visible'];
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
